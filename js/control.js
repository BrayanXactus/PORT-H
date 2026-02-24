$(document).ready(function() {
    $("#ajax-loading").hide();
});

var APPNAME = 'encuestas';
var angularAppAQ = angular.module(APPNAME, ['ngRoute', 'oc.lazyLoad', 'ui.select', 'ngSanitize']);

var sProtocol = window.location.protocol.toLowerCase() === "https:" ? "https" : "http";

angularAppAQ.constant('configuracionGlobal', {
    'APPNAME': APPNAME,
    'URL': sProtocol + '://' + window.location.host + '/'
});

angularAppAQ.controller('principalController', function ($scope, $http, $rootScope, $filter, $location, configuracionGlobal) {
    $rootScope.validarSesion = function (bRecargarForm, sUrlSolicitud) {
        
        $http({
            'method': 'POST',
            'async': true,
            'url': configuracionGlobal.URL + 'php/ControlEncuestas.php',
            'data': { 'method': 'validarSesion' },
            'headers': {
                'Content-type': 'application/json'
            }
        }).then(function (success) {
            $rootScope.oDatosSesion = success.data;
            $rootScope.oDatosSesion.sUrlSolicitud = sUrlSolicitud;
            
            $rootScope.$evalAsync();
    
            if ($rootScope.oDatosSesion.bSession && $rootScope.oDatosSesion.sUrlSolicitud === 'sesion') { 
                $location.url("/");
            } 
            else if (!$rootScope.oDatosSesion.bSession && $rootScope.oDatosSesion.sUrlSolicitud !== 'abierta' && $rootScope.oDatosSesion.sUrlSolicitud !== 'sesion' && $rootScope.oDatosSesion.sUrlSolicitud !== 'generalidades' && $rootScope.oDatosSesion.sUrlSolicitud !== '' && $rootScope.oDatosSesion.sUrlSolicitud !== 'cuenca') {
                $location.url("/");
            }
            else if ($rootScope.oDatosSesion.bSession && $rootScope.oDatosSesion.sUrlSolicitud.indexOf('edicion') !== -1 && !$rootScope.oDatosSesion.editar_encuesta) {
                $location.url("/");
            } 
            else if ($rootScope.oDatosSesion.bSession && $rootScope.oDatosSesion.sUrlSolicitud === 'usuario' && !$rootScope.oDatosSesion.administrar_usuarios) {
                $location.url("/");
            }
            else if ($rootScope.oDatosSesion.bSession && $rootScope.oDatosSesion.sUrlSolicitud === 'consultas' && !$rootScope.oDatosSesion.consultar_encuesta) {
                $location.url("/home_usuario");
            } else {
            }
        }, function (error) {
            console.error('❌ Error en validarSesion:', error);
        });
    };

    $rootScope.cerrarSesion = function () {
        $http({
            'method': 'POST',
            'async': true,
            'url': configuracionGlobal.URL + 'php/ControlEncuestas.php',
            'data': { "method": "cerrarSesion" },
            'headers': {
                'Content-type': 'application/json'
            }
        }).then(function (success) {
			$location.url("/");
        }, function (error) {
            
        });
    }

    $rootScope.escapeRegExp = function(str) {
        return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
    }

    $rootScope.reemplazar = function(str, find, replace) {
        if (angular.isNumber(str)) {
            var c = str.toString().replace(new RegExp($rootScope.escapeRegExp(find), 'g'), replace);
            return parseFloat(c);
        }

        return str.replace(new RegExp($rootScope.escapeRegExp(find), 'g'), replace);
    }
	
	$rootScope.navegarMenu = function (sMenu) {
        $location.url("/" + sMenu);
    }

    $rootScope.sUrlSolicitud = 'sesion';
    $rootScope.oDatosSesion = { "nombre": null, "sUrlSolicitud": "sesion" };

    $rootScope.$on('$routeChangeSuccess', function (event, currentRoute) {
        
        let sUrlSolicitud = 'sesion';
        try {
            if (currentRoute && currentRoute.originalPath && typeof currentRoute.originalPath === 'string') {
                if (currentRoute.originalPath.length > 0 && currentRoute.originalPath !== '/') {
                    sUrlSolicitud = $rootScope.reemplazar(currentRoute.originalPath, '/', '');
                } else {
                    sUrlSolicitud = 'sesion';
                }
            }
        } catch (error) {
            console.error('Error procesando currentRoute:', error);
            sUrlSolicitud = 'sesion';
        }
		
		$rootScope.validarSesion(true, sUrlSolicitud);
		
		if (typeof(Storage) !== 'undefined') {
			sessionStorage.setItem('sUrlSolicitud', sUrlSolicitud);
		}
		
		let oIntervalGuardarSesion = setInterval(function() {
			let frm = typeof(Storage) !== 'undefined' ? sessionStorage.getItem('sUrlSolicitud') : null;
			
			if (frm !== null) {
				$rootScope.validarSesion(true, frm);
			}
		}, 3600000);
	});
});

angularAppAQ.directive("filesInput", function() {
    return {
        require: "ngModel",
        link: function postLink(scope, elem, attrs, ngModel) {
            var tipo = typeof attrs.tipo === 'undefined' ? 'basico': attrs.tipo;
            elem.on("change", function(e) {
                var files = tipo === 'basico' ? elem[0].files: elem[0];
                ngModel.$setViewValue(files);
            });
        }
    };
});

angularAppAQ.directive('numericOnly', function(){
    return {
        require: 'ngModel',
        link: function(scope, element, attrs, modelCtrl) {

            modelCtrl.$parsers.push(function (inputValue) {
                var transformedInput = inputValue ? inputValue.replace(/[^\d]/g,'') : null;

                if (transformedInput!=inputValue) {
                    modelCtrl.$setViewValue(transformedInput);
                    modelCtrl.$render();
                }

                return transformedInput;
            });
        }
    };
});

angularAppAQ.directive('decimalOnly', function(){
    return {
        require: 'ngModel',
        link: function(scope, element, attrs, modelCtrl) {

            modelCtrl.$parsers.push(function (inputValue) {
                var transformedInput = inputValue ? inputValue.replace(/[^\d.-]/g,'') : null;

                if (transformedInput!=inputValue) {
                    modelCtrl.$setViewValue(transformedInput);
                    modelCtrl.$render();
                }

                return transformedInput;
            });
        }
    };
});
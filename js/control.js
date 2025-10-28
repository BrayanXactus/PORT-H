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
    
            // Solo redirigir si estamos en "/sesion" y ya estamos autenticados
            if ($rootScope.oDatosSesion.bSession && $rootScope.oDatosSesion.sUrlSolicitud === 'sesion') { 
                $location.url("/");
            } 
            // No redireccionar en la ruta raíz si ya está autenticado
            else if (!$rootScope.oDatosSesion.bSession && $rootScope.oDatosSesion.sUrlSolicitud !== 'abierta' && $rootScope.oDatosSesion.sUrlSolicitud !== 'sesion' && $rootScope.oDatosSesion.sUrlSolicitud !== 'generalidades' && $rootScope.oDatosSesion.sUrlSolicitud !== '' && $rootScope.oDatosSesion.sUrlSolicitud !== 'cuenca') {
                $location.url("/");
            }
            // Verificar permisos para rutas protegidas
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

    $scope.visualizarImagenOld = function (oRegistro, oCampo) {

        //console.log(oCampo);

        /*if (input.files && input.files[0]) {
            let idVista = 'vpimagen_' + tabla + '_' + campo;

            console.log(idVista);

            let reader = new FileReader();
            reader.onload = function (e) {
                let preview = document.getElementById(idVista),
                    image = document.createElement('img');
                image.className = "img-fluid";
                image.style.height = "100%";
                image.src = reader.result;
                preview.innerHTML = '';
                preview.append(image);
            };
            reader.readAsDataURL(input.files[0]);
        }*/
    };

    /*$scope.menu = function (sMenu) {
        $scope.sMenuActivo = sMenu;
    }

    $rootScope.usuario  = function () {
        $rootScope.form = 'usuario';
        window.sessionStorage.setItem('form', $rootScope.form);
        $rootScope.bMostrarEncabezado = true;
        $rootScope.sShowForm = 'views/usuario.html?i=' + Math.random().toString(36).slice(2);

        console.log($rootScope.sShowForm);
        console.log($rootScope.bMostrarEncabezado);
        console.log($rootScope.form);
    }

    $rootScope.inicio  = function () {
        $rootScope.form = 'inicio';
        window.sessionStorage.setItem('form', $rootScope.form);
        $rootScope.sShowForm = 'views/inicio.html?i=' + Math.random().toString(36).slice(2);
        $rootScope.bMostrarEncabezado = true;

        console.log($rootScope.sShowForm);
        console.log($rootScope.bMostrarEncabezado);
        console.log($rootScope.form);
    }

    $scope.sMenuActivo = '';
    $rootScope.sErrorLogin = '';
    $rootScope.oLogin = { 'correo' : null, 'contrasenia' : null, 'method' : 'iniciarSesion' };
    $rootScope.oSession = {};
    $rootScope.form = 'sesion';
    $rootScope.bMostrarEncabezado = false;
    $rootScope.sShowForm = '';
    $rootScope.nombreUsuario = null;

    $rootScope.validarSesion(true);*/

    $rootScope.sUrlSolicitud = 'sesion';
    $rootScope.oDatosSesion = { "nombre": null, "sUrlSolicitud": "sesion" };

    $rootScope.$on('$routeChangeSuccess', function (event, currentRoute) {
        
        let sUrlSolicitud = 'sesion'; // valor por defecto
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
		//$rootScope.$apply();
	
        /*if ($rootScope.sUrlSolicitud !== 'sesion') {
            $(".header-navbar").show();
			$("#sticky-wrapper").show();
        } else {
            $(".header-navbar").hide();
			$("#sticky-wrapper").hide();
        }*/
		
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
            // tipos: basico - dinamico (editar la imagen)
            var tipo = typeof attrs.tipo === 'undefined' ? 'basico': attrs.tipo;
            elem.on("change", function(e) {
                //var files = elem[0].files;
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
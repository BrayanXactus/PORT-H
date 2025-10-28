$(document).ready(function() {
    $("#ajax-loading").hide();
});

var APPNAME = 'encuestas';
var angularAppAQ = angular.module(APPNAME, ['ngRoute', 'oc.lazyLoad']);

var sProtocol = window.location.protocol.toLowerCase() === "https:" ? "https" : "http";

angularAppAQ.constant('configuracionGlobal', {
    'APPNAME': APPNAME,
    'URL': sProtocol + '://' + window.location.host + '/'
});

angularAppAQ.controller('principalController', function ($scope, $http, $rootScope, $filter, servicioGeneral, configuracionGlobal) {
    $rootScope.validarSesion = function (bRecargarForm) {
        $http({
            'method': 'POST',
            'async': true,
            'url': configuracionGlobal.URL + 'php/ControlEncuestas.php',
            'data': { 'method': 'validarSesion' },
            'headers': {
                'Content-type': 'application/json'
            }
        }).then(function (success) {

            console.log(success.data);
            console.log(bRecargarForm);

			if (success.data.bSession && bRecargarForm) {
                let form = typeof window.sessionStorage.getItem("form") !== 'undefined' ? (window.sessionStorage.getItem("form") !== null ? window.sessionStorage.getItem("form") : 'sesion') : 'sesion';
                $rootScope.bMostrarEncabezado = form !== 'sesion';
                $rootScope.sShowForm = 'views/' + form + '.html?i=' + Math.random().toString(36).slice(2);
                $rootScope.form = form;
                $rootScope.nombreUsuario = success.data.nombre;
            } else if (!success.data.bSession) {
                $rootScope.form = 'sesion';
                window.sessionStorage.setItem('form', $rootScope.form);
                $rootScope.bMostrarEncabezado = false;
                $rootScope.nombreUsuario = null;
                $rootScope.sShowForm = 'views/sesion.html?i=' + Math.random().toString(36).slice(2);
            }
        }, function (error) {
            
        });
    };

    $rootScope.escapeRegExp = function(str) {
        return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
    },

    $rootScope.reemplazar = function(str, find, replace) {
        if (angular.isNumber(str)) {
            var c = str.toString().replace(new RegExp($rootScope.escapeRegExp(find), 'g'), replace);
            return parseFloat(c);
        }

        return str.replace(new RegExp($rootScope.escapeRegExp(find), 'g'), replace);
    }

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

    $rootScope.$on('$routeChangeSuccess', function (event, currentRoute) {
        console.log('$routeChangeSuccess');
        $rootScope.sUrlSolicitud = currentRoute.originalPath !== null ? (currentRoute.originalPath.length > 0 && currentRoute.originalPath !== '/' ? $rootScope.reemplazar(currentRoute.originalPath, '/', '') : 'sesion')  : 'sesion';
    
        if ($rootScope.sUrlSolicitud !== 'sesion') {
            $(".header-navbar").show();
        }

        console.log($rootScope.sUrlSolicitud);
    });
});


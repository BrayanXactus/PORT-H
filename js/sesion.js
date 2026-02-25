angular.module(APPNAME).controller('sesionController', function($scope, $rootScope, $location, $http, configuracionGlobal, servicioGeneral) {
    $scope.iniciarSesion = function () {
        $scope.sErrorLogin = '';

        $rootScope.ensureCsrf().then(function () {
            return $http({
            method: 'POST',
            async: true,
            url: configuracionGlobal.URL + 'php/ControlEncuestas.php',
            data: $scope.oLogin,
            headers: { 'Content-type': 'application/json' }
            });
        }).then(function (success) {
            $scope.oSession = success.data;
            $scope.configurarSesionLogin();
        }, function (error) {

        });
    };

    $scope.configurarSesionLogin = function () {
        if ($scope.oSession.bLogin) {
            var rutaDestino = sessionStorage.getItem('rutaDestino');
            
            if (rutaDestino) {
                sessionStorage.removeItem('rutaDestino');
                $location.path('/' + rutaDestino);
            } else {
                $location.path("/inicio");
            }
        } else {
            $scope.oLogin.contrasenia = null;
            
            toastr.options = {
                closeButton: false,
                debug: false,
                newestOnTop: false,
                progressBar: false,
                positionClass: "toast-top-right",
                preventDuplicates: false,
                onclick: null,
                showDuration: 300,
                hideDuration: 1000,
                timeOut: 5000,
                warning: false,
                extendedTimeOut: "1000",
                showEasing: "swing",
                hideEasing: "linear",
                showMethod: "fadeIn",
                hideMethod: "fadeOut"
            };

            toastr.error("Datos de acceso inválidos", "Error");
        }
    }

    $scope.oLogin = { 'correo' : null, 'contrasenia' : null, 'method' : 'iniciarSesion' };
});
angular.module(APPNAME).controller('home_usuarioController', ['$scope', '$rootScope', '$location', function($scope, $rootScope, $location) {
    
    $scope.navegarMenu = function(ruta) {
        // Rutas that require authentication
        var rutasProtegidas = ['consultas', 'usuario', 'registro'];
        
        if (rutasProtegidas.includes(ruta) && (!$rootScope.oDatosSesion || !$rootScope.oDatosSesion.bSession)) {
            // Save the requested route to redirect after login
            sessionStorage.setItem('rutaDestino', ruta);
            $location.path('/sesion');
        } else {
            // If no authentication required or already authenticated, navigate directly
            $location.path('/' + ruta);
        }
    };
}]);
angular.module(APPNAME).controller('homeController', ['$scope', '$rootScope', '$location', function($scope, $rootScope, $location) {
    
    console.log('Home controller cargado');
    
    $scope.navegarMenu = function(ruta) {
        console.log('Navegando a:', ruta);
        
        // Rutas que requieren autenticación
        var rutasProtegidas = ['inicio', 'consultas', 'usuario', 'registro'];
        
        // Verificar si es la ruta de generalidades (no requiere autenticación)
        if (ruta === 'generalidades') {
            $location.path('/generalidades');
            return;
        }
        
        // Verificar autenticación para rutas protegidas
        if (rutasProtegidas.includes(ruta) && (!$rootScope.oDatosSesion || !$rootScope.oDatosSesion.bSession)) {
            sessionStorage.setItem('rutaDestino', ruta);
            $location.path('/sesion');
        } else {
            $location.path('/' + ruta);
        }
    }; 
    
}]);
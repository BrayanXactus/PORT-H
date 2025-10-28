angularAppAQ.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/', {
        templateUrl: 'views/sesion.html?i=' + Math.random().toString(36).slice(2),
        controller: 'sesionController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: ['js/sesion.js?i=' + Math.random().toString(36).slice(2)]
                }]);
            }]
        }
    });

    $routeProvider.when('/sesion', {
        templateUrl: 'form/sesion.html?i=' + Math.random().toString(36).slice(2),
        controller: 'sesionController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: ['js/sesion.js?i=' + Math.random().toString(36).slice(2)]
                }]);
            }]
        }
    });

    $routeProvider.when('/inicio', {
        templateUrl: 'views/inicio.html?i=' + Math.random().toString(36).slice(2),
        controller: 'inicioController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: ['js/inicio.js?i=' + Math.random().toString(36).slice(2)]
                }]);
            }]
        }
    });

    $routeProvider.when('/usuario', {
        templateUrl: 'views/usuario.html?i=' + Math.random().toString(36).slice(2),
        controller: 'usuarioController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: ['js/usuario.js?i=' + Math.random().toString(36).slice(2)]
                }]);
            }]
        }
    });
    
    $routeProvider.otherwise({
        redirectTo: '/404'
    });
}]);

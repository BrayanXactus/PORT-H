angularAppAQ.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/', {
        templateUrl: 'views/home.html?i=' + Math.random().toString(36).slice(2),
        controller: 'homeController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: ['js/home.js?i=' + Math.random().toString(36).slice(2)]
                }]);
            }]
        }
    });
  
    $routeProvider.when('/generalidades', {
        templateUrl: 'views/generalidades.html?i=' + Math.random().toString(36).slice(2),
        controller: 'generalidadesController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: ['js/generalidades.js?i=' + Math.random().toString(36).slice(2)]
                }]).catch(function(error) {
                    console.error('Error cargando generalidades:', error);
                    return {};
                });
            }]
        }
    });
    $routeProvider.when('/cuenca', {
    templateUrl: 'views/cuenca.html?i=' + Math.random().toString(36).slice(2),
    controller: 'cuencaController',
    resolve: {
        lazy: ['$ocLazyLoad', function($ocLazyLoad) {
            return $ocLazyLoad.load([{
                name: APPNAME,
                files: ['js/cuenca.js?i=' + Math.random().toString(36).slice(2)]
            }]).catch(function(error) {
                console.error('Error cargando cuenca:', error);
                return {};
            });
        }]
    }
});
    $routeProvider.when('/sesion', {
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

    $routeProvider.when('/registro', {
        templateUrl: 'views/registro.html?i=' + Math.random().toString(36).slice(2),
        controller: 'registroController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: ['js/registro.js?i=' + Math.random().toString(36).slice(2)]
                }]);
            }]
        }
    });

    $routeProvider.when('/edicion/:id_edicion', {
        templateUrl: 'views/edicion.html?i=' + Math.random().toString(36).slice(2),
        controller: 'edicionController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: ['js/edicion.js?i=' + Math.random().toString(36).slice(2)]
                }]);
            }]
        }
    });

    $routeProvider.when('/consultas', {
        templateUrl: 'views/consultas.html?i=' + Math.random().toString(36).slice(2),
        controller: 'consultasController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: [
                        'js/consultas.js?i=' + Math.random().toString(36).slice(2)
                    ]
                }]);
            }]
        }
    });

    $routeProvider.when('/home', {
        templateUrl: 'views/home.html?i=' + Math.random().toString(36).slice(2),
        controller: 'homeController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: [
                        'js/mapa.js?i=' + Math.random().toString(36).slice(2)
                    ]
                }]);
            }]
        }
    });

    $routeProvider.when('/mapa', {
        templateUrl: 'views/mapa.html?i=' + Math.random().toString(36).slice(2),
        controller: 'mapaController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: [
                        'js/mapa.js?i=' + Math.random().toString(36).slice(2)
                    ]
                }]);
            }]
        }
    });

    $routeProvider.when('/abierta', {
        templateUrl: 'views/abierta.html?i=' + Math.random().toString(36).slice(2),
        controller: 'abiertaController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: [
                        'https://cdn.jsdelivr.net/npm/chart.js',
                        'https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.min.js',
                        'assets/leaflet.browser/leaflet.browser.print.min.js',
                        
                        'https://html2canvas.hertzen.com/dist/html2canvas.js',
                        'https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js',
                        'js/abierta.js?i=' + Math.random().toString(36).slice(2)
                    ]
                }]);
            }]
        }
    });

    $routeProvider.when('/home_usuario', {
        templateUrl: 'views/home_usuario.html?i=' + Math.random().toString(36).slice(2),
        controller: 'home_usuarioController',
        resolve: {
            lazy: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load([{
                    name: APPNAME,
                    files: [
                        'js/home_usuario.js?i=' + Math.random().toString(36).slice(2)
                    ]
                }]);
            }]
        }
    });
    
    $routeProvider.otherwise({
        redirectTo: '/404'
    });
}]);

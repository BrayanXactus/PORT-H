// Asegúrate de que el módulo existe antes de crear el controlador
if (typeof angular !== 'undefined' && angular.module) {
    try {
        angular.module(APPNAME).controller('generalidadesController', ['$scope', '$rootScope', '$location', '$timeout', function($scope, $rootScope, $location, $timeout) {
            
            // Inicializar datos de forma segura
            $scope.cuencas = [];
            
            // Usar $timeout para asegurar que el DOM esté listo
            $timeout(function() {
                $scope.cuencas = [
                    { id: 'bogota', nombre: 'Río Bogotá', seleccionada: false },
                    { id: 'garagoa', nombre: 'Río Garagoa', seleccionada: false },
                    { id: 'guavio', nombre: 'Río Guavio', seleccionada: false },
                    { id: 'Guayuriba', nombre: 'Río Guayuriba', seleccionada: false },
                    { id: 'negro', nombre: 'Río Negro', seleccionada: false },
                    { id: 'seco', nombre: 'Río Seco y Otros Directos', seleccionada: false },
                    { id: 'suarez', nombre: 'Río Suarez', seleccionada: false },
                    { id: 'sumapaz', nombre: 'Río Sumapaz', seleccionada: false },
                    { id: 'carare', nombre: 'Río Carare Minero', seleccionada: false }
                ];
                
            }, 100);

            // Función para seleccionar cuenca y navegar directamente
$scope.seleccionarCuenca = function(cuenca) {
    if (!cuenca) return;
    
    try {
        // Deseleccionar todas las cuencas
        if ($scope.cuencas && Array.isArray($scope.cuencas)) {
            $scope.cuencas.forEach(function(c) {
                if (c) c.seleccionada = false;
            });
        }
        
        // Seleccionar la cuenca clickeada
        cuenca.seleccionada = true;
        
        // Guardar la cuenca seleccionada
        if (typeof(Storage) !== "undefined") {
            var filtroData = {
                cuenca: cuenca,
                aplicarFiltro: true,
                timestamp: new Date().getTime()
            };
            sessionStorage.setItem('cuencaSeleccionada', JSON.stringify(filtroData));
        }
        
        // Navegar directamente a cuenca
        $location.path('/cuenca');
        
        // Forzar la actualización
        if (!$scope.$$phase) {
            $scope.$apply();
        }
        
    } catch(error) {
        console.error('Error al seleccionar cuenca:', error);
    }
};

            // Función para continuar con la selección
          $scope.continuarSeleccion = function() {
    try {
        
        if (!$scope.cuencas || !Array.isArray($scope.cuencas)) {
            return;
        }
        
        var cuencaSeleccionada = $scope.cuencas.find(function(c) { 
            return c && c.seleccionada; 
        });
        
        if (cuencaSeleccionada) {
            
            // Guardar la cuenca seleccionada
            if (typeof(Storage) !== "undefined") {
                var filtroData = {
                    cuenca: cuencaSeleccionada,
                    aplicarFiltro: true,
                    timestamp: new Date().getTime()
                };
                sessionStorage.setItem('cuencaSeleccionada', JSON.stringify(filtroData));
            }
            $location.path('/cuenca');
            
            setTimeout(function() {
            }, 50);
            
            window.location.hash = '#!/cuenca';
            
            setTimeout(function() {
            }, 100);
            
            // Forzar la actualización
            if (!$scope.$$phase) {
                $scope.$apply();
            }
            
            setTimeout(function() {
            }, 500);
            
        } else {
            console.log('❌ No hay cuenca seleccionada');
        }
    } catch(error) {
        console.error('💥 Error al continuar selección:', error);
    }
};

            // Función para volver al menú principal
            $scope.volverHome = function() {
                try {
                    $location.path('/');
                } catch(error) {
                    console.error('Error al volver al home:', error);
                    // Fallback usando window.history
                    window.history.back();
                }
            };

            // Verificar si hay alguna cuenca seleccionada
            $scope.tieneCuencaSeleccionada = function() {
                if (!$scope.cuencas || !Array.isArray($scope.cuencas)) return false;
                return $scope.cuencas.some(function(c) { return c && c.seleccionada; });
            };

        }]);
    } catch(error) {
        console.error('Error creando controlador generalidades:', error);
    }
}
angular.module(APPNAME).controller('usuarioController', function($scope, $rootScope, $location, configuracionGlobal, servicioGeneral) {
    $scope.catalogoUsuarios = function () {
        $("#ajax-loading").show();
        
        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "catalogoUsuarios" },
            "success": function (response) {
                $scope.aUsuarios = response.catalogo;
                $('[data-toggle="tooltip"]').tooltip({
                    "container":"body"
                });
                $("#ajax-loading").hide();
            }
        }); 
    }

    $scope.guardarEditarUsuario = function () {
        let bValidarContraseniaIgual = true;

        if ($scope.oRegistro.id !== null) {
            bValidarContraseniaIgual = $scope.oRegistro.mondificar_contrasenia;
        }

        if (bValidarContraseniaIgual && $scope.oRegistro.contrasenia !== $scope.oRegistro.contrasenia_2) {
            toastr.error("Las constraseñas no son iguales", "Error");
        } else {
            $("#ajax-loading").show();
        
            servicioGeneral.enviarAjax({ 
                "url": "ControlEncuestas.php", 
                "data": $scope.oRegistro,
                "success": function (response) {
                    if (response.bProceso) {
                        toastr.success("Proceso realizado", "Información");

                        $scope.oRegistro = { 
                            "id": null, 
                            "tipo_usuario": 1, 
                            "nombre": null, 
                            "correo_electronico": null,
                            "modificar_contrasenia": true,
                            "contrasenia": null, 
                            "contrasenia_2": null,
                            "method": "guardarEditarUsuario"
                        };

                        $scope.catalogoUsuarios();
                    } else if (response.iCantidadExiste > 0) {
                        $("#ajax-loading").hide();
                        toastr.error("El correo electrónico ya se encuentra registrado", "Error");
                    } else {
                        $("#ajax-loading").hide();
                        toastr.error("Ocurrió un error", "Error");
                    }
                }
            });
        }
    }

    $scope.editarUsuario = function (oRegistro) {
        $scope.oRegistro = { 
            "id": oRegistro.id, 
            "tipo_usuario": oRegistro.tipo_usuario, 
            "nombre": oRegistro.nombre, 
            "correo_electronico": oRegistro.correo,
            "modificar_contrasenia": false,
            "contrasenia": null, 
            "contrasenia_2": null,
            "method": "guardarEditarUsuario"
        }   
        
        console.log($scope.oRegistro);
    }

    $scope.cancelarEdicion = function () {
        $scope.oRegistro = { 
            "id": null, 
            "tipo_usuario": 1, 
            "nombre": null, 
            "correo_electronico": null,
            "mondificar_contrasenia": true,
            "contrasenia": null, 
            "contrasenia_2": null,
            "method": "guardarEditarUsuario"
        };
    }
    
    $scope.aUsuarios = [];
    $scope.oRegistro = { 
        "id": null, 
        "tipo_usuario": 1, 
        "nombre": null, 
        "correo_electronico": null,
        "modificar_contrasenia": true,
        "contrasenia": null, 
        "contrasenia_2": null,
        "method": "guardarEditarUsuario"
    };

    //servicioGeneral.alert("success", "Bienvenido!", 10000);

    //toastr.success("Bienvenido", "Test");

    $scope.catalogoUsuarios();
});
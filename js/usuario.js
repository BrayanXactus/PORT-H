angular.module(APPNAME).controller('usuarioController', function($scope, $rootScope, $location, configuracionGlobal, servicioGeneral) {
    $scope.catalogoUsuarios = function () {
        $("#ajax-loading").show();
        
        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "catalogoUsuarios" },
            "success": function (response) {
                console.log("Respuesta del servidor:", response); // Añade esta línea
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
                            "nombre": null, 
                            "correo_electronico": null,
                            "fecha_caducidad": null,
                            "modificar_contrasenia": true,
                            "contrasenia": null, 
                            "contrasenia_2": null,
                            "method": "guardarEditarUsuario",
                            "editar_encuesta": null,
                            "agregar_encuesta": null,
                            "exportar_encuesta": null,
                            "administrar_usuarios": null,
                            "consultar_encuesta": null
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
    // Convertir la cadena de fecha a un objeto Date
    let fechaCaducidad = null;
    if (oRegistro.fecha_caducidad) {
        try {
            // Convertir la cadena de fecha a un objeto Date de JavaScript
            fechaCaducidad = new Date(oRegistro.fecha_caducidad);
            
            // Verificar si la fecha es válida
            if (isNaN(fechaCaducidad.getTime())) {
                console.error("Fecha inválida");
                fechaCaducidad = null;
            }
        } catch(e) {
            console.error("Error al convertir la fecha:", e);
            fechaCaducidad = null;
        }
    }
    
    $scope.oRegistro = { 
        "id": oRegistro.id, 
        "nombre": oRegistro.nombre, 
        "correo_electronico": oRegistro.correo,
        "fecha_caducidad": fechaCaducidad, // Ahora es un objeto Date
        "modificar_contrasenia": false,
        "contrasenia": null, 
        "contrasenia_2": null,
        "method": "guardarEditarUsuario",
        "editar_encuesta": oRegistro.editar_encuesta,
        "agregar_encuesta": oRegistro.agregar_encuesta,
        "exportar_encuesta": oRegistro.exportar_encuesta,
        "administrar_usuarios": oRegistro.administrar_usuarios,
        "consultar_encuesta": oRegistro.consultar_encuesta
    }
    
    console.log("Objeto fecha convertido:", fechaCaducidad);
}

    $scope.cancelarEdicion = function () {
        $scope.oRegistro = { 
            "id": null, 
            "nombre": null, 
            "correo_electronico": null,
            "fecha_caducidad": null,
            "mondificar_contrasenia": true,
            "contrasenia": null, 
            "contrasenia_2": null,
            "method": "guardarEditarUsuario",
            "editar_encuesta": null,
            "agregar_encuesta": null,
            "exportar_encuesta": null,
            "administrar_usuarios": null,
            "consultar_encuesta": null
        };
    }
    
    $scope.aUsuarios = [];
    $scope.oRegistro = { 
        "id": null, 
        "nombre": null, 
        "correo_electronico": null,
        "fecha_caducidad": null,
        "modificar_contrasenia": true,
        "contrasenia": null, 
        "contrasenia_2": null,
        "method": "guardarEditarUsuario",
        "editar_encuesta": null,
        "agregar_encuesta": null,
        "exportar_encuesta": null,
        "administrar_usuarios": null,
        "consultar_encuesta": null
    };

    //servicioGeneral.alert("success", "Bienvenido!", 10000);

    //toastr.success("Bienvenido", "Test");

    $scope.catalogoUsuarios();
});
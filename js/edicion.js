angular.module(APPNAME).controller('edicionController', function($scope, $rootScope, $location, $routeParams, configuracionGlobal, servicioGeneral) {
    $scope.cargarDatosEncuesta = function () {
        if ($routeParams.id_edicion !== undefined) {
            $("#ajax-loading").show();
            $scope.oEditarEncuesta = { "id": null, "estructura": [] };
    
            servicioGeneral.enviarAjax({ 
                "url": "ControlEncuestas.php", 
                "data": { "method": "informacionEncuesta", "accion": "editar", "id_edicion": $routeParams.id_edicion, "listas" : true },
                "success": function (response) {
                    if (response.id > 0) {
                        $scope.oEditarEncuesta.id = response.id;
                        $scope.oEditarEncuesta.estructura = angular.copy(response.estructura);
                        $scope.oEditarEncuesta.modulo1 = response.modulo1; 
                        $scope.oEditarEncuesta.modulo2 = response.modulo2; 
                        $scope.oEditarEncuesta.modulo3 = response.modulo3; 
                        $scope.oEditarEncuesta.modulo4 = response.modulo4;

                        console.log($scope.oEditarEncuesta);
        
                        $scope.aListas = [];
                        $scope.aIdsListas = [];
        
                        angular.forEach(response.listas, function (oLista, l) {
                            $scope.aListas[oLista.id_lista] = $scope.aListas[oLista.id_lista] !== undefined ? $scope.aListas[oLista.id_lista] : [];
                            $scope.aListas[oLista.id_lista].push({ "id": oLista.id, "nombre": oLista.descripcion, "codigo": oLista.codigo, "id_kobo": oLista.id_kobo });
                            $scope.aIdsListas[oLista.id] = { "id": oLista.id, "nombre": oLista.descripcion, "codigo": oLista.codigo };
                        });

                        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                                if (oGrupo.campos !== undefined) {
                                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                                        if (oCampo.tipo === 'numero' || oCampo.tipo === 'decimal' || oCampo.tipo === 'seleccion_simple_texto_editable') {
                                            if ((oCampo.tipo === 'numero' || oCampo.tipo === 'decimal') && oCampo.valor !== null) {
                                                oCampo.valor = oCampo.valor.toString();
                                            }

                                            oCampo.tipo = 'texto';
                                        }

                                        if (oCampo.tipo === 'seleccion_simple' || oCampo.tipo === 'radio') {
                                            oCampo.aOpcionesLista = $scope.aListas[oCampo.id_lista] !== undefined ? $scope.aListas[oCampo.id_lista] : [];
                                            $scope.seleccionSimple(oRegistro.tabla, oCampo);
                                        } else if (oCampo.tipo === 'seleccion_multiple') {
                                            oCampo.aOpcionesLista = $scope.aListas[oCampo.elementos] !== undefined ? $scope.aListas[oCampo.elementos] : [];
                                            $scope.seleccionMultiple(oRegistro.tabla, oCampo);
                                        } else if (oCampo.tipo === 'fecha' && oCampo.valor !== null) {
                                            oCampo.valor = new Date(oCampo.valor + ' 00:00:00');
                                        } else if (oCampo.tipo === 'fecha_hora' && oCampo.valor !== null) {
                                            oCampo.valor = new Date(oCampo.valor);
                                        } else if (oCampo.tipo === 'imagen') {
                                            oCampo.adjunto = null;
                                        }

                                        if ((oCampo.tipo === 'numero' || oCampo.tipo === 'decimal') && oCampo.valor !== null) {
                                            oCampo.valor = oCampo.valor.toString();
                                        }
                                    });
            
                                    if (oGrupo.tipo === 'mapa' && oGrupo.latitud !== null && oGrupo.longitud !== null) {
                                        setTimeout(function () {
                                            $scope.crearMapa(oGrupo.contenedor, oGrupo.latitud, oGrupo.longitud);
                                        }, 500);
                                    }
                                } else if (oGrupo.filas !== undefined) {
                                    angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                                        angular.forEach (aFilaCampos, function (oCampo, c) {
                                            if (oCampo.tipo === 'numero' || oCampo.tipo === 'decimal' || oCampo.tipo === 'seleccion_simple_texto_editable') {
                                                if ((oCampo.tipo === 'numero' || oCampo.tipo === 'decimal') && oCampo.valor !== null) {
                                                    oCampo.valor = oCampo.valor.toString();
                                                }
                                                
                                                oCampo.tipo = 'texto';
                                            }

                                            if (oCampo.tipo === 'seleccion_simple' || oCampo.tipo === 'radio') {
                                                oCampo.aOpcionesLista = $scope.aListas[oCampo.id_lista] !== undefined ? $scope.aListas[oCampo.id_lista] : [];
                                                $scope.seleccionSimple(oRegistro.tabla, oCampo);
                                            } else if (oCampo.tipo === 'seleccion_multiple') {
                                                oCampo.aOpcionesLista = $scope.aListas[oCampo.elementos] !== undefined ? $scope.aListas[oCampo.elementos] : [];
                                                $scope.seleccionMultiple(oRegistro.tabla, oCampo);
                                            }
                                        });
                                    });
                                }
                            });
                        });

                        //Se reinician algunos valores dependientes
                        angular.forEach (response.estructura, function (oRegistro1, r1) {
                            angular.forEach (oRegistro1.grupos, function (oGrupo1, g1) {
                                if (oGrupo1.campos !== undefined) {
                                    angular.forEach (oGrupo1.campos, function (oCampo1, c1) {
                                        if (oRegistro1.tabla === 'uso_recurso_hidrico' && (oCampo1.bd === 'personas_permanentes' || oCampo1.bd === 'consumo' || oCampo1.bd === 'dias_mes' || oCampo1.bd === 'personas_transitorias') && oCampo1.valor !== null) {
                                            angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                                                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                                                    if (oGrupo.campos !== undefined) {
                                                        angular.forEach (oGrupo.campos, function (oCampo, c) {
                                                            if (oRegistro.tabla === 'uso_recurso_hidrico' && (oCampo1.bd === 'personas_permanentes' || oCampo1.bd === 'consumo' || oCampo1.bd === 'dias_mes' || oCampo1.bd === 'personas_transitorias') && oCampo.bd === oCampo1.bd) {
                                                                oCampo.valor = oCampo1.valor;
                                                            }
                                                        });
                                                    }
                                                });
                                            });
                                        }
                                    });
                                }
                            });
                        });

                        /*setTimeout(function () {
                            angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                                    if (oGrupo.campos !== undefined) {
                                        angular.forEach (oGrupo.campos, function (oCampo, c) {
                                            if (oRegistro.tabla === 'uso_recurso_hidrico' && oCampo.bd === 'personas_permanentes') {

                                                console.log(oCampo.valor);
                                                console.log(servicioGeneral.isNumeric(oCampo.valor));

                                                if (servicioGeneral.isNumeric(oCampo.valor)) {
                                                    $scope.ingresaDatos(oRegistro.tabla, oCampo);
                                                }
                                            }
                                        });
                                    }
                                });
                            });
                        }, 5000);*/

                        $scope.$evalAsync();

                        setTimeout(function () {
                            $scope.ocultarMostrarModulo();
                        }, 300);

                        $("#ajax-loading").hide();
                    } else {
                        $("#ajax-loading").hide();
                        $location.url("/inicio");
                    }
                }
            });
        }
    }

    $scope.crearMapa = function (sContenedor, fLatitud, fLongitud) {
        let mapa = L.map(sContenedor, { 'zoomControl': true }).setView([fLatitud, fLongitud], 10);
        L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoiYWl0ZXNvbCIsImEiOiJja3lscjYwdzIycGw1Mnhsa3o0ZGJxMWo4In0.HLgzGp72KMRynenp1el9Fg', {
            maxZoom: 18,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, ' +
                '<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            id: 'mapbox/satellite-v9',
            tileSize: 512,
            zoomOffset: -1
        }).addTo(mapa);

        L.marker([fLatitud, fLongitud], { }).addTo(mapa);
    }

    $scope.guardarCambiosEncuesta = function () {
        $("#ajax-loading").show();
		let oDatosEditar = angular.copy($scope.oEditarEncuesta);

        angular.forEach (oDatosEditar.estructura, function (oRegistro, r) {
			angular.forEach (oRegistro.grupos, function (oGrupo, g){
                if (oGrupo.campos !== undefined) {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        let sValor = oCampo.valor !== null ? (oCampo.valor.toString().trim().length > 0 ? oCampo.valor.toString().trim() : null) : null;
    
                        if (oCampo.tipo === 'fecha') {
                            sValor = servicioGeneral.obtenerFecha($('#' + oRegistro.tabla + '_' + oCampo.bd).val());
                        } else if (oCampo.tipo === 'fecha_hora') {
                            let sFecha = servicioGeneral.obtenerFecha($('#' + oRegistro.tabla + '_' + oCampo.bd).val());
                            sValor = null;
    
                            if (sFecha !== null) {
                                let sHora = servicioGeneral.obtenerHora($('#' + oRegistro.tabla + '_' + oCampo.bd).val());
    
                                if (sFecha !== null && sHora !== null) {
                                    sValor = sFecha + ' ' + sHora;
                                }
                            }
                        } else if (oRegistro.tabla === 'usuario_encuesta' && oCampo.bd === 'nombre_usuario') {
                            oCampo.tipo = 'texto';
                        } else if (oRegistro.tabla === 'uso_recurso_hidrico' && oCampo.bd === 'consumo') {
                            oCampo.tipo = 'texto';
                        }
    
                        oCampo.valor = sValor;
                    });
                }
            });
		});

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "guardarCambiosEncuesta", "datos": oDatosEditar },
            "success": function (response) {
                $("#ajax-loading").hide();

                if (response.bProceso) {
                    toastr.success("Proceso realizado", "Información");
                } else {
                    $("#ajax-loading").hide();
                    toastr.error("Ocurrió un error", "Error");
                }
            }
        });
    }

    $scope.limpiarSelect = function(sTabla, oCampoSeleccionado) {
        oCampoSeleccionado.valor = null;
        $scope.seleccionSimple(sTabla, oCampoSeleccionado);
    }

    $scope.seleccionSimple = function (sTabla, oCampoSeleccionado) {
        if ($scope.aValoresActualesSelect[sTabla + '_' + oCampoSeleccionado.bd] !== undefined && oCampoSeleccionado.valor !== null && oCampoSeleccionado.tipo === 'radio') {
            if ($scope.aValoresActualesSelect[sTabla + '_' + oCampoSeleccionado.bd] === oCampoSeleccionado.valor) {
                oCampoSeleccionado.valor = null;
            }
        }

        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.dependiente !== undefined) {
                    if (oGrupo.dependiente === oCampoSeleccionado.bd && sTabla === oRegistro.tabla) {
                        oGrupo.mostrar = oCampoSeleccionado.valor !== null ? parseInt(oCampoSeleccionado.valor) === parseInt(oGrupo.valor_dependiente) : false;

                        if (oGrupo.campos !== undefined) {
                            angular.forEach (oGrupo.campos, function (oCampo, c) {
                                if (oGrupo.dependiente === 'id_departamento') {
                                    oCampo.valor = oGrupo.mostrar ? "25" : null;
                                } else if (!oGrupo.mostrar) {
                                    oCampo.valor = null;
                                }
                            });
                        } else if (oGrupo.filas !== undefined) {
                            angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                                angular.forEach (aFilaCampos, function (oCampo, c) {
                                    if (oGrupo.dependiente === 'id_departamento') {
                                        oCampo.valor = oGrupo.mostrar ? "25" : null;
                                    } else if (!oGrupo.mostrar) {
                                        oCampo.valor = null;
                                    }     
                                });
                            });
                        }
                    }
                } else {
                    oGrupo.mostrar = true;

                    if (oCampoSeleccionado.tipo === 'seleccion_simple_texto_editable' && sTabla === oRegistro.tabla) {
                        angular.forEach (oGrupo.campos, function (oCampo, c) {

                            if (oCampoSeleccionado.bd === 'nombre_encuestador' && oCampo.bd === 'cedula_encuestador') {
                                oCampo.valor = null;

                                if (oCampoSeleccionado.valor !== null) {
                                    if ($scope.aIdsListas[oCampoSeleccionado.valor] !== undefined) {
                                        var oLista = $scope.aIdsListas[oCampoSeleccionado.valor];
                                        oCampo.valor = oLista.codigo;
                                    }
                                }
                            }
                        });
                    } 
                    
                    //Selección automática de código
                    if ((oCampoSeleccionado.bd === 'id_municipio' || oCampoSeleccionado.bd === 'id_nombre_und_geografica_nivel1' || oCampoSeleccionado.bd === 'id_nombre_und_geografica_nivel2') && sTabla === oRegistro.tabla) {
                        angular.forEach (oGrupo.campos, function (oCampo, c) {
                            if (
                                (oCampoSeleccionado.bd === 'id_municipio' && oCampo.bd === 'id_dane_municipio') || 
                                (oCampoSeleccionado.bd === 'id_nombre_und_geografica_nivel1' && oCampo.bd === 'id_codigo_und_geografica_nivel1') ||
                                (oCampoSeleccionado.bd === 'id_nombre_und_geografica_nivel2' && oCampo.bd === 'id_codigo_und_geografica_nivel2')
                            ) {
                                oCampo.valor = null;

                                if (oCampoSeleccionado.valor !== null) {
                                    var aListaCodigos = [];

                                    switch (oCampo.bd) {
                                        case 'id_dane_municipio': aListaCodigos = $scope.aListas[5]; break;
                                        case 'id_codigo_und_geografica_nivel1': aListaCodigos = $scope.aListas[9]; break;
                                        case 'id_codigo_und_geografica_nivel2': aListaCodigos = $scope.aListas[11]; break;
                                    }

                                    if ($scope.aIdsListas[oCampoSeleccionado.valor] !== undefined) {
                                        var oLista = $scope.aIdsListas[oCampoSeleccionado.valor];

                                        angular.forEach (aListaCodigos, function(oRegistroCodigo, m){
                                            if (oRegistroCodigo.id_kobo === oLista.codigo) {
                                                oCampo.valor = oRegistroCodigo.id;
                                            }
                                        });
                                    }
                                }
                            }
                        });
                    }

                    //Información de la captación en la fuente
                    if (oCampoSeleccionado.bd === 'id_tipo_captacion') {
                        if (sTabla === oRegistro.tabla && oGrupo.filas !== undefined && oGrupo.tabla === 'captacion_en_fuente_opciones') {
                            if (oCampoSeleccionado.valor === null) {
                                oGrupo.mostrar = false;
                            }

                            angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                                angular.forEach (aFilaCampos, function (oCampo, c) {
                                    oCampo.mostrar = (parseInt(oCampoSeleccionado.valor) === 419 && oCampo.fila === 'captacion_en_fuente_42') || 
                                    (parseInt(oCampoSeleccionado.valor) === 427 && (oCampo.fila === 'captacion_en_fuente_43' || oCampo.fila === 'captacion_en_fuente_44' || oCampo.fila === 'captacion_en_fuente_45')) || 
                                    (parseInt(oCampoSeleccionado.valor) === 420 && oCampo.fila === 'captacion_en_fuente_42') || 
                                    (parseInt(oCampoSeleccionado.valor) === 421 && (oCampo.fila === 'captacion_en_fuente_42' || oCampo.fila === 'captacion_en_fuente_48')) || 
                                    (parseInt(oCampoSeleccionado.valor) === 422 && (oCampo.fila === 'captacion_en_fuente_43' || oCampo.fila === 'captacion_en_fuente_44' || oCampo.fila === 'captacion_en_fuente_45')) || 
                                    (parseInt(oCampoSeleccionado.valor) === 423 && (oCampo.fila === 'captacion_en_fuente_43' || oCampo.fila === 'captacion_en_fuente_44' || oCampo.fila === 'captacion_en_fuente_45')) || 
                                    (parseInt(oCampoSeleccionado.valor) === 424 && (oCampo.fila === 'captacion_en_fuente_43' || oCampo.fila === 'captacion_en_fuente_44' || oCampo.fila === 'captacion_en_fuente_45')) || 
                                    (parseInt(oCampoSeleccionado.valor) === 425 && (oCampo.fila === 'captacion_en_fuente_43' || oCampo.fila === 'captacion_en_fuente_44' || oCampo.fila === 'captacion_en_fuente_45')) || 
                                    (parseInt(oCampoSeleccionado.valor) === 426 && (oCampo.fila === 'captacion_en_fuente_43' || oCampo.fila === 'captacion_en_fuente_44' || oCampo.fila === 'captacion_en_fuente_45')) || 
                                    (oCampoSeleccionado.valor !== null && (oCampo.fila === 'captacion_en_fuente_46' || oCampo.fila === 'captacion_en_fuente_47')); 

                                    if (!oCampo.mostrar) {
                                        oCampo.valor = null;
                                    }
                                });
                            });
                        }
                    }

                    //Uso doméstico
                    if (oCampoSeleccionado.bd === 'id_uso_domestico' && sTabla === oRegistro.tabla) {
                        angular.forEach (oGrupo.campos, function (oCampo, c) {
                            if (oCampoSeleccionado.valor === 438) { //no aplica
                                if (oCampo.bd === 'personas_permanentes' || oCampo.bd === 'personas_transitorias' || oCampo.bd === 'dias_mes' || oCampo.bd === 'consumo' || oCampo.bd === 'menores_cinco_anios' || oCampo.bd === 'mayores_sesenta_anios') {
                                    oCampo.valor = null;
                                }

                                if (oCampo.bd === 'personas_permanentes') {
                                    $scope.ingresaDatos(oRegistro.tabla, oCampo);
                                }
                            } 
                        });
                    }
                }
            });
        });

        if (oCampoSeleccionado.bd === 'id_uso_pecuario') {
            $scope.sumasRecursoHidrico(['uso_pecuario_1_consumo', 'uso_pecuario_2_consumo', 'uso_pecuario_3_consumo', 'uso_pecuario_4_consumo'], 'pecuario');
        } else if (oCampoSeleccionado.bd === 'id_uso_acuicola') {
            $scope.sumasRecursoHidrico(['uso_acuicola_1_consumo', 'uso_acuicola_2_consumo', 'uso_acuicola_3_consumo', 'uso_acuicola_4_consumo'], 'acuicola');
        } else if (oCampoSeleccionado.bd === 'id_uso_agricola_silvicola') {
            $scope.sumasRecursoHidrico(['uso_agricola_silvicola_1_consumo', 'uso_agricola_silvicola_2_consumo', 'uso_agricola_silvicola_3_consumo', 'uso_agricola_silvicola_4_consumo'], 'agricola');
        } else if (oCampoSeleccionado.bd === 'id_uso_industrial') {
            $scope.sumasRecursoHidrico(['uso_industrial_consumo'], 'industrial');
        } else if (oCampoSeleccionado.bd === 'id_uso_minero') {
            $scope.sumasRecursoHidrico(['uso_minero_consumo'], 'mineria');
        } else if (oCampoSeleccionado.bd === 'id_uso_generacion_hidroelectrica') {
            $scope.sumasRecursoHidrico(['uso_generacion_hidroelectrica_consumo'], 'generacion_electrica');
        } else if (oCampoSeleccionado.bd === 'id_uso_servicios' || oCampoSeleccionado.bd === 'id_uso_explotacion_petrolera' || oCampoSeleccionado.bd === 'id_otros_usos') {
            $scope.sumasRecursoHidrico(['uso_servicios_consumo', 'uso_explotacion_petrolera_consumo', 'otros_usos_consumo'], 'otros');
        }

        $scope.aValoresActualesSelect[sTabla + '_' + oCampoSeleccionado.bd] = oCampoSeleccionado.valor;

        $scope.$evalAsync();
    }

    $scope.seleccionMultiple = function (sTabla, oCampoSeleccionado) {
        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.dependiente !== undefined) {
                    if (oGrupo.dependiente === oCampoSeleccionado.bd && sTabla === oRegistro.tabla) {
                        oGrupo.mostrar = oCampoSeleccionado.valor !== null ? oCampoSeleccionado.valor.indexOf(oGrupo.valor_dependiente) !== -1 : false;
                    }
                }
            });
        });
    }

    $scope.visualizarImagen = function (event) {
        setTimeout(function() {
            if (event.target !== undefined) {
                if (event.target.id !== undefined && event.target.files !== undefined) {
                    var file = event.target.files[0];

                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var img = new Image();
                        img.src = reader.result;
                        var output = document.getElementById('vpimagen_' + event.target.id);
                        output.src = img.src;

                        $('#dvimagen_' + event.target.id).show();

                        if ($("#" + event.target.id).attr("tabla") !== undefined && $("#" + event.target.id).attr("bd") !== undefined) {
                            angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                                    if (oGrupo.campos !== undefined) {
                                        angular.forEach (oGrupo.campos, function (oCampo, c) {
                                            if (oGrupo.tipo === 'imagen' && oCampo.bd === $("#" + event.target.id).attr("bd") && oRegistro.tabla === $("#" + event.target.id).attr("tabla")) {
                                                oCampo.valor = reader.result;
                                                oCampo.archivo = true;
                                                delete oCampo.url;
                                            }
                                        });
                                    }
                                });
                            });
                        }
                    }

                    reader.readAsDataURL(file);
                }
            }
        }, 500);
    }

    $scope.eliminarImagen = function (oRegistroImagen, oCampoImagenEliminar) {
        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.campos !== undefined) {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        if (oGrupo.tipo === 'imagen' && oCampo.bd === oCampoImagenEliminar.bd && oRegistro.tabla === oRegistroImagen.tabla) {
                            oCampo.valor = null;
                            oCampo.archivo = false;
                            oCampo.adjunto = null;
                            delete oCampo.url;

                            var output = document.getElementById('vpimagen_' + oRegistro.tabla + '_' + oCampoImagenEliminar.bd);
                            output.src = '';

                            $('#dvimagen_' + oRegistro.tabla + '_' + oCampoImagenEliminar.bd).hide();
                        }
                    });
                }
            });
        });
    }

    $scope.ingresaDatos = function (sTablaSeleccionada, oCampoSeleccionado) {
        if (oCampoSeleccionado.bd === 'nombre_encuestado') {
            angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                if (oRegistro.tabla === 'usuario_encuesta') {
                    angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                        if (oGrupo.campos !== undefined) {
                            angular.forEach (oGrupo.campos, function (oCampo, c) {
                                if (oCampo.bd === 'nombre_usuario') {
                                    oCampo.valor = oCampoSeleccionado.valor;
                                }
                            });
                        }
                    });
                }
            });
        } else if (oCampoSeleccionado.bd === 'personas_permanentes') {
            angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                if (oRegistro.tabla === sTablaSeleccionada) {
                    angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                        if (oGrupo.campos !== undefined) {
                            angular.forEach (oGrupo.campos, function (oCampo, c) {
                                if (oCampo.bd === 'consumo') {
                                    oCampo.valor = null;

                                    if (servicioGeneral.isNumeric(oCampoSeleccionado.valor)) {
                                        // Calcula el valor
                                        let valorCalculado = parseFloat(oCampoSeleccionado.valor) * (120 / 86400);
                                        // Formatea y asigna
                                        oCampo.valor = parseFloat(valorCalculado.toFixed(4)); 
                                    }
                                }
                            });
                        } else if (oGrupo.filas !== undefined) {
                            angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                                angular.forEach (aFilaCampos, function (oCampo, c) {
                                    if (oCampo.bd === 'domestico') {
                                        oCampo.valor = null;
    
                                        if (servicioGeneral.isNumeric(oCampoSeleccionado.valor)) {
                                            // Calcula el valor
                                            let valorCalculado = parseFloat(oCampoSeleccionado.valor) * (120 / 86400);
                                            // Formatea y asigna
                                            oCampo.valor = parseFloat(valorCalculado.toFixed(4)); 
                                        }
                                    }
                                });
                            });
                        }
                    });
                }
            });

            $scope.sumaTotalRecursoHidrico();

        } else if (oCampoSeleccionado.bd === 'direccion_domicilio' || oCampoSeleccionado.bd === 'direccion_domicilio_referencia' || oCampoSeleccionado.bd === 'direccion_domicilio_predio' || oCampoSeleccionado.bd === 'telefono_domicilio') {
            var sCorrespondencia = '';
            var sTelefonoCorrespondencia = '';

            angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                if (oRegistro.tabla === sTablaSeleccionada) {
                    angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                        if (oGrupo.campos !== undefined) {
                            angular.forEach (oGrupo.campos, function (oCampo, c) {
                                if (oCampo.bd === 'direccion_domicilio' && oCampo.valor !== null && oCampo.valor.trim().length > 0) {
                                    sCorrespondencia = oCampo.valor.trim();
                                } else if (oCampo.bd === 'direccion_domicilio_referencia' && oCampo.valor !== null && oCampo.valor.trim().length > 0) {
                                    sCorrespondencia = sCorrespondencia.trim() + ' ' + oCampo.valor.trim();
                                } else if (oCampo.bd === 'direccion_domicilio_predio' && oCampo.valor !== null && oCampo.valor.trim().length > 0) {
                                    sCorrespondencia = sCorrespondencia.trim() + ' ' + oCampo.valor.trim();
                                } else if (oCampo.bd === 'telefono_domicilio' && oCampo.valor !== null && oCampo.valor.trim().length > 0) {
                                    sTelefonoCorrespondencia = oCampo.valor.trim();
                                }
                            });
                        }
                    });
                }
            });

            angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                if (oRegistro.tabla === sTablaSeleccionada) {
                    angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                        if (oGrupo.campos !== undefined) {
                            angular.forEach (oGrupo.campos, function (oCampo, c) {
                                if (oCampo.bd === 'direccion_correspondencia') {
                                    oCampo.valor = sCorrespondencia.trim().length > 0 ? sCorrespondencia.trim() : null;
                                } else if (oCampo.bd === 'telefono_correspondencia') {
                                    oCampo.valor = sTelefonoCorrespondencia.trim().length > 0 ? sTelefonoCorrespondencia.trim() : null;
                                }
                            });
                        }
                    });
                }
            });
        } else if (oCampoSeleccionado.bd === 'uso_pecuario_1_consumo' || oCampoSeleccionado.bd === 'uso_pecuario_2_consumo' || oCampoSeleccionado.bd === 'uso_pecuario_3_consumo' || oCampoSeleccionado.bd === 'uso_pecuario_4_consumo') {
            $scope.sumasRecursoHidrico(['uso_pecuario_1_consumo', 'uso_pecuario_2_consumo', 'uso_pecuario_3_consumo', 'uso_pecuario_4_consumo'], 'pecuario');
        } else if (oCampoSeleccionado.bd === 'uso_acuicola_1_consumo' || oCampoSeleccionado.bd === 'uso_acuicola_2_consumo' || oCampoSeleccionado.bd === 'uso_acuicola_3_consumo' || oCampoSeleccionado.bd === 'uso_acuicola_4_consumo') {
            $scope.sumasRecursoHidrico(['uso_acuicola_1_consumo', 'uso_acuicola_2_consumo', 'uso_acuicola_3_consumo', 'uso_acuicola_4_consumo'], 'acuicola');
        } else if (oCampoSeleccionado.bd === 'uso_agricola_silvicola_1_consumo' || oCampoSeleccionado.bd === 'uso_agricola_silvicola_2_consumo' || oCampoSeleccionado.bd === 'uso_agricola_silvicola_3_consumo' || oCampoSeleccionado.bd === 'uso_agricola_silvicola_4_consumo') {
            $scope.sumasRecursoHidrico(['uso_agricola_silvicola_1_consumo', 'uso_agricola_silvicola_2_consumo', 'uso_agricola_silvicola_3_consumo', 'uso_agricola_silvicola_4_consumo'], 'agricola');
        } else if (oCampoSeleccionado.bd === 'uso_industrial_consumo') {
            $scope.sumasRecursoHidrico(['uso_industrial_consumo'], 'industrial');
        } else if (oCampoSeleccionado.bd === 'uso_minero_consumo') {
            $scope.sumasRecursoHidrico(['uso_minero_consumo'], 'mineria');
        } else if (oCampoSeleccionado.bd === 'uso_generacion_hidroelectrica_consumo') {
            $scope.sumasRecursoHidrico(['uso_generacion_hidroelectrica_consumo'], 'generacion_electrica');
        } else if (oCampoSeleccionado.bd === 'uso_servicios_consumo' || oCampoSeleccionado.bd === 'uso_explotacion_petrolera_consumo' || oCampoSeleccionado.bd === 'otros_usos_consumo') {
            $scope.sumasRecursoHidrico(['uso_servicios_consumo', 'uso_explotacion_petrolera_consumo', 'otros_usos_consumo'], 'otros');
        } else if (['domestico', 'pecuario', 'acuicola', 'agricola', 'industrial', 'mineria', 'generacion_electrica', 'otros'].indexOf(oCampoSeleccionado.bd) !== -1) {
            $scope.sumaTotalRecursoHidrico();
        }

        $scope.$evalAsync();
    }

    $scope.sumasRecursoHidrico = function (aCamposSumaUso, sCampoColocarSuma) {
        let fSuma = null;
        
        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
            if (oRegistro.tabla === 'uso_recurso_hidrico') {
                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                    if (oGrupo.filas !== undefined) {
                        angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                            angular.forEach (aFilaCampos, function (oCampo, c) {
                                if (aCamposSumaUso.indexOf(oCampo.bd) !== -1 && oCampo.valor !== null && servicioGeneral.isNumeric(oCampo.valor)) {
                                    if (fSuma === null) {
                                        fSuma = 0;
                                    }

                                    fSuma += parseFloat(oCampo.valor);
                                }
                            });
                        });
                    }
                });
            }
        });

        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
            if (oRegistro.tabla === 'uso_recurso_hidrico') {
                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                    if (oGrupo.filas !== undefined) {
                        angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                            angular.forEach (aFilaCampos, function (oCampo, c) {
                                if (oCampo.bd === sCampoColocarSuma) {
                                    oCampo.valor = fSuma !== null ? parseFloat(fSuma.toFixed(4)) : null;
                                }
                            });
                        });
                    }
                });
            }
        });

        $scope.sumaTotalRecursoHidrico();
    }

    $scope.sumaTotalRecursoHidrico = function () {
        let fTotal = null;
        let aCampostotal = ['domestico', 'pecuario', 'acuicola', 'agricola', 'industrial', 'mineria', 'generacion_electrica', 'otros'];

        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
            if (oRegistro.tabla === 'uso_recurso_hidrico') {
                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                    if (oGrupo.filas !== undefined) {
                        angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                            angular.forEach (aFilaCampos, function (oCampo, c) {
                                if (aCampostotal.indexOf(oCampo.bd) !== -1 && oCampo.valor !== null && servicioGeneral.isNumeric(oCampo.valor)) {
                                    if (fTotal === null) {
                                        fTotal = 0;
                                    }

                                    fTotal += parseFloat(oCampo.valor);
                                }
                            });
                        });
                    }
                });
            }
        });

        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
            if (oRegistro.tabla === 'uso_recurso_hidrico') {
                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                    if (oGrupo.filas !== undefined) {
                        angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                            angular.forEach (aFilaCampos, function (oCampo, c) {
                                if (oCampo.bd === 'total') {
                                    oCampo.valor = fTotal !== null ? parseFloat(fTotal.toFixed(4)) : null;
                                }
                            });
                        });
                    }
                });
            }
        });
    }

    $scope.ocultarMostrarModulo = function () {
        angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.campos !== undefined) {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        if (oRegistro.id_modulo === 1 && !$scope.oEditarEncuesta.modulo1) {
                            oCampo.valor = null;
                        } else if (oRegistro.id_modulo === 2 && !$scope.oEditarEncuesta.modulo2) {
                            oCampo.valor = null;
                        } else if (oRegistro.id_modulo === 3 && !$scope.oEditarEncuesta.modulo3) {
                            oCampo.valor = null;
                        } else if (oRegistro.id_modulo === 4 && !$scope.oEditarEncuesta.modulo4) {
                            oCampo.valor = null;
                        }  
                    });
                } else if (oGrupo.filas !== undefined) {
                    angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                        angular.forEach (aFilaCampos, function (oCampo, c) {
                            if (oRegistro.id_modulo === 1 && !$scope.oEditarEncuesta.modulo1) {
                                oCampo.valor = null;
                            } else if (oRegistro.id_modulo === 2 && !$scope.oEditarEncuesta.modulo2) {
                                oCampo.valor = null;
                            } else if (oRegistro.id_modulo === 3 && !$scope.oEditarEncuesta.modulo3) {
                                oCampo.valor = null;
                            } else if (oRegistro.id_modulo === 4 && !$scope.oEditarEncuesta.modulo4) {
                                oCampo.valor = null;
                            } 
                        });
                    });
                }
            });
        });

        if ($scope.oEditarEncuesta.modulo1) {
            $(".modulo1").show();
        } else {
            $(".modulo1").hide();
        }

        if ($scope.oEditarEncuesta.modulo2) {
            $(".modulo2").show();
        } else {
            $(".modulo2").hide();
        }

        if ($scope.oEditarEncuesta.modulo3) {
            $(".modulo3").show();
        } else {
            $(".modulo3").hide();
        }

        if ($scope.oEditarEncuesta.modulo4) {
            $(".modulo4").show();
        } else {
            $(".modulo4").hide();
        }
    }

    $scope.oEditarEncuesta = { "id": null, "estructura": [] };
    $scope.aListas = [];
    $scope.aIdsListas = [];
    $scope.aValoresActualesSelect = [];
    $scope.cargarDatosEncuesta();
});
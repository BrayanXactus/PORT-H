angular.module(APPNAME).controller('registroController', function($scope, $rootScope, $location, configuracionGlobal, servicioGeneral) {
    $scope.estructuraEncuesta = function () {
        $("#ajax-loading").show();

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "cargarEstructuraEncuesta", "listas": true },
            "success": function (response) {
                $scope.oEncuesta = { "identificador": null, "modulo1": true, "modulo2": true, "modulo3": true, "modulo4": true, "estructura": response.estructura };
                $scope.oCopiaEncuesta = angular.copy($scope.oEncuesta);
                let aValoresEncuestaPendienteGuardar = [];
                let oEncuestaPendienteGuardar = null;

                $scope.aListas = [];
                $scope.aIdsListas = [];

                angular.forEach(response.listas, function (oLista, l) {
                    $scope.aListas[oLista.id_lista] = $scope.aListas[oLista.id_lista] !== undefined ? $scope.aListas[oLista.id_lista] : [];
                    $scope.aListas[oLista.id_lista].push({ "id": oLista.id, "nombre": oLista.descripcion, "codigo": oLista.codigo, "id_kobo": oLista.id_kobo });
                    $scope.aIdsListas[oLista.id] = { "id": oLista.id, "nombre": oLista.descripcion, "codigo": oLista.codigo };
                });

                //Se obtiene encuesta guardada
                if (window.indexedDB) {
                    $scope.bdRequest = indexedDB.open('encuestas_pendientes', 1);

                    $scope.bdRequest.onupgradeneeded = (event) => {
                        let db = event.target.result;
                    
                        let store_lista = db.createObjectStore("lista", {
                            autoIncrement: true
                        });
        
                        let store_actual = db.createObjectStore("actual", {
                            autoIncrement: true
                        });
                    };

                    $scope.bdRequest.onsuccess = (event) => {
                        let db = event.target.result;
                        let txn = db.transaction("actual", "readonly");
                        let objectStore = txn.objectStore("actual");
            
                        objectStore.openCursor().onsuccess = (event) => {
                            let cursor = event.target.result;
            
                            if (cursor) {
                                oEncuestaPendienteGuardar = cursor.value;
                                cursor.continue();
                            }
                        };

                        txn.oncomplete = function () {
                            db.close();

                            if (oEncuestaPendienteGuardar !== null) {
                                $scope.oEncuesta.modulo1 = oEncuestaPendienteGuardar.modulo1;
                                $scope.oEncuesta.modulo2 = oEncuestaPendienteGuardar.modulo2;
                                $scope.oEncuesta.modulo3 = oEncuestaPendienteGuardar.modulo3;
                                $scope.oEncuesta.modulo4 = oEncuestaPendienteGuardar.modulo4;

                                angular.forEach (oEncuestaPendienteGuardar.estructura, function (oRegistro, r) {
                                    angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                                        if (oGrupo.campos !== undefined) {
                                            angular.forEach (oGrupo.campos, function (oCampo, c) {
                                                let sIndice = oRegistro.tabla + '_' + oCampo.bd;
            
                                                if (oCampo.valor !== null) {
                                                    let oInfo = { "valor": oCampo.valor };
                                                    aValoresEncuestaPendienteGuardar[sIndice] = oInfo;
                                                }
                                            });
                                        } else if (oGrupo.filas !== undefined) {
                                            angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                                                angular.forEach (aFilaCampos, function (oCampo, c) {
                                                    let sIndice = oRegistro.tabla + '_' + oCampo.bd;
            
                                                    if (oCampo.valor !== null) {
                                                        let oInfo = { "valor": oCampo.valor };
                                                        aValoresEncuestaPendienteGuardar[sIndice] = oInfo;
                                                    }
                                                });
                                            });
                                        } 
                                    });
                                });
                            }

                            $scope.iniciarValores(aValoresEncuestaPendienteGuardar);
                        }
                    };
                } else {
                    $scope.iniciarValores(aValoresEncuestaPendienteGuardar);
                }
                
                if (window.navigator.onLine) {
                    $scope.enviarEncuestas();
                }
            }
        });
    }

    $scope.iniciarValores = function (aValoresEncuestaPendienteGuardar) {
        let sFechaActual = servicioGeneral.fechaHoraActual(true);

        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.campos !== undefined) {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        let sIndice = oRegistro.tabla + '_' + oCampo.bd;
                        let oValorEncuestaPendienteGuardar = aValoresEncuestaPendienteGuardar[sIndice] !== undefined ? aValoresEncuestaPendienteGuardar[sIndice] : {};

                        if (oCampo.bd === 'fecha_diligenciamiento' || oCampo.bd === 'fecha_procesamiento') {
                            oCampo.tipo = 'disabled_edit';
                        }

                        if (oValorEncuestaPendienteGuardar.valor !== undefined) {
                            let sValor = oValorEncuestaPendienteGuardar.valor;

                            if (oCampo.tipo === 'fecha') {
                                oCampo.valor = new Date(sValor.substring(0, 10) + ' 00:00:00');
                            } else if (oCampo.tipo === 'fecha_hora') {
                                oCampo.valor = new Date(sValor);
                            } else if (oCampo.tipo === 'seleccion_multiple') {
                                oCampo.valor = null;

                                if (sValor !== null) {
                                    let aValores = sValor.split(',');
                                    oCampo.valor = [];

                                    for (let k in aValores) {
                                        oCampo.valor.push(parseInt(aValores[k]));
                                    }
                                }
                            } else {
                                oCampo.valor = oCampo.tipo === 'seleccion_simple' || oCampo.tipo === 'radio' || oCampo.tipo === 'seleccion_simple_texto_editable' ? parseInt(sValor) : sValor;
                            }
                        }

                        //Predeterminados
                        if (oCampo.bd === 'coordinador_proyecto') {
                            oCampo.valor = 'Eder Pedraza F.';
                        } else if (oCampo.bd === 'fecha_diligenciamiento') {
                            oCampo.valor = sFechaActual;
                        } else if (oCampo.bd === 'fecha_procesamiento') {
                            oCampo.valor = sFechaActual;
                        } else if (oCampo.bd === 'corregimiento' && oRegistro.tabla === 'informacion_actividad_minera_cuerpos_agua') {
                            oCampo.valor = 'No Aplica';
                        }

                        if (oCampo.tipo === 'seleccion_simple' || oCampo.tipo === 'radio' || oCampo.tipo === 'seleccion_simple_texto_editable') {
                            oCampo.aOpcionesLista = $scope.aListas[oCampo.id_lista] !== undefined ? $scope.aListas[oCampo.id_lista] : [];
                            $scope.seleccionSimple(oRegistro.tabla, oCampo);
                        } else if (oCampo.tipo === 'seleccion_multiple') {
                            oCampo.aOpcionesLista = $scope.aListas[oCampo.elementos] !== undefined ? $scope.aListas[oCampo.elementos] : [];
                            $scope.seleccionMultiple(oRegistro.tabla, oCampo);
                        } else if (oCampo.tipo === 'imagen') {
                            oCampo.adjunto = null;
                            
                            if (oCampo.valor !== null) {
                                setTimeout(function() {
                                    var img = new Image();
                                    img.src = oCampo.valor;
                                    var output = document.getElementById('vpimagen_' + sIndice);
                                    output.src = img.src;
                                    $('#dvimagen_' + sIndice).show();
                                }, 500);
                            }
                        }
                    });
                } else if (oGrupo.filas !== undefined) {
                    angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                        angular.forEach (aFilaCampos, function (oCampo, c) {
                            let sIndice = oRegistro.tabla + '_' + oCampo.bd;
                            let oValorEncuestaPendienteGuardar = aValoresEncuestaPendienteGuardar[sIndice] !== undefined ? aValoresEncuestaPendienteGuardar[sIndice] : {};

                            if (oValorEncuestaPendienteGuardar.valor !== undefined) {
                                oCampo.valor = oValorEncuestaPendienteGuardar.valor;
                            }

                            if (oCampo.tipo === 'seleccion_simple' || oCampo.tipo === 'radio' || oCampo.tipo === 'seleccion_simple_texto_editable') {
                                oCampo.aOpcionesLista = $scope.aListas[oCampo.id_lista] !== undefined ? $scope.aListas[oCampo.id_lista] : [];
                                $scope.seleccionSimple(oRegistro.tabla, oCampo);
                            }
                        });
                    });
                } 
            });
        });

        $("#ajax-loading").hide();
        $scope.bEjecutarGuardadoLocal = false;
        setTimeout(function () {
            $scope.ocultarMostrarModulo();
        }, 300);
        $scope.$evalAsync();
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

        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
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
        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.dependiente !== undefined) {
                    if (oGrupo.dependiente === oCampoSeleccionado.bd && sTabla === oRegistro.tabla) {
                        oGrupo.mostrar = oCampoSeleccionado.valor !== null ? oCampoSeleccionado.valor.indexOf(oGrupo.valor_dependiente) !== -1 : false;
                    }
                } else {
                    oGrupo.mostrar = true;
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
                            angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
                                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                                    if (oGrupo.campos !== undefined) {
                                        angular.forEach (oGrupo.campos, function (oCampo, c) {
                                            if (oGrupo.tipo === 'imagen' && oCampo.bd === $("#" + event.target.id).attr("bd") && oRegistro.tabla === $("#" + event.target.id).attr("tabla")) {
                                                oCampo.valor = reader.result;
                                                oCampo.archivo = null;
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

    $scope.visualizarImagen2 = function (event) {
        setTimeout(function() {
            if (event.target !== undefined) {
                if (event.target.id !== undefined && event.target.files !== undefined) {
                    var output = document.getElementById('vpimagen_' + event.target.id);
                    output.src = URL.createObjectURL(event.target.files[0]);
                    $('#dvimagen_' + event.target.id).show();

                    if ($("#" + event.target.id).attr("tabla") !== undefined && $("#" + event.target.id).attr("bd") !== undefined) {
                        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
                            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                                if (oGrupo.campos !== undefined) {
                                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                                        if (oGrupo.tipo === 'imagen' && oCampo.bd === $("#" + event.target.id).attr("bd") && oRegistro.tabla === $("#" + event.target.id).attr("tabla")) {
                                            oCampo.valor = output.src;
                                            oCampo.archivo = servicioGeneral.getBase64Image(output);
                                        }
                                    });
                                }
                            });
                        });
                    }
    
                    /*output.onload = function() {
                        URL.revokeObjectURL(output.src);
                    }*/
                }
            }
        }, 500);
    }

    $scope.enviarEncuestas = function() {
        let oDatosEncuestaEnviar = null;
        let iContadorEncuestasPendientes = 0;
        let iClave = 0;
        $scope.bEjecutarGuardadoLocal = true;

        if (window.indexedDB) {
            $scope.bdRequest = indexedDB.open('encuestas_pendientes', 1);

            $scope.bdRequest.onupgradeneeded = (event) => {
                let db = event.target.result;
           
                let store_lista = db.createObjectStore("lista", {
                    autoIncrement: true
                });

                let store_actual = db.createObjectStore("actual", {
                    autoIncrement: true
                });
            };

            $scope.bdRequest.onsuccess = (event) => {
                let db = event.target.result;
                let txn = db.transaction("lista", "readonly");
                let objectStore = txn.objectStore("lista");
    
                objectStore.openCursor().onsuccess = (event) => {
                    let cursor = event.target.result;

                    if (cursor) {
                        oDatosEncuestaEnviar = cursor.value;
                        iClave = cursor.key;
                        iContadorEncuestasPendientes++;
                        cursor.continue();
                    }
                };

                txn.oncomplete = function () {
                    db.close();
                    $scope.iEncuestasPendientesEnviar = iContadorEncuestasPendientes;
                    
                    if (oDatosEncuestaEnviar !== null) {
                        $scope.bEjecutarGuardadoLocal = true;
                        $scope.$evalAsync();
                        $scope.guardarEncuestaServidor(oDatosEncuestaEnviar, true, iClave);
                    } else {
                        $scope.bEjecutarGuardadoLocal = false;
                        $scope.$evalAsync();
                    }
                };
            };
        } else {
            $scope.bEjecutarGuardadoLocal = false;
        }
    }

    $scope.guardarEncuesta = function() {
        $("#ajax-loading").show();

        setTimeout(function () {
            let oDatosRegistrar = angular.copy($scope.oEncuesta);
            $scope.bEjecutarGuardadoLocal = true;

            angular.forEach (oDatosRegistrar.estructura, function (oRegistro, r) {
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
                            } else if (oCampo.tipo === 'imagen' && oCampo.adjunto !== undefined) {
                                delete oCampo.adjunto;
                            }
        
                            oCampo.valor = sValor;
                        });
                    }
                });
            });

            if (window.navigator.onLine) {
                $scope.guardarEncuestaServidor(oDatosRegistrar, false, 0);
            } else {
                $scope.almacenarEncuestaLocal(oDatosRegistrar);
            }
        }, 100);
    }

    $scope.guardarEncuestaServidor = function (oDatosRegistrar, bCargaPendienteConexion, iClave) {
        if (bCargaPendienteConexion) {
            $("#ajax-loading").hide();
        }

        //Covierto los campos número, decimal y seleccion_simple_texto_editable a texto
        angular.forEach (oDatosRegistrar.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.campos !== undefined) {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        if (oCampo.tipo === 'numero' || oCampo.tipo === 'decimal') {
                            oCampo.valor = oCampo.valor !== null ? (oCampo.valor.toString().trim().length > 0 ? oCampo.valor.toString().trim() : null) : null;
                            oCampo.tipo = 'texto';
                        } else if (oCampo.tipo === 'seleccion_simple_texto_editable' && oCampo.valor !== null) {
                            if ($scope.aIdsListas[oCampo.valor] !== undefined) {
                                var oListaAsociada = $scope.aIdsListas[oCampo.valor];
                                oCampo.valor = oListaAsociada.nombre;
                            } else {
                                oCampo.valor = null;
                            }

                            oCampo.tipo = 'texto';
                        }
                    });
                } else if (oGrupo.filas !== undefined) {
                    angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                        angular.forEach (aFilaCampos, function (oCampo, c) {
                            if (oCampo.tipo === 'numero' || oCampo.tipo === 'decimal') {
                                oCampo.valor = oCampo.valor !== null ? (oCampo.valor.toString().trim().length > 0 ? oCampo.valor.toString().trim() : null) : null;
                                oCampo.tipo = 'texto';
                            } else if (oCampo.tipo === 'seleccion_simple_texto_editable' && oCampo.valor !== null) {
                                if ($scope.aIdsListas[oCampo.valor] !== undefined) {
                                    var oListaAsociada = $scope.aIdsListas[oCampo.valor];
                                    oCampo.valor = oListaAsociada.nombre;
                                } else {
                                    oCampo.valor = null;
                                }

                                oCampo.tipo = 'texto';
                            }
                        });
                    });
                }
            });
        });

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "guardarEncuestaServidor", "datos": oDatosRegistrar },
            "success": function (response) {
                $("#ajax-loading").hide();
                
                if (response.bProceso) {
                    if (bCargaPendienteConexion) {
                        $scope.bdRequest = indexedDB.open('encuestas_pendientes', 1);

                        $scope.bdRequest.onsuccess = (event) => {
                            toastr.success("Encuesta sincronizada", "Información");
                            let db = event.target.result;
                            let txn = db.transaction("lista", "readwrite");
                            let store = txn.objectStore("lista");
                            let query = store.delete(iClave);

                            query.onsuccess = function (event) {
                                $scope.iEncuestasPendientesEnviar = $scope.iEncuestasPendientesEnviar - 1;
                                $scope.bEjecutarGuardadoLocal = false;
                                $scope.$evalAsync();
                            };

                            query.onerror = function (event) {
                                console.log("error");
                                console.log(event.target.errorCode);
                                $scope.bEjecutarGuardadoLocal = false;
                            }

                            txn.oncomplete = function () {
                                db.close();
                            };
                        };
                    } else {
                        toastr.success("Proceso realizado", "Información");
                        $scope.oEncuesta = angular.copy($scope.oCopiaEncuesta);
                        $scope.iniciarValores([]);
                        $("#encuesta_codigo_encuesta").focus();
                        $scope.bEjecutarGuardadoLocal = false;
                    }

                    $scope.$evalAsync();
                } else {
                    $("#ajax-loading").hide();
                    $scope.bEjecutarGuardadoLocal = false;

                    if (!bCargaPendienteConexion) {
                        toastr.error("Ocurrió un error en el envío de los datos", "Error");
                    }
                }
            },
            "error": function() {
                $("#ajax-loading").hide();
                $scope.bEjecutarGuardadoLocal = false;

                if (!bCargaPendienteConexion) {
                    toastr.error("Ocurrió un error en el envío de los datos", "Error");
                }
            }
        });
    }

    $scope.insertarBDLocal = function (db, encuesta) {
        let txn = db.transaction('lista', 'readwrite');
        let store = txn.objectStore('lista');
        let query = store.put(encuesta);
    
        query.onsuccess = function (event) {
            $scope.oEncuesta = angular.copy($scope.oCopiaEncuesta);
            $scope.iEncuestasPendientesEnviar = $scope.iEncuestasPendientesEnviar + 1;
            $scope.bEjecutarGuardadoLocal = false;
            $scope.iniciarValores([]);
            $("#encuesta_codigo_encuesta").focus();
            $("#ajax-loading").hide();
            
            toastr.warning("Información almacenada. Esperando conexión para enviar", "Información");
        };
    
        query.onerror = function (event) {
            $scope.bEjecutarGuardadoLocal = false;
            console.log(event.target.errorCode);
        }
    
        txn.oncomplete = function () {
            db.close();
        };
    }

    $scope.almacenarEncuestaLocal = function (oDatosRegistrar) {
        if (window.indexedDB) { 
            $scope.bdRequest = indexedDB.open('encuestas_pendientes', 1);

            $scope.bdRequest.onupgradeneeded = (event) => {
                let db = event.target.result;
           
                let store = db.createObjectStore('lista', {
                    autoIncrement: true
                });

                let store_actual = db.createObjectStore("actual", {
                    autoIncrement: true
                });
            };

            $scope.bdRequest.onsuccess = (event) => {
                let db = event.target.result;
                oDatosRegistrar.identificador = servicioGeneral.generarIdLetras(30);
                $scope.insertarBDLocal(db, oDatosRegistrar);
            };
        } else {
            $("#ajax-loading").hide();
            toastr.warning("Sin conexión", "Error");
            $scope.bEjecutarGuardadoLocal = false;
        }
    }

    $scope.almacenarEncuestaLocalOld = function (oDatosRegistrar) {
        let oListaEncuestas = angular.copy(JSON.parse(localStorage.getItem('encuestas')));
        oListaEncuestas.lista.push(oDatosRegistrar);
        localStorage.setItem('encuestas', JSON.stringify(oListaEncuestas));
        toastr.warning("Información almacenada. Esperando conexión para enviar", "Información");

        $scope.oEncuesta = angular.copy($scope.oCopiaEncuesta);
        localStorage.removeItem('encuesta_sesion');
        $scope.iniciarValores([]);

        $("#encuesta_codigo_encuesta").focus();
        $("#ajax-loading").hide();
    }

    $scope.encuestaSesion = function () {
        let oEncuestaGuardar = angular.copy($scope.oEncuesta);

        angular.forEach (oEncuestaGuardar.estructura, function (oRegistro, r) {
			angular.forEach (oRegistro.grupos, function (oGrupo, g){
                if (oGrupo.campos !== undefined) {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        let sValor = oCampo.valor !== undefined && oCampo.valor !== null ? (oCampo.valor.toString().trim().length > 0 ? oCampo.valor.toString().trim() : null) : null;

                        if (oCampo.valor === undefined) {
                            console.log(oCampo);
                            console.log(oRegistro.tabla);
                        }
    
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
                        } else if (oCampo.tipo === 'imagen' && oCampo.adjunto !== undefined) {
                            delete oCampo.adjunto;
                        }
    
                        oCampo.valor = sValor;
                    });
                }
            });
		});
		
		let iClave = 0;

        if (window.indexedDB) { 
            $scope.bdRequest = indexedDB.open('encuestas_pendientes', 1);

            $scope.bdRequest.onupgradeneeded = (event) => {
                let db = event.target.result;
            
                let store_lista = db.createObjectStore("lista", {
                    autoIncrement: true
                });

                let store_actual = db.createObjectStore("actual", {
                    autoIncrement: true
                });
            };
            
            $scope.bdRequest.onsuccess = (event) => {
                let db = event.target.result;
                let txn = db.transaction("actual", "readwrite");
                let objectStore = txn.objectStore("actual");
    
                objectStore.openCursor().onsuccess = (event) => {
                    let cursor = event.target.result;
    
                    if (cursor) {
                        iClave = cursor.key;
                        var res = cursor.update(oEncuestaGuardar);

                        res.onsuccess = function(e){
                            
                        }
                        res.onerror = function(e){
                            
                        }

                        cursor.continue();
                    }
                };
    
                txn.oncomplete = function () {
                    if (iClave === 0) {
                        let txn2 = db.transaction('actual', 'readwrite');
                        let store2 = txn2.objectStore('actual');
                        let query = store2.put(oEncuestaGuardar);

                        query.onsuccess = function (event) {
                            console.log("create success!!");
                        }

                        query.onerror = function (event) {
                            console.error("create failed!!");
                        }
                    }

                    db.close();
                }
            }
        }

        $scope.$evalAsync();
    }

    $scope.eliminarImagen = function (oRegistroImagen, oCampoImagenEliminar) {
        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.campos !== undefined) {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        if (oGrupo.tipo === 'imagen' && oCampo.bd === oCampoImagenEliminar.bd && oRegistro.tabla === oRegistroImagen.tabla) {
                            oCampo.valor = null;
                            oCampo.archivo = null;
                            oCampo.adjunto = null;

                            var output = document.getElementById('vpimagen_' + oRegistro.tabla + '_' + oCampoImagenEliminar.bd);
                            output.src = '';

                            $('#dvimagen_' + oRegistro.tabla + '_' + oCampoImagenEliminar.bd).hide();
                        }
                    });
                }
            });
        });
    }

    $scope.activarLocalizacion = function (sTablaSeleccionada) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (pos) {
                var crd = pos.coords;
                angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
                    if (sTablaSeleccionada === oRegistro.tabla) {
                        angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                            if (oGrupo.campos !== undefined) {
                                angular.forEach (oGrupo.campos, function (oCampo, c) {
                                    if (oCampo.bd === 'latitud') {
                                        oCampo.valor = crd.latitude;      
                                    } else if (oCampo.bd === 'longitud') {
                                        oCampo.valor = crd.longitude;
                                    } else if (oCampo.bd === 'precision') {
                                        oCampo.valor = crd.accuracy;
                                    } else if (oCampo.bd === 'altitud') {
                                        oCampo.valor = crd.altitude;
                                    }
                                });
                            }
                        });
                    }
                });
            }, function (err) {
                console.warn('ERROR(' + err.code + '): ' + err.message);
            }, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            });
        }
    }

    $scope.limpiarFormulario = function () {
        $scope.oEncuesta = angular.copy($scope.oCopiaEncuesta);
        $scope.iniciarValores([]);
    }

    $scope.ingresaDatos = function (sTablaSeleccionada, oCampoSeleccionado) {
        if (oCampoSeleccionado.bd === 'nombre_encuestado') {
            angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
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
            angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
                if (oRegistro.tabla === sTablaSeleccionada) {
                    angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                        if (oGrupo.campos !== undefined) {
                            angular.forEach (oGrupo.campos, function (oCampo, c) {
                                if (oCampo.bd === 'consumo') {
                                    oCampo.valor = null;

                                    if (servicioGeneral.isNumeric(oCampoSeleccionado.valor)) {
                                        oCampo.valor = parseFloat(oCampoSeleccionado.valor) * (120 / 86400);
                                    }
                                }
                            });
                        } else if (oGrupo.filas !== undefined) {
                            angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                                angular.forEach (aFilaCampos, function (oCampo, c) {
                                    if (oCampo.bd === 'domestico') {
                                        oCampo.valor = null;
    
                                        if (servicioGeneral.isNumeric(oCampoSeleccionado.valor)) {
                                            oCampo.valor = parseFloat(oCampoSeleccionado.valor) * (120 / 86400);
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

            angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
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

            angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
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
        
        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
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

        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
            if (oRegistro.tabla === 'uso_recurso_hidrico') {
                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                    if (oGrupo.filas !== undefined) {
                        angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                            angular.forEach (aFilaCampos, function (oCampo, c) {
                                if (oCampo.bd === sCampoColocarSuma) {
                                    oCampo.valor = fSuma;
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

        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
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

        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
            if (oRegistro.tabla === 'uso_recurso_hidrico') {
                angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                    if (oGrupo.filas !== undefined) {
                        angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                            angular.forEach (aFilaCampos, function (oCampo, c) {
                                if (oCampo.bd === 'total') {
                                    oCampo.valor = fTotal;
                                }
                            });
                        });
                    }
                });
            }
        });
    }

    $scope.ocultarMostrarModulo = function () {
        angular.forEach ($scope.oEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.campos !== undefined) {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        if (oRegistro.id_modulo === 1 && !$scope.oEncuesta.modulo1) {
                            oCampo.valor = null;
                        } else if (oRegistro.id_modulo === 2 && !$scope.oEncuesta.modulo2) {
                            oCampo.valor = null;
                        } else if (oRegistro.id_modulo === 3 && !$scope.oEncuesta.modulo3) {
                            oCampo.valor = null;
                        } else if (oRegistro.id_modulo === 4 && !$scope.oEncuesta.modulo4) {
                            oCampo.valor = null;
                        }  
                    });
                } else if (oGrupo.filas !== undefined) {
                    angular.forEach (oGrupo.filas, function (aFilaCampos, f) {
                        angular.forEach (aFilaCampos, function (oCampo, c) {
                            if (oRegistro.id_modulo === 1 && !$scope.oEncuesta.modulo1) {
                                oCampo.valor = null;
                            } else if (oRegistro.id_modulo === 2 && !$scope.oEncuesta.modulo2) {
                                oCampo.valor = null;
                            } else if (oRegistro.id_modulo === 3 && !$scope.oEncuesta.modulo3) {
                                oCampo.valor = null;
                            } else if (oRegistro.id_modulo === 4 && !$scope.oEncuesta.modulo4) {
                                oCampo.valor = null;
                            } 
                        });
                    });
                }
            });
        });

        if ($scope.oEncuesta.modulo1) {
            $(".modulo1").show();
        } else {
            $(".modulo1").hide();
        }

        if ($scope.oEncuesta.modulo2) {
            $(".modulo2").show();
        } else {
            $(".modulo2").hide();
        }

        if ($scope.oEncuesta.modulo3) {
            $(".modulo3").show();
        } else {
            $(".modulo3").hide();
        }

        if ($scope.oEncuesta.modulo4) {
            $(".modulo4").show();
        } else {
            $(".modulo4").hide();
        }
    }

    $scope.validarTipoConexion = function () {
        if (navigator !== undefined) {
            if (navigator.connection !== undefined) {
                if (navigator.connection.effectiveType !== undefined) {
                    return navigator.connection.effectiveType.toString().toLowerCase() === "4g";
                }
            }
        }

        return true;
    }

    $scope.oEncuesta = { "identificador": null, "modulo1": true, "modulo2": true, "modulo3": true, "modulo4": true, "estructura": [] };
    $scope.oCopiaEncuesta = angular.copy($scope.oEncuesta);
    $scope.aListas = [];
    $scope.aIdsListas = [];
    $scope.aValoresActualesSelect = [];
    $scope.bEjecutarGuardadoLocal = false;
    $scope.iEncuestasPendientesEnviar = 0;
    $scope.validarTipoConexion();

    $scope.bdRequest = null;
	
	let oIntervalGuardarSesion = setInterval(function() {
		if (!$scope.bEjecutarGuardadoLocal && $rootScope.oDatosSesion.sUrlSolicitud === 'registro') {
            $scope.encuestaSesion();
        }
    }, 4000);

    let oIntervalEnviarEncuestas = setInterval(function() {
        if (!$scope.bEjecutarGuardadoLocal && $rootScope.oDatosSesion.sUrlSolicitud === 'registro' && window.navigator.onLine) {
            $scope.enviarEncuestas();
        }
    }, 11000);

    $scope.estructuraEncuesta();
});
angular.module(APPNAME).controller('inicioController', function($scope, $rootScope, $location, configuracionGlobal, servicioGeneral) {
    $scope.seleccionaPagina = function (iPagina, bRecargarResultados) {
        var aMuestra = $scope.aRegistrosMuestra;
        iPagina = iPagina === 0 ? 1 : iPagina;

        if (bRecargarResultados || iPagina !== $scope.iPaginaActual) {
            aMuestra = [];
            var iRegistrosDesde = (iPagina - 1) * 10;
            var iRegistrosHasta = (iPagina * 10) - 1;
            $scope.iDesde = iRegistrosDesde + 1;
            $scope.iHasta = iRegistrosDesde;

            for(var i = iRegistrosDesde; i <= iRegistrosHasta; i++) {
                if (typeof $scope.aRegistrosTotales[i] !== 'undefined') {
                    let oRegistro = $scope.aRegistrosTotales[i];
                    aMuestra.push(oRegistro);
                    $scope.iHasta++;
                }
            }
        }

        $scope.aRegistrosMuestra = aMuestra;
        $scope.iPaginaActual = iPagina;

        if (!bRecargarResultados) {
            var rangoPaginas = [];
            for(var i = 1; i <= $scope.iTotalPaginas; i++) {
                var bMostrar = true;

                if (i < $scope.iPaginaActual && $scope.iRegistrosTotales > 11) {
                    var iCorteDistancia = $scope.iPaginaActual - 5;
                    bMostrar = i >= iCorteDistancia;
                } else if (i > $scope.iPaginaActual && $scope.iRegistrosTotales > 11) {
                    var iLimite = $scope.iPaginaActual + 5;
                    var iCorteDistancia = iLimite < 11 ? 11 : iLimite;
                    bMostrar = i <= iCorteDistancia;
                }

                rangoPaginas.push({ 'pagina': i, 'mostrar': bMostrar });
            }

            $scope.rangoP = rangoPaginas;
        }

        $("#ajax-loading").hide();
        $scope.$evalAsync();
    }

    $scope.paginaAnterior = function () {
        if ($scope.iPaginaActual > 1) {
            var iPaginaAnterior = $scope.iPaginaActual - 1;
            $scope.seleccionaPagina(iPaginaAnterior, false);
        }
    }

    $scope.paginaSiguiente = function () {
        if ($scope.iPaginaActual < $scope.iTotalPaginas) {
            var iPaginaSiguiente = $scope.iPaginaActual + 1;
            $scope.seleccionaPagina(iPaginaSiguiente, false);
        }
    }

    $scope.capturaFecha = function(sIdCampo) {
        let aPartesFecha = [];

        if ($('#' + sIdCampo).val().toString().length > 0) {
            if ($('#' + sIdCampo).val().toString().indexOf('/') !== -1) {
                aPartesFecha = $('#' + sIdCampo).val().toString().split('/');
            } else if ($('#' + sIdCampo).val().toString().indexOf('-') !== -1) {
                aPartesFecha = $('#' + sIdCampo).val().toString().split('-');
            }
        }
        
        if (aPartesFecha.length === 3) {
            if (aPartesFecha[0].toString().length === 4) {
                return aPartesFecha[0] + '-' + aPartesFecha[1] + '-' + aPartesFecha[2];
            } else if (aPartesFecha[2].toString().length === 4) {
                return aPartesFecha[2] + '-' + aPartesFecha[1] + '-' + aPartesFecha[0];
            }
        }

        return null;
    }

    $scope.catalogoEncuestas = function catalogoEncuestas() {
        $("#ajax-loading").show();

        if ($scope.oFiltros.codigo_encuesta !== null) {
            if ($scope.oFiltros.codigo_encuesta.toString().trim().length === 0) {
                $scope.oFiltros.codigo_encuesta = null;
            }
        }

        let oAplicarFiltros = angular.copy($scope.oFiltros);
        delete oAplicarFiltros.aNombreEncuestador;
        delete oAplicarFiltros.aCodigoCuencaHidrografica;

        //fechas
        oAplicarFiltros.fecha_diligenciamiento_desde = $scope.capturaFecha('filtro_fecha_diligenciamiento_desde');
        oAplicarFiltros.fecha_diligenciamiento_hasta = $scope.capturaFecha('filtro_fecha_diligenciamiento_hasta');
        let bFechasValidas = true;
        
        if (oAplicarFiltros.fecha_diligenciamiento_desde !== null && oAplicarFiltros.fecha_diligenciamiento_hasta !== null) {
            bFechasValidas = oAplicarFiltros.fecha_diligenciamiento_desde <= oAplicarFiltros.fecha_diligenciamiento_hasta;
        }
        
        if (bFechasValidas) {
            servicioGeneral.enviarAjax({ 
                "url": "ControlEncuestas.php", 
                "data": oAplicarFiltros,
                "success": function (response) {
                    $scope.aRegistrosTotales = response.catalogo;
    
                    if ( $.fn.DataTable.isDataTable('#tb_encuestas') ) {
                        $('#tb_encuestas').DataTable().destroy();
                    }
    
                    $('#tb_encuestas').DataTable( {
                        "data": $scope.aRegistrosTotales,
                        "columns": [
                            { "data": "codigo_encuesta" },
                            { "data": "codigo_cuenca_hidrografica" },
                            { "data": "fecha_diligenciamiento" },
                            { "data": "estado_validacion" },
                            { 
                                "data": function (r) {
                                    let sAcciones = $rootScope.oDatosSesion.editar_encuesta ? '<a href="#!/edicion/' + r.id_edicion + '"><i class="fas fa-edit"></i></a>&nbsp;&nbsp;' : '';
                                    sAcciones += '<a class="ver_encuesta" id="' + r.id + '"><i class="fas fa-clipboard"></i></a>';
                                    return sAcciones;
                                }
                            }
                        ],
                        "language": {
                            "processing": "Procesando...",
                            "lengthMenu": "Mostrar _MENU_ registros",
                            "zeroRecords": "No se encontraron resultados",
                            "emptyTable": "Ningún dato disponible en esta tabla",
                            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
                            "search": "Buscar:",
                            "infoThousands": ",",
                            "loadingRecords": "Cargando...",
                            "info": "Mostrando _PAGE_ de _PAGES_ páginas",
                            "paginate": {
                                "first": "Primero",
                                "last": "Último",
                                "next": "Siguiente",
                                "previous": "Anterior"
                            }
                        },
                        "order": [[2, 'desc']]
                    });
    
                    $(document).off("click", ".ver_encuesta");
    
                    $(document).on("click", ".ver_encuesta", function () {
                        id = parseInt($(this).attr('id'));
                        $scope.cargarInformacionEncuesta(id);
                    });
    
                    $("#ajax-loading").hide();
    
                    $scope.activarFiltros();
    
                    /*$scope.iRegistrosTotales = $scope.aRegistrosTotales.length;
                    $scope.iTotalPaginas = $scope.iRegistrosTotales % 10 === 0 ? ($scope.iRegistrosTotales / 10) : parseInt(($scope.iRegistrosTotales / 10)) + 1;
                    $scope.iPaginaActual = $scope.iPaginaActual > $scope.iTotalPaginas ? $scope.iTotalPaginas : $scope.iPaginaActual;
    
                    var rangoPaginas = [];
                    for(var i = 1; i <= $scope.iTotalPaginas; i++) {
                        var bMostrar = true;
    
                        if (i < $scope.iPaginaActual && $scope.iRegistrosTotales > 11) {
                            var iCorteDistancia = $scope.iPaginaActual - 5;
                            bMostrar = i >= iCorteDistancia;
                        } else if (i > $scope.iPaginaActual && $scope.iRegistrosTotales > 11) {
                            var iLimite = $scope.iPaginaActual + 5;
                            var iCorteDistancia = iLimite < 11 ? 11 : iLimite;
                            bMostrar = i <= iCorteDistancia;
                        }
    
                        rangoPaginas.push({ 'pagina': i, 'mostrar': bMostrar });
                    }
                    $scope.rangoP = rangoPaginas;
                    $scope.seleccionaPagina($scope.iPaginaActual, true);*/
                }
            }); 
        } else {
            $("#ajax-loading").hide();
            toastr.error("Rango de fechas no válido", "Error");
        }
    }
    
    $scope.activarFiltros = function () {
        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "filtrosEncuestas" },
            "success": function (response) {
                $scope.oFiltros.aNombreEncuestador = response.nombre_encuestador;
                $scope.oFiltros.aCodigoCuencaHidrografica = response.codigo_cuenca_hidrografica;
                $scope.oFiltros.aIngenieroCampo = response.ingeniero_campo;

                servicioGeneral.selectize('filtro_nombre_encuestador', {
                    items: [$scope.oFiltros.nombre_encuestador],
                    options: $scope.oFiltros.aNombreEncuestador,
                    onChange: function (value) {
                        $scope.oFiltros.nombre_encuestador = value;
                        $scope.catalogoEncuestas();
                    }
                });

                servicioGeneral.selectize('filtro_codigo_cuenca_hidrografica', {
                    items: [$scope.oFiltros.codigo_cuenca_hidrografica],
                    options: $scope.oFiltros.aCodigoCuencaHidrografica,
                    onChange: function (value) {
                        $scope.oFiltros.codigo_cuenca_hidrografica = value;
                        $scope.catalogoEncuestas();
                    }
                });

                servicioGeneral.selectize('filtro_ingeniero_campo', {
                    items: [$scope.oFiltros.ingeniero_campo],
                    options: $scope.oFiltros.aIngenieroCampo,
                    onChange: function (value) {
                        $scope.oFiltros.ingeniero_campo = value;
                        $scope.catalogoEncuestas();
                    }
                });

                $("#ajax-loading").hide();
            }
        }); 
    }

    $scope.cargarInformacionEncuesta = function (id) {
        $("#ajax-loading").show();
        $scope.oInformacionEncuesta = { "id": null, "estructura": [] };

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "informacionEncuesta", "accion": "ver", "id": id, "listas" : true },
            "success": function (response) {
                $scope.oInformacionEncuesta = response;

                angular.forEach ($scope.oInformacionEncuesta.estructura, function (oRegistro, r) {
                    angular.forEach (oRegistro.grupos, function (oGrupo, g){
                        if (oGrupo.tipo === 'mapa' && oGrupo.latitud !== null && oGrupo.longitud !== null) {
                            setTimeout(function () {
                                $scope.crearMapa(oGrupo.contenedor, oGrupo.latitud, oGrupo.longitud);
                            }, 500);
                        }
                    });
                });

                console.log($scope.oInformacionEncuesta.id);
                console.log($scope.bExportar);

                $("#ajax-loading").hide();
            }
        }); 
    }

    $scope.ocultarEncuesta = function () {
        $scope.oInformacionEncuesta = { "id": null, "estructura": [] };
        $scope.bExportar = false;
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

    $scope.exportarEncuestas = function () {
        $scope.bExportar = true;
        $scope.oExportarEncuesta = { };

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "cargarEstructuraEncuesta" },
            "success": function (response) {
                $scope.oExportarEncuesta = response;
                $("#ajax-loading").hide();
            }
        });
    }

    $scope.seleccionaTabla = function (oTabla) {
        setTimeout(function () {
            $scope.$evalAsync();
            
            angular.forEach (oTabla.grupos, function (oGrupo, g) {
                angular.forEach (oGrupo.campos, function (oCampo, c) {
                    oCampo.seleccionado = oTabla.seleccionado;       
                });        
            });

            $scope.$evalAsync();
        }, 100);        
    }

    $scope.generarCSVEncuestas = function () {
        let aCamposSeleccionados = [];
        let aIDEncuestas = [];

        angular.forEach ($scope.oExportarEncuesta.estructura, function (oRegistro, r) {
            angular.forEach (oRegistro.grupos, function (oGrupo, g) {
                if (oGrupo.tipo === 'informacion' || oGrupo.tipo === 'mapa') {
                    angular.forEach (oGrupo.campos, function (oCampo, c) {
                        if (oCampo.seleccionado) {
                            aCamposSeleccionados.push({ "tabla" : oRegistro.tabla, "campo": oCampo.bd });
                        }
                    });
                } else if (oGrupo.tipo === 'tabla') {
                    angular.forEach (oGrupo.filas, function (aCampos, p) {
                        angular.forEach (aCampos, function (oCampo, c) {
                            if (oCampo.seleccionado) {
                                aCamposSeleccionados.push({ "tabla" : oRegistro.tabla, "campo": oCampo.bd });
                            }
                        });
                    });
                }
            });
        });

        if (aCamposSeleccionados.length > 0) {
            $("#ajax-loading").show();
            angular.forEach ($scope.aRegistrosTotales, function (oEncuesta, e) {
                aIDEncuestas.push(oEncuesta.id);
            });

            servicioGeneral.enviarAjax({ 
                "url": "ControlEncuestas.php", 
                "data": { "method": "obtenerDatosEncuestasCSV", "campos": aCamposSeleccionados, "ids": aIDEncuestas },
                "success": function (response) {
                    var link = document.createElement("a");
                    link.setAttribute('href', response.url);
                    link.setAttribute('target', '_blank');
                    link.click();
                    $("#ajax-loading").hide();
                }
            });
        }
    }

    $scope.limpiarFiltros = function () {
        $scope.oFiltros.codigo_encuesta = null;
        $scope.oFiltros.nombre_encuestador = null;
        $scope.oFiltros.codigo_cuenca_hidrografica = null;
        $scope.oFiltros.ingeniero_campo = null;
        $scope.oFiltros.fecha_diligenciamiento_desde = null;
        $scope.oFiltros.fecha_diligenciamiento_hasta = null;
        $('#filtro_fecha_diligenciamiento_desde').val("");
        $('#filtro_fecha_diligenciamiento_hasta').val("");
        $scope.catalogoEncuestas();
    }

    $scope.aRegistrosTotales = [];
    $scope.aRegistrosMuestra = [];
    $scope.oExportarEncuesta = { };
    $scope.bExportar = false;
    $scope.iTotalEncuestasVista = 0;

    $scope.rangoP = [];
    $scope.iRegistrosTotales = 0;
    $scope.iTotalPaginas = 0;
    $scope.iPaginaActual = 1;
    $scope.iDesde = 0;
    $scope.iHasta = 0;

    $scope.oInformacionEncuesta = { "id": null, "estructura": [] };

    $scope.oFiltros = { 
        "method": "catalogoEncuestas",
        "codigo_encuesta" : null,
        "aNombreEncuestador": [],
        "aCodigoCuencaHidrografica": [],
        "nombre_encuestador": null,
        "codigo_cuenca_hidrografica": null,
        "ingeniero_campo": null,
        "fecha_diligenciamiento_desde": null,
        "fecha_diligenciamiento_hasta": null
    };

    $('#filtro_nombre_encuestador').selectize();
    $('#filtro_codigo_cuenca_hidrografica').selectize();
    $('#filtro_ingeniero_campo').selectize();

    $scope.catalogoEncuestas();
});
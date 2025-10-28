angular.module(APPNAME).controller('inicioController', function($scope, $rootScope, $location, configuracionGlobal, servicioGeneral) {
    $scope.catalogoEncuestas = function () {
        $scope.bConsultandoEncuestas = true;
        $("#ajax-loading").show();

        if ($scope.oFiltros.codigo_encuesta !== null) {
            if ($scope.oFiltros.codigo_encuesta.toString().trim().length === 0) {
                $scope.oFiltros.codigo_encuesta = null;
            }
        }

        let oAplicarFiltros = angular.copy($scope.oFiltros);
        delete oAplicarFiltros.aNombreEncuestador;
        delete oAplicarFiltros.aCodigoCuencaHidrografica;
        
        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": oAplicarFiltros,
            "success": function (response) {
                $scope.aRegistrosTotales = response.catalogo;
                $scope.actualizarCatalogo();
            }
        }); 
    }

    $scope.actualizarCatalogo = function () {
        let iCantidadActual = $scope.aRegistrosMuestra.length;
        let iCantidadCargar = iCantidadActual + 10;
        let iContador = 0;
        let aEncuestas = [];

        angular.forEach ($scope.aRegistrosTotales, function (oEncuesta, e) {
            if (iContador < iCantidadCargar) {
                aEncuestas.push(oEncuesta);
            }

            iContador++;
        });

        $scope.aRegistrosMuestra = aEncuestas;

        if ($scope.aRegistrosTotales.length !== $scope.iTotalEncuestasVista) {
            $scope.iTotalEncuestasVista = $scope.aRegistrosTotales.length;
            $scope.activarFiltros();
        } else {
            $scope.bConsultandoEncuestas = false;
            $("#ajax-loading").hide();
        }
    }

    $scope.activarFiltros = function () {
        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "filtrosEncuestas" },
            "success": function (response) {
                $scope.oFiltros.aNombreEncuestador = response.nombre_encuestador;
                $scope.oFiltros.aCodigoCuencaHidrografica = response.codigo_cuenca_hidrografica;

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

                $scope.bConsultandoEncuestas = false;
                $("#ajax-loading").hide();
            }
        }); 
    }

    $scope.editarInformacionEncuesta = function (id) {
        $("#ajax-loading").show();
        $scope.oEditarEncuesta = { "id": null, "estructura": [] };

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "informacionEncuesta", "id": id },
            "success": function (response) {
                $scope.oEditarEncuesta = response;

                angular.forEach ($scope.oEditarEncuesta.estructura, function (oRegistro, r) {
                    angular.forEach (oRegistro.grupos, function (oGrupo, g){
                        if (oGrupo.tipo === 'mapa' && oGrupo.latitud !== null && oGrupo.longitud !== null) {
                            setTimeout(function () {
                                $scope.crearMapa(oGrupo.contenedor, oGrupo.latitud, oGrupo.longitud);
                            }, 500);
                        }
                    });
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
            "data": { "method": "informacionEncuesta", "id": id },
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

                $("#ajax-loading").hide();
            }
        }); 
    }

    $scope.guardarCambiosEncuesta = function () {
        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "guardarCambiosEncuesta", "datos": $scope.oEditarEncuesta },
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

    $scope.ocultarEncuesta = function () {
        $scope.oInformacionEncuesta = { "id": null, "estructura": [] };
        $scope.oEditarEncuesta = { "id": null, "estructura": [] };
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
                angular.forEach (oGrupo.campos, function (oCampo, c) {
                    if (oCampo.seleccionado) {
                        aCamposSeleccionados.push({ "tabla" : oRegistro.tabla, "campo": oCampo.bd });
                    }
                });
            });
        });

        if (aCamposSeleccionados.length > 0) {
            angular.forEach ($scope.aRegistrosTotales, function (oEncuesta, e) {
                aIDEncuestas.push(oEncuesta.id);
            });

            servicioGeneral.enviarAjax({ 
                "url": "ControlEncuestas.php", 
                "data": { "method": "obtenerDatosEncuestasCSV", "campos": aCamposSeleccionados, "ids": aIDEncuestas },
                "success": function (response) {
                    servicioGeneral.generarCSV(response.estructura, 'encuestas');
                    $("#ajax-loading").hide();
                }
            });
        }
    }

    $scope.aRegistrosTotales = [];
    $scope.aRegistrosMuestra = [];
    $scope.oExportarEncuesta = { };
    $scope.bExportar = false;
    $scope.bConsultandoEncuestas = false;
    $scope.iTotalEncuestasVista = 0;

    $scope.oInformacionEncuesta = { "id": null, "estructura": [] };
    $scope.oEditarEncuesta = { "id": null, "estructura": [] };

    $scope.oFiltros = { 
        "method": "catalogoEncuestas",
        "codigo_encuesta" : null,
        "aNombreEncuestador": [],
        "aCodigoCuencaHidrografica": [],
        "nombre_encuestador": null,
        "codigo_cuenca_hidrografica": null
    };

    $('#filtro_nombre_encuestador').selectize();
    $('#filtro_codigo_cuenca_hidrografica').selectize();

    window.onscroll = function(ev) {
        console.log(window.innerHeight);
        console.log(window.scrollY);
        console.log($('#wrapper').height());
        console.log($scope.bConsultandoEncuestas);
        console.log('-----');

        if ((window.innerHeight + window.scrollY) >= $('#wrapper').height() && $scope.oInformacionEncuesta.id === null && $scope.oEditarEncuesta.id === null && !$scope.bExportar && !$scope.bConsultandoEncuestas) {
            $scope.catalogoEncuestas();
        }
    };

    $scope.catalogoEncuestas();
});
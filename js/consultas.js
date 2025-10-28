angular.module(APPNAME).controller('consultasController', function($scope, $rootScope, $location, configuracionGlobal, servicioGeneral) {
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

    $scope.catalogoEncuestas = function catalogoEncuestas() {
        let oAplicarFiltros = {
            'method': 'catalogoEncuestasConsultas',
            'filtros': [],
            'modulos': []
        };

        angular.forEach($scope.aFiltrosBasico, (oFiltro, f) => {
            if (oFiltro.seleccionados.length > 0) {
                const oFiltroC = angular.copy(oFiltro);
                delete oFiltroC.elementos;
                delete oFiltroC.tipo;
                delete oFiltroC.titulo;
                oAplicarFiltros.filtros.push(oFiltroC);
            }
        });

        if ($scope.oFiltros.captaciones) {
            oAplicarFiltros.modulos.push(1);

            angular.forEach($scope.aFiltrosCaptaciones, (oFiltro, f) => {
                if (oFiltro.seleccionados.length > 0) {
                    const oFiltroC = angular.copy(oFiltro);
                    delete oFiltroC.elementos;
                    delete oFiltroC.tipo;
                    delete oFiltroC.titulo;
                    oAplicarFiltros.filtros.push(oFiltroC);
                }
            });
        }

        if ($scope.oFiltros.vertimiento) {
            oAplicarFiltros.modulos.push(2);

            angular.forEach($scope.aFiltrosVertimiento, (oFiltro, f) => {
                if (oFiltro.seleccionados.length > 0) {
                    const oFiltroC = angular.copy(oFiltro);
                    delete oFiltroC.elementos;
                    delete oFiltroC.tipo;
                    delete oFiltroC.titulo;
                    oAplicarFiltros.filtros.push(oFiltroC);
                }
            });
        }

        if ($scope.oFiltros.ocupacion) {
            oAplicarFiltros.modulos.push(3);

            angular.forEach($scope.aFiltrosOcupacion, (oFiltro, f) => {
                if (oFiltro.seleccionados.length > 0) {
                    const oFiltroC = angular.copy(oFiltro);
                    delete oFiltroC.elementos;
                    delete oFiltroC.tipo;
                    delete oFiltroC.titulo;
                    oAplicarFiltros.filtros.push(oFiltroC);
                }
            });
        }

        if ($scope.oFiltros.minera) {
            oAplicarFiltros.modulos.push(4);

            angular.forEach($scope.aFiltrosMinera, (oFiltro, f) => {
                if (oFiltro.seleccionados.length > 0) {
                    const oFiltroC = angular.copy(oFiltro);
                    delete oFiltroC.elementos;
                    delete oFiltroC.tipo;
                    delete oFiltroC.titulo;
                    oAplicarFiltros.filtros.push(oFiltroC);
                } 
            });
        }

        $('#tb_encuestas').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "order": [[0, "asc"]],
            "searching": false,
            "ajax": {
                "url": "php/ControlEncuestas.php",
                "type": "POST",
                "beforeSend" : function () {

                },
                "data": oAplicarFiltros,
                "error": function (error) {
                    console.log(error);
                },
                "dataSrc": function (json) {
                    return json.data;
                }
            },
            "language": {
                "sProcessing":     "<i class='fa fa-spinner fa-spin fa-3x fa-fw'></i><span class='sr-only'>Cargando...</span>",
                "sLengthMenu":     "Mostrar _MENU_ registros",
                "sZeroRecords":    "No se encontraron resultados",
                "sEmptyTable":     "Ningún dato disponible en esta tabla",
                "sInfo":           "Registros _START_ al _END_ de un total de _TOTAL_",
                "sInfoEmpty":      "Registros 0 al 0 de un total de 0",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix":    "",
                "sSearch":         "Buscar:",
                "sUrl":            "",
                "sInfoThousands":  ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst":    "Primero",
                    "sLast":     "Último",
                    "sNext":     "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                },
                "buttons": {
                    "copy": "Copiar",
                    "colvis": "Visibilidad",
                    "pageLength": {
                        _: "Mostrar elementos",
                        "-1": "Mostrar todos"
                    },
                    "colvis": "Columnas visibles"
                }
            },
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
                    },
                    "ordering": false
                }
            ]
        });

        $(document).off("click", ".ver_encuesta");

        $(document).on("click", ".ver_encuesta", function () {
            $scope.idEncuestaSeleccionada = parseInt($(this).attr('id'));
            $scope.cargarInformacionEncuesta($scope.idEncuestaSeleccionada);
        });
    }

    $scope.cargarConteoEncuestas = function () {
        $("#ajax-loading").show();

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "conteoEncuestasSesion" },
            "success": function (response) {
                $scope.activarFiltros("filtrosEncuestasDatosBasicos");
            }
        }); 
    }
    
    $scope.activarFiltros = function (tipoFiltro) {
        $("#ajax-loading").show();

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": tipoFiltro },
            "success": function (response) {
                if (tipoFiltro === 'filtrosEncuestasDatosBasicos') {
                    $scope.aFiltrosBasico = response;
                } else if (tipoFiltro === 'filtrosEncuestasCaptaciones') {
                    $scope.aFiltrosCaptaciones = response;
                } else if (tipoFiltro === 'filtrosEncuestasVertimiento') {
                    $scope.aFiltrosVertimiento = response;
                } else if (tipoFiltro === 'filtrosEncuestasOcupacion') {
                    $scope.aFiltrosOcupacion = response;
                } else if (tipoFiltro === 'filtrosEncuestasMinera') {
                    $scope.aFiltrosMinera = response;
                }

                angular.forEach(response, (oFiltro, f) => {
                    if (oFiltro.tipo === 'select') {
                        servicioGeneral.selectize(oFiltro.id, {
                            options: oFiltro.elementos,
                            maxItems: null,
                            valueField: 'id',
                            labelField: 'descripcion',
                            searchField: 'descripcion',
                            create: false,
                            onChange: function (value) {
                                oFiltro.seleccionados = value;
                            }
                        });
                    }
                });                

                $("#ajax-loading").hide();
                $scope.catalogoEncuestas();
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

                $("#ajax-loading").hide();
            }
        }); 
    }

    $scope.ocultarEncuesta = function () {
        $scope.oInformacionEncuesta = { "id": null, "estructura": [] };
        $scope.bExportar = false;
    }

    $scope.crearMapa = function (sContenedor, fLatitud, fLongitud) {
        /*let mapa = L.map(sContenedor, { 'zoomControl': true }).setView([fLatitud, fLongitud], 10);
        L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoiYWl0ZXNvbCIsImEiOiJja3lscjYwdzIycGw1Mnhsa3o0ZGJxMWo4In0.HLgzGp72KMRynenp1el9Fg', {
            maxZoom: 18,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, ' +
                '<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            id: 'mapbox/satellite-v9',
            tileSize: 512,
            zoomOffset: -1
        }).addTo(mapa);

        L.marker([fLatitud, fLongitud], { }).addTo(mapa);*/

        let map = new L.Map(sContenedor);
        let osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        let osmAttrib='Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
        let osm = new L.TileLayer(osmUrl, {minZoom: 2, maxZoom: 19, attribution: osmAttrib});
        map.setView(new L.LatLng(fLatitud, fLongitud),15);
        map.addLayer(osm);
    
        let marker = L.marker([fLatitud, fLongitud]).addTo(map);
        //marker.bindPopup("<b>Botanischer Garten</b><br>is here").openPopup();
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

    $scope.limpiarFiltros = function (tipo) {
        let aFiltrosLimpiar = [];

        if (tipo === 'basico') {
            aFiltrosLimpiar = $scope.aFiltrosBasico;
        } else if (tipo === 'captaciones') {
            aFiltrosLimpiar = $scope.aFiltrosCaptaciones;
        } else if (tipo === 'vertimiento') {
            aFiltrosLimpiar = $scope.aFiltrosVertimiento;
        } else if (tipo === 'ocupacion') {
            aFiltrosLimpiar = $scope.aFiltrosOcupacion;
        } else if (tipo === 'minera') {
            aFiltrosLimpiar = $scope.aFiltrosMinera;
        }

        angular.forEach(aFiltrosLimpiar, (oFiltro, f) => {
            oFiltro.seleccionados = [];

            if (oFiltro.tipo === 'select') {
                servicioGeneral.selectize(oFiltro.id, {
                    options: oFiltro.elementos,
                    maxItems: null,
                    valueField: 'id',
                    labelField: 'descripcion',
                    searchField: 'descripcion',
                    create: false,
                    onChange: function (value) {
                        oFiltro.seleccionados = value;
                        //$scope.catalogoEncuestas();
                    }
                });
            }
        });

        $scope.$evalAsync();
        $scope.catalogoEncuestas();
    }

    $scope.filtroCaptaciones = function () {
        $scope.oFiltros.captaciones = !$scope.oFiltros.captaciones;

        $scope.ocultarOpcionesFiltros();

        if ($scope.oFiltros.captaciones) {
            $scope.mostrarFiltros.captaciones = true;
        } else {
            $scope.mostrarFiltros.basico = true;
        }

        if ($scope.oFiltros.captaciones && $scope.aFiltrosCaptaciones.length === 0) {
            $scope.activarFiltros("filtrosEncuestasCaptaciones");
        } else {
            $scope.catalogoEncuestas();
        }    
    }

    $scope.filtroVertimiento = function () {
        $scope.oFiltros.vertimiento = !$scope.oFiltros.vertimiento;

        $scope.ocultarOpcionesFiltros();

        if ($scope.oFiltros.vertimiento) {
            $scope.mostrarFiltros.vertimiento = true;
        } else {
            $scope.mostrarFiltros.basico = true;
        }

        if ($scope.oFiltros.vertimiento && $scope.aFiltrosVertimiento.length === 0) {
            $scope.activarFiltros("filtrosEncuestasVertimiento");
        } else {
            $scope.catalogoEncuestas();
        }
    }

    $scope.filtroOcupacion = function () {
        $scope.oFiltros.ocupacion = !$scope.oFiltros.ocupacion;

        $scope.ocultarOpcionesFiltros();

        if ($scope.oFiltros.ocupacion) {
            $scope.mostrarFiltros.ocupacion = true;
        } else {
            $scope.mostrarFiltros.basico = true;
        }

        if ($scope.oFiltros.ocupacion && $scope.aFiltrosOcupacion.length === 0) {
            $scope.activarFiltros("filtrosEncuestasOcupacion");
        } else {
            $scope.catalogoEncuestas();
        }
    }

    $scope.filtroMinera = function () {
        $scope.oFiltros.minera = !$scope.oFiltros.minera;

        $scope.ocultarOpcionesFiltros();

        if ($scope.oFiltros.minera) {
            $scope.mostrarFiltros.minera = true;
        } else {
            $scope.mostrarFiltros.basico = true;
        }

        if ($scope.oFiltros.minera && $scope.aFiltrosMinera.length === 0) {
            $scope.activarFiltros("filtrosEncuestasMinera");
        } else {
            $scope.catalogoEncuestas();
        }
    }

    $scope.mostrarOcultarFiltro = function (tipo) {
        if (tipo === 'basico') {
            $scope.mostrarFiltros.basico = !$scope.mostrarFiltros.basico;
            $scope.mostrarFiltros.captaciones = false;
            $scope.mostrarFiltros.vertimiento = false;
            $scope.mostrarFiltros.ocupacion = false;
            $scope.mostrarFiltros.minera = false;
        } else if (tipo === 'captaciones') {
            $scope.mostrarFiltros.captaciones = !$scope.mostrarFiltros.captaciones;
            $scope.mostrarFiltros.basico = false;
            $scope.mostrarFiltros.vertimiento = false;
            $scope.mostrarFiltros.ocupacion = false;
            $scope.mostrarFiltros.minera = false;
        } else if (tipo === 'vertimiento') {
            $scope.mostrarFiltros.vertimiento = !$scope.mostrarFiltros.vertimiento;
            $scope.mostrarFiltros.captaciones = false;
            $scope.mostrarFiltros.basico = false;
            $scope.mostrarFiltros.ocupacion = false;
            $scope.mostrarFiltros.minera = false;
        } else if (tipo === 'ocupacion') {
            $scope.mostrarFiltros.ocupacion = !$scope.mostrarFiltros.ocupacion;
            $scope.mostrarFiltros.captaciones = false;
            $scope.mostrarFiltros.vertimiento = false;
            $scope.mostrarFiltros.basico = false;
            $scope.mostrarFiltros.minera = false;
        } else if (tipo === 'minera') {
            $scope.mostrarFiltros.minera = !$scope.mostrarFiltros.minera;
            $scope.mostrarFiltros.captaciones = false;
            $scope.mostrarFiltros.vertimiento = false;
            $scope.mostrarFiltros.ocupacion = false;
            $scope.mostrarFiltros.basico = false;
        }
    }

    $scope.ocultarOpcionesFiltros = function () {
        $scope.mostrarFiltros = {
            "basico": false,
            "captaciones": false,
            "vertimiento": false,
            "ocupacion": false,
            "minera": false,
        }
    }

    $scope.exportarPDF = function () {
        $("#ajax-loading").show();

        servicioGeneral.enviarAjax({ 
            "url": "ControlEncuestas.php", 
            "data": { "method": "generarPDF", "encuesta": $scope.oInformacionEncuesta },
            "success": function (response) {
                $("#ajax-loading").hide();
                window.open(response.file);        
            }
        });
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

    $scope.aFiltrosBasico = [];
    $scope.aFiltrosCaptaciones = [];
    $scope.aFiltrosVertimiento = [];
    $scope.aFiltrosOcupacion = [];
    $scope.aFiltrosMinera = [];

    $scope.oFiltros = { 
        "method": "catalogoEncuestas",
        "captaciones": false,
        "vertimiento": false,
        "ocupacion": false,
        "minera": false,
    };

    $scope.mostrarFiltros = {
        "basico": true,
        "captaciones": false,
        "vertimiento": false,
        "ocupacion": false,
        "minera": false,
    }

    $scope.cargarConteoEncuestas();   

    //$scope.catalogoEncuestas();
});
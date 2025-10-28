angular.module(APPNAME).controller('cuencaController', function($scope, $location, $http, configuracionGlobal, servicioGeneral) {
    $("#ajax-loading").show();

    $scope.loadingCounter = 0;
    $scope.totalLoadingSteps = 3;

    $scope.resumen = {
    ENCUESTAS: 0, USUARIOS: 0, USOS: 0, UNHI: 0, MUNICIPIOS: 0,
    CAPTACIONES: 0, VERTIMIENTOS: 0, OCUPACIONES: 0, MINERIA: 0, TOTAL: 0
    };

    $scope.updateLoading = function() {
        $scope.loadingCounter++;
        if ($scope.loadingCounter >= $scope.totalLoadingSteps) {
            setTimeout(function() {
                $("#ajax-loading").hide();
                document.getElementById('reporte').classList.add('loaded');

                if ($scope.map1 && typeof $scope.map1.invalidateSize === 'function') {
                    $scope.map1.invalidateSize();
                }
            }, 500);
        }
    };

    $scope.textosGenerales = {};
    $scope.textoActual = {
        titulo: "Cargando información...",
        descripcion: "Por favor espere mientras cargamos los datos de la cuenca."
    };

$scope.cargarResumenEncuestas = function () {
  servicioGeneral.enviarAjax({
    url: "ControlEncuestas.php",
    data: { method: "resumenEncuestas" },
    success: function (response) {
      try {
        $scope.$applyAsync(function () {
          $scope.resumen = {
            ENCUESTAS: +response.ENCUESTAS || 0,
            USUARIOS: +response.USUARIOS || 0,
            USOS: +response.USOS || 0,
            UNHI: +response.UNHI || 0,
            MUNICIPIOS: +response.MUNICIPIOS || 0,
            CAPTACIONES: +response.CAPTACIONES || 0,
            VERTIMIENTOS: +response.VERTIMIENTOS || 0,
            OCUPACIONES: +response.OCUPACIONES || 0,
            MINERIA: +response.MINERIA || 0,
            TOTAL: +response.TOTAL || 0
          };
        });
      } catch (e) {
        console.error('Error procesando resumenEncuestas:', e);
      }
      $scope.updateLoading();
    },
    error: function () {
      $scope.updateLoading();
    }
  });
};

    $scope.cargarTextosGenerales = function() {
        try {
            $scope.textosGenerales = textosCuencas.cuencas;
        } catch(error) {
            console.error('Error cargando textos:', error);
        }
    };

    $scope.actualizarTextoGeneral = function(idSubZona) {
        if ($scope.textosGenerales[idSubZona]) {
            $scope.textoActual = $scope.textosGenerales[idSubZona];
        } else {
            $scope.textoActual = {
                titulo: "Información General del Censo de Usuarios del Recurso Hídrico",
                descripcion: "<p>Información no disponible para esta cuenca.</p>"
            };
        }
    };

    $scope.aplicarFiltroCuencaSeleccionada = function() {
        try {
            if (typeof(Storage) !== "undefined") {
                var cuencaData = sessionStorage.getItem('cuencaSeleccionada');
                if (cuencaData) {
                    var parsedData = JSON.parse(cuencaData);
                    if (parsedData.aplicarFiltro && parsedData.cuenca) {
                        var mapaCuencasASubZona = {
                            'Río Bogotá': 901,
                            'Río Garagoa': 902,
                            'Río Guavio': 900,
                            'Río Ouayuriba': 899,
                            'Río Negro': 905,
                            'Río Seco y Otros Directos': 903,
                            'Río Suarez': 904,
                            'Río Sumapaz': 898,
                            'Río Carare Minero': 906
                        };
                        var subZonaId = mapaCuencasASubZona[parsedData.cuenca.nombre];
                        if (subZonaId) {
                            setTimeout(function() {
                                if ($scope.aSubZonaHidrografica && Array.isArray($scope.aSubZonaHidrografica)) {
                                    var subZonaEncontrada = $scope.aSubZonaHidrografica.find(sz => sz.id === subZonaId);
                                    if (subZonaEncontrada) {
                                        $scope.seleccionaSubZona(subZonaEncontrada);
                                    }
                                }
                            }, 1000);
                        }
                        parsedData.aplicarFiltro = false;
                        sessionStorage.setItem('cuencaSeleccionada', JSON.stringify(parsedData));
                    }
                }
            }
        } catch (error) {
            console.error('Error aplicando filtro automático:', error);
        }
    };

    $scope.filtroSubZonaHidrografica = () => {
        servicioGeneral.enviarAjax({
            "url": "ControlEncuestas.php",
            "data": { "method": "subLista", "idLista": 45 },
            "success": function(response) {
                $scope.aSubZonaHidrografica = response;
                $scope.updateLoading();
                
                var cuencaData = sessionStorage.getItem('cuencaSeleccionada');
                if (cuencaData && JSON.parse(cuencaData).aplicarFiltro) {
                    $scope.aplicarFiltroCuencaSeleccionada();
                } else {
                    const oSubZonaDefault = $scope.aSubZonaHidrografica.find(x => x.id === 901);
                    if(oSubZonaDefault) $scope.seleccionaSubZona(oSubZonaDefault);
                }
            },
            "error": function() { $scope.updateLoading(); }
        });
    };

    $scope.seleccionaSubZona = (oSubZona) => {
        $scope.oFiltro.idSubZona = oSubZona.id;
        $scope.oFiltro.descripcion = oSubZona.descripcion;

        $scope.actualizarTextoGeneral(oSubZona.id.toString());
        
        if ($scope.aClusterMarkers) {
             $scope.map1.removeLayer($scope.aClusterMarkers);
        }
        $scope.aClusterMarkers = L.markerClusterGroup();

        servicioGeneral.enviarAjax({
            "url": "ControlEncuestas.php",
            "data": { "method": "sesionReporte1" },
            "success": function(response) {
                let markerGroup = L.featureGroup();
                response.predios.forEach((p) => {
                    const marker = L.marker([p.latitud, p.longitud]);
                    $scope.aClusterMarkers.addLayer(marker);
                    marker.addTo(markerGroup);
                });

                $scope.map1.addLayer($scope.aClusterMarkers);
                if(response.predios.length > 0 && markerGroup.getBounds().isValid()) {
                    $scope.map1.fitBounds(markerGroup.getBounds(), { padding: [20,20] });
                }

                $scope.oFiltro.totalEncuestas = (response.total / 1000).toFixed();
                $scope.oFiltro.usuarios = (response.usuarios / 1000).toFixed();
                $scope.oFiltro.usos = response.usos;
                $scope.oFiltro.uh = response.uh;
                $scope.oFiltro.municipios = response.municipios;

                $scope.updateLoading();
            },
            "error": function() { $scope.updateLoading(); }
        });
    };

    $scope.oFiltro = {
        "idSubZona": null,
        "descripcion": null,
        "totalEncuestas": null,
        "usuarios": null,
        "usos": null,
        "uh": null,
        "municipios": null
    };

    $scope.aSubZonaHidrografica = [];
    $scope.aClusterMarkers = null;

    $scope.map1 = new L.Map('mapa1');
    let osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    let osmAttrib = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
    let osm1 = new L.TileLayer(osmUrl, { minZoom: 2, maxZoom: 19, attribution: osmAttrib });

    $scope.map1.setView(new L.LatLng(4.711161905, -74.16767461), 9);
    $scope.map1.addLayer(osm1);

    $scope.municipiosData1 = null;
    $scope.municipiosLayerGroup1 = null;

    $scope.cargarMunicipiosGeoJSON = function() {
        $http.get('https://services6.arcgis.com/yq6pe3Lw2oWFjWtF/arcgis/rest/services/Jurisdiccion_CAR/FeatureServer/9/query?where=1%3D1&outFields=*&outSR=4326&f=geojson')
        .then(function(response){
            $scope.municipiosData1 = response.data;
            $scope.dibujarTodosLosMunicipios();
        })
        .catch(function(error){
            console.error('Error cargando municipios (mapa):', error);
        });
    };

    $scope.convertirEsriAGeoJSON1 = function(feature, projectionString) {
        var geometry = null;

        if (feature.geometry) {
            if (feature.geometry.rings) {
                var rings = feature.geometry.rings.map(function(ring) {
                    return ring.map(function(point) {
                        var converted = proj4(projectionString, 'EPSG:4326', [point[0], point[1]]);
                        return [converted[0], converted[1]];
                    });
                });
                geometry = { type: 'Polygon', coordinates: rings };
            } else if (feature.geometry.paths) {
                var coordinates = feature.geometry.paths.map(function(path) {
                    return path.map(function(point) {
                        var converted = proj4(projectionString, 'EPSG:4326', [point[0], point[1]]);
                        return [converted[0], converted[1]];
                    });
                });
                geometry = {
                    type: coordinates.length === 1 ? 'LineString' : 'MultiLineString',
                    coordinates: coordinates.length === 1 ? coordinates[0] : coordinates
                };
            } else if (feature.geometry.x && feature.geometry.y) {
                var converted = proj4(projectionString, 'EPSG:4326', [feature.geometry.x, feature.geometry.y]);
                geometry = { type: 'Point', coordinates: [converted[0], converted[1]] };
            }
        }

        return { type: 'Feature', properties: feature.attributes, geometry: geometry };
    };

    $scope.dibujarTodosLosMunicipios = function() {
        if (!$scope.municipiosData1 || !$scope.map1) return;

        
        if ($scope.municipiosLayerGroup1) {
            try { $scope.map1.removeLayer($scope.municipiosLayerGroup1); } catch(e){}
        }
        $scope.municipiosLayerGroup1 = L.layerGroup();
        var boundsGroup = L.featureGroup();

        var features = $scope.municipiosData1.features || [];

        features.forEach(function(feature){
            try {
                var isEsri = !!(feature && feature.geometry && (feature.geometry.rings || feature.geometry.paths));
                var gj = isEsri
                    ? $scope.convertirEsriAGeoJSON1(feature, '+proj=tmerc +lat_0=4.0 +lon_0=-73.0 +k=0.9992 +x_0=5000000 +y_0=2000000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs')
                    : feature;

                if (!gj || !gj.geometry) return;

                var props = (gj.properties || feature.attributes || {});
                var poly = L.geoJSON(gj, {
                    style: {
                    color: '#197091', weight: 1, opacity: 0.8,
                    fillColor: '#c1d043', fillOpacity: 0.2
                    },
                    onEachFeature: function (f, lyr) {
                        const props = (f && f.properties) || {};
                        lyr.bindPopup($scope.popupMunicipioHTML(props));
                        const name = props.Municipio || props.NOM_MPIO || props.NOMBRE || 'Municipio';
                        lyr.bindTooltip(name, { direction:'top', sticky:true, opacity:0.9 });

                        lyr.on('click', function(){
                            if ($scope._munSel && $scope._munSel !== lyr) {
                            try { $scope._munSel.setStyle($scope.styleMunicipioBase); } catch(_) {}
                            }
                            $scope._munSel = lyr;
                            try { lyr.setStyle($scope.styleMunicipioSel); lyr.bringToFront(); } catch(_){}
                            try { $scope.map2.fitBounds(lyr.getBounds(), { padding:[10,10], maxZoom: 11 }); } catch(_){}
                        });
                    }
                });

                poly.addTo($scope.municipiosLayerGroup1);
                boundsGroup.addLayer(poly);
            } catch(_) {}
        });

        $scope.municipiosLayerGroup1.addTo($scope.map1);

        var b = boundsGroup.getBounds();
        if (b && b.isValid()) { $scope.map1.fitBounds(b, { padding:[20,20], maxZoom: 10 }); }
        else { $scope.map1.setView([4.711161905, -74.16767461], 9); }
    };

    $scope.popupMunicipioHTML = function (props = {}) {
        const p = props.properties || props.attributes || props;

        const rows = [];
        const add = (label, key, fmt) => {
            const val = p[key];
            if (val != null && val !== '') {
            rows.push(
                `<tr><th style="text-align:left;padding:4px 8px;">${label}</th>
                    <td style="padding:4px 8px;">${fmt==='num' ? $scope._numCO(val) : val}</td></tr>`
            );
            }
        };

        add('Municipio',        'Municipio');
        add('Dirección',        'Direccion');
        add('COD DANE',         'CODDANE');
        add('Departamento',     'Departamen');
        add('Área (m²)',        'Shape.STArea()',   'num');
        add('Perímetro (m)',    'Shape.STLength()', 'num');

        const titulo = p['Municipio'] || p['NOM_MPIO'] || 'Municipio';
        return `
            <div style="font-family:Segoe UI,Arial,sans-serif;min-width:260px;">
            <div style="background:#197091;color:#fff;padding:8px 10px;border-radius:6px 6px 0 0;font-weight:600;">
                ${titulo}
            </div>
            <div style="border:1px solid #e3e3e3;border-top:none;border-radius:0 0 6px 6px;overflow:hidden;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">${rows.join('')}</table>
            </div>
            </div>`;
    };

    $scope.cargarMunicipiosGeoJSON();
    $scope.cargarTextosGenerales();

    
    $scope.filtroSubZonaHidrografica();
    $scope.cargarResumenEncuestas();

    setTimeout(function() {
        if ($("#ajax-loading").is(":visible")) {
            $("#ajax-loading").hide();
            document.getElementById('reporte').classList.add('loaded');
            if ($scope.map1 && typeof $scope.map1.invalidateSize === 'function') {
                $scope.map1.invalidateSize();
            }
        }
    }, 15000);
});

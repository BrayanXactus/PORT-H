


angular.module(APPNAME).controller('mapaController', function($scope, $location, $http, configuracionGlobal, servicioGeneral) {
    
    
    
    $(".container").css("margin", "0");
    $(".container").css("padding", "0");
    $(".container").css("padding-left", "0");
    $(".container").css("padding-right", "0");
    $(".container").css("min-width", "100%");
    $(".container").css("position", "relative");

    
    
    
    const MPIO_BORDER_COLOR = '#197091';
    const MPIO_FILL_COLOR   = '#c1d043';
    const CTM12_PROJECTION  = '+proj=tmerc +lat_0=4.0 +lon_0=-73.0 +k=0.9992 +x_0=5000000 +y_0=2000000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs';

    
    
    
    $scope.oFiltro = { "captaciones": false, "vertimiento": false, "ocupacion": false, "minera": false };
    
    

    
    
    
    $scope.map = new L.Map('mapa');
    const osmUrl   = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    const osmAttrib= 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
    const osm      = new L.TileLayer(osmUrl, { minZoom: 2, maxZoom: 19, attribution: osmAttrib });

    
    $scope.map.setView(new L.LatLng(4.711161905, -74.16767461), 9);
    $scope.map.addLayer(osm);


    
    

    $scope._num = v => (v === null || v === undefined) ? NaN : parseFloat(("" + v).replace(",", "."));

    $scope.pick = function(obj, keys, fallback) {
    var fb = (typeof fallback === 'undefined') ? 'No disponible' : fallback;
    if (!obj) return fb;
    for (var i = 0; i < keys.length; i++) {
        var k = keys[i];
        if (obj[k] !== undefined && obj[k] !== null && obj[k] !== '') return obj[k];
    }
    return fb;
    };

    $scope.numFmt = function(v, suf) {
    if (v === undefined || v === null || v === '') return '—';
    var n = parseFloat(v);
    if (isNaN(n)) return v;
    var s = n.toLocaleString('es-CO', { maximumFractionDigits: 2 });
    return suf ? (s + ' ' + suf) : s;
    };

    $scope.row = function(label, value) {
    if (value === undefined || value === null || value === '' || value === 'No disponible') return '';
    return `<div style="display:flex; gap:6px; margin:2px 0;">
                <div style="min-width:190px; color:#197091; font-weight:600;">${label}</div>
                <div style="flex:1;">${value}</div>
            </div>`;
    };
    $scope.section = function(title, bodyHTML) {
    if (!bodyHTML || bodyHTML.trim() === '') return '';
    return `<div style="border:1px solid #e9ecef; border-radius:8px; margin:10px 0; overflow:hidden;">
                <div style="background:linear-gradient(135deg,#197091,#29a398); color:white; padding:8px 10px; font-weight:700;">${title}</div>
                <div style="padding:10px;">${bodyHTML}</div>
            </div>`;
    };

    
    $scope.ctm12ToLatLng = function(este, norte) {
    if (typeof proj4 === 'undefined') return null;
    const x = $scope._num(este), y = $scope._num(norte);
    if (!isFinite(x) || !isFinite(y)) return null;
    try {
        const [lng, lat] = proj4(CTM12_PROJECTION, 'EPSG:4326', [x, y]);
        if (!isFinite(lat) || !isFinite(lng)) return null;
        if (lat < -5 || lat > 15 || lng < -85 || lng > -65) return null; 
        return { lat, lng };
    } catch (e) { return null; }
    };

    
    $scope.getBestLatLng = function(p) {
    if (p.este_modulo != null && p.norte_modulo != null) {
        const ll = $scope.ctm12ToLatLng(p.este_modulo, p.norte_modulo);
        if (ll) return ll;
    }
    const lat = $scope._num(p.latitud), lng = $scope._num(p.longitud);
    if (isFinite(lat) && isFinite(lng)) return { lat, lng };
    if (p.este_usuario != null && p.norte_usuario != null) {
        const ll = $scope.ctm12ToLatLng(p.este_usuario, p.norte_usuario);
        if (ll) return ll;
    }
    if (p.este != null && p.norte != null) {
        const ll = $scope.ctm12ToLatLng(p.este, p.norte);
        if (ll) return ll;
    }
    return null;
    };

    
    $scope.getModuloDePunto = function(p, fallback) {
    if (!p) return fallback ?? null;
    var raw = (p.id_modulo ?? p.modulo ?? p.idModulo ?? p.IdModulo);
    var m = parseInt(raw, 10);
    return isNaN(m) ? (fallback ?? null) : m;
    };

    
    $scope.buildPopupContent = function(p, moduloOverride) {
    var este_usuario = $scope.pick(p, ['este_usuario', 'este_u'], '—');
    var norte_usuario = $scope.pick(p, ['norte_usuario', 'norte_u'], '—');
    var depto = $scope.pick(p, ['departamento', 'depto', 'nombre_departamento'], '—');
    var mpio = $scope.pick(p, ['municipio', 'nombre_municipio'], '—');
    var depto_m = $scope.pick(p, ['departamento_modulo', 'depto_m', 'nombre_departamento_m'], '—');
    var mpio_m = $scope.pick(p, ['municipio_modulo', 'municipio_m'], '—');

    var nomUHN1 = $scope.pick(p, ['nombre_unidad_1','nombre_uhn1','und_geo_nivel1'], '—');
    var codUHN1 = $scope.pick(p, ['codigo_unidad_1','cod_uhn1','codigo_unidad_1'], '—');
    var nomUHN2 = $scope.pick(p, ['nombre_unidad_2','nombre_uhn2','und_geo_nivel2'], '—');
    var codUHN2 = $scope.pick(p, ['codigo_unidad_2','cod_uhn2','codigo_unidad_2','codigo_unidad_hidrografica_nivel_2'], '—');
    var este_modulo = $scope.pick(p, ['este_modulo', 'este_m'], '—');
    var norte_modulo = $scope.pick(p, ['norte_modulo', 'norte_m'], '—');

    
    var modulo = (typeof moduloOverride === 'number') ? moduloOverride : ($scope.getModuloDePunto(p, 1));

    var usuarioHTML = '';
    usuarioHTML += $scope.row('0. Código encuesta', $scope.pick(p, ['codigo_encuesta'], '—'));
    usuarioHTML += $scope.row('1. Código UHN I', codUHN1);
    usuarioHTML += $scope.row('18. Departamento', depto);
    usuarioHTML += $scope.row('21. Municipio', mpio);
    usuarioHTML += $scope.row('26a. Este - x (m)', este_usuario);
    usuarioHTML += $scope.row('26a. Norte - y (m)', norte_usuario);

    var moduloHTML = '';
    if (modulo === 1) {
        var usos = {
        '246. Doméstico': $scope.pick(p, ['domestico', 'valor_domestico', 'cap_domestico'], ''),
        '247. Pecuario': $scope.pick(p, ['pecuario', 'valor_pecuario', 'cap_pecuario'], ''),
        '248. Acuícola': $scope.pick(p, ['acuicola', 'valor_acuicola', 'cap_acuicola'], ''),
        '249. Agrícola': $scope.pick(p, ['agricola', 'valor_agricola', 'cap_agricola'], ''),
        '250. Industrial': $scope.pick(p, ['industrial', 'valor_industrial', 'cap_industrial'], ''),
        '251. Minería': $scope.pick(p, ['mineria', 'valor_mineria', 'cap_mineria'], ''),
        '252. Generación eléctrica': $scope.pick(p, ['generacion_electrica', 'valor_generacion_electrica', 'cap_generacion'], ''),
        '253. Otros': $scope.pick(p, ['otros', 'valor_otros', 'cap_otros'], '')
        };
        var usosHTML = '';
        Object.keys(usos).forEach(function(lbl) {
        var val = usos[lbl];
        if (val !== '' && val !== null && val !== undefined) {
            usosHTML += $scope.row(lbl, $scope.numFmt(val, 'L/s'));
        }
        });

        moduloHTML += $scope.row('30. Departamento', depto_m);
        moduloHTML += $scope.row('32. Municipio', mpio_m);
        moduloHTML += $scope.row('36. Nombre UHN I', nomUHN1);
        moduloHTML += $scope.row('37. Código UHN I', codUHN1);
        moduloHTML += $scope.row('38. Nombre UHN II', nomUHN2);
        moduloHTML += $scope.row('39. Código UHN II', codUHN2);
        moduloHTML += $scope.row('57a. Este - x (m)', este_modulo);
        moduloHTML += $scope.row('57a. Norte - y (m)', norte_modulo);
        moduloHTML += usosHTML;
        moduloHTML += $scope.row('254. Total (L/s)', $scope.numFmt($scope.pick(p, ['total_lps', 'valor_captado', 'total', 'total_litros_segundo'], '—'), 'L/s'));
        moduloHTML = $scope.section('MÓDULO CAPTACIÓN', moduloHTML);

    } else if (modulo === 2) {
        moduloHTML += $scope.row('150. Objetivo de calidad (Uso/destinación)', $scope.pick(p, ['objetivo_calidad', 'uso_destinacion', 'objetivo_cuerpo_receptor', 'calidad_cuerpo_receptor'], '—'));
        moduloHTML += $scope.row('151. Departamento', depto_m);
        moduloHTML += $scope.row('153. Municipio', mpio_m);
        moduloHTML += $scope.row('157. Nombre UHN I', nomUHN1);
        moduloHTML += $scope.row('158. Código UHN I', codUHN1);
        moduloHTML += $scope.row('159. Nombre UHN II', nomUHN2);
        moduloHTML += $scope.row('160. Código UHN II', codUHN2);
        moduloHTML += $scope.row('161. Actividad que genera el vertimiento', $scope.pick(p, ['actividad_vertimiento', 'actividad', 'actividad_generadora'], '—'));
        moduloHTML += $scope.row('163. Caudal vertido', $scope.numFmt($scope.pick(p, ['caudal_vertido', 'caudal_vertido_lps', 'caudal', 'lps'], '—'), 'L/s'));
        moduloHTML += $scope.row('168a. Este - x (m)', este_modulo);
        moduloHTML += $scope.row('168a. Norte - y (m)', norte_modulo);
        moduloHTML = $scope.section('MÓDULO VERTIMIENTO', moduloHTML);

    } else if (modulo === 3) {
        moduloHTML += $scope.row('184. Cuerpo de agua (Obras hidráulicas)', $scope.pick(p, ['cuerpo_agua', 'cuerpo_agua_ocupacion'], '—'));
        moduloHTML += $scope.row('185. Nombre de la fuente', $scope.pick(p, ['nombre_fuente', 'fuente'], '—'));
        moduloHTML += $scope.row('191. Departamento', depto_m);
        moduloHTML += $scope.row('193. Municipio', mpio_m);
        moduloHTML += $scope.row('197. Nombre UHN I', nomUHN1);
        moduloHTML += $scope.row('198. Código UHN I', codUHN1);
        moduloHTML += $scope.row('199. Nombre UHN II', nomUHN2);
        moduloHTML += $scope.row('200. Código UHN II', codUHN2);
        moduloHTML += $scope.row('202. Tipo de obra', $scope.pick(p, ['tipo_obra', 'obra'], '—'));
        moduloHTML += $scope.row('211a. Este - x (m)', este_modulo);
        moduloHTML += $scope.row('211a. Norte - y (m)', norte_modulo);
        moduloHTML = $scope.section('MÓDULO OCUPACIÓN DE CAUCE', moduloHTML);

    } else if (modulo === 4) {
        moduloHTML += $scope.row('221. Nombre de la fuente', $scope.pick(p, ['nombre_fuente', 'fuente'], '—'));
        moduloHTML += $scope.row('222. Descripción de la actividad', $scope.pick(p, ['descripcion_actividad', 'actividad', 'detalle_actividad'], '—'));
        moduloHTML += $scope.row('224. Departamento', depto_m);
        moduloHTML += $scope.row('226. Municipio', mpio_m);
        moduloHTML += $scope.row('230. Nombre UHN I', nomUHN1);
        moduloHTML += $scope.row('231. Código UHN I', codUHN1);
        moduloHTML += $scope.row('232. Nombre UHN II', nomUHN2);
        moduloHTML += $scope.row('233. Código UHN II', codUHN2);
        moduloHTML += $scope.row('234. Tipo de material de arrastre', $scope.pick(p, ['tipo_material', 'material_arrastre'], '—'));
        moduloHTML += $scope.row('243a. Este - x (m)', este_modulo);
        moduloHTML += $scope.row('243a. Norte - y (m)', norte_modulo);
        moduloHTML = $scope.section('MÓDULO MINERÍA', moduloHTML);
    }

    var titulo = $scope.pick(p, ['titulo_popup'], null) || `📍 Encuesta ${$scope.pick(p, ['codigo_encuesta'], '—')}`;
    return `
        <div style="font-family:Arial, sans-serif; min-width:320px; max-width:480px;">
        <div style="background:linear-gradient(135deg,#197091,#29a398); color:#fff; padding:10px 12px; border-radius:6px 6px 0 0; font-weight:700;">
            ${titulo}
        </div>
        <div style="padding:10px; border:1px solid #e9ecef; border-top:none; border-radius:0 0 6px 6px;">
            ${$scope.section('MÓDULO USUARIO', usuarioHTML)}
            ${moduloHTML}
            <div style="text-align:right; color:#6c757d; font-size:12px; margin-top:6px;">
            ID Encuesta: ${$scope.pick(p, ['id_encuesta'], '—')}
            </div>
        </div>
        </div>`;
    };

    
    
    
    const LegendControl = L.Control.extend({
        options: { position: 'topright' },
        onAdd: function () {
            const div = L.DomUtil.create('div', 'leaflet-control legend-control');
            div.style.background = 'white';
            div.style.padding = '8px 10px';
            div.style.borderRadius = '8px';
            div.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
            div.style.fontFamily = 'Inter, Arial, sans-serif';
            div.style.fontSize = '12px';
            div.innerHTML = `
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="display:inline-block;width:28px;height:0;border-top:3px solid ${MPIO_BORDER_COLOR};"></span>
                    <span style="color:#2b2b2b;font-weight:600;">Cuenca del Río Bogotá</span>
                </div>
            `;
            return div;
        }
    });
    $scope.map.addControl(new LegendControl());

    
    
    
    $scope.aClusterMarkers = L.markerClusterGroup();
    $scope.map.addLayer($scope.aClusterMarkers);

    
    
    
    $scope.cargarPuntosMapa = () => {
    $("#ajax-loading").show();

    
    if ($scope.aClusterMarkers) {
        try { $scope.aClusterMarkers.clearLayers(); } catch (e) {}
    }

    servicioGeneral.enviarAjax({
        url: "ControlEncuestas.php",
        data: { method: "puntosMapa", modulos: $scope.oFiltro },
        success: function (response) {

        const posiciones = [];
        const addSet = (arr, moduloId) => {
            if (!Array.isArray(arr)) return;
            arr.forEach((p) => {
            const ll = $scope.getBestLatLng(p);
            if (!ll) return;
            const marker = L.marker([ll.lat, ll.lng]);
            const html = $scope.buildPopupContent(p, moduloId);
            marker.bindPopup(html, { maxWidth: 520, className: 'custom-popup' });
            $scope.aClusterMarkers.addLayer(marker);
            posiciones.push([ll.lat, ll.lng]);
            });
        };

        if ($scope.oFiltro.captaciones && response.captaciones) addSet(response.captaciones, 1);
        if ($scope.oFiltro.vertimiento && response.vertimiento) addSet(response.vertimiento, 2);
        if ($scope.oFiltro.ocupacion  && response.ocupacion ) addSet(response.ocupacion,  3);
        if ($scope.oFiltro.minera     && response.minera    ) addSet(response.minera,     4);

        
        if (posiciones.length > 0) {
            const fg = L.featureGroup(posiciones.map(([la, lo]) => L.marker([la, lo])));
            const b = fg.getBounds();
            if (b.isValid()) { $scope.map.fitBounds(b, { padding: [20,20], maxZoom: 12 }); }
        }

        $("#ajax-loading").hide();
        },
        error: function () {
        $("#ajax-loading").hide();
        console.error('Error al cargar puntos del mapa');
        }
    });
    };

    /*$scope.descargar = function (formato) {
        
        $scope.descargando = true;
        $("#ajax-loading").show();

        
        servicioGeneral.enviarAjax({
            url: "ControlEncuestas.php",
            data: {
            method: "exportarMapa",            
            format: formato,                   
            modulos: $scope.oFiltro || {},     
            filtros: $scope.filtros || {}      
            },
            success: function (response) {
            try {
                const url = response && response.url;
                if (!url) {
                alert('No se recibió URL de descarga desde el servidor.');
                return;
                }
                
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('target', '_blank');
                link.click();
            } finally {
                $("#ajax-loading").hide();
                $scope.$applyAsync(() => { $scope.descargando = false; });
            }
            },
            error: function (xhr) {
            $("#ajax-loading").hide();
            $scope.$applyAsync(() => { $scope.descargando = false; });
            console.error('Error al preparar la descarga', xhr);
            alert('Error al preparar la descarga.');
            }
        });
    };*/

    
    
    
    $scope.filtroCaptaciones = () => {
        $scope.oFiltro.captaciones = !$scope.oFiltro.captaciones;
        $scope.cargarPuntosMapa();
    };

    $scope.filtroVertimiento = () => {
        $scope.oFiltro.vertimiento = !$scope.oFiltro.vertimiento;
        $scope.cargarPuntosMapa();
    };

    $scope.filtroOcupacion = () => {
        $scope.oFiltro.ocupacion = !$scope.oFiltro.ocupacion;
        $scope.cargarPuntosMapa();
    };

    $scope.filtroMinera = () => {
        $scope.oFiltro.minera = !$scope.oFiltro.minera;
        $scope.cargarPuntosMapa();
    };

    
    
    
    $scope.municipiosData = null;
    $scope.municipiosLayerGroup = null;

    
    $scope.convertirEsriAGeoJSON = function(feature, projectionString) {
        let geometry = null;

        if (feature && feature.geometry) {
            if (feature.geometry.rings) {
                const rings = feature.geometry.rings.map(function(ring) {
                    return ring.map(function(point) {
                        const converted = proj4(projectionString, 'EPSG:4326', [point[0], point[1]]);
                        return [converted[0], converted[1]]; 
                    });
                });
                geometry = { type: 'Polygon', coordinates: rings };
            } else if (feature.geometry.paths) {
                const coordinates = feature.geometry.paths.map(function(path) {
                    return path.map(function(point) {
                        const converted = proj4(projectionString, 'EPSG:4326', [point[0], point[1]]);
                        return [converted[0], converted[1]];
                    });
                });
                geometry = {
                    type: coordinates.length === 1 ? 'LineString' : 'MultiLineString',
                    coordinates: coordinates.length === 1 ? coordinates[0] : coordinates
                };
            } else if (feature.geometry.x != null && feature.geometry.y != null) {
                const converted = proj4(projectionString, 'EPSG:4326', [feature.geometry.x, feature.geometry.y]);
                geometry = { type: 'Point', coordinates: [converted[0], converted[1]] };
            }
        }

        return { type: 'Feature', properties: feature.attributes || {}, geometry: geometry };
    };

    
    $scope.dibujarTodosLosMunicipios = function(){
        if (!$scope.municipiosData || !$scope.map) return;

        
        if ($scope.municipiosLayerGroup) {
            try { $scope.map.removeLayer($scope.municipiosLayerGroup); } catch(e){}
        }
        $scope.municipiosLayerGroup = L.layerGroup();
        const boundsGroup = L.featureGroup();

        const features = $scope.municipiosData.features || [];
        features.forEach(function(feature){
            try{
            const capa = L.geoJSON(feature, {
                style: {
                color: '#197091',
                weight: 1,
                opacity: 0.8,
                fillColor: '#c1d043',
                fillOpacity: 0.2
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

            capa.addTo($scope.municipiosLayerGroup);
            boundsGroup.addLayer(capa);
            }catch(_){}
        });

        $scope.municipiosLayerGroup.addTo($scope.map);

        const b = boundsGroup.getBounds();
        if (b && b.isValid()) { $scope.map.fitBounds(b, { padding:[20,20], maxZoom: 10 }); }
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

    function attachHover(layer){
        layer.on('mouseover', function(){ this.setStyle({ weight: 2, fillOpacity: 0.3 }); });
        layer.on('mouseout',  function(){ this.setStyle({ weight: 1, fillOpacity: 0.2 }); });
    }

    
    $scope.cargarMunicipiosGeoJSON = function() {
        $http.get('https://services6.arcgis.com/yq6pe3Lw2oWFjWtF/arcgis/rest/services/Jurisdiccion_CAR/FeatureServer/9/query?where=1%3D1&outFields=*&outSR=4326&f=geojson')
        .then(function(response) {
            $scope.municipiosData = response.data;      
            $scope.dibujarTodosLosMunicipios();         
        })
        .catch(function(err){ console.error('Municipios (mapa):', err); });
    };

    
    
    
    
    $scope.cargarMunicipiosGeoJSON();

    
    
    
});

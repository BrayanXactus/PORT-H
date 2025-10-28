angular.module(APPNAME).controller('abiertaController', function($scope, $location, $http, $timeout, configuracionGlobal, servicioGeneral) {
    $("#ajax-loading").show();

    $scope.loadingCounter = 0;
    $scope.totalLoadingSteps = 3;

    $scope.updateLoading = function() {
        $scope.loadingCounter++;
        if ($scope.loadingCounter >= $scope.totalLoadingSteps) {
            setTimeout(function() {
                $("#ajax-loading").hide();
                if (document.getElementById('reporte')) {
                    document.getElementById('reporte').classList.add('loaded');
                }
            }, 500);
        }
    };

    $scope.CTM12_PROJECTION = '+proj=tmerc +lat_0=4.0 +lon_0=-73.0 +k=0.9992 +x_0=5000000 +y_0=2000000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs';


$scope._claveModulo = function(idModulo){
  return ({1:'CAPTACIONES', 2:'VERTIMIENTOS', 3:'OCUPACIONES', 4:'MINERA'})[idModulo] || null;
};
$scope._normalizarConteo = function(resp){
  let detalle = [], resumen = null;

  if (resp && Array.isArray(resp)) {
    detalle = resp;
  } else if (resp && typeof resp === 'object') {
    if (Array.isArray(resp.detalle)) detalle = resp.detalle;
    if (resp.resumen && typeof resp.resumen === 'object') resumen = resp.resumen;

    const flat = {
      CAPTACIONES: resp.CAPTACIONES, VERTIMIENTOS: resp.VERTIMIENTOS,
      OCUPACIONES: resp.OCUPACIONES, MINERA: resp.MINERA, TOTAL: resp.TOTAL
    };
    const hayFlat = Object.values(flat).some(v => v !== undefined && v !== null);
    if (!resumen && hayFlat) resumen = flat;
  }

  if (resumen) {
    ['CAPTACIONES','VERTIMIENTOS','OCUPACIONES','MINERA','TOTAL'].forEach(k=>{
      if (resumen[k] == null) resumen[k] = 0;
      resumen[k] = parseInt(resumen[k],10) || 0;
    });
  }

  detalle = (detalle || []).map(d=>({
    nombre: d.nombre,
    cantidad: parseFloat(d.cantidad) || 0,
    consumo:  (d.consumo != null ? parseFloat(d.consumo) : NaN),
    cantidad_encuestas: (d.cantidad_encuestas != null) ? parseInt(d.cantidad_encuestas,10) : undefined
  }));

  return { detalle, resumen };
};

$scope._valorParaGrafica = function(d){
  let v = parseFloat(d?.consumo);
  if (isFinite(v)) return v;
  v = parseFloat(d?.cantidad);
  return isFinite(v) ? v : 0;
};


    $scope._filtros = function (extra) {
    return angular.extend({
        idModulo: $scope.oFiltro.idModulo,
        UHN1: $scope.oFiltro.UHN1,
        UHN2: $scope.oFiltro.UHN2,
        idMunicipio: $scope.oFiltro.idMunicipio,
        idSubZona: $scope.oFiltro.idSubZona
    }, extra || {});
    };

    $scope.getModuloDePunto = function(p) {
        if (!p) return $scope.oFiltro.idModulo || null;
        var raw = (p.id_modulo ?? p.modulo ?? p.idModulo ?? p.IdModulo);
        var m = parseInt(raw, 10);
        if (!isNaN(m)) return m;
        return $scope.oFiltro.idModulo || null;
    };

    $scope.actualizarResumenDesdePuntos = function(arr) {
        var caps = 0, vert = 0, ocu = 0, min = 0;
        (arr || []).forEach(function(p) {
            var m = $scope.getModuloDePunto(p);
            if (m === 1) caps++;
            else if (m === 2) vert++;
            else if (m === 3) ocu++;
            else if (m === 4) min++;
        });

        if ((caps + vert + ocu + min) === 0 && Array.isArray(arr)) {
            if ($scope.oFiltro.idModulo === 1) caps = arr.length;
            else if ($scope.oFiltro.idModulo === 2) vert = arr.length;
            else if ($scope.oFiltro.idModulo === 3) ocu = arr.length;
            else if ($scope.oFiltro.idModulo === 4) min = arr.length;
        }

        var total = caps + vert + ocu + min;
        $scope.$applyAsync(function() {
            $scope.oFiltro.captaciones = caps;
            $scope.oFiltro.vertimiento = vert;
            $scope.oFiltro.ocupacion  = ocu;
            $scope.oFiltro.minera     = min;
            $scope.oFiltro.total      = total;
        });
    };

    $scope._puntosCargados = false;

    $scope.syncTabWithModulo = function() {
        var id = $scope.oFiltro.idModulo;
        if (id === 1)       { $scope.cambiarGrafica(1); } 
        else if (id === 2)  { $scope.cambiarGrafica(2); } 
        else if (id === 3)  { $scope.cambiarGrafica(3); } 
        else if (id === 4)  { $scope.cambiarGrafica(2); } 
    };


    $scope.getSelectize = function(id) {
        var el = document.getElementById(id);
        return (el && el.selectize) ? el.selectize : null;
    };

    $scope.extraerCodigoNumericoUHN1 = function(codigo) {
        if (!codigo || typeof codigo !== 'string') return null;
        var m = codigo.match(/(\d{6})$/);
        return m ? m[1] : null;
    };

    $scope.agruparUHN2porUHN1 = function(aUHN1, aUHN2) {
        var mapa = {};
        var idxCodUHN1 = {};
        (aUHN1 || []).forEach(function(u1) {
            var cod6 = $scope.extraerCodigoNumericoUHN1(u1.codigo);
            if (cod6) idxCodUHN1[u1.id] = cod6;
        });

        (aUHN1 || []).forEach(function(u1) { mapa[u1.id] = []; });

        (aUHN2 || []).forEach(function(u2) {
            Object.keys(idxCodUHN1).forEach(function(idU1) {
                var cod6 = idxCodUHN1[idU1];
                if (cod6 && typeof u2.codigo === 'string' && u2.codigo.indexOf(cod6) !== -1) {
                    mapa[idU1].push(u2);
                }
            });
        });

        return mapa;
    };

    $scope.cargarOpcionesSelectize = function(id, opciones) {
        var sz = $scope.getSelectize(id);
        if (!sz) return;
        sz.clear(true);
        sz.clearOptions();
        (opciones || []).forEach(function(opt) { sz.addOption(opt); });
        sz.refreshOptions(false);
    };

    $scope.cargarMunicipiosGeoJSON = function () {
        const url = 'https://services6.arcgis.com/yq6pe3Lw2oWFjWtF/arcgis/rest/services/Jurisdiccion_CAR/FeatureServer/9/query?where=1%3D1&outFields=*&outSR=4326&f=geojson'

        $http.get(url)
            .then(function (response) {
            $scope.municipiosData = response.data;

            $scope.procesarMunicipios();
            $scope.dibujarTodosLosMunicipios();
            })
            .catch(function (error) {
            console.error('Error cargando municipios (GeoJSON remoto):', error);
            });
    };

    
    $scope.iuaPorUHN1 = {};          
    $scope.uhn1NameToId = {};        
    $scope.mpioToUHN1Id = {};        
    $scope.iuaBreaks = null;         
    $scope.iuaLegendCtrl = null;     
    $scope.uhn12Data = null;                 
    $scope.uhn12ByName = {};                 
    $scope.uhn1ResaltadoLayer3 = null;       

    function _normTxtUHN(txt) {
        if (!txt) return '';
        return txt
            .toString()
            .trim()
            .toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/-/g, " ")
            .replace(/\s+/g, " ")
            .replace(/\./g, "")
            ;
    }

    function _normUHN1Name(x){
    if (!x) return '';
    return x.toString()
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/-/g,' ')
        .replace(/\s+/g,' ')
        .trim().toUpperCase();
    }

    const COLOR_BY_NAME = {
        [_normUHN1Name('RÍO BAJO BOGOTÁ')]:     '#7ED321',
        [_normUHN1Name('RÍO CALANDAIMA')]:      '#F8E71C',
        [_normUHN1Name('SECTOR SALTO - APULO')]:'#7ED321',
        [_normUHN1Name('SECTOR SOACHA - SALTO')]:'#4A90E2',
        [_normUHN1Name('EMBALSE DEL MUÑA')]:    '#D0021B',
        [_normUHN1Name('RÍO TUNJUELO')]:        '#F8E71C',
        [_normUHN1Name('SECTOR TIBITOC - SOACHA')]: '#D0021B',
        [_normUHN1Name('RÍO TEUSACÁ')]:         '#D0021B',
        [_normUHN1Name('EMBALSE TOMINÉ')]:      '#F8E71C',
        [_normUHN1Name('EMBALSE SISGA')]:       '#D0021B',
        [_normUHN1Name('RÍO ALTO BOGOTÁ')]:     '#D0021B',
        [_normUHN1Name('SECTOR SISGA - TIBITOC')]: '#D0021B',
        [_normUHN1Name('RÍO NEUSA')]:           '#D0021B',
        [_normUHN1Name('RÍO FRÍO')]:            '#F5A623',
        [_normUHN1Name('RÍO BALSILLAS')]:       '#F5A623',
        [_normUHN1Name('RÍO APULO')]:           '#D0021B',
        [_normUHN1Name('RÍO SOACHA')]:          '#7ED321',
        [_normUHN1Name('RÍO NEGRO')]:           '#D0021B',
        [_normUHN1Name('RÍO CHICÚ')]:           '#F8E71C'
    };

    const COLOR_BY_CODE = {
        '2120-01':'#7ED321',
        '2120-02':'#F8E71C',
        '2120-03':'#7ED321',
        '2120-04':'#4A90E2',
        '2120-05':'#D0021B',
        '2120-06':'#F8E71C',
        '2120-07':'#D0021B',
        '2120-08':'#D0021B',
        '2120-09':'#F8E71C',
        '2120-10':'#D0021B',
        '2120-11':'#D0021B',
        '2120-12':'#D0021B',
        '2120-13':'#D0021B',
        '2120-14':'#F5A623',
        '2120-15':'#F5A623',
        '2120-16':'#D0021B',
        '2120-17':'#7ED321',
        '2120-18':'#D0021B',
        '2120-19':'#F8E71C'
    };

    function getColorUHN1(attrs, nombreFallback){
        const codeKeys = ['N_NV_SUB', 'NO_UNIDAD','NO_UHN1','NO_UNID','CODIGO','COD_UHN1','CODIGO_UHN1','NO_UHN'];
        let code = null;
        for (let k of codeKeys){
            const v = attrs?.[k];
            if (v && typeof v === 'string' && /2120-\d{2}/.test(v)) { code = v; break; }
        }

        const nombre = _normUHN1Name(
            nombreFallback ||
            attrs?.N_NV_SUB || attrs?.NOMBRE_UHN1 || attrs?.NOM_UHN1 ||
            attrs?.NOMBRE_N1 || attrs?.NOM_NIVEL1 || attrs?.Nombre_UHN1 ||
            attrs?.Nombre_N1 || attrs?.NOMBRE
        );

        return COLOR_BY_CODE[code] || COLOR_BY_NAME[nombre] || '#FFE135';
    }

    function quantile(arr, p) {
        if (!arr || arr.length === 0) return 0;
        const a = arr.slice().sort((x,y)=>x-y);
        const idx = (a.length - 1) * p;
        const lo = Math.floor(idx), hi = Math.ceil(idx);
        if (lo === hi) return a[lo];
        return a[lo] + (a[hi] - a[lo]) * (idx - lo);
    }
    function colorForClass(cls) {
        return ({
            'Muy bajo':  '#E3F2FD',
            'Bajo':      '#90CAF9',
            'Medio':     '#42A5F5',
            'Alto':      '#1E88E5',
            'Muy alto':  '#0D47A1'
        })[cls] || '#c1d043';
    }
    function classByBreaks(v, br) {
        if (v <= br.q20) return 'Muy bajo';
        if (v <= br.q40) return 'Bajo';
        if (v <= br.q60) return 'Medio';
        if (v <= br.q80) return 'Alto';
        return 'Muy alto';
    }
    $scope.calcularIUAporUHN1FromPuntos = function(puntos) {
        const sums = {};
        (puntos || []).forEach(p => {
            const nom = (p.unidad_1 || '').toUpperCase().trim();
            const idUHN1 = (p.id_uhn1) ? parseInt(p.id_uhn1,10) : $scope.uhn1NameToId[nom];
            if (!idUHN1) return;
            const v = parseFloat(p.valor_captado);
            const val = isNaN(v) ? 0 : v;
            sums[idUHN1] = (sums[idUHN1] || 0) + val;
        });
        const vals = Object.values(sums);
        if (vals.length === 0) {
            $scope.iuaPorUHN1 = {};
            $scope.iuaBreaks = null;
            return;
        }
        const br = {
            q20: quantile(vals, 0.20),
            q40: quantile(vals, 0.40),
            q60: quantile(vals, 0.60),
            q80: quantile(vals, 0.80)
        };
        $scope.iuaBreaks = br;
        const out = {};
        Object.keys(sums).forEach(id => {
            const total = sums[id];
            const cls = classByBreaks(total, br);
            out[id] = { totalLps: total, clase: cls, color: colorForClass(cls) };
        });
        $scope.iuaPorUHN1 = out;
    };
    $scope.estiloMunicipioPorUHN = function(props) {
        const nomMpio = ((props && (props.NOM_MPIO || props.Nombre || props.MPIO || '')) + '').toUpperCase().trim();
        const idUHN1 = $scope.mpioToUHN1Id[nomMpio];
        const rec = idUHN1 ? $scope.iuaPorUHN1[idUHN1] : null;
        return {
            color: '#197091',
            weight: 1,
            opacity: 0.8,
            fillColor: rec ? rec.color : '#c1d043',
            fillOpacity: rec ? 0.45 : 0.2
        };
    };
    $scope.reestilizarMunicipios = function() {
        if (!$scope.municipiosLayerGroup) return;
        $scope.municipiosLayerGroup.eachLayer(function(layer) {
            const props = (layer.feature && layer.feature.properties) || {};
            layer.setStyle($scope.estiloMunicipioPorUHN(props));
        });
    };
    $scope.addIUALegendMapa2 = function() {
        if (!$scope.map2) return;
        if ($scope.iuaLegendCtrl) { $scope.map2.removeControl($scope.iuaLegendCtrl); $scope.iuaLegendCtrl = null; }
        if (!$scope.iuaBreaks) return;
        const br = $scope.iuaBreaks;
        const items = [
            {label:`Muy bajo ≤ ${$scope.numFmt(br.q20,'L/s')}`, cls:'Muy bajo'},
            {label:`Bajo ≤ ${$scope.numFmt(br.q40,'L/s')}`,     cls:'Bajo'},
            {label:`Medio ≤ ${$scope.numFmt(br.q60,'L/s')}`,    cls:'Medio'},
            {label:`Alto ≤ ${$scope.numFmt(br.q80,'L/s')}`,     cls:'Alto'},
            {label:`Muy alto > ${$scope.numFmt(br.q80,'L/s')}`, cls:'Muy alto'}
        ];
        $scope.iuaLegendCtrl = L.control({ position: 'bottomright' });
        $scope.iuaLegendCtrl.onAdd = function() {
        const div = L.DomUtil.create('div', 'leaflet-control');
        div.style.background = 'rgba(255,255,255,0.9)';
        div.style.padding = '8px 10px';
        div.style.borderRadius = '6px';
        div.style.boxShadow = '0 1px 3px rgba(0,0,0,0.25)';
        div.style.fontFamily = 'Arial, sans-serif';
        return div;
        };
        $scope.iuaLegendCtrl.addTo($scope.map2);
    };

    $scope.dibujarTodosLosMunicipios = function () {
        if (!$scope.municipiosData || !$scope.municipiosData.features || !$scope.map2 || typeof proj4 === 'undefined') return;

        if ($scope.municipiosLayerGroup) { $scope.map2.removeLayer($scope.municipiosLayerGroup); }
        $scope.municipiosLayerGroup = L.layerGroup().addTo($scope.map2);

        const CTM12_PROJECTION = $scope.CTM12_PROJECTION;

        ($scope.municipiosData.features || []).forEach(function (feat) {
            try {
                const isEsri = !!(feat && feat.geometry && (feat.geometry.rings || feat.geometry.paths));
                const gj = isEsri ? $scope.convertirEsriAGeoJSON(feat, CTM12_PROJECTION) : feat;

                if (!gj || !gj.geometry) return;

                const attrs = (gj.properties || feat.properties || feat.attributes || {});
                const get = (obj, keys) => {
                    for (let k of keys) if (obj && obj[k] != null && obj[k] !== '') return obj[k];
                    return null;
                };

                const nombre = get(attrs, ['NOM_MPIO','NOMBRE','MUNICIPIO','MUN','NOM_MPIO_','NOMBRE_MPIO']) || 'Municipio';
                const dpto   = get(attrs, ['NOM_DPTO','DEPARTAM','DPTO','DEPARTAMENTO']);
                const codigo = get(attrs, ['CODIGO','DANE_MPIO','COD_MPIO','CODIGO_MPIO','MPIO_CCDGO']);
                const areaHa = get(attrs, ['Area_Ha','AREA_HA','AREA_HECT','AREA_HA_']);

                const layer = L.geoJSON(gj, {
                    style: function (f) {
                        const props = (f && f.properties) || attrs || {};
                        return Object.assign({}, $scope.styleMunicipioBase, $scope.estiloMunicipioPorUHN(props));
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

                layer.addTo($scope.municipiosLayerGroup);
            } catch (e) {
            }
        });

        try {
            const b = $scope.municipiosLayerGroup.getBounds();
            if (b && b.isValid()) $scope.map2.fitBounds(b);
        } catch (_){}
    };

    $scope.uhnData = null;
    $scope.uhn1LayerGroup = null;
    $scope.uhn1LayersById = {};
    $scope.uhn1NameIndex = {};

    function _normTxt(s){
    return (s||'').toString()
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/\s+/g,' ').trim().toUpperCase();
    }

    function _pick(attrs, keys){
        for (let k of keys){ if (attrs && attrs[k] != null && attrs[k] !== '') return attrs[k]; }
        return null;
    }

    $scope.estiloUHN1Base = function(){
        return { color:'#197091', weight:1, opacity:.9, fillColor:'#c1d043', fillOpacity:.25 };
    };
    $scope.estiloUHN1Resaltado = function(){
        return { color:'#FF6B35', weight:3, opacity:1, fillColor:'#FFE135', fillOpacity:.55 };
    };

    $scope.reconstruirIndiceUHN1 = function(){
        $scope.uhn1NameIndex = {};
        ($scope.aUHN1 || []).forEach(u=>{
            const k = _normTxt(u.descripcion);
            if (k) $scope.uhn1NameIndex[k] = parseInt(u.id,10);
        });
    };

    $scope.dibujarUHN1Todos = function(){
        if (!$scope.mapUHN || !$scope.uhnData || !$scope.uhnData.features) return;

        if ($scope.uhn1LayerGroup){ $scope.mapUHN.removeLayer($scope.uhn1LayerGroup); }
        $scope.uhn1LayerGroup = L.layerGroup().addTo($scope.mapUHN);
        $scope.uhn1LayersById = {};

        let dibujados = 0, errores = 0;

        const idToName = {};
        ($scope.aUHN1 || []).forEach(u => { idToName[parseInt(u.id,10)] = u.descripcion; });

        ($scope.uhnData.features || []).forEach((feat, idx)=>{
            try{
                const attrs = feat.attributes || feat.properties || {};

                const nombreUHN1 = (function(){
                    const k = ['N_NV_SUB','NOMBRE_UHN1','NOM_UHN1','NOMBRE_N1','NOM_NIVEL1','Nombre_UHN1','Nombre_N1','NOMBRE'];
                    for (let i=0;i<k.length;i++){ if (attrs[k[i]] != null && attrs[k[i]] !== '') return attrs[k[i]]; }
                    return null;
                })();

                const idAttr = (function(){
                    const k = ['ID_UHN1','ID_N1','ID_NIVEL1','ID','COD_UHN1','COD_N1','CODIGO_N1'];
                    for (let i=0;i<k.length;i++){
                        const v = attrs[k[i]];
                        const n = (v!=null && v!=='') ? parseInt(v,10) : NaN;
                        if (isFinite(n)) return n;
                    }
                    return null;
                })();

                const idUHN1 = isFinite(idAttr) ? idAttr : ($scope.uhn1NameIndex[
                    (nombreUHN1||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim().toUpperCase()
                ] || null);

                const gj = $scope.convertirEsriAGeoJSON(feat, $scope.CTM12_PROJECTION);
                if (!gj || !gj.geometry) return;

                const displayName = nombreUHN1 || (idUHN1 ? (idToName[idUHN1] || `UHN I ${idUHN1}`) : 'UHN I');

                const colorFill = getColorUHN1(attrs, nombreUHN1);

                const poly = L.geoJSON(gj, {
                    style: Object.assign({}, $scope.estiloUHN1Base, {
                        fillColor: colorFill,
                        color: '#1f3b4d',
                        weight: 2,
                        fillOpacity: 0.6
                    })
                });

                poly.bindPopup(`
                    <div style="font-family:Arial, sans-serif;">
                        <strong style="color:#197091;">${displayName}</strong><br>
                        ${idUHN1 ? `<div><strong>ID UHN I:</strong> ${idUHN1}</div>` : ``}
                    </div>
                `);

                poly.addTo($scope.uhn1LayerGroup);

                if (idUHN1){
                    if (!$scope.uhn1LayersById[idUHN1]) $scope.uhn1LayersById[idUHN1] = [];
                    $scope.uhn1LayersById[idUHN1].push(poly);
                }
                dibujados++;
            }catch(e){ errores++; }
        });

        try{
            const b = $scope.uhn1LayerGroup.getBounds();
            if (b.isValid()){ $scope.mapUHN.fitBounds(b); }
        }catch(e){}
    };

    $scope.cargarUHNGeoJSON = function(){
    $http.get('js/Nivel1_2.json')
        .then(function(resp){
        $scope.uhnData = resp.data;
        $scope.reconstruirIndiceUHN1();
        $scope.dibujarUHN1Todos();
        if ($scope.oFiltro.UHN1) $scope.resaltarUHN1($scope.oFiltro.UHN1, true);
        })
        .catch(function(err){
        console.error('Error cargando Nivel1_2.json', err);
        });
    };

    
    $scope.resaltarUHN1 = function(idUHN1, hacerFit){
    if (!$scope.uhn1LayerGroup) return;
    
    $scope.uhn1LayerGroup.eachLayer(function(l){
        try{ l.setStyle($scope.estiloUHN1Base()); }catch(_){}
    });
    
    const arr = $scope.uhn1LayersById[idUHN1] || [];
    let bounds = null;
    arr.forEach(l=>{
        try{
        l.setStyle($scope.estiloUHN1Resaltado());
        if (!bounds) bounds = l.getBounds(); else bounds = bounds.extend(l.getBounds());
        l.bringToFront();
        }catch(_){}
    });
    if (hacerFit && bounds && bounds.isValid()){
        $scope.mapUHN.fitBounds(bounds, { padding:[20,20], maxZoom:11 });
    }
    };

    
    $scope.convertirEsriAGeoJSON = function(feature, projectionString) {
        var geometry = null;

        if (feature.geometry) {
            if (feature.geometry.rings) {
                var rings = feature.geometry.rings.map(function(ring) {
                    return ring.map(function(point) {
                        var converted = proj4(projectionString, 'EPSG:4326', [point[0], point[1]]);
                        return [converted[0], converted[1]];
                    });
                });

                geometry = {
                    type: 'Polygon',
                    coordinates: rings
                };
            }
            else if (feature.geometry.paths) {
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
            }
            else if (feature.geometry.x && feature.geometry.y) {
                var converted = proj4(projectionString, 'EPSG:4326', [feature.geometry.x, feature.geometry.y]);
                geometry = {
                    type: 'Point',
                    coordinates: [converted[0], converted[1]]
                };
            }
        }

        return {
            type: 'Feature',
            properties: feature.attributes,
            geometry: geometry
        };
    };

    
    
    $scope._num = v => (v === null || v === undefined) ? NaN : parseFloat(("" + v).replace(",", "."));

    $scope.ctm12ToLatLng = function(este, norte) {
        if (typeof proj4 === 'undefined') return null;
        const x = $scope._num(este), y = $scope._num(norte);
        if (!isFinite(x) || !isFinite(y)) return null;
        try {
            
            const [lng, lat] = proj4($scope.CTM12_PROJECTION, 'EPSG:4326', [x, y]);
            if (!isFinite(lat) || !isFinite(lng)) return null;
            
            if (lat < -5 || lat > 15 || lng < -85 || lng > -65) return null;
            return { lat, lng };
        } catch (e) { console.warn("Fallo conversión CTM12->WGS84:", e); return null; }
    };

    /**
     * Devuelve la mejor lat/lon disponible para un punto:
     * 1) Convierte este_modulo/norte_modulo si existen
     * 2) Usa latitud/longitud si son válidas
     * 3) Intenta convertir este_usuario/norte_usuario o este/norte
     */
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

    $scope.getEsteNorteFromPunto = function(p) {
        var este = $scope.pick(p, ['este', 'x', 'este_x', 'utm_x', 'ctm12_x'], null);
        var norte = $scope.pick(p, ['norte', 'y', 'norte_y', 'utm_y', 'ctm12_y'], null);
        if (este !== null && norte !== null) {
            return {
                este: $scope.numFmt(este, 'm'),
                norte: $scope.numFmt(norte, 'm')
            };
        }
        try {
            if (typeof proj4 !== 'undefined' && p.longitud != null && p.latitud != null) {
                var conv = proj4('EPSG:4326', $scope.CTM12_PROJECTION, [parseFloat(p.longitud), parseFloat(p.latitud)]);
                return {
                    este: $scope.numFmt(conv[0], 'm'),
                    norte: $scope.numFmt(conv[1], 'm')
                };
            }
        } catch (e) { console.warn('No se pudo convertir a CTM12:', e); }
        return { este: '—', norte: '—' };
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

    $scope.buildPopupContent = function(p) {
        
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


        var usuarioHTML = '';
        usuarioHTML += $scope.row('0. Código encuesta', $scope.pick(p, ['codigo_encuesta'], '—'));
        usuarioHTML += $scope.row('1. Código UHN I', codUHN1);
        usuarioHTML += $scope.row('18. Departamento', depto);
        usuarioHTML += $scope.row('21. Municipio', mpio);
        usuarioHTML += $scope.row('26a. Este - x (m)', este_usuario);
        usuarioHTML += $scope.row('26a. Norte - y (m)', norte_usuario);

        var modulo = $scope.oFiltro.idModulo; 
        var moduloHTML = '';

        var nomUHN1 = $scope.pick(p, ['nombre_unidad_1', 'nombre_uhn1', 'und_geo_nivel1'], '—');
        var codUHN1 =  $scope.pick(p, ['codigo_unidad_1', 'cod_uhn1', 'codigo_unidad_1'], '—');
        var nomUHN2 = $scope.pick(p, ['nombre_unidad_2', 'nombre_uhn2', 'und_geo_nivel2'], '—');
        var codUHN2 = $scope.pick(p, ['codigo_unidad_2', 'cod_uhn2', 'codigo_unidad_2', 'codigo_unidad_hidrografica_nivel_2'], '—');
        var este_modulo = $scope.pick(p, ['este_modulo', 'este_m'], '—');
        var norte_modulo = $scope.pick(p, ['norte_modulo', 'norte_m'], '—');

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
            </div>
        `;
    };

    $scope.norm = function(s){
        return (s || '')
            .toString()
            .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .replace(/\s+/g,' ')
            .trim()
            .toUpperCase();
    };

    $scope.procesarMunicipios = function () {
        const feats = ($scope.municipiosData && $scope.municipiosData.features) || [];
        $scope.municipiosIndex = [];     
        $scope.municipiosMap   = {};     
        $scope.municipiosByCode= {};     

        feats.forEach((feature, idx) => {
            const p = feature.properties || feature.attributes || {};
            const nombre = p.Municipio || p.NOM_MPIO || p.NOMBRE || p.MUNICIPIO || null;
            const codigo = p.CODDANE || p.CODIGO || p.MPIO_CCDGO || p.DANE_MPIO || p.COD_MPIO || null;

            const item = {
            norm: $scope.norm(nombre || ''),
            nombre: nombre || '',
            codigo: (codigo != null) ? String(codigo).trim() : null,
            feature
            };
            $scope.municipiosIndex.push(item);
            if (item.norm)  $scope.municipiosMap[item.norm] = feature;
            if (item.codigo)$scope.municipiosByCode[item.codigo] = feature;
        });
    };

    $scope.buscarMunicipio = function(entradaUsuario){
        const target = $scope.norm(entradaUsuario);
        if (!$scope.municipiosIndex || $scope.municipiosIndex.length===0) {
            console.warn('Índice vacío, llamando procesarMunicipios...');
            $scope.procesarMunicipios();
        }

        
        let hit = $scope.municipiosIndex.find(x => x.norm === target);

        
        if (!hit) hit = $scope.municipiosIndex.find(x => x.norm.includes(target) || target.includes(x.norm));

        
        if (!hit && /^\d{4,6}$/.test(entradaUsuario)) {
            hit = $scope.municipiosIndex.find(x => x.codigo === String(entradaUsuario).trim());
        }

        
        if (!hit) hit = $scope.municipiosIndex.find(x => x.norm.startsWith(target));

        if (!hit) {
            console.warn('No se encontró municipio para:', entradaUsuario, 'target:', target);
            
            const sug = $scope.municipiosIndex
            .filter(x => x.norm.includes(target.split(' ')[0] || ''))
            .slice(0,5)
            .map(x => x.nombre);
            console.log('Sugerencias:', sug);
            return null;
        }
        return hit.feature;
    };

    $scope.filtrarPorMunicipio = function(nombreUsuario) {
        if (!nombreUsuario || !$scope.map2) return;

        const feature = $scope.buscarMunicipio(nombreUsuario);
        if (!feature) return;

        $scope.dibujarMunicipio(feature);
    };

    $scope.dibujarMunicipio = function (municipioFeature) {
        try {
            if ($scope.municipioLayer) { $scope.map2.removeLayer($scope.municipioLayer); $scope.municipioLayer = null; }

            
            let gj = null;
            if (municipioFeature && municipioFeature.type === 'Feature' && municipioFeature.geometry) {
            gj = municipioFeature;
            } else if (municipioFeature && municipioFeature.type === 'FeatureCollection') {
            
            gj = (municipioFeature.features || []).find(f => f && f.geometry) || null;
            } else if (municipioFeature && municipioFeature.geometry && (municipioFeature.geometry.coordinates || municipioFeature.geometry.rings)) {
            
            gj = { type:'Feature', geometry: municipioFeature.geometry, properties: (municipioFeature.properties||municipioFeature.attributes||{}) };
            }

            if (!gj || !gj.geometry) { console.error('Sin geometría'); return; }

            $scope.municipioLayer = L.geoJSON(gj, {
            style: $scope.styleMunicipioSel,
            onEachFeature: function (f, lyr) {
                const props = f.properties || {};
                lyr.bindPopup($scope.popupMunicipioHTML(props)).openPopup();

                const name = props.Municipio || props.NOM_MPIO || props.NOMBRE || 'Municipio';
                lyr.bindTooltip(name, { direction:'top', sticky:true, opacity:0.9 });

                
                if ($scope._munSel && $scope._munSel !== lyr) {
                try { $scope._munSel.setStyle($scope.styleMunicipioBase); } catch(_) {}
                }
                $scope._munSel = lyr;
                try { lyr.bringToFront(); } catch(_){}
            }
            }).addTo($scope.map2);

            try { $scope.map2.fitBounds($scope.municipioLayer.getBounds(), { padding:[10,10], maxZoom: 11 }); } catch(_){}

        } catch (e) {
            console.error('dibujarMunicipio error:', e);
        }
    };

    $scope.convertirCoordenadasMunicipio = function(rings) {
        var coordenadasConvertidas = [];

        rings.forEach(function(ring) {
            var anillo = [];
            for (var i = 0; i < ring.length; i += 2) {
                if (i + 1 < ring.length) {
                    var x = parseFloat(ring[i]);
                    var y = parseFloat(ring[i + 1]);

                    if (!isNaN(x) && !isNaN(y) && x !== 0 && y !== 0) {
                        var latLng = $scope.convertirProyeccionALatLng(x, y);

                        if (!isNaN(latLng.lat) && !isNaN(latLng.lng)) {
                            anillo.push([latLng.lat, latLng.lng]);
                        }
                    }
                }
            }

            if (anillo.length > 2) {
                coordenadasConvertidas.push(anillo);
            }
        });

        return coordenadasConvertidas;
    };

    
    $scope._numCO = function (v) {
    
    if (v == null || v === '') return '';
    const n = Number(String(v).replace(/\./g,'').replace(',', '.'));
    if (!isFinite(n)) return v;
    return n.toLocaleString('es-CO');
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

$scope.convertirProyeccionALatLng = function(x, y) {
    if (!x || !y || isNaN(x) || isNaN(y)) {
        return { lat: 0, lng: 0 };
    }

    var falseEasting = 5000000.0;
    var falseNorthing = 2000000.0;
    var centralMeridian = -73.0;
    var latitudeOfOrigin = 4.0;

    var x_adj = x - falseEasting;
    var y_adj = y - falseNorthing;

    var lat = latitudeOfOrigin + (y_adj / 111000.0);
    var lng = centralMeridian + (x_adj / (111000.0 * Math.cos(lat * Math.PI / 180)));

    if (lat < -5 || lat > 15 || lng < -85 || lng > -65) {
        lat = 4.0 + ((y - 2000000) / 111000);
        lng = -73.0 + ((x - 5000000) / (111000 * 0.8));
    }

    return { lat: lat, lng: lng };
};

$scope.limpiarMunicipio = function() {
    $scope.municipioSeleccionado = false;

    if ($scope.municipioLayer && $scope.map2) {
        $scope.map2.removeLayer($scope.municipioLayer);
        $scope.municipioLayer = null;
    }

    
    if ($scope._munSel) {
        try { $scope._munSel.setStyle($scope.styleMunicipioBase); } catch(_) {}
        $scope._munSel = null;
    }

    if ($scope.municipiosLayerGroup) {
        $scope.municipiosLayerGroup.eachLayer(function(layer) {
        try { layer.setStyle($scope.styleMunicipioBase); } catch(_) {}
        });
    }

    $scope.map2.setView([4.7, -74.0], 9);
};

$scope._ensureUHN12 = function(cb){
    if ($scope.uhn12Data && Object.keys($scope.uhn12ByName).length) { cb && cb(); return; }
    $http.get('js/Nivel1_2.json').then(function(resp){
        $scope.uhn12Data = resp.data || {};
        $scope.uhn12ByName = {};
        const feats = ($scope.uhn12Data.features || []);
        feats.forEach(f => {
        const a = f.attributes || f.properties || {};
        const nombre = a.N_NV_SUB || a.NOMBRE_UHN1 || a.NOMBRE || null;
        if (!nombre) return;
        const k = _normTxtUHN(nombre);
        if (!$scope.uhn12ByName[k]) $scope.uhn12ByName[k] = [];
        $scope.uhn12ByName[k].push(f);
        });
        cb && cb();
    }).catch(function(err){
        console.error('Error cargando js/Nivel1_2.json', err);
    });
};


function _getUHN1IdFromAttrs(attrs){
  const k = ['ID_UHN1','ID_N1','ID_NIVEL1','ID','COD_UHN1','COD_N1','CODIGO_N1'];
  for (let i=0;i<k.length;i++){
    const v = attrs && attrs[k[i]];
    const n = (v!=null && v!=='') ? parseInt(v,10) : NaN;
    if (isFinite(n)) return n;
  }
  return null;
}


$scope._clearUHN1Highlight = function(){
    try{
        
        if ($scope.uhn1OutlineLayerUHN){ $scope.mapUHN.removeLayer($scope.uhn1OutlineLayerUHN); $scope.uhn1OutlineLayerUHN = null; }
        
        if ($scope._prevUHN1Styles){
        Object.keys($scope._prevUHN1Styles).forEach(lid=>{
            const rec = $scope._prevUHN1Styles[lid];
            if (rec && rec.layer && rec.base){
            try{ rec.layer.setStyle(rec.base); }catch(_){}
            }
        });
        }
    }catch(_){}
    $scope._prevUHN1Styles = {};
};




$scope.filtrarPorUHN1_Nombre = function (nombreUHN1) {
    if (!nombreUHN1 || !$scope.mapUHN) { return; }
    $scope._ensureUHN12(function () {
        const target = _normTxtUHN(nombreUHN1);
        let features = $scope.uhn12ByName[target] || [];

        if (features.length === 0) {
        const keys = Object.keys($scope.uhn12ByName);
        for (let i = 0; i < keys.length && features.length === 0; i++) {
            const k = keys[i];
            if (k.includes(target) || target.includes(k)) { features = $scope.uhn12ByName[k]; }
        }
        }

        if (!features || features.length === 0) {
        console.warn('UHN I no encontrada en Nivel1_2.json:', nombreUHN1);
        $scope._clearUHN1Highlight();
        return;
        }

        $scope.dibujarUHN1EnMapaUHN(features, nombreUHN1);
    });
};






$scope.dibujarUHN1EnMapaUHN = function (features, nombreUHN1) {
    try {
        if (!$scope.mapUHN) return;

        
        $scope._clearUHN1Highlight();

        
        const CTM12 = $scope.CTM12_PROJECTION;
        const gjList = [];
        const idsEncontrados = new Set();

        features.forEach(feat => {
        const gj = $scope.convertirEsriAGeoJSON(feat, CTM12);
        if (gj && gj.geometry) gjList.push(gj);

        const a = feat.attributes || feat.properties || {};
        const idUHN1 = _getUHN1IdFromAttrs(a);
        if (isFinite(idUHN1)) idsEncontrados.add(idUHN1);
        });

        if (gjList.length === 0) { console.error('Sin geometrías válidas para UHN I'); return; }

        
        
        $scope._prevUHN1Styles = {};
        idsEncontrados.forEach(id=>{
        const layers = $scope.uhn1LayersById && $scope.uhn1LayersById[id];
        if (layers && layers.length){
            layers.forEach(l=>{
            try{
                
                $scope._prevUHN1Styles[l._leaflet_id] = {
                layer: l,
                base: { fillOpacity: l.options.fillOpacity, weight: l.options.weight, color: l.options.color }
                };
                
                l.setStyle({
                fillOpacity: Math.min(1, (l.options.fillOpacity || 0.6) + 0.2),
                weight: Math.max(2, (l.options.weight || 2) + 1)
                });
                l.bringToFront();
            }catch(_){}
            });
        }
        });

        
        $scope.uhn1OutlineLayerUHN = L.geoJSON(gjList, {
        style: { color: '#FF6B35', weight: 4, opacity: 1, fillOpacity: 0, fill: false, interactive: false }
        }).addTo($scope.mapUHN);
        $scope.uhn1OutlineLayerUHN.bringToFront();

        
        const a0 = (features[0].attributes || features[0].properties || {});
        const codigo = a0.CÓDIGO || a0.CODIGO || '';
        $scope.uhn1OutlineLayerUHN.bindPopup(`
        <div style="font-family:Arial, sans-serif; min-width:220px;">
            <div style="background:linear-gradient(45deg,#FF6B35,#FFE135);color:#fff;padding:8px;margin:-8px -8px 8px -8px;border-radius:4px 4px 0 0;">
            <strong style="font-size:16px;">📍 ${nombreUHN1}</strong>
            </div>
            ${codigo ? `<strong>🔢 Código:</strong> ${codigo}<br>` : ``}
        </div>
        `);

        
        const b = $scope.uhn1OutlineLayerUHN.getBounds();
        if (b && b.isValid()) { $scope.mapUHN.fitBounds(b, { padding: [20, 20], maxZoom: 10 }); }

        
        setTimeout(() => { try { $scope.uhn1OutlineLayerUHN.openPopup(); } catch (_) {} }, 250);

    } catch (err) {
        console.error('Error al resaltar UHN I en mapa UHN:', err);
    }
};

    

$scope.aplicarFiltroCuencaSeleccionada = function() {
  try {
    if (typeof(Storage) !== "undefined") {
      const cuencaData = sessionStorage.getItem('cuencaSeleccionada');
      if (!cuencaData) return;

      const parsedData = JSON.parse(cuencaData);
      if (!parsedData.aplicarFiltro || !parsedData.cuenca) return;

      const mapaCuencasASubZona = {
        'Río Bogotá': 901,
        'Río Garagoa': 902,
        'Río Guavio': 900,
        'Río Guayuriba': 899,           
        'Río Negro': 905,
        'Río Seco y Otros Directos': 903,
        'Río Suarez': 904,
        'Río Sumapaz': 898,
        'Río Carare Minero': 906
      };

      const subZonaId = mapaCuencasASubZona[parsedData.cuenca.nombre];
      if (subZonaId) {
        setTimeout(function() {
          const selectorSubZona = document.getElementById('subzona-hidrografica');
          if (selectorSubZona?.selectize) {
            selectorSubZona.selectize.setValue(subZonaId, true);
          }
        }, 2000);
      }

      parsedData.aplicarFiltro = false;
      sessionStorage.setItem('cuencaSeleccionada', JSON.stringify(parsedData));
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
                servicioGeneral.selectize("subzona-hidrografica", {
                    options: $scope.aSubZonaHidrografica, maxItems: 1, valueField: 'id', labelField: 'descripcion', searchField: 'descripcion', create: false,
                    onChange: function(value) {
                        $scope.oFiltro.idSubZona = value ? parseInt(value) : null;
                        $scope.cargarPuntosSesion2();
                        $scope.graficosSesion3();
                        $scope.refrescarMapaDemanda();
                        $scope.cargarDatosGrandesUsuarios();
                        $scope.actualizarResumenCounters();
                    }
                });
                $scope.filtroSesion4();
                setTimeout(function() { $scope.cargarTodasLasGraficas(); }, 1500);
                $scope.updateLoading();
            },
            "error": function() { $scope.updateLoading(); }
        });
    };

    $scope.cargarTodasLasGraficas = function () {
    const modulos = [1, 2, 3];
    const contenedores = { 1: "grafico-captacion", 2: "grafico-vertimiento", 3: "grafico-ocupacion" };
    const titulos = { 1: "Captaciones y usos", 2: "Vertimientos y actividad económica", 3: "Ocupaciones de cauce y tipo de obra" };
    if (!$scope.graficas) $scope.graficas = [];
    let graficasCompletadas = 0;

modulos.forEach(idModulo => {
  servicioGeneral.enviarAjax({
    url: "ControlEncuestas.php",
    data: $scope._filtros({ method: "reporteConteoModulo", idModulo }),
    success: function(response) {
      const { detalle } = $scope._normalizarConteo(response);
      const aEtiquetas = [], aValores = [];
(detalle || []).forEach(d => {
  
  const countTxt = (typeof d.cantidad_encuestas !== 'undefined') ? ` (${d.cantidad_encuestas})` : ` (${d.cantidad})`;

  
  

  
  const valorBarraBruto = $scope._valorParaGrafica(d);

  const valorBarra = (isFinite(valorBarraBruto) && valorBarraBruto > 0) ? valorBarraBruto : 0;
  if (valorBarra > 0) {
    aEtiquetas.push(`${d.nombre}${countTxt}`);
    aValores.push(valorBarra);
  }
});


      if (aValores.length > 0) {
        const contenedor = document.getElementById(contenedores[idModulo]);
        if (contenedor) {
          if ($scope.graficas[idModulo]) { try { $scope.graficas[idModulo].destroy(); } catch(e){} }
          $scope.graficas[idModulo] = servicioGeneral.graficaBarra(
            aEtiquetas, aValores, titulos[idModulo], "bar", contenedores[idModulo]
          );
          if (idModulo !== 1) { document.getElementById(`grafico-container-${idModulo}`).style.display = 'none'; }
        }
      }

      graficasCompletadas++;
      if (graficasCompletadas >= modulos.length) { $scope.updateLoading(); }
    },
    error: function() {
      graficasCompletadas++;
      if (graficasCompletadas >= modulos.length) { $scope.updateLoading(); }
    }
  });
});


    
    setTimeout(function(){ $scope.cambiarGrafica(1); }, 0);
    };


    $scope.filtroSesion2 = () => {
        servicioGeneral.enviarAjax({
            "url": "ControlEncuestas.php",
            "data": { "method": "sesionReporte2" },
            "success": (response) => {
                
                $scope.aCatalogoMunicipios = response.municipio || [];
                $scope.aUHN1 = response.unidad_hidrografica_nivel_1 || [];
                $scope.aUHN2 = response.unidad_hidrografica_nivel_2 || [];
                $scope.aDepartamentos = response.departamento || [];

                
                $scope.mapUHN1toUHN2 = $scope.agruparUHN2porUHN1($scope.aUHN1, $scope.aUHN2);

                
                $scope.uhn1NameToId = {};
                ($scope.aUHN1 || []).forEach(u => {
                    const k = (u.descripcion || '').toUpperCase().trim();
                    if (k) $scope.uhn1NameToId[k] = u.id;
                });
                
                $scope.mpioToUHN1Id = {};
                ($scope.aCatalogoMunicipios || []).forEach(m => {
                    const k = (m.descripcion || '').toUpperCase().trim();
                    if (k && m.unidad_geografica != null) $scope.mpioToUHN1Id[k] = parseInt(m.unidad_geografica,10);
                });

                
                
                servicioGeneral.selectize("unidad-hidrografica-nivel-1", {
                    options: $scope.aUHN1,
                    maxItems: 1, valueField: 'id', labelField: 'descripcion', searchField: 'descripcion', create: false,
                    onChange: function(value) {
                        var v = (value !== null && value !== '') ? parseInt(value) : null;
                        $scope.onUHN1Change(v, true);
                    }
                });

                
                servicioGeneral.selectize("unidad-hidrografica-nivel-2", {
                    options: [],
                    maxItems: 1, valueField: 'id', labelField: 'descripcion', searchField: 'descripcion', create: false,
                    onChange: function(value) {
                        $scope.oFiltro.UHN2 = (value !== null && value !== '') ? parseInt(value) : null;
                        $scope.cargarPuntosSesion2();
                        $scope.graficosSesion3();
                        $scope.refrescarMapaDemanda();
                        $scope.cargarDatosGrandesUsuarios();
                        $scope.actualizarResumenCounters();
                    }
                });

                
                servicioGeneral.selectize("departamento", {
                    options: $scope.aDepartamentos,
                    maxItems: 1, valueField: 'id', labelField: 'descripcion', searchField: 'descripcion', create: false,
                    onChange: function() {
                        
                    }
                });

                
                servicioGeneral.selectize("municipio", {
                    options: [],
                    maxItems: 1, valueField: 'id', labelField: 'descripcion', searchField: 'descripcion', create: false,
                    onChange: function(value) {
                        $scope.onMunicipioChange(value);
                    }
                });

                $scope.updateLoading();
                $scope.aplicarFiltroCuencaSeleccionada();
                $scope.actualizarResumenCounters();
                $scope.cargarPuntosSesion2();       
                $scope.graficosSesion3();           
                $scope.cargarDatosGrandesUsuarios();
                $scope.refrescarMapaDemanda();      
            },
            "error": function() { $scope.updateLoading(); }
        });
    };

    
    $scope.filtroSesion4 = () => {
    servicioGeneral.enviarAjax({
        url: "ControlEncuestas.php",
        data: $scope._filtros({ method: "sesionReporte4" }),
        success: function(response) {
        $scope.aCamposUsosDemanda = response.demanda;
        let vals = [], labs = [];
        ($scope.aCamposUsosDemanda || []).forEach(d => {
            const v = parseFloat(d.cantidad);
            if (isFinite(v) && v > 0) {
            vals.push(v);
            const countTxt = (typeof d.encuestas !== 'undefined') ? ` (${d.encuestas})` : '';
            labs.push(`${d.nombre}${countTxt}`);
            }
        });
        if (vals.length > 0) {
            if ($scope.grafica) { try { $scope.grafica.destroy(); } catch(e){} }
            $scope.grafica = servicioGeneral.graficaBarra(labs, vals, "Demanda Hídrica por Sector", "doughnut", "grafico-demanda-hidrica");
        }

        
        $scope.filtroSesion2();
        },
        error: function() { $scope.updateLoading(); $scope.filtroSesion2(); }
    });
    };


$scope.refrescarMapaDemanda = function () {
  servicioGeneral.enviarAjax({
    url: "ControlEncuestas.php",
    data: $scope._filtros({ method: "sesionReporte4" }),
    success: function(response) {
      $scope.aCamposUsosDemanda = response.demanda;
      let vals = [], labs = [];
      ($scope.aCamposUsosDemanda || []).forEach(d => {
        const v = parseFloat(d.cantidad);
        if (isFinite(v) && v > 0) {
          vals.push(v);
          const countTxt = (typeof d.encuestas !== 'undefined') ? ` (${d.encuestas})` : '';
          labs.push(`${d.nombre}${countTxt}`);
        }
      });
      if (vals.length > 0) {
        if ($scope.grafica) { try { $scope.grafica.destroy(); } catch(e){} }
        $scope.grafica = servicioGeneral.graficaBarra(labs, vals, "Demanda Hídrica por Sector", "doughnut", "grafico-demanda-hidrica");
      }
    }
  });
};


$scope.onUHN1Change = function(uhn1Id, dispararPuntos) {
    $scope.oFiltro.UHN1 = uhn1Id;
    $scope.oFiltro.UHN2 = null;
    $scope.oFiltro.idMunicipio = null;

    var listaUHN2 = ($scope.mapUHN1toUHN2 && uhn1Id) ? ($scope.mapUHN1toUHN2[uhn1Id] || []) : [];
    $scope.cargarOpcionesSelectize('unidad-hidrografica-nivel-2', listaUHN2);

    var elementosMunicipio = ($scope.aCatalogoMunicipios || []).filter(m => m.unidad_geografica === uhn1Id);
    $scope._municipiosFiltrados = elementosMunicipio;
    $scope.cargarOpcionesSelectize('municipio', elementosMunicipio);

    if (dispararPuntos) {
        $scope.cargarPuntosSesion2();
        $scope.graficosSesion3();
        $scope.refrescarMapaDemanda();
        $scope.cargarDatosGrandesUsuarios();
        $scope.actualizarResumenCounters();
    }
    var uhn1Obj = ($scope.aUHN1 || []).find(u => u.id == uhn1Id);
    if (uhn1Obj && uhn1Obj.descripcion) {
        setTimeout(function(){ $scope.filtrarPorUHN1_Nombre(uhn1Obj.descripcion); }, 300);
    }
    
};


$scope.onMunicipioChange = function(value) {
    var szMunicipio = (value !== null && value !== '') ? parseInt(value) : null;
    $scope.oFiltro.idMunicipio = szMunicipio;

    if (szMunicipio) {
        $scope.municipioSeleccionado = true;
        var elSel = ($scope._municipiosFiltrados || []).find(m => m.id == szMunicipio);
        $scope.cargarPuntosSesion2();
        if (elSel && elSel.descripcion) {
            setTimeout(function() { $scope.filtrarPorMunicipio(elSel.descripcion); }, 500);
        }
        $scope.refrescarMapaDemanda();
    } else {
        $scope.municipioSeleccionado = false;
        $scope.cargarPuntosSesion2();
        $scope.limpiarMunicipio();
        $scope.refrescarMapaDemanda();
    }
    $scope.graficosSesion3();
    $scope.cargarDatosGrandesUsuarios();
    $scope.actualizarResumenCounters();
};


$scope._ultimoConjuntoPuntos = [];
$scope.heatLayer3 = null;
$scope.heatLegend3 = null;
$scope.titleCtrl3 = null;
$scope.northCtrl2 = null;
$scope.northCtrl3 = null;


function addNorthControl(map, scopeKey) {
    if (!map) return;
    if ($scope[scopeKey]) { map.removeControl($scope[scopeKey]); $scope[scopeKey] = null; }
    const North = L.Control.extend({
        options: { position: 'topright' },
        onAdd: function() {
            const div = L.DomUtil.create('div', 'leaflet-control');
            div.style.background = 'rgba(255,255,255,0.9)';
            div.style.padding = '6px 8px';
            div.style.borderRadius = '6px';
            div.style.boxShadow = '0 1px 3px rgba(0,0,0,0.25)';
            div.style.fontFamily = 'Arial, sans-serif';
            div.style.fontSize = '12px';
            div.style.textAlign = 'center';
            div.innerHTML = `
                <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                    <div style="width:0;height:0;border-left:6px solid transparent;border-right:6px solid transparent;border-bottom:12px solid #197091;"></div>
                    <div style="font-weight:700;color:#197091;">N</div>
                </div>`;
            return div;
        }
    });
    $scope[scopeKey] = new North();
    map.addControl($scope[scopeKey]);
}


$scope.addHeatmapTitleAndLegend = function(minV, maxV) {
    if (!$scope.map3) return;
    if ($scope.titleCtrl3) { $scope.map3.removeControl($scope.titleCtrl3); $scope.titleCtrl3 = null; }
    if ($scope.heatLegend3) { $scope.map3.removeControl($scope.heatLegend3); $scope.heatLegend3 = null; }

    $scope.titleCtrl3 = L.control({ position: 'topleft' });
    $scope.titleCtrl3.onAdd = function() {
        const div = L.DomUtil.create('div', 'leaflet-control');
        div.style.background = 'rgba(25,112,145,0.95)';
        div.style.color = '#fff';
        div.style.padding = '8px 12px';
        div.style.borderRadius = '6px';
        div.style.boxShadow = '0 1px 3px rgba(0,0,0,0.25)';
        div.style.fontFamily = 'Arial, sans-serif';
        div.style.fontWeight = '700';
        div.style.fontSize = '13px';
        return div;
    };
    $scope.titleCtrl3.addTo($scope.map3);

    $scope.heatLegend3 = L.control({ position: 'bottomright' });
    $scope.heatLegend3.onAdd = function() {
        const div = L.DomUtil.create('div', 'leaflet-control');
        div.style.background = 'rgba(255,255,255,0.9)';
        div.style.padding = '8px 10px';
        div.style.borderRadius = '6px';
        div.style.boxShadow = '0 1px 3px rgba(0,0,0,0.25)';
        div.style.fontFamily = 'Arial, sans-serif';
        const gradient = 'linear-gradient(90deg,#7d7174,#3699c4,#29a398,#197091,#c1d043)';
        div.innerHTML = `
            <div style="font-weight:700; margin-bottom:6px; color:#197091;">Convenciones</div>
            <div style="display:flex;align-items:center;gap:8px;">
            <div style="font-size:12px;color:#333;">Bajo</div>
                <div style="width:140px;height:10px;background:${gradient};border:1px solid #197091;"></div>
                <div style="flex:1;"></div>
                <div style="font-size:12px;color:#333;">Alto</div>
            </div>
            <div style="margin-top:6px; font-size:11px; color:#333;">
                Intensidad ~ 254. Total (L/s) normalizado<br>
                Min: ${$scope.numFmt(minV,'L/s')} · Max: ${$scope.numFmt(maxV,'L/s')}
            </div>`;
        return div;
    };
    $scope.heatLegend3.addTo($scope.map3);
};

    
$scope.cargarPuntosSesion2 = () => {
  const oDatos = {
    method: "puntosMapaPredio",
    idModulo: $scope.oFiltro.idModulo,
    UHN1: $scope.oFiltro.UHN1,
    UHN2: $scope.oFiltro.UHN2,
    idMunicipio: $scope.oFiltro.idMunicipio
  };

  if ($scope.aClusterMarkers && $scope.map2) {
    $scope.map2.removeLayer($scope.aClusterMarkers);
  }
  $scope.aClusterMarkers = L.markerClusterGroup();

  servicioGeneral.enviarAjax({
    url: "ControlEncuestas.php",
    data: oDatos,
    success: function(response) {
      
      let puntos = Array.isArray(response) ? response : (Array.isArray(response?.puntos) ? response.puntos : []);
      $scope._ultimoConjuntoPuntos = puntos;
      $scope._puntosCargados = true;

      
      $scope.actualizarResumenDesdePuntos(puntos);

      const posicionesUsadas = [];
      (puntos || []).forEach((punto) => {
        const ll = $scope.getBestLatLng(punto);
        if (!ll) return;

        const marker = L.marker([ll.lat, ll.lng]);
        const popupContent = $scope.buildPopupContent(punto);
        marker.bindPopup(popupContent, { maxWidth: 520, className: 'custom-popup' });
        marker.on('click', function() { console.log('Punto seleccionado:', punto); });

        $scope.aClusterMarkers.addLayer(marker);
        posicionesUsadas.push([ll.lat, ll.lng, punto]);
      });

      if ($scope.map2) $scope.map2.addLayer($scope.aClusterMarkers);

      
      if (posicionesUsadas.length > 0 && $scope.map2 && !$scope.municipioSeleccionado) {
        $scope.map2.setView(new L.LatLng(posicionesUsadas[0][0], posicionesUsadas[0][1]), 9);
      }

      
      if ($scope.map3 && typeof L.heatLayer === 'function') {
        if ($scope.heatLayer3) { $scope.map3.removeLayer($scope.heatLayer3); $scope.heatLayer3 = null; }

        let min = Infinity, max = -Infinity;
        posicionesUsadas.forEach(([, , p]) => {
          const v = parseFloat(p.valor_captado);
          if (isFinite(v)) { if (v < min) min = v; if (v > max) max = v; }
        });
        if (!isFinite(min)) { min = 0; max = 1; }
        const span = (max - min) || 1;

        const heatData = posicionesUsadas.map(([lat, lng, p]) => {
          const v = parseFloat(p.valor_captado);
          const w = isFinite(v) ? ((v - min) / span) : 0.1;
          return [lat, lng, Math.max(0.05, Math.min(1, w))];
        });

        const customGradient = { 0: '#7d7174', 0.25: '#3699c4', 0.5: '#29a398', 0.75: '#197091', 1: '#c1d043' };
        $scope.heatLayer3 = L.heatLayer(heatData, { radius: 25, maxZoom: 9, gradient: customGradient }).addTo($scope.map3);

        if (posicionesUsadas.length > 0) {
          const fg = L.featureGroup(posicionesUsadas.map(([lat, lng]) => L.marker([lat, lng])));
          const b = fg.getBounds();
          if (b.isValid()) $scope.map3.fitBounds(b);
        }

        $scope.addHeatmapTitleAndLegend(min, max);
      }

      
      $scope.calcularIUAporUHN1FromPuntos(puntos);
      $scope.crearGraficoIUA();
      $scope.reestilizarMunicipios();
      $scope.addIUALegendMapa2();

      $scope.graficosSesion3();
    }
  });
};


$scope.graficosSesion3 = () => {
  if ($scope.oFiltro.idModulo == null) return;

  servicioGeneral.enviarAjax({
    url: "ControlEncuestas.php",
    data: $scope._filtros({ method: "reporteConteoModulo", idModulo: $scope.oFiltro.idModulo }),
    success: function(response) {
      const { detalle } = $scope._normalizarConteo(response);
      const aEtiquetas = [], aValores = [];
        (detalle || []).forEach(d => {
        const countTxt = (typeof d.cantidad_encuestas !== 'undefined') ? ` (${d.cantidad_encuestas})` : ` (${d.cantidad})`;

        
        

        
        const valorBarraBruto = $scope._valorParaGrafica(d);

        const valorBarra = (isFinite(valorBarraBruto) && valorBarraBruto > 0) ? valorBarraBruto : 0;
        if (valorBarra > 0) {
            aEtiquetas.push(`${d.nombre}${countTxt}`);
            aValores.push(valorBarra);
        }
        });


      let graficoId, titulo;
      if ($scope.oFiltro.idModulo === 1) { graficoId = "grafico-captacion";  titulo = "Captaciones y usos"; }
      else if ($scope.oFiltro.idModulo === 2) { graficoId = "grafico-vertimiento"; titulo = "Vertimientos y actividad económica"; }
      else if ($scope.oFiltro.idModulo === 3) { graficoId = "grafico-ocupacion"; titulo = "Ocupaciones de cauce y tipo de obra"; }
      else { return; }

      if ($scope.graficas[$scope.oFiltro.idModulo]) { try { $scope.graficas[$scope.oFiltro.idModulo].destroy(); } catch(e){} }
      if (aValores.length > 0) {
        $scope.graficas[$scope.oFiltro.idModulo] = servicioGeneral.graficaBarra(aEtiquetas, aValores, titulo, "bar", graficoId);
      }
      if ($scope.graficaActual) { $scope.cambiarGrafica($scope.graficaActual); }
    }
  });
};



    
    $scope.llamarReporteModulo = function(idModulo) {
        return new Promise(function(resolve) {
            servicioGeneral.enviarAjax({
                "url": "ControlEncuestas.php",
                "data": {
                    "method": "reporteConteoModulo",
                    "idModulo": idModulo,
                    "UHN1": $scope.oFiltro.UHN1,
                    "UHN2": $scope.oFiltro.UHN2,
                    "idMunicipio": $scope.oFiltro.idMunicipio,
                    "idSubZona": $scope.oFiltro.idSubZona
                },
                "success": function(resp) {
                    var total = 0;
                    (resp || []).forEach(function(r) {
                        var n = parseInt(r.cantidad, 10);
                        if (!isNaN(n)) total += n;
                    });
                    resolve(total);
                },
                "error": function() {
                    resolve(0);
                }
            });
        });
    };

    
$scope.llamarReporteResumen = function () {
  return new Promise((resolve) => {
    servicioGeneral.enviarAjax({
      url: "ControlEncuestas.php",
      data: $scope._filtros({ method: "reporteConteoModulo" }), 
      success: function(resp) {
        const { resumen } = $scope._normalizarConteo(resp);
        resolve(resumen || null);
      },
      error: function(){ resolve(null); }
    });
  });
};

$scope.actualizarResumenCounters = function() {
  if ($scope._puntosCargados) {
    
    $scope.actualizarResumenDesdePuntos($scope._ultimoConjuntoPuntos);
    return;
  }
  
  $scope.llamarReporteResumen().then(function(resumen){
    if (resumen) {
      $scope.$applyAsync(function(){
        $scope.oFiltro.captaciones = resumen.CAPTACIONES || 0;
        $scope.oFiltro.vertimiento = resumen.VERTIMIENTOS || 0;
        $scope.oFiltro.ocupacion  = resumen.OCUPACIONES || 0;
        $scope.oFiltro.minera     = resumen.MINERIA || 0;
        $scope.oFiltro.total      = resumen.TOTAL || (
          ($scope.oFiltro.captaciones||0)+($scope.oFiltro.vertimiento||0)+($scope.oFiltro.ocupacion||0)+($scope.oFiltro.minera||0)
        );
      });
    } else {
      
      $scope.$applyAsync(function(){
        $scope.oFiltro.captaciones = 0;
        $scope.oFiltro.vertimiento = 0;
        $scope.oFiltro.ocupacion  = 0;
        $scope.oFiltro.minera     = 0;
        $scope.oFiltro.total      = 0;
      });
    }
  });
};

$scope.totalPorModuloDesdeResumen = function(resumen, idModulo){
  if (!resumen) return 0;
  const k = $scope._claveModulo(idModulo);
  return (k && resumen[k]) ? parseInt(resumen[k],10)||0 : 0;
};





    $scope.limpiarMunicipio = function() {
        $scope.municipioSeleccionado = false;

        if ($scope.municipioLayer && $scope.map2) {
            $scope.map2.removeLayer($scope.municipioLayer);
            $scope.municipioLayer = null;
        }

        if ($scope.municipiosLayerGroup) {
            $scope.municipiosLayerGroup.eachLayer(function(layer) {
                layer.setStyle({
                    color: '#197091',
                    weight: 1,
                    opacity: 0.8,
                    fillColor: '#c1d043',
                    fillOpacity: 0.2
                });
            });
        }

        $scope.map2.setView([4.7, -74.0], 9);
    };

    $scope.cambiarGrafica = function(idGrafica) {
        $scope.graficaActual = idGrafica;
        for (let i = 1; i <= 3; i++) {
            if (document.getElementById(`grafico-container-${i}`)) { document.getElementById(`grafico-container-${i}`).style.display = 'none'; }
        }
        let botones = document.querySelectorAll('.graf-nav .btn');
        botones.forEach(btn => { btn.classList.remove('active'); btn.style.backgroundColor = '#f8f9fa'; btn.style.color = '#197091'; });
        if (document.getElementById(`grafico-container-${idGrafica}`)) { document.getElementById(`grafico-container-${idGrafica}`).style.display = 'block'; }
        let botonActivo = document.querySelectorAll('.graf-nav .btn')[idGrafica - 1];
        if (botonActivo) {
            botonActivo.classList.add('active');
            botonActivo.style.backgroundColor = '#197091';
            botonActivo.style.color = 'white';
        }
    };

    $scope.cargarDatosGrandesUsuarios = function () {
    $scope.grandesUsuarios = [];

    servicioGeneral.enviarAjax({
        url: "ControlEncuestas.php",
        data: $scope._filtros({ method: "grandes_usuarios" }),
        success: function (response) {
        if (Array.isArray(response)) {
            response.forEach(function (u) {
            const litros = parseFloat(u.litros_totales);
            if (isFinite(litros) && litros > 0) {
                $scope.grandesUsuarios.push({
                nombre: u.nombre_usuario,
                demanda: litros
                });
            }
            });
        }

        
        $scope.grandesUsuarios.sort((a, b) => b.demanda - a.demanda);
        const top = $scope.grandesUsuarios.slice(0, 10);

        
        const tbl = document.getElementById('tabla-grandes-usuarios');
        if (tbl) {
            tbl.innerHTML = top.map(u => `
            <tr>
                <td>${u.nombre}</td>
                <td style="text-align:right;">${$scope.numFmt(u.demanda,'L/s')}</td>
            </tr>`).join('');
        }

        
        $scope.crearGraficoGrandesUsuarios(top);
        }
    });
    };

$scope.crearGraficoGrandesUsuarios = function (data) {
  const canvas = document.getElementById('grafico-grandes-usuarios');
  if (!canvas) return;
  const ctx = canvas.getContext('2d'); if (!ctx) return;

  const labels = data.map(d => d.nombre);
  const values = data.map(d => d.demanda);

  if (window.chartGrandesUsuarios) { try { window.chartGrandesUsuarios.destroy(); } catch(e){} }
  window.chartGrandesUsuarios = new Chart(ctx, {
    type: 'bar',
    data: { labels, datasets: [{ label: 'Demanda (L/s)', data: values }] },
    options: {
      responsive: true,
      plugins: { legend: { display: false }, title: { display: true, text: 'Grandes Usuarios (Top 10 por Demanda)' } },
      scales: { y: { beginAtZero: true, title: { display: true, text: 'L/s' } } }
    }
  });
};


   
$scope.crearGraficoIUA = function () {
  if (typeof Chart === 'undefined') { console.warn('Chart.js no está cargado.'); return; }

  const canvas = document.getElementById('grafico-demanda-linea') || document.getElementById('grafico-iua-linea');
  if (!canvas) return;
  const ctx = canvas.getContext('2d'); if (!ctx) return;

  
  const idToName = {};
  ($scope.aUHN1 || []).forEach(u => { idToName[u.id] = (u.descripcion || `UHN1-${u.id}`); });

  const entries = Object.keys($scope.iuaPorUHN1 || {}).map(id => ({
    label: idToName[id] || `UHN1-${id}`,
    valor: $scope.iuaPorUHN1[id].totalLps,
    clase: $scope.iuaPorUHN1[id].clase
  })).sort((a,b) => b.valor - a.valor);

  if (!entries.length) { if (window.lineChart) { try{window.lineChart.destroy();}catch(e){} } return; }

  const labels = entries.map(e => e.label);
  const values = entries.map(e => e.valor);
  const colorMap = { 'Muy alto':'#FF0000','Alto':'#FFA500','Medio':'#FFFF00','Bajo':'#008000','Muy bajo':'#ADD8E6' };
  const pointColors = entries.map(e => colorMap[e.clase] || '#197091');

  if (window.lineChart) { try { window.lineChart.destroy(); } catch(e){} }
  window.lineChart = new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ label: 'Índice de Uso del Agua (L/s)', data: values, fill: false, borderColor: '#197091', tension: 0.3, pointBackgroundColor: pointColors, pointBorderColor: '#ffffff', pointBorderWidth: 2, pointRadius: 5, borderWidth: 2 }] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, title: { display: true, text: 'Índice de Uso del Agua por UHN I' } },
      scales: { y: { beginAtZero: true, title: { display: true, text: 'L/s (suma por UHN I)' } }, x: { title: { display: true, text: 'UHN I' } } }
    }
  });
};



    $scope.exportarPDF = () => {
        $("#ajax-loading").show();

        window.jsPDF = window.jspdf.jsPDF;
        const doc = new jsPDF('p', 'mm', 'a4');
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 10;

        const headerImg = new Image();
        headerImg.crossOrigin = "Anonymous";
        headerImg.src = '/image/top-picture.png';

        headerImg.onload = function() {
            const imgRatio = headerImg.width / headerImg.height;
            const headerWidth = pageWidth - (margin * 2);
            const headerHeight = headerWidth / imgRatio;

            const addHeaderFooter = (pageNum, totalPages) => {
                try {
                    doc.addImage(headerImg, 'PNG', margin, margin, headerWidth, headerHeight);
                } catch (e) {
                    doc.setFontSize(14);
                    doc.setTextColor(25, 112, 145);
                    doc.text('PLAN DE ORDENACIÓN DEL RECURSO HÍDRICO', pageWidth/2, margin + 5, {align: 'center'});
                    doc.text('CENSO DE USUARIOS DEL RECURSO HÍDRICO', pageWidth/2, margin + 12, {align: 'center'});
                }
                doc.setDrawColor(193, 208, 67);
                doc.setLineWidth(0.5);
                doc.line(margin, margin + headerHeight + 2, pageWidth - margin, margin + headerHeight + 2);
                doc.setFontSize(10);
                doc.setTextColor(100);
                doc.line(margin, pageHeight - margin - 10, pageWidth - margin, pageHeight - margin - 10);
                doc.text(`Página ${pageNum} de ${totalPages}`, pageWidth - 30, pageHeight - margin - 3);
            };

            const canvases = document.querySelectorAll('canvas');
            const canvasDisplayValues = [];
            canvases.forEach(canvas => {
                const container = canvas.closest('[id^="grafico-container"]');
                if (container) {
                    canvasDisplayValues.push(container.style.display);
                    container.style.display = 'block';
                }
            });

            const reporteSections = document.querySelectorAll('#reporte > div.row');
            const capturePromises = [];
            reporteSections.forEach(section => {
                if (section.offsetHeight > 0) {
                    const promise = html2canvas(section, { scale: 0.7, useCORS: true, allowTaint: false, backgroundColor: '#ffffff' })
                    .catch(error => {
                        const emptyCanvas = document.createElement('canvas');
                        emptyCanvas.width = 100; emptyCanvas.height = 30;
                        const ctx = emptyCanvas.getContext('2d');
                        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, 100, 30);
                        ctx.fillStyle = '#ff0000'; ctx.font = '10px Arial'; ctx.fillText('Error en esta sección', 5, 15);
                        return emptyCanvas;
                    });
                    capturePromises.push(promise);
                }
            });

            Promise.all(capturePromises).then(sectionCanvases => {
                canvases.forEach((canvas, i) => {
                    const container = canvas.closest('[id^="grafico-container"]');
                    if (container && i < canvasDisplayValues.length) { container.style.display = canvasDisplayValues[i]; }
                });

                let totalPages = 1;
                let currentHeight = margin + headerHeight + 5;
                sectionCanvases.forEach(canvas => {
                    if (canvas && canvas.width > 0) {
                        const imgWidth = pageWidth - 2 * margin;
                        const imgHeight = canvas.height * imgWidth / canvas.width;
                        if (currentHeight + imgHeight > pageHeight - margin - 15) {
                            totalPages++;
                            currentHeight = margin + headerHeight + 5;
                        }
                        currentHeight += imgHeight + 10;
                    }
                });

                let currentPage = 1;
                currentHeight = margin + headerHeight + 5;
                addHeaderFooter(currentPage, totalPages);

                sectionCanvases.forEach(canvas => {
                    if (canvas && canvas.width > 0) {
                        const imgWidth = pageWidth - 2 * margin;
                        const imgHeight = canvas.height * imgWidth / canvas.width;
                        if (currentHeight + imgHeight > pageHeight - margin - 15) {
                            doc.addPage();
                            currentPage++;
                            currentHeight = margin + headerHeight + 5;
                            addHeaderFooter(currentPage, totalPages);
                        }
                        try {
                            const imgData = canvas.toDataURL('image/png');
                            doc.addImage(imgData, 'PNG', margin, currentHeight, imgWidth, imgHeight);
                            currentHeight += imgHeight + 10;
                        } catch (error) {
                            doc.setTextColor(255, 0, 0);
                            doc.text("Error al mostrar esta sección", margin, currentHeight + 10);
                            currentHeight += 20;
                        }
                    }
                });

                doc.save('reporte-cuenca.pdf');
                $("#ajax-loading").hide();
            }).catch(error => {
                console.error("Error general generando PDF:", error);
                $("#ajax-loading").hide();
                alert("Ocurrió un error al generar el PDF. Por favor intente nuevamente.");
            });
        };

        headerImg.onerror = function() {
            console.log("Error cargando imagen de cabecera");
        };
    };

    
    $scope.oFiltro = { "idSubZona": null, "descripcion": null, "idModulo": null, "UHN1": null, "UHN2": null, "idMunicipio": null, "total": 0 };
    $scope.aSubZonaHidrografica = [];
    $scope.aCatalogoMunicipios = [];
    $scope.aClusterMarkers = null;
    $scope.map2 = null;
    $scope.map3 = null;
    $scope.municipioLayer = null;
    $scope.municipiosData = null;
    $scope.municipiosMap = {};
    $scope.municipioSeleccionado = false;

    $scope.aModulos = [
        { idModulo: 1, nombre: 'Captación' },
        { idModulo: 2, nombre: 'Vertimiento' },
        { idModulo: 3, nombre: 'Ocupación de cauce - Obras hidráulicas' },
        { idModulo: 4, nombre: 'Actividad minera' }
    ];

    servicioGeneral.selectize("modulo", {
        options: $scope.aModulos, maxItems: 1, valueField: 'idModulo', labelField: 'nombre', searchField: 'nombre', create: false,
        onChange: function(value) {
            $scope.oFiltro.idModulo = value !== null ? parseInt(value) : null;

            
            $scope.syncTabWithModulo();

            $scope.cargarPuntosSesion2();
            $scope.refrescarMapaDemanda();
            $scope.graficosSesion3();
            $scope.cargarDatosGrandesUsuarios();
            $scope.actualizarResumenCounters();
        }
    });


    
    
    $scope.grafica = null;
    $scope.graficas = [];
    $scope.graficaActual = 1;

    
    $scope.baseLayers = null;
    $scope.layerCtrl2 = null;
    $scope.layerCtrl3 = null;

    function buildBaseLayers() {
        const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
        const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 });
        const esriTopo = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 });
        const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', { maxZoom: 19 });

        return {
            "OSM Básico": osm,
            "ESRI Satélite": esriSat,
            "ESRI Topográfico": esriTopo,
            "Carto Light": cartoLight
        };
    }

    
    $timeout(function() {
        let osmAttrib = 'Map data © OpenStreetMap contributors';

        if (document.getElementById('mapa2')) {
            
            $scope.map2 = new L.Map('mapa2', { attributionControl: false, zoomControl: true });
            $scope.baseLayers = buildBaseLayers();
            const defaultBase2 = $scope.baseLayers["OSM Básico"];
            defaultBase2.addTo($scope.map2);

            $scope.map2.setView(new L.LatLng(4.711161905, -74.16767461), 9);

            
            if ($scope.layerCtrl2) { $scope.map2.removeControl($scope.layerCtrl2); }
            $scope.layerCtrl2 = L.control.layers($scope.baseLayers, {}, { collapsed: true, position: 'topleft' }).addTo($scope.map2);

            
            L.control.scale({ metric: true, imperial: false, position: 'bottomleft' }).addTo($scope.map2);

            
            addNorthControl($scope.map2, 'northCtrl2');

        } else {
            console.error("Error Crítico: El contenedor #mapa2 no fue encontrado en el DOM.");
        }

        if (document.getElementById('mapa3')) {
            $scope.map3 = new L.Map('mapa3', { attributionControl: false, zoomControl: true });
            const defaultBase3 = buildBaseLayers()["OSM Básico"];
            defaultBase3.addTo($scope.map3);

            $scope.map3.setView(new L.LatLng(4.711161905, -74.16767461), 9);

            
            if ($scope.layerCtrl3) { $scope.map3.removeControl($scope.layerCtrl3); }
            $scope.layerCtrl3 = L.control.layers(buildBaseLayers(), {}, { collapsed: true, position: 'topleft' }).addTo($scope.map3);

            
            L.control.scale({ metric: true, imperial: false, position: 'bottomleft' }).addTo($scope.map3);

            
            addNorthControl($scope.map3, 'northCtrl3');

        } else {
            console.error("Error Crítico: El contenedor #mapa3 no fue encontrado en el DOM.");
        }

        
        if (document.getElementById('mapa-uhn')) {
        $scope.mapUHN = new L.Map('mapa-uhn', { attributionControl:false, zoomControl:true });
        const baseUHN = buildBaseLayers()["OSM Básico"];
        baseUHN.addTo($scope.mapUHN);
        $scope.mapUHN.setView(new L.LatLng(4.711161905, -74.16767461), 8);
        
        L.control.scale({ metric:true, imperial:false, position:'bottomleft' }).addTo($scope.mapUHN);
        addNorthControl($scope.mapUHN, 'northCtrlUHN');
        } else {
        console.error("El contenedor #mapa-uhn no existe en el DOM.");
        }

        
        $scope.cargarMunicipiosGeoJSON();

        
        $scope.cargarUHNGeoJSON();

        $scope.filtroSubZonaHidrografica();

    }, 0);
});

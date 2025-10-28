<?php

error_reporting(E_ALL);
ini_set('display_errors', 'true');
ini_set('display_startup_errors', 'true');

include '/var/www/visor.movilencuesta.com/html/php/core/config.php';
include '/var/www/visor.movilencuesta.com/html/php/core/DataBase.php';

class Kobo extends DataBase {
    public function __construct() 
    {
        parent::__construct();
    }
    
    public function __destruct() 
    {
        $this->stm = null;
        $this->pdo = null;
    }

    public function encuestas() 
    {
        $this->sSql = "
            SELECT 
                e.*, 
                ae.id AS id_actividades_extraccion,
                cef.id AS id_captacion_en_fuente,
                fc.id AS id_fuente_captacion,
                frv.id AS id_fuente_receptora_vertimiento,
                iamca.id AS id_informacion_actividad_minera_cuerpos_agua,
                ifoc.id AS id_informacion_fuente_ocupacion_cauce,
                ijc.id AS id_informacion_juridica_captacion,
                ijv.id AS id_informacion_juridica_vertimiento,
                iocoh.id AS id_informacion_ocupacion_cauce_obra_hidraulica,
                iv.id AS id_informacion_vertimiento,
                p.id AS id_predio,
                urh.id AS id_uso_recurso_hidrico,
                ue.id AS id_usuario_encuesta
            FROM dbo.encuesta e
                LEFT JOIN dbo.actividades_extraccion ae ON (ae.id_encuesta = e.id)
                LEFT JOIN dbo.captacion_en_fuente cef ON (cef.id_encuesta = e.id)
                LEFT JOIN dbo.fuente_captacion fc ON (fc.id_encuesta = e.id)
                LEFT JOIN dbo.fuente_receptora_vertimiento frv ON (frv.id_encuesta = e.id)
                LEFT JOIN dbo.informacion_actividad_minera_cuerpos_agua iamca ON (iamca.id_encuesta = e.id)
                LEFT JOIN dbo.informacion_fuente_ocupacion_cauce ifoc ON (ifoc.id_encuesta = e.id)
                LEFT JOIN dbo.informacion_juridica_captacion ijc ON (ijc.id_encuesta = e.id)
                LEFT JOIN dbo.informacion_juridica_vertimiento ijv ON (ijv.id_encuesta = e.id)
                LEFT JOIN dbo.informacion_ocupacion_cauce_obra_hidraulica iocoh ON (iocoh.id_encuesta = e.id)
                LEFT JOIN dbo.informacion_vertimiento iv ON (iv.id_encuesta = e.id)
                LEFT JOIN dbo.predio p ON (p.id_encuesta = e.id)
                LEFT JOIN dbo.uso_recurso_hidrico urh ON (urh.id_encuesta = e.id)
                LEFT JOIN dbo.usuario_encuesta ue ON (ue.id_encuesta = e.id)
        ";
        $this->aExecute = array();
        $this->execQuery();
        $aEncuestasIndices = [];

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oEncuesta = (object) $r;
			$aEncuestasIndices[$oEncuesta->koboid] = $oEncuesta;
        }

        return $aEncuestasIndices;
    }

    public function execCurl(string $sUrl) 
    {
        $ch = curl_init($sUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , 'Authorization: Token 59d0450b1855399063bfab8ce8cd7096fd717a15'));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
	
	public function obtenerImagenUrl(array $aEncuesta, string $sCampoImagen) 
	{
		$sUrl = null;
		
		if (isset($aEncuesta[$sCampoImagen], $aEncuesta['_attachments'])) {
			$sNombreImagen = $aEncuesta[$sCampoImagen];
			
			foreach ($aEncuesta['_attachments'] as $oAdjunto) {
				if (strpos($oAdjunto->download_url, $sNombreImagen) !== false) {
					$sUrl = $oAdjunto->download_url;
				}
			}
		}
		
		return $sUrl;
	}	
}

$oKobo = new Kobo();
$oInfoAssets = $oKobo->execCurl('https://kf.movilencuesta.com/api/v2/assets.json');

$findme = '245';
$aNombresCampos = array();
$aEncuestasIndices = $oKobo->encuestas();

$aEncuesta = [];
$aUsuarioEncuesta = [];
$aPredio = [];
$aFuenteCaptacion = [];
$aCaptacionEnFuente = [];
$aUsoRecursoHidrico = [];
$aInformacionJuridicaCaptacion = [];
$aFuenteReceptoraVertimiento = [];
$aInformacionVertimiento = [];
$aInformacionJuridicaVertimiento = [];
$aInformacionOcupacionCauce = [];
$aInformacionOcupacionCauceObraHidraulica = [];
$aInformacionActividadMineraCuerposAgua = [];
$aActividadesExtraccion = [];
$aIndicesEncuestasAgregar = [];

$iContador = 1;
$bValidarFechaInicioEncuesta = true;

$bFoto = false;

if (isset($oInfoAssets->results)) {
    foreach ($oInfoAssets->results as $oInfo) {
        if (isset($oInfo->summary)) {
            if (isset($oInfo->summary->labels)) {
                foreach ($oInfo->summary->labels as $sLabel) {
                    
                }
            }
        }

        if (isset($oInfo->data)) {
            $oPoll = $oKobo->execCurl($oInfo->data);

            if (isset($oPoll->results)) {
                foreach ($oPoll->results as $i => $oResult) {
                    $aResult = (array) $oResult;

                    /*foreach ($aResult as $sNombreCampo => $sValor) {
                        if (is_numeric(strpos($sNombreCampo, $findme)) && !in_array($sNombreCampo, $aNombresCampos)) {
                            $aNombresCampos[] = $sNombreCampo;
                        }
                    }*/
					
					if (isset($aResult['_id'], $aResult['_uuid'])) {
						$sIndice = $aResult['_id'] . "|" . $aResult['_uuid'];
						$sFechaDesdeConsultar = date('Y-m-d', strtotime($oKobo->restarFechas(date('Y-m-d 00:00:00'), 5)));
						$bContinuarFecha = !isset($aResult['today']) ? true : ($bValidarFechaInicioEncuesta ? $aResult['today'] >= $sFechaDesdeConsultar : true);
						//$bContinuarFecha = true;
						
						if ($bContinuarFecha) {
							$aEncuesta = [
								'koboid' => $sIndice,
								'codigo_encuesta' => isset($aResult['group_oc51g24/group_ci4xh08/_0_codigo_encuesta']) ? $aResult['group_oc51g24/group_ci4xh08/_0_codigo_encuesta'] : null,
								'codigo_cuenca_hidrografica' => isset($aResult['group_oc51g24/group_ci4xh08/_1_codigo_asociado_cuenca_hidro']) ? $aResult['group_oc51g24/group_ci4xh08/_1_codigo_asociado_cuenca_hidro'] : null,
								'fecha_diligenciamiento' => isset($aResult['group_oc51g24/group_ci4xh08/_2_Fecha_diligenciamiento']) ? str_replace('T', ' ', substr($aResult['group_oc51g24/group_ci4xh08/_2_Fecha_diligenciamiento'], 0, 19)) : null,
								'observaciones_generales' => isset($aResult['group_oc51g24/group_lq6ws97/_255_Observaciones_generales']) ? $aResult['group_oc51g24/group_lq6ws97/_255_Observaciones_generales'] : null,
								'nombre_encuestador' => isset($aResult['group_oc51g24/group_lq6ws97/_256_Nombre_encuestador']) ? $aResult['group_oc51g24/group_lq6ws97/_256_Nombre_encuestador'] : null,
								'cedula_encuestador' => isset($aResult['group_oc51g24/group_lq6ws97/_256_1_Cedula_encuestador']) ? $aResult['group_oc51g24/group_lq6ws97/_256_1_Cedula_encuestador'] : null,
								'ingeniero_campo' => isset($aResult['group_oc51g24/group_lq6ws97/_258_Ingeniero_de_Campo']) ? $aResult['group_oc51g24/group_lq6ws97/_258_Ingeniero_de_Campo'] : null,
								'nombre_encuestado' => isset($aResult['group_oc51g24/group_lq6ws97/_257_Nombre_encuestado']) ? $aResult['group_oc51g24/group_lq6ws97/_257_Nombre_encuestado'] : null,
								'coordinador_proyecto' => isset($aResult['group_oc51g24/group_lq6ws97/_259_Coordinador_Proyecto']) ? $aResult['group_oc51g24/group_lq6ws97/_259_Coordinador_Proyecto'] : null,
								'fecha_procesamiento' => isset($aResult['group_oc51g24/group_lq6ws97/_260_Fecha_procesamiento_a_o_mes_dia']) ? str_replace('T', ' ', substr($aResult['group_oc51g24/group_lq6ws97/_260_Fecha_procesamiento_a_o_mes_dia'], 0, 19)) : null,
								'observaciones_adicionales' => isset($aResult['group_oc51g24/group_lq6ws97/_261_Observaciones_adicionales']) ? $aResult['group_oc51g24/group_lq6ws97/_261_Observaciones_adicionales'] : null,
								'foto_adicional1' => isset($aResult['group_oc51g24/group_lq6ws97/Fotos_Adicional_1']) ? $aResult['group_oc51g24/group_lq6ws97/Fotos_Adicional_1'] : null,
								'foto_adicional2' => isset($aResult['group_oc51g24/group_lq6ws97/Fotos_Adicional_2']) ? $aResult['group_oc51g24/group_lq6ws97/Fotos_Adicional_2'] : null,
								'foto_adicional1_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_lq6ws97/Fotos_Adicional_1'),
								'foto_adicional2_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_lq6ws97/Fotos_Adicional_2')
							];
							
							$aUsuarioEncuestaRegistro = [
								'tipo_usuario' => isset($aResult['group_oc51g24/group_at1co62/Tipo_Usuario']) ? $aResult['group_oc51g24/group_at1co62/Tipo_Usuario'] : null,
								'nombre_usuario' => isset($aResult['group_oc51g24/group_at1co62/_5_nombre_usuario']) ? $aResult['group_oc51g24/group_at1co62/_5_nombre_usuario'] : null,
								'tipo_identificacion' => isset($aResult['group_oc51g24/group_at1co62/_6_Tipo_Identificacion']) ? $aResult['group_oc51g24/group_at1co62/_6_Tipo_Identificacion'] : null,
								'documento' => isset($aResult['group_oc51g24/group_at1co62/_7_numero_documento']) ? $aResult['group_oc51g24/group_at1co62/_7_numero_documento'] : null,
								'representante_legal' => isset($aResult['group_oc51g24/group_at1co62/_8_representante_legal']) ? $aResult['group_oc51g24/group_at1co62/_8_representante_legal'] : null,
								'direccion_domicilio' => isset($aResult['group_oc51g24/group_at1co62/_11_Direcci_n_domicilio_usuario']) ? $aResult['group_oc51g24/group_at1co62/_11_Direcci_n_domicilio_usuario'] : null,
								'telefono_domicilio' => isset($aResult['group_oc51g24/group_at1co62/_12_tel_fono']) ? $aResult['group_oc51g24/group_at1co62/_12_tel_fono'] : null,
								'direccion_correspondencia' => isset($aResult['group_oc51g24/group_at1co62/_13_direcci_n_correspondencia_u']) ? $aResult['group_oc51g24/group_at1co62/_13_direcci_n_correspondencia_u'] : null,
								'telefono_correspondencia' => isset($aResult['group_oc51g24/group_at1co62/_14_tel_fono']) ? $aResult['group_oc51g24/group_at1co62/_14_tel_fono'] : null
							];
		
							$aCoordenadas = isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_26_coordenadas_predio']) ? explode(' ', $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_26_coordenadas_predio']) : array();
		
							$aPredioRegistro = [
								'nombre' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/nombre_predio15']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/nombre_predio15'] : null,
								'area' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_16_Area']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_16_Area'] : null,
								'direccion' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_17_direcci_n_predio']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_17_direcci_n_predio'] : null,
								'departamento' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_18_departamento']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_18_departamento'] : null,
								'dane_departamento' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_19_codigo_dane_departamento']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_19_codigo_dane_departamento'] : null,
								'corregimiento' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_20_corregimiento']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_20_corregimiento'] : null,
								'municipio' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_21_Municipio']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_21_Municipio'] : null,
								'dane_municipio' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_22_Codigo_Dane']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_22_Codigo_Dane'] : null,
								'vereda' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_23_vereda']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_23_vereda'] : null,
								'cedula_catastral' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_24_Cedula_Catastral']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_24_Cedula_Catastral'] : null,
								'tenencia_tipo' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/_25_Tenencia_Tipo']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/_25_Tenencia_Tipo'] : null,
								'latitud' => isset($aCoordenadas[0]) && count($aCoordenadas) === 4 ? $aCoordenadas[0] : null,
								'longitud' => isset($aCoordenadas[1]) && count($aCoordenadas) === 4 ? $aCoordenadas[1] : null,
								'altitud' => isset($aCoordenadas[2]) && count($aCoordenadas) === 4 ? $aCoordenadas[2] : null,
								'precision' => isset($aCoordenadas[3]) && count($aCoordenadas) === 4 ? $aCoordenadas[3] : null,
								'este' => isset($aResult['group_xg0uz54_header_columna']) ? $aResult['group_xg0uz54_header_columna'] : null,
								'norte' => isset($aResult['group_xg0uz54_header_columna_1']) ? $aResult['group_xg0uz54_header_columna_1'] : null,
								'altura' => isset($aResult['group_xg0uz54_header_columna_2']) ? $aResult['group_xg0uz54_header_columna_2'] : null,
								'imagen_punto_referenciado_gps' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia'] : null,
								'imagen_punto_referenciado_predio1' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia_001']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia_001'] : null,
								'imagen_punto_referenciado_predio2' => isset($aResult['group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia_002']) ? $aResult['group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia_002'] : null,
								'imagen_punto_referenciado_gps_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia'),
								'imagen_punto_referenciado_predio1_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia_001'),
								'imagen_punto_referenciado_predio2_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_at1co62/group_kp9nl32/imagen_punt_referencia_002')
							];
							
							$aFuenteCaptacionRegistro = [
								'fuente_superficial' => isset($aResult['group_oc51g24/group_pz2ci38_001/group_qi9kj02_001/_27_Fuente_Superficial']) ? $aResult['group_oc51g24/group_pz2ci38_001/group_qi9kj02_001/_27_Fuente_Superficial'] : null,
								'nombre_fuente' => isset($aResult['group_oc51g24/group_pz2ci38_001/_28_nombre_fuente']) ? $aResult['group_oc51g24/group_pz2ci38_001/_28_nombre_fuente'] : null,
								'observaciones' => isset($aResult['group_oc51g24/group_pz2ci38_001/_29_observaciones']) ? $aResult['group_oc51g24/group_pz2ci38_001/_29_observaciones'] : null,
								'departamento' => isset($aResult['group_oc51g24/group_pz2ci38_001/_30_Departamento']) ? $aResult['group_oc51g24/group_pz2ci38_001/_30_Departamento'] : null,
								'dane_departamento' => isset($aResult['group_oc51g24/group_pz2ci38_001/_31_codigo_dane_departamento']) ? $aResult['group_oc51g24/group_pz2ci38_001/_31_codigo_dane_departamento'] : null,
								'municipio' => isset($aResult['group_oc51g24/group_pz2ci38_001/_32_Municipio']) ? $aResult['group_oc51g24/group_pz2ci38_001/_32_Municipio'] : null,
								'dane_municipio' => isset($aResult['group_oc51g24/group_pz2ci38_001/_33_Codigo_Dane']) ? $aResult['group_oc51g24/group_pz2ci38_001/_33_Codigo_Dane'] : null,
								'corregimiento' => isset($aResult['group_oc51g24/group_pz2ci38_001/_34_Corregimiento']) ? $aResult['group_oc51g24/group_pz2ci38_001/_34_Corregimiento'] : null,
								'vereda' => isset($aResult['group_oc51g24/group_pz2ci38_001/_35_Vereda']) ? $aResult['group_oc51g24/group_pz2ci38_001/_35_Vereda'] : null,
								'nombre_und_geografica_nivel1' => isset($aResult['group_oc51g24/group_pz2ci38_001/group_fj49p80_002/_36_Nombre']) ? $aResult['group_oc51g24/group_pz2ci38_001/group_fj49p80_002/_36_Nombre'] : null,
								'codigo_und_geografica_nivel1' => isset($aResult['group_oc51g24/group_pz2ci38_001/group_fj49p80_002/_37_codigo']) ? $aResult['group_oc51g24/group_pz2ci38_001/group_fj49p80_002/_37_codigo'] : null,
								'nombre_und_geografica_nivel2' => isset($aResult['group_oc51g24/group_pz2ci38_001/group_fj49p80_002/group_yh8ho85_003/_38_nombre']) ? $aResult['group_oc51g24/group_pz2ci38_001/group_fj49p80_002/group_yh8ho85_003/_38_nombre'] : null,
								'codigo_und_geografica_nivel2' => isset($aResult['group_oc51g24/group_pz2ci38_001/group_fj49p80_002/group_yh8ho85_003/_39_codigo']) ? $aResult['group_oc51g24/group_pz2ci38_001/group_fj49p80_002/group_yh8ho85_003/_39_codigo'] : null
							];

							$aCoordenadas = isset($aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/_57_Coordenadas_de_la_Captaci_n']) ? explode(' ', $aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/_57_Coordenadas_de_la_Captaci_n']) : array();

							$aCaptacionEnFuenteRegistro = [
								'tipo_captacion' => isset($aResult['group_oc51g24/group_bz3ir72/group_vm1dn78/_40_tipo_captacion']) ? $aResult['group_oc51g24/group_bz3ir72/group_vm1dn78/_40_tipo_captacion'] : null,
								'estado' => isset($aResult['group_oc51g24/group_bz3ir72/estado41/_41_estado']) ? $aResult['group_oc51g24/group_bz3ir72/estado41/_41_estado'] : null,
								'diametro' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila/group_fk9hj60_fila_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila/group_fk9hj60_fila_columna'] : null,
								'diametro_observaciones' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila/group_fk9hj60_fila_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila/group_fk9hj60_fila_columna_1'] : null,
								'ancho' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_1/group_fk9hj60_fila_1_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_1/group_fk9hj60_fila_1_columna'] : null,
								'ancho_observaciones' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_1/group_fk9hj60_fila_1_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_1/group_fk9hj60_fila_1_columna_1'] : null,
								'alto' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_2/group_fk9hj60_fila_2_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_2/group_fk9hj60_fila_2_columna'] : null,
								'alto_observaciones' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_2/group_fk9hj60_fila_2_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_2/group_fk9hj60_fila_2_columna_1'] : null,
								'profundidad' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_3/group_fk9hj60_fila_3_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_3/group_fk9hj60_fila_3_columna'] : null,
								'profundidad_observaciones' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_3/group_fk9hj60_fila_3_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_3/group_fk9hj60_fila_3_columna_1'] : null,
								'tiempo_captacion' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_4/group_fk9hj60_fila_4_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_4/group_fk9hj60_fila_4_columna'] : null,
								'tiempo_captacion_observaciones' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_4/group_fk9hj60_fila_4_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_4/group_fk9hj60_fila_4_columna_1'] : null,
								'frecuencia' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_5/group_fk9hj60_fila_5_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_5/group_fk9hj60_fila_5_columna'] : null,
								'frecuencia_observaciones' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_5/group_fk9hj60_fila_5_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_5/group_fk9hj60_fila_5_columna_1'] : null,
								'capacidad_bomba' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_6/group_fk9hj60_fila_6_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_6/group_fk9hj60_fila_6_columna'] : null,
								'capacidad_bomba_observaciones' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_6/group_fk9hj60_fila_6_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_6/group_fk9hj60_fila_6_columna_1'] : null,
								'capacidad_instalada' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_7/group_fk9hj60_fila_7_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_7/group_fk9hj60_fila_7_columna'] : null,
								'capacidad_instalada_observaciones' => isset($aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_7/group_fk9hj60_fila_7_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_fk9hj60_fila_7/group_fk9hj60_fila_7_columna_1'] : null,
								'caracteristicas_abastecimiento' => isset($aResult['group_oc51g24/group_bz3ir72/Caracter_sticas_Sistema_de_Aba']) ? $aResult['group_oc51g24/group_bz3ir72/Caracter_sticas_Sistema_de_Aba'] : null,
								'observaciones_captacion' => isset($aResult['group_oc51g24/group_bz3ir72/_56_Observaciones_captaci_n']) ? $aResult['group_oc51g24/group_bz3ir72/_56_Observaciones_captaci_n'] : null,
								'latitud' => isset($aCoordenadas[0]) && count($aCoordenadas) === 4 ? $aCoordenadas[0] : null,
								'longitud' => isset($aCoordenadas[1]) && count($aCoordenadas) === 4 ? $aCoordenadas[1] : null,
								'altitud' => isset($aCoordenadas[2]) && count($aCoordenadas) === 4 ? $aCoordenadas[2] : null,
								'precision' => isset($aCoordenadas[3]) && count($aCoordenadas) === 4 ? $aCoordenadas[3] : null,
								'este' => isset($aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/group_nu3uw14_fila/group_nu3uw14_fila_columna']) ? $aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/group_nu3uw14_fila/group_nu3uw14_fila_columna'] : null,
								'norte' => isset($aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/group_nu3uw14_fila/group_nu3uw14_fila_columna_1']) ? $aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/group_nu3uw14_fila/group_nu3uw14_fila_columna_1'] : null,
								'altura' => isset($aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/group_nu3uw14_fila/group_nu3uw14_fila_columna_2']) ? $aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/group_nu3uw14_fila/group_nu3uw14_fila_columna_2'] : null,
								'imagen_punto_referenciado_gps' => isset($aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58_imagen_punto_referenciado']) ? $aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58_imagen_punto_referenciado'] : null,
								'imagen_punto_referenciado_captacion1' => isset($aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58i_imagen_punto_referenciado']) ? $aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58i_imagen_punto_referenciado'] : null,
								'imagen_punto_referenciado_captacion2' => isset($aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58i_imagen_punto_referenciado_001']) ? $aResult['group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58i_imagen_punto_referenciado_001'] : null,
								'imagen_punto_referenciado_gps_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58_imagen_punto_referenciado'),
								'imagen_punto_referenciado_captacion1_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58i_imagen_punto_referenciado'),
								'imagen_punto_referenciado_captacion2_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_bz3ir72/group_ls8cl21_header/_58i_imagen_punto_referenciado_001'),
								'esquema_localizacion' => isset($aResult['group_oc51g24/group_bz3ir72/_59_esquema']) ? $aResult['group_oc51g24/group_bz3ir72/_59_esquema'] : null
							];

							$aUsoRecursoHidricoRegistro = [
								'uso_domestico' => isset($aResult['group_oc51g24/group_ho0yq77/_60_Uso_dom_stico']) ? $aResult['group_oc51g24/group_ho0yq77/_60_Uso_dom_stico'] : null,
								'personas_permanentes' => isset($aResult['group_oc51g24/group_ho0yq77/_61_No_Personas_permanentes']) ? $aResult['group_oc51g24/group_ho0yq77/_61_No_Personas_permanentes'] : null,
								'personas_transitorias' => isset($aResult['group_oc51g24/group_ho0yq77/_62_No_Personas_transitorias']) ? $aResult['group_oc51g24/group_ho0yq77/_62_No_Personas_transitorias'] : null,
								'dias_mes' => isset($aResult['group_oc51g24/group_ho0yq77/_63_dias_mes']) ? $aResult['group_oc51g24/group_ho0yq77/_63_dias_mes'] : null,
								'consumo' => isset($aResult['group_oc51g24/group_ho0yq77/_64_Consumo_l_s']) ? $aResult['group_oc51g24/group_ho0yq77/_64_Consumo_l_s'] : null,
								'menores_cinco_anios' => isset($aResult['group_oc51g24/group_ho0yq77/group_su88o83/_65_Menores_de_5_a_os']) ? $aResult['group_oc51g24/group_ho0yq77/group_su88o83/_65_Menores_de_5_a_os'] : null,
								'mayores_sesenta_anios' => isset($aResult['group_oc51g24/group_ho0yq77/group_su88o83/_66_Mayores_de_60_a_os']) ? $aResult['group_oc51g24/group_ho0yq77/group_su88o83/_66_Mayores_de_60_a_os'] : null,
								'tipo_construccion' => isset($aResult['group_oc51g24/group_ho0yq77/group_su88o83/group_ot5dv51/_67_Construcci_n_Tipo']) ? $aResult['group_oc51g24/group_ho0yq77/group_su88o83/group_ot5dv51/_67_Construcci_n_Tipo'] : null,
								'uso_pecuario' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_68']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_68'] : null,
								'uso_acuicola' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_81_Uso_Pecuari']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_81_Uso_Pecuari'] : null,
								'uso_agricola_silvicola' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_94']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_94'] : null,
								'riego_predominante' => isset($aResult['group_oc51g24/group_ho0yq77/group_fb32m90/_116_Tipo_de_Riego_Predominante']) ? $aResult['group_oc51g24/group_ho0yq77/group_fb32m90/_116_Tipo_de_Riego_Predominante'] : null,
								'uso_industrial' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_117_Uso_Industr']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_117_Uso_Industr'] : null,
								'uso_minero' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_120_Uso_Minero']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_120_Uso_Minero'] : null,
								'uso_generacion_hidroelectrica' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_123_Uso_Generac']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_123_Uso_Generac'] : null,
								'uso_recreacional' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_126_Uso_Recreac']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_126_Uso_Recreac'] : null,
								'uso_servicios' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_129_Uso_Servici']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_129_Uso_Servici'] : null,
								'uso_explotacion_petrolera' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_132_Uso_explota']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_132_Uso_explota'] : null,
								'otros_usos' => isset($aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_135_Otros_usos_']) ? $aResult['group_oc51g24/group_ho0yq77/Aplica_Numeral_135_Otros_usos_'] : null
							];

							$aInformacionJuridicaCaptacionRegistro = [
								'domestico' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_6/group_hn3qy72_fila_6_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_6/group_hn3qy72_fila_6_columna'] : null,
								'domestico_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_6/group_hn3qy72_fila_6_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_6/group_hn3qy72_fila_6_columna_1'] : null,  //group_hn3qy72
								'pecuario' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_7/group_hn3qy72_fila_7_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_7/group_hn3qy72_fila_7_columna'] : null,
								'pecuario_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_7/group_hn3qy72_fila_7_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_7/group_hn3qy72_fila_7_columna_1'] : null,
								'acuicola' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila/group_hn3qy72_fila_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila/group_hn3qy72_fila_columna'] : null,
								'acuicola_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila/group_hn3qy72_fila_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila/group_hn3qy72_fila_columna_1'] : null,
								'agricola' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_3/group_hn3qy72_fila_3_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_3/group_hn3qy72_fila_3_columna'] : null,
								'agricola_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_3/group_hn3qy72_fila_3_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_3/group_hn3qy72_fila_3_columna_1'] : null,
								'industrial' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_1/group_hn3qy72_fila_1_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_1/group_hn3qy72_fila_1_columna'] : null,
								'industrial_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_1/group_hn3qy72_fila_1_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_1/group_hn3qy72_fila_1_columna_1'] : null,
								'mineria' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_2/group_hn3qy72_fila_2_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_2/group_hn3qy72_fila_2_columna'] : null,
								'mineria_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_2/group_hn3qy72_fila_2_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_2/group_hn3qy72_fila_2_columna_1'] : null,
								'generacion_electrica' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_5/group_hn3qy72_fila_5_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_5/group_hn3qy72_fila_5_columna'] : null,
								'generacion_electrica_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_5/group_hn3qy72_fila_5_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_5/group_hn3qy72_fila_5_columna_1'] : null,
								'otros' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_4/group_hn3qy72_fila_4_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_4/group_hn3qy72_fila_4_columna'] : null,
								'otros_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_4/group_hn3qy72_fila_4_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_4/group_hn3qy72_fila_4_columna_1'] : null,
								'total' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_8/group_hn3qy72_fila_8_columna']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_8/group_hn3qy72_fila_8_columna'] : null,
								'total_observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_8/group_hn3qy72_fila_8_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/group_hn3qy72_fila_8/group_hn3qy72_fila_8_columna_1'] : null,
								'permiso_concesion_uso_agua' => isset($aResult['group_oc51g24/group_xb1jx28/_138_Permiso_de_Concesi_n_de_uso_del_agua']) ? $aResult['group_oc51g24/group_xb1jx28/_138_Permiso_de_Concesi_n_de_uso_del_agua'] : null,
								'numero_expediente' => isset($aResult['group_oc51g24/group_xb1jx28/_139_N_Expediente']) ? $aResult['group_oc51g24/group_xb1jx28/_139_N_Expediente'] : null,
								'fecha_expediente' => isset($aResult['group_oc51g24/group_xb1jx28/_140_Fecha_a_o_mes_dia']) ? $aResult['group_oc51g24/group_xb1jx28/_140_Fecha_a_o_mes_dia'] : null,
								'numero_resolucion' => isset($aResult['group_oc51g24/group_xb1jx28/_141_N_Resoluci_n']) ? $aResult['group_oc51g24/group_xb1jx28/_141_N_Resoluci_n'] : null,
								'fecha_resolucion' => isset($aResult['group_oc51g24/group_xb1jx28/_142_Fecha_a_o_mes_dia']) ? $aResult['group_oc51g24/group_xb1jx28/_142_Fecha_a_o_mes_dia'] : null,
								'vigencia_anios' => isset($aResult['group_oc51g24/group_xb1jx28/_143_Vigencia_a_os']) ? $aResult['group_oc51g24/group_xb1jx28/_143_Vigencia_a_os'] : null,
								'caudal_concesionado' => isset($aResult['group_oc51g24/group_xb1jx28/_144_Caudal_Concesionado_l_s']) ? $aResult['group_oc51g24/group_xb1jx28/_144_Caudal_Concesionado_l_s'] : null,
								'caudal_utilizado_1' => isset($aResult['group_oc51g24/group_xb1jx28/_145_Caudal_Utilizado_fila/_145_Caudal_Utilizado_fila_columna']) ? $aResult['group_oc51g24/group_xb1jx28/_145_Caudal_Utilizado_fila/_145_Caudal_Utilizado_fila_columna'] : null,
								'caudal_utilizado_2' => isset($aResult['group_oc51g24/group_xb1jx28/_145_Caudal_Utilizado_fila/_145_Caudal_Utilizado_fila_columna_1']) ? $aResult['group_oc51g24/group_xb1jx28/_145_Caudal_Utilizado_fila/_145_Caudal_Utilizado_fila_columna_1'] : null,
								'caudal_utilizado_3' => isset($aResult['group_oc51g24/group_xb1jx28/_145_Caudal_Utilizado_fila/_145_Caudal_Utilizado_fila_columna_2']) ? $aResult['group_oc51g24/group_xb1jx28/_145_Caudal_Utilizado_fila/_145_Caudal_Utilizado_fila_columna_2'] : null,
								'observaciones' => isset($aResult['group_oc51g24/group_xb1jx28/_146_Observaciones']) ? $aResult['group_oc51g24/group_xb1jx28/_146_Observaciones'] : null
							];

							$aFuenteReceptoraVertimientoRegistro = [
								'cuerpo_receptor' => isset($aResult['group_oc51g24/group_mp3rf49/group_gg4un13/_147_Cuerpo_Receptor']) ? $aResult['group_oc51g24/group_mp3rf49/group_gg4un13/_147_Cuerpo_Receptor'] : null,
								'fuente_receptora' => isset($aResult['group_oc51g24/group_mp3rf49/group_gg4un13/_148_Nombre_de_la_Fuente_receptora']) ? $aResult['group_oc51g24/group_mp3rf49/group_gg4un13/_148_Nombre_de_la_Fuente_receptora'] : null,
								'observaciones' => isset($aResult['group_oc51g24/group_mp3rf49/group_gg4un13/_149_Observaciones']) ? $aResult['group_oc51g24/group_mp3rf49/group_gg4un13/_149_Observaciones'] : null,
								'objetivo_calidad_receptor' => isset($aResult['group_oc51g24/group_mp3rf49/_150_Objetivo_de_Cali_Validar_en_Oficina']) ? $aResult['group_oc51g24/group_mp3rf49/_150_Objetivo_de_Cali_Validar_en_Oficina'] : null,
								'departamento' => isset($aResult['group_oc51g24/group_mp3rf49/_151_Departamento']) ? $aResult['group_oc51g24/group_mp3rf49/_151_Departamento'] : null,
								'dane_departamento' => isset($aResult['group_oc51g24/group_mp3rf49/_152_C_digo_DANE']) ? $aResult['group_oc51g24/group_mp3rf49/_152_C_digo_DANE'] : null,
								'municipio' => isset($aResult['group_oc51g24/group_mp3rf49/_153_Municipio']) ? $aResult['group_oc51g24/group_mp3rf49/_153_Municipio'] : null,
								'dane_municipio' => isset($aResult['group_oc51g24/group_mp3rf49/_154_C_digo_DANE']) ? $aResult['group_oc51g24/group_mp3rf49/_154_C_digo_DANE'] : null,
								'corregimiento' => isset($aResult['group_oc51g24/group_mp3rf49/_155_Corregimiento']) ? $aResult['group_oc51g24/group_mp3rf49/_155_Corregimiento'] : null,
								'vereda' => isset($aResult['group_oc51g24/group_mp3rf49/_156_Vereda']) ? $aResult['group_oc51g24/group_mp3rf49/_156_Vereda'] : null,
								'nombre_und_geografica_nivel1' => isset($aResult['group_oc51g24/group_mp3rf49/group_kl1um73/_157_Nombre']) ? $aResult['group_oc51g24/group_mp3rf49/group_kl1um73/_157_Nombre'] : null,
								'codigo_und_geografica_nivel1' => isset($aResult['group_oc51g24/group_mp3rf49/group_kl1um73/_158_C_digo']) ? $aResult['group_oc51g24/group_mp3rf49/group_kl1um73/_158_C_digo'] : null,
								'nombre_und_geografica_nivel2' => isset($aResult['group_oc51g24/group_mp3rf49/group_vg4gw03/_159_Nombre']) ? $aResult['group_oc51g24/group_mp3rf49/group_vg4gw03/_159_Nombre'] : null,
								'codigo_und_geografica_nivel2' => isset($aResult['group_oc51g24/group_mp3rf49/group_vg4gw03/_160_C_digo']) ? $aResult['group_oc51g24/group_mp3rf49/group_vg4gw03/_160_C_digo'] : null
							];

							$aCoordenadas = isset($aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_168_Coordenadas_del_Vertimiento']) ? explode(' ', $aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_168_Coordenadas_del_Vertimiento']) : array();

							$aInformacionVertimientoRegistro = [
								'actividad_vertimiento' => isset($aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_161_Actividad_que_genera_el_Ve']) ? $aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_161_Actividad_que_genera_el_Ve'] : null,
								'tipo_descarga' => isset($aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_162_Tipo_de_descarga']) ? $aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_162_Tipo_de_descarga'] : null,
								'caudal_vertido' => isset($aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_163_Caudal_vertido_l_s']) ? $aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_163_Caudal_vertido_l_s'] : null,
								'tiempo_descarga' => isset($aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_164_Tiempo_descarga_hr_d_a']) ? $aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_164_Tiempo_descarga_hr_d_a'] : null,
								'frecuencia' => isset($aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_165_Frecuencia_d_as_mes']) ? $aResult['group_oc51g24/group_xv9ko45/group_ib68c76/_165_Frecuencia_d_as_mes'] : null,
								'disposicion_vertimiento' => isset($aResult['group_oc51g24/group_xv9ko45/group_fm5bh09/_166_Disposici_n_del_Vertimient']) ? $aResult['group_oc51g24/group_xv9ko45/group_fm5bh09/_166_Disposici_n_del_Vertimient'] : null,
								'estado' => isset($aResult['group_oc51g24/group_xv9ko45/group_fm5bh09/_167_Estado']) ? $aResult['group_oc51g24/group_xv9ko45/group_fm5bh09/_167_Estado'] : null,
								'latitud' => isset($aCoordenadas[0]) && count($aCoordenadas) === 4 ? $aCoordenadas[0] : null,
								'longitud' => isset($aCoordenadas[1]) && count($aCoordenadas) === 4 ? $aCoordenadas[1] : null,
								'altitud' => isset($aCoordenadas[2]) && count($aCoordenadas) === 4 ? $aCoordenadas[2] : null,
								'precision' => isset($aCoordenadas[3]) && count($aCoordenadas) === 4 ? $aCoordenadas[3] : null,
								'este' => isset($aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/group_bt3hw49_fila/group_bt3hw49_fila_columna']) ? $aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/group_bt3hw49_fila/group_bt3hw49_fila_columna'] : null,
								'norte' => isset($aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/group_bt3hw49_fila/group_bt3hw49_fila_columna_1']) ? $aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/group_bt3hw49_fila/group_bt3hw49_fila_columna_1'] : null,
								'altura' => isset($aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/group_bt3hw49_fila/group_bt3hw49_fila_columna_2']) ? $aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/group_bt3hw49_fila/group_bt3hw49_fila_columna_2'] : null,
								'imagen_punto_referenciado_gps' => isset($aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_fico']) ? $aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_fico'] : null,
								'imagen_punto_referenciado_vertimiento1' => isset($aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_f']) ? $aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_f'] : null,
								'imagen_punto_referenciado_vertimiento2' => isset($aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_f_001']) ? $aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_f_001'] : null,
								'imagen_punto_referenciado_gps_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_fico'),
								'imagen_punto_referenciado_vertimiento1_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_f'),
								'imagen_punto_referenciado_vertimiento2_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_xv9ko45/group_uo4gy31/_169_Archivo_Registro_fotogr_f_001'),
								'observaciones_vertimiento' => isset($aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_170_Observaciones_del_Vertimiento']) ? $aResult['group_oc51g24/group_xv9ko45/group_uo4gy31/_170_Observaciones_del_Vertimiento'] : null,
								'presenta_sistema_tratamiento' => isset($aResult['group_oc51g24/group_xv9ko45/_171_Presenta_alg_n_tipo_de_si']) ? $aResult['group_oc51g24/group_xv9ko45/_171_Presenta_alg_n_tipo_de_si'] : null,
								'esquema_localizacion_punto_vertimiento' => isset($aResult['group_oc51g24/group_xv9ko45/_173_Esquema_de_local_punto_de_Vertimiento']) ? $aResult['group_oc51g24/group_xv9ko45/_173_Esquema_de_local_punto_de_Vertimiento'] : null,
								'foto1_punto_vertimiento' => isset($aResult['group_oc51g24/group_xv9ko45/Foto_1_punto_de_vertimiento']) ? $aResult['group_oc51g24/group_xv9ko45/Foto_1_punto_de_vertimiento'] : null,
								'foto2_punto_vertimiento' => isset($aResult['group_oc51g24/group_xv9ko45/Foto_2_punto_de_vertimiento']) ? $aResult['group_oc51g24/group_xv9ko45/Foto_2_punto_de_vertimiento'] : null,
								'foto1_punto_vertimiento_url' => $oKobo->obtenerImagenUrl($aResult, 'roup_oc51g24/group_xv9ko45/Foto_1_punto_de_vertimiento'),
								'foto2_punto_vertimiento_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_xv9ko45/Foto_2_punto_de_vertimiento'),
								'pozo_septico_campo_infiltracion_menos_100_mts' => isset($aResult['group_oc51g24/group_xv9ko45/_174_Si_el_predio_cue_s_una_fuente_h_drica']) ? $aResult['group_oc51g24/group_xv9ko45/_174_Si_el_predio_cue_s_una_fuente_h_drica'] : null
							];

							$aInformacionJuridicaVertimientoRegistro = [
								'permiso_vertimiento' => isset($aResult['group_oc51g24/group_yq06z17/_175_Permiso_de_Vertimiento']) ? $aResult['group_oc51g24/group_yq06z17/_175_Permiso_de_Vertimiento'] : null,
								'numero_expediente' => isset($aResult['group_oc51g24/group_yq06z17/_176_N_Expediente']) ? $aResult['group_oc51g24/group_yq06z17/_176_N_Expediente'] : null,
								'fecha_expediente' => isset($aResult['group_oc51g24/group_yq06z17/_177_Fecha_a_o_mes_dia']) ? $aResult['group_oc51g24/group_yq06z17/_177_Fecha_a_o_mes_dia'] : null,
								'numero_resolucion' => isset($aResult['group_oc51g24/group_yq06z17/_178_N_Resoluci_n']) ? $aResult['group_oc51g24/group_yq06z17/_178_N_Resoluci_n'] : null,
								'fecha_resolucion' => isset($aResult['group_oc51g24/group_yq06z17/_179_Fecha_a_o_mes_dia']) ? $aResult['group_oc51g24/group_yq06z17/_179_Fecha_a_o_mes_dia'] : null,
								'vigencia_anios' => isset($aResult['group_oc51g24/group_yq06z17/_180_Vigencia']) ? $aResult['group_oc51g24/group_yq06z17/_180_Vigencia'] : null,
								'caudal_vertido_autorizado' => isset($aResult['group_oc51g24/group_yq06z17/_181_Caudal_de_Vertido_Autorizado_l_s']) ? $aResult['group_oc51g24/group_yq06z17/_181_Caudal_de_Vertido_Autorizado_l_s'] : null,
								'norma_vertimiento' => isset($aResult['group_oc51g24/group_yq06z17/_182_Norma_que_debe_C_mplir_el_Vertimiento']) ? $aResult['group_oc51g24/group_yq06z17/_182_Norma_que_debe_C_mplir_el_Vertimiento'] : null,
								'observaciones' => isset($aResult['group_oc51g24/group_yq06z17/_183_Observaciones']) ? $aResult['group_oc51g24/group_yq06z17/_183_Observaciones'] : null,
							];

							$aInformacionOcupacionCauceRegistro = [
								'cuerpo_agua' => isset($aResult['group_oc51g24/group_eh4qh97/group_es8cl17/_184_Cuerpo_de_agua_con_ocupaci']) ? $aResult['group_oc51g24/group_eh4qh97/group_es8cl17/_184_Cuerpo_de_agua_con_ocupaci'] : null,
								'nombre_fuente' => isset($aResult['group_oc51g24/group_eh4qh97/_185_Nombre_de_la_Fuente']) ? $aResult['group_oc51g24/group_eh4qh97/_185_Nombre_de_la_Fuente'] : null,
								'observaciones' => isset($aResult['group_oc51g24/group_eh4qh97/_186_Observaciones']) ? $aResult['group_oc51g24/group_eh4qh97/_186_Observaciones'] : null,
								'fuente_area_influcencia' => isset($aResult['group_oc51g24/group_eh4qh97/_187_Uso_de_la_fuente_l_rea_de_influencia']) ? $aResult['group_oc51g24/group_eh4qh97/_187_Uso_de_la_fuente_l_rea_de_influencia'] : null,
								'pendiente_cauce' => isset($aResult['group_oc51g24/group_eh4qh97/_188_Pendiente_del_cauce']) ? $aResult['group_oc51g24/group_eh4qh97/_188_Pendiente_del_cauce'] : null,
								'tipo_cauce' => isset($aResult['group_oc51g24/group_eh4qh97/group_js98s09/group_cq6kp00/_189_Tipo_Cauce']) ? $aResult['group_oc51g24/group_eh4qh97/group_js98s09/group_cq6kp00/_189_Tipo_Cauce'] : null,
								'tipo_lecho' => isset($aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_190_Tipo_de_lecho']) ? $aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_190_Tipo_de_lecho'] : null,
								'departamento' => isset($aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_191_Departamento']) ? $aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_191_Departamento'] : null,
								'dane_departamento' => isset($aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_192_C_digo_DANE']) ? $aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_192_C_digo_DANE'] : null,
								'municipio' => isset($aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_193_Municipio']) ? $aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_193_Municipio'] : null,
								'dane_municipio' => isset($aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_194_Codigo_Dane']) ? $aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_194_Codigo_Dane'] : null,
								'corregimiento' => isset($aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_195_Corregimiento']) ? $aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_195_Corregimiento'] : null,
								'vereda' => isset($aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_196_Vereda_Validar_en_Oficina']) ? $aResult['group_oc51g24/group_eh4qh97/group_sg1ml59/_196_Vereda_Validar_en_Oficina'] : null,
								'nombre_und_geografica_nivel1' => isset($aResult['group_oc51g24/group_eh4qh97/group_ab42e92/_197_Nombre']) ? $aResult['group_oc51g24/group_eh4qh97/group_ab42e92/_197_Nombre'] : null,
								'codigo_und_geografica_nivel1' => isset($aResult['group_oc51g24/group_eh4qh97/group_ab42e92/_198_C_digo']) ? $aResult['group_oc51g24/group_eh4qh97/group_ab42e92/_198_C_digo'] : null,
								'nombre_und_geografica_nivel2' => isset($aResult['group_oc51g24/group_eh4qh97/group_jl9mx40/_199_Nombre']) ? $aResult['group_oc51g24/group_eh4qh97/group_jl9mx40/_199_Nombre'] : null,
								'codigo_und_geografica_nivel2' => isset($aResult['group_oc51g24/group_eh4qh97/group_jl9mx40/_200_C_digo']) ? $aResult['group_oc51g24/group_eh4qh97/group_jl9mx40/_200_C_digo'] : null
							];

							$aCoordenadas = isset($aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_211_Coordenadas_de_la_obra_hidr_ulica']) ? explode(' ', $aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_211_Coordenadas_de_la_obra_hidr_ulica']) : array();

							$aInformacionOcupacionCauceObraHidraulicaRegistro = [
								'descripcion_obra' => isset($aResult['group_oc51g24/group_wf0tq11/_201_Descripci_n_de_la_Obra']) ? $aResult['group_oc51g24/group_wf0tq11/_201_Descripci_n_de_la_Obra'] : null,
								'tipo_obra' => isset($aResult['group_oc51g24/group_wf0tq11/group_sx98p91/_202_Tipo_de_Obra']) ? $aResult['group_oc51g24/group_wf0tq11/group_sx98p91/_202_Tipo_de_Obra'] : null, //
								'estado' => isset($aResult['group_oc51g24/group_wf0tq11/_203_Estado']) ? $aResult['group_oc51g24/group_wf0tq11/_203_Estado'] : null, //
								'tipo_material' => isset($aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_204_Tipo_de_Material']) ? $aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_204_Tipo_de_Material'] : null, //
								'alto' => isset($aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_205_Altura_m']) ? $aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_205_Altura_m'] : null, //
								'ancho' => isset($aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_206_Ancho_m']) ? $aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_206_Ancho_m'] : null, //
								'largo' => isset($aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_207_Longitud_m']) ? $aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_207_Longitud_m'] : null, 
								'profundidad' => isset($aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_208_Profundidad_m']) ? $aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_208_Profundidad_m'] : null, //
								'area' => isset($aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_209_rea_m2']) ? $aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_209_rea_m2'] : null,
								'diametro' => isset($aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_210_Di_metro_m']) ? $aResult['group_oc51g24/group_wf0tq11/group_ru6zr10/_210_Di_metro_m'] : null,
								'latitud' => isset($aCoordenadas[0]) && count($aCoordenadas) === 4 ? $aCoordenadas[0] : null,
								'longitud' => isset($aCoordenadas[1]) && count($aCoordenadas) === 4 ? $aCoordenadas[1] : null,
								'altitud' => isset($aCoordenadas[2]) && count($aCoordenadas) === 4 ? $aCoordenadas[2] : null,
								'precision' => isset($aCoordenadas[3]) && count($aCoordenadas) === 4 ? $aCoordenadas[3] : null,
								'este' => isset($aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/group_oq05e69_fila/group_oq05e69_fila_columna']) ? $aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/group_oq05e69_fila/group_oq05e69_fila_columna'] : null, //group_oq05e69
								'norte' => isset($aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/group_oq05e69_fila/group_oq05e69_fila_columna_1']) ? $aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/group_oq05e69_fila/group_oq05e69_fila_columna_1'] : null,
								'altura' => isset($aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/group_oq05e69_fila/group_oq05e69_fila_columna_2']) ? $aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/group_oq05e69_fila/group_oq05e69_fila_columna_2'] : null,
								'imagen_punto_referenciado_gps' => isset($aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_fico']) ? $aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_fico'] : null, //
								'imagen_punto_referenciado_obra_hidraulica1' => isset($aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_f_001']) ? $aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_f_001'] : null, //
								'imagen_punto_referenciado_obra_hidraulica2' => isset($aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_f']) ? $aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_f'] : null, //
								'imagen_punto_referenciado_gps_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_fico'),
								'imagen_punto_referenciado_obra_hidraulica1_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_f_001'),
								'imagen_punto_referenciado_obra_hidraulica2_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_wf0tq11/group_zo5gr91/_212_Archivo_Registro_fotogr_f'),
								'esquema_localizacion_obra' => isset($aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_213_Esquema_de_localizaci_n_de_la_obra']) ? $aResult['group_oc51g24/group_wf0tq11/group_zo5gr91/_213_Esquema_de_localizaci_n_de_la_obra'] : null,
								'permiso_ocupacion_cauce' => isset($aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_214_Permiso_de_Ocupaci_n_de_cauce']) ? $aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_214_Permiso_de_Ocupaci_n_de_cauce'] : null,
								'numero_expediente' => isset($aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_215_N_Expediente']) ? $aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_215_N_Expediente'] : null,
								'fecha_expediente' => isset($aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_216_Fecha_a_o_mes_dia']) ? $aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_216_Fecha_a_o_mes_dia'] : null,
								'numero_resolucion' => isset($aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_217_N_Resoluci_n']) ? $aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_217_N_Resoluci_n'] : null,
								'fecha_resolucion' => isset($aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_218_Fecha_a_o_mes_dia']) ? $aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_218_Fecha_a_o_mes_dia'] : null,
								'vigencia_anios' => isset($aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_219_Vigencia_a_os']) ? $aResult['group_oc51g24/group_wf0tq11/group_hv3wx18/_219_Vigencia_a_os'] : null
							];

							$aInformacionActividadMineraCuerposAguaRegistro = [
								'cuerpo_agua' => isset($aResult['group_oc51g24/group_bo4qq12/group_jb2pi91/_220_Cuerpo_de_agua_utilizado_p']) ? $aResult['group_oc51g24/group_bo4qq12/group_jb2pi91/_220_Cuerpo_de_agua_utilizado_p'] : null,
								'nombre_fuente' => isset($aResult['group_oc51g24/group_bo4qq12/_221_Nombre_de_la_Fuente']) ? $aResult['group_oc51g24/group_bo4qq12/_221_Nombre_de_la_Fuente'] : null,
								'descripcion_actividad' => isset($aResult['group_oc51g24/group_bo4qq12/_222_Descripci_n_de_la_Actividad']) ? $aResult['group_oc51g24/group_bo4qq12/_222_Descripci_n_de_la_Actividad'] : null,
								'caracteristicas_lecho' => isset($aResult['group_oc51g24/group_bo4qq12/group_re1nq40/_223_Caracter_sticas_del_lecho']) ? $aResult['group_oc51g24/group_bo4qq12/group_re1nq40/_223_Caracter_sticas_del_lecho'] : null,
								'departamento' => isset($aResult['group_oc51g24/group_bo4qq12/_224_Departamento']) ? $aResult['group_oc51g24/group_bo4qq12/_224_Departamento'] : null,
								'dane_departamento' => isset($aResult['group_oc51g24/group_bo4qq12/_225_C_digo_DANE']) ? $aResult['group_oc51g24/group_bo4qq12/_225_C_digo_DANE'] : null,
								'municipio' => isset($aResult['group_oc51g24/group_bo4qq12/_226_Municipio']) ? $aResult['group_oc51g24/group_bo4qq12/_226_Municipio'] : null,
								'dane_municipio' => isset($aResult['group_oc51g24/group_bo4qq12/_227_C_digo_DANE']) ? $aResult['group_oc51g24/group_bo4qq12/_227_C_digo_DANE'] : null,
								'corregimiento' => isset($aResult['group_oc51g24/group_bo4qq12/_228_Corregimiento']) ? $aResult['group_oc51g24/group_bo4qq12/_228_Corregimiento'] : null,
								'vereda' => isset($aResult['group_oc51g24/group_bo4qq12/_229_Vereda']) ? $aResult['group_oc51g24/group_bo4qq12/_229_Vereda'] : null,
								'nombre_und_geografica_nivel1' => isset($aResult['group_oc51g24/group_bo4qq12/group_rv1xi37/_230_Nombre']) ? $aResult['group_oc51g24/group_bo4qq12/group_rv1xi37/_230_Nombre'] : null,
								'codigo_und_geografica_nivel1' => isset($aResult['group_oc51g24/group_bo4qq12/group_rv1xi37/_231_Codigo']) ? $aResult['group_oc51g24/group_bo4qq12/group_rv1xi37/_231_Codigo'] : null,
								'nombre_und_geografica_nivel2' => isset($aResult['group_oc51g24/group_bo4qq12/group_iq2hv72/_232_Nombre']) ? $aResult['group_oc51g24/group_bo4qq12/group_iq2hv72/_232_Nombre'] : null,
								'codigo_und_geografica_nivel2' => isset($aResult['group_oc51g24/group_bo4qq12/group_iq2hv72/_233_Codigo']) ? $aResult['group_oc51g24/group_bo4qq12/group_iq2hv72/_233_Codigo'] : null
							];

							$aCoordenadas = isset($aResult['group_oc51g24/group_vt4bx10/group_px0gc51/_243_Coordenadas_del_Lugar_de_Extracci_n']) ? explode(' ', $aResult['group_oc51g24/group_vt4bx10/group_px0gc51/_243_Coordenadas_del_Lugar_de_Extracci_n']) : array();

							$aActividadesExtraccionRegistro = [
								'tipo_material_arrastre' => isset($aResult['group_oc51g24/group_vt4bx10/group_rx95w02/_234_Tipo_de_Material_de_Arrastre']) ? $aResult['group_oc51g24/group_vt4bx10/group_rx95w02/_234_Tipo_de_Material_de_Arrastre'] : null,
								'volumen_extraccion_atorizado' => isset($aResult['group_oc51g24/group_vt4bx10/group_ks7ew31/_235_Volumen_de_extra_n_Autorizado_m3_a_o']) ? $aResult['group_oc51g24/group_vt4bx10/group_ks7ew31/_235_Volumen_de_extra_n_Autorizado_m3_a_o'] : null,
								'volumen_extraccion_real' => isset($aResult['group_oc51g24/group_vt4bx10/group_rx95w02/_236_Volumen_de_extracci_n_real_m3_a_o_']) ? $aResult['group_oc51g24/group_vt4bx10/group_rx95w02/_236_Volumen_de_extracci_n_real_m3_a_o_'] : null,
								'titulo_minero' => isset($aResult['group_oc51g24/group_vt4bx10/group_mn2pd70/_237_T_tulo_Minero']) ? $aResult['group_oc51g24/group_vt4bx10/group_mn2pd70/_237_T_tulo_Minero'] : null,
								'numero_resolucion_titulo_minero' => isset($aResult['group_oc51g24/group_vt4bx10/group_mn2pd70/Numero_de_Resoluci_n']) ? $aResult['group_oc51g24/group_vt4bx10/group_mn2pd70/Numero_de_Resoluci_n'] : null,
								'fecha_titulo_minero' => null,
								'licencia_permiso_ambiental' => isset($aResult['group_oc51g24/group_vt4bx10/group_xj1ge22/_240_Licencia_o_Permiso_Ambiental']) ? $aResult['group_oc51g24/group_vt4bx10/group_xj1ge22/_240_Licencia_o_Permiso_Ambiental'] : null,
								'numero_resolucion_permiso_ambiental' => null,
								'fecha_permiso_ambiental' => null,
								'latitud' => isset($aCoordenadas[0]) && count($aCoordenadas) === 4 ? $aCoordenadas[0] : null,
								'longitud' => isset($aCoordenadas[1]) && count($aCoordenadas) === 4 ? $aCoordenadas[1] : null,
								'altitud' => isset($aCoordenadas[2]) && count($aCoordenadas) === 4 ? $aCoordenadas[2] : null,
								'precision' => isset($aCoordenadas[3]) && count($aCoordenadas) === 4 ? $aCoordenadas[3] : null,
								'este' => isset($aResult['group_oc51g24/group_vt4bx10/group_px0gc51/group_ao9vg69_fila/group_ao9vg69_fila_columna']) ? $aResult['group_oc51g24/group_vt4bx10/group_px0gc51/group_ao9vg69_fila/group_ao9vg69_fila_columna'] : null, //group_ao9vg69
								'norte' => isset($aResult['group_oc51g24/group_vt4bx10/group_px0gc51/group_ao9vg69_fila/group_ao9vg69_fila_columna_1']) ? $aResult['group_oc51g24/group_vt4bx10/group_px0gc51/group_ao9vg69_fila/group_ao9vg69_fila_columna_1'] : null,
								'altura' => isset($aResult['group_oc51g24/group_vt4bx10/group_px0gc51/group_ao9vg69_fila/group_ao9vg69_fila_columna_2']) ? $aResult['group_oc51g24/group_vt4bx10/group_px0gc51/group_ao9vg69_fila/group_ao9vg69_fila_columna_2'] : null,
								'imagen_punto_referenciado_gps' => isset($aResult['group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_fico']) ? $aResult['group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_fico'] : null,
								'imagen_punto_referenciado_predio_extraccion1' => isset($aResult['group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_f']) ? $aResult['group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_f'] : null,
								'imagen_punto_referenciado_predio_extraccion2' => isset($aResult['group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_f_001']) ? $aResult['group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_f_001'] : null,
								'imagen_punto_referenciado_gps_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_fico'),
								'imagen_punto_referenciado_predio_extraccion1_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_f'),
								'imagen_punto_referenciado_predio_extraccion2_url' => $oKobo->obtenerImagenUrl($aResult, 'group_oc51g24/group_vt4bx10/group_px0gc51/_244_Archivo_Registro_fotogr_f_001'),
								'esquema_localizacion' => null
							];

							if (isset($aEncuestasIndices[$sIndice])) {
								$oEncuestaExistente = $aEncuestasIndices[$sIndice];
								$idEncuesta = $oEncuestaExistente->id;

								$aEncuesta['fecha_edicion_kobo'] = date('Y-m-d H:i:s');
								$oKobo->updateSingle('encuesta', $aEncuesta, array('id' => $idEncuesta));
								
								if (isset($oEncuestaExistente->id_usuario_encuesta)) {
									$oKobo->updateSingle('usuario_encuesta', $aUsuarioEncuestaRegistro, array('id' => $oEncuestaExistente->id_usuario_encuesta));
								} else {
									$aUsuarioEncuestaRegistro['id_encuesta'] = $idEncuesta;
									$aUsuarioEncuesta[] = $aUsuarioEncuestaRegistro;
								}

								if (isset($oEncuestaExistente->id_predio)) {
									$oKobo->updateSingle('predio', $aPredioRegistro, array('id' => $oEncuestaExistente->id_predio));
								} else {
									$aPredioRegistro['id_encuesta'] = $idEncuesta;
									$aPredio[] = $aPredioRegistro;
								}

								if (isset($oEncuestaExistente->id_fuente_captacion)) {
									$oKobo->updateSingle('fuente_captacion', $aFuenteCaptacionRegistro, array('id' => $oEncuestaExistente->id_fuente_captacion));
								} else {
									$aFuenteCaptacionRegistro['id_encuesta'] = $idEncuesta;
									$aFuenteCaptacion[] = $aFuenteCaptacionRegistro;
								}

								if (isset($oEncuestaExistente->id_captacion_en_fuente)) {
									$oKobo->updateSingle('captacion_en_fuente', $aCaptacionEnFuenteRegistro, array('id' => $oEncuestaExistente->id_captacion_en_fuente));
								} else {
									$aCaptacionEnFuenteRegistro['id_encuesta'] = $idEncuesta;
									$aCaptacionEnFuente[] = $aCaptacionEnFuenteRegistro;
								}

								if (isset($oEncuestaExistente->id_uso_recurso_hidrico)) {
									$oKobo->updateSingle('uso_recurso_hidrico', $aUsoRecursoHidricoRegistro, array('id' => $oEncuestaExistente->id_uso_recurso_hidrico));
								} else {
									$aUsoRecursoHidricoRegistro['id_encuesta'] = $idEncuesta;
									$aUsoRecursoHidrico[] = $aUsoRecursoHidricoRegistro;
								}

								if (isset($oEncuestaExistente->id_informacion_juridica_captacion)) {
									$oKobo->updateSingle('informacion_juridica_captacion', $aInformacionJuridicaCaptacionRegistro, array('id' => $oEncuestaExistente->id_informacion_juridica_captacion));
								} else {
									$aInformacionJuridicaCaptacionRegistro['id_encuesta'] = $idEncuesta;
									$aInformacionJuridicaCaptacion[] = $aInformacionJuridicaCaptacionRegistro;
								}

								if (isset($oEncuestaExistente->id_fuente_receptora_vertimiento)) {
									$oKobo->updateSingle('fuente_receptora_vertimiento', $aFuenteReceptoraVertimientoRegistro, array('id' => $oEncuestaExistente->id_fuente_receptora_vertimiento));
								} else {
									$aFuenteReceptoraVertimientoRegistro['id_encuesta'] = $idEncuesta;
									$aFuenteReceptoraVertimiento[] = $aFuenteReceptoraVertimientoRegistro;
								}

								if (isset($oEncuestaExistente->id_informacion_vertimiento)) {
									$oKobo->updateSingle('informacion_vertimiento', $aInformacionVertimientoRegistro, array('id' => $oEncuestaExistente->id_informacion_vertimiento));
								} else {
									$aInformacionVertimientoRegistro['id_encuesta'] = $idEncuesta;
									$aInformacionVertimiento[] = $aInformacionVertimientoRegistro;
								}

								if (isset($oEncuestaExistente->id_informacion_juridica_vertimiento)) {
									$oKobo->updateSingle('informacion_juridica_vertimiento', $aInformacionJuridicaVertimientoRegistro, array('id' => $oEncuestaExistente->id_informacion_juridica_vertimiento));
								} else {
									$aInformacionJuridicaVertimientoRegistro['id_encuesta'] = $idEncuesta;
									$aInformacionJuridicaVertimiento[] = $aInformacionJuridicaVertimientoRegistro;
								}

								if (isset($oEncuestaExistente->id_informacion_fuente_ocupacion_cauce)) {
									$oKobo->updateSingle('informacion_fuente_ocupacion_cauce', $aInformacionOcupacionCauceRegistro, array('id' => $oEncuestaExistente->id_informacion_fuente_ocupacion_cauce));
								} else {
									$aInformacionOcupacionCauceRegistro['id_encuesta'] = $idEncuesta;
									$aInformacionOcupacionCauce[] = $aInformacionOcupacionCauceRegistro;
								}

								if (isset($oEncuestaExistente->id_informacion_ocupacion_cauce_obra_hidraulica)) {
									$oKobo->updateSingle('informacion_ocupacion_cauce_obra_hidraulica', $aInformacionOcupacionCauceObraHidraulicaRegistro, array('id' => $oEncuestaExistente->id_informacion_ocupacion_cauce_obra_hidraulica));
								} else {
									$aInformacionOcupacionCauceObraHidraulicaRegistro['id_encuesta'] = $idEncuesta;
									$aInformacionOcupacionCauceObraHidraulica[] = $aInformacionOcupacionCauceObraHidraulicaRegistro;
								}

								if (isset($oEncuestaExistente->id_informacion_actividad_minera_cuerpos_agua)) {
									$oKobo->updateSingle('informacion_actividad_minera_cuerpos_agua', $aInformacionActividadMineraCuerposAguaRegistro, array('id' => $oEncuestaExistente->id_informacion_actividad_minera_cuerpos_agua));
								} else {
									$aInformacionActividadMineraCuerposAguaRegistro['id_encuesta'] = $idEncuesta;
									$aInformacionActividadMineraCuerposAgua[] = $aInformacionActividadMineraCuerposAguaRegistro;
								}

								if (isset($oEncuestaExistente->id_actividades_extraccion)) {
									$oKobo->updateSingle('actividades_extraccion', $aActividadesExtraccionRegistro, array('id' => $oEncuestaExistente->id_actividades_extraccion));
								} else {
									$aActividadesExtraccionRegistro['id_encuesta'] = $idEncuesta;
									$aActividadesExtraccion[] = $aActividadesExtraccionRegistro;
								}

							} else if (!in_array($sIndice, $aIndicesEncuestasAgregar)) {
								$aEncuesta['fecha_registro'] = date('Y-m-d H:i:s');
								$oKobo->insertSingle('encuesta', $aEncuesta);

								//var_dump($oKobo->iLastInsertId);
								$idEncuesta = $oKobo->iLastInsertId;

								$aUsuarioEncuestaRegistro['id_encuesta'] = $idEncuesta;
								$aUsuarioEncuesta[] = $aUsuarioEncuestaRegistro;

								$aPredioRegistro['id_encuesta'] = $idEncuesta;
								$aPredio[] = $aPredioRegistro;

								$aFuenteCaptacionRegistro['id_encuesta'] = $idEncuesta;
								$aFuenteCaptacion[] = $aFuenteCaptacionRegistro;

								$aCaptacionEnFuenteRegistro['id_encuesta'] = $idEncuesta;
								$aCaptacionEnFuente[] = $aCaptacionEnFuenteRegistro;

								$aUsoRecursoHidricoRegistro['id_encuesta'] = $idEncuesta;
								$aUsoRecursoHidrico[] = $aUsoRecursoHidricoRegistro;

								$aInformacionJuridicaCaptacionRegistro['id_encuesta'] = $idEncuesta;
								$aInformacionJuridicaCaptacion[] = $aInformacionJuridicaCaptacionRegistro;

								$aFuenteReceptoraVertimientoRegistro['id_encuesta'] = $idEncuesta;
								$aFuenteReceptoraVertimiento[] = $aFuenteReceptoraVertimientoRegistro;

								$aInformacionVertimientoRegistro['id_encuesta'] = $idEncuesta;
								$aInformacionVertimiento[] = $aInformacionVertimientoRegistro;

								$aInformacionJuridicaVertimientoRegistro['id_encuesta'] = $idEncuesta;
								$aInformacionJuridicaVertimiento[] = $aInformacionJuridicaVertimientoRegistro;

								$aInformacionOcupacionCauceRegistro['id_encuesta'] = $idEncuesta;
								$aInformacionOcupacionCauce[] = $aInformacionOcupacionCauceRegistro;

								$aInformacionOcupacionCauceObraHidraulicaRegistro['id_encuesta'] = $idEncuesta;
								$aInformacionOcupacionCauceObraHidraulica[] = $aInformacionOcupacionCauceObraHidraulicaRegistro;

								$aInformacionActividadMineraCuerposAguaRegistro['id_encuesta'] = $idEncuesta;
								$aInformacionActividadMineraCuerposAgua[] = $aInformacionActividadMineraCuerposAguaRegistro;

								$aActividadesExtraccionRegistro['id_encuesta'] = $idEncuesta;
								$aActividadesExtraccion[] = $aActividadesExtraccionRegistro;

								$aIndicesEncuestasAgregar[] = $sIndice;
							}
						}
					}
				}
            }
        }
    }
}

if (count($aUsuarioEncuesta) > 0) {
    $oKobo->iniciarInsertMultiple('usuario_encuesta', $aUsuarioEncuesta);
}

if (count($aPredio) > 0) {
    $oKobo->iniciarInsertMultiple('predio', $aPredio);
}

if (count($aFuenteCaptacion) > 0) {
    $oKobo->iniciarInsertMultiple('fuente_captacion', $aFuenteCaptacion);
}

if (count($aCaptacionEnFuente) > 0) {
    $oKobo->iniciarInsertMultiple('captacion_en_fuente', $aCaptacionEnFuente);
}

if (count($aUsoRecursoHidrico) > 0) {
    $oKobo->iniciarInsertMultiple('uso_recurso_hidrico', $aUsoRecursoHidrico);
}

if (count($aInformacionJuridicaCaptacion) > 0) {
    $oKobo->iniciarInsertMultiple('informacion_juridica_captacion', $aInformacionJuridicaCaptacion);
}

if (count($aFuenteReceptoraVertimiento) > 0) {
    $oKobo->iniciarInsertMultiple('fuente_receptora_vertimiento', $aFuenteReceptoraVertimiento);
}

if (count($aInformacionVertimiento) > 0) {
    $oKobo->iniciarInsertMultiple('informacion_vertimiento', $aInformacionVertimiento);
}

if (count($aInformacionJuridicaVertimiento) > 0) {
    $oKobo->iniciarInsertMultiple('informacion_juridica_vertimiento', $aInformacionJuridicaVertimiento);
}

if (count($aInformacionOcupacionCauce) > 0) {
    $oKobo->iniciarInsertMultiple('informacion_fuente_ocupacion_cauce', $aInformacionOcupacionCauce);
}

if (count($aInformacionOcupacionCauceObraHidraulica) > 0) {
    $oKobo->iniciarInsertMultiple('informacion_ocupacion_cauce_obra_hidraulica', $aInformacionOcupacionCauceObraHidraulica);
}

if (count($aInformacionActividadMineraCuerposAgua) > 0) {
    $oKobo->iniciarInsertMultiple('informacion_actividad_minera_cuerpos_agua', $aInformacionActividadMineraCuerposAgua);
}

if (count($aActividadesExtraccion) > 0) {
    $oKobo->iniciarInsertMultiple('actividades_extraccion', $aActividadesExtraccion);
}

print_r($aNombresCampos);
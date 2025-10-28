<?php

class Administrar extends DataBase
{
    private $oPostData = null;
    private $oSesion = null;
    private $oArchivos = null;
    private $aEstructuraEncuesta = array();
    public $aCamposUsosGrupo = array();
    public $aCamposUsosDemanda = array();
    public $aCamposActividadVertimiento = array();
    public $aCamposOcupacionesCauceTipoObra = array();
    public $aCamposMineria = array();

    public function __construct($oPost = null) 
    {
        parent::__construct();
        $this->oPostData = $oPost;
        $this->oSesion = new Sesion();
        $this->oArchivos = new Archivos();
        $this->aEstructuraEncuesta = array();
    }

    public function iniciarSesion() : array
    {
        //$this->actualizaMunicipioUH();

        $this->sSql = "
            SELECT id, nombre, correo, contrasenia, editar_encuesta, agregar_encuesta, exportar_encuesta, administrar_usuarios, consultar_encuesta, fecha_caducidad
            FROM dbo.usuario
            WHERE correo = :correo
        ";

        $this->aExecute = array(':correo' => $this->oPostData->correo);
        $this->execQuery();
        $aLogin = array('bLogin' => false);

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oUsuario = (object) $r;

            $oUsuario->editar_encuesta = (bool) $oUsuario->editar_encuesta;
            $oUsuario->agregar_encuesta = (bool) $oUsuario->agregar_encuesta;
            $oUsuario->exportar_encuesta = (bool) $oUsuario->exportar_encuesta;
            $oUsuario->administrar_usuarios = (bool) $oUsuario->administrar_usuarios;
            $oUsuario->consultar_encuesta = (bool) $oUsuario->consultar_encuesta;

            $fechaActual = new DateTime();
            $fechaTerminacion = new DateTime($oUsuario->fecha_caducidad);
            $fechaTerminacion->modify('+3 days');

            if ($this->oPostData->correo === $oUsuario->correo && sha1($this->oPostData->contrasenia) === $oUsuario->contrasenia && $fechaActual <= $fechaTerminacion) {
                $aLogin['bLogin'] = true;
                $this->oSesion->iniciarSesion($oUsuario);

                $aData = array(
                    "id_usuario" => $oUsuario->id,
                    "fecha" => date('Y-m-d H:i:s')
                );

                $this->insertSingle('usuario_acceso', $aData);
            }
        }

        return $aLogin;
    }

    public function validarSesion() : array
    {   
        $bSesion = $this->oSesion->validarSesion();
        $sNombre = null;
        $bEditarEncuesta = false;
        $bAgregarEncuesta = false;
        $bExportarEncuesta = false;
        $bAdministrarUsuarios = false;
        $bConsultarEncuesta = false;
        
        if ($bSesion) {
            $this->sSql = "
                SELECT nombre, correo, editar_encuesta, agregar_encuesta, exportar_encuesta, administrar_usuarios, consultar_encuesta, fecha_caducidad
                FROM dbo.usuario
                WHERE id = :idUsuario
            ";

            $this->aExecute = array(':idUsuario' => $this->oSesion->idUsuario());
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $oUsuario = (object) $r;
                $sNombre = $oUsuario->nombre;
                $bEditarEncuesta = (bool) $oUsuario->editar_encuesta;
                $bAgregarEncuesta = (bool) $oUsuario->agregar_encuesta;
                $bExportarEncuesta = (bool) $oUsuario->exportar_encuesta;
                $bAdministrarUsuarios = (bool) $oUsuario->administrar_usuarios;
                $bConsultarEncuesta = (bool) $oUsuario->consultar_encuesta;
            }
        }

        if (!empty($oUsuario->fecha_caducidad)) {
            $fechaActual = new DateTime();
            $fechaTerminacion = new DateTime($oUsuario->fecha_caducidad);
            $fechaTerminacion->modify('+3 days');

            if ($fechaActual >= $fechaTerminacion) {
                $bSesion = false;
                $sNombre = null;
                $bEditarEncuesta = false;
                $bAgregarEncuesta = false;
                $bExportarEncuesta = false;
                $bAdministrarUsuarios = false;
                $bConsultarEncuesta = false;
                $this->oSesion->cerrarSesion();
            }
        }

        return array(
            'bSession' => $bSesion, 
            'nombre' => $sNombre,
            'editar_encuesta' => $bEditarEncuesta,
            'agregar_encuesta' => $bAgregarEncuesta,
            'exportar_encuesta' => $bExportarEncuesta,
            'administrar_usuarios' => $bAdministrarUsuarios,
            'consultar_encuesta' => $bConsultarEncuesta
        );
    }

    public function catalogoUsuarios() : array
    {
        $this->sSql = "
            SELECT id, nombre, correo, contrasenia, editar_encuesta, agregar_encuesta, exportar_encuesta, administrar_usuarios, consultar_encuesta, fecha_caducidad
            FROM dbo.usuario
            WHERE 1 = 1
        ";

        $aCatalogo = array();

        $this->aExecute = array();
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oUsuario = (object) $r;
            $oUsuario->editar_encuesta = (bool) $oUsuario->editar_encuesta;
            $oUsuario->agregar_encuesta = (bool) $oUsuario->agregar_encuesta;
            $oUsuario->exportar_encuesta = (bool) $oUsuario->exportar_encuesta;
            $oUsuario->administrar_usuarios = (bool) $oUsuario->administrar_usuarios;
            $oUsuario->consultar_encuesta = (bool) $oUsuario->consultar_encuesta;
            $aCatalogo[] = $oUsuario;
        }

        return array('catalogo' => $aCatalogo);
    }

    public function guardarEditarUsuario() : array
    {
        $aData = array(
            "nombre" => trim($this->oPostData->nombre),
            "correo" => trim(mb_strtolower($this->oPostData->correo_electronico)),
            "fecha_caducidad" => isset($this->oPostData->fecha_caducidad) ? $this->oPostData->fecha_caducidad : null,  // Añadir esta línea
            "editar_encuesta" => $this->oPostData->editar_encuesta ? 1 : 0,
            "agregar_encuesta" => $this->oPostData->agregar_encuesta ? 1 : 0,
            "exportar_encuesta" => $this->oPostData->exportar_encuesta ? 1 : 0,
            "administrar_usuarios" => $this->oPostData->administrar_usuarios ? 1 : 0,
            "consultar_encuesta" => $this->oPostData->consultar_encuesta ? 1 : 0
        );

        $bProceso = false;
        $idExcluir = isset($this->oPostData->id) ? $this->oPostData->id : 0;
        $iCantidadExiste = $this->countIdsAND('usuario', 'id', array('correo' => trim(mb_strtolower($this->oPostData->correo_electronico))), array(), $idExcluir);

        if ($iCantidadExiste === 0) {
            if (isset($this->oPostData->id)) {
                $bModificarContrasenia = isset($this->oPostData->modificar_contrasenia) ? $this->oPostData->modificar_contrasenia : false;

                if ($bModificarContrasenia) {
                    $aData['contrasenia'] = sha1($this->oPostData->contrasenia);
                    $aData['usuario_edicion'] = $this->oSesion->idUsuario();
                    $aData['fecha_edicion'] = date('Y-m-d H:i:s');
                }

                $bProceso = $this->updateSingle('usuario', $aData, array('id' => $this->oPostData->id));
            } else {
                $aData['contrasenia'] = sha1($this->oPostData->contrasenia);
                $aData['usuario_registro'] = $this->oSesion->idUsuario();
                $aData['fecha_registro'] = date('Y-m-d H:i:s');

                $bProceso = $this->insertSingle('usuario', $aData);
            }
        }

        return array('bProceso' => $bProceso, 'iCantidadExiste' => $iCantidadExiste);
    }

    public function encuestasDuplicadas(array $aEncuestasDuplicadas) {
        $aIdsEliminar = [];

        foreach ($aEncuestasDuplicadas as $sIndice => $aIds) {
            if (count($aIds) > 1) {
                $iNoEliminar = 0;

                foreach ($aIds as $oInfo) {
                    $iNoEliminar = $iNoEliminar === 0 ? $oInfo->id : $iNoEliminar;

                    if (!is_null($oInfo->id_usuario_edicion)) {
                        $iNoEliminar = $oInfo->id;
                    }
                }

                foreach ($aIds as $oInfo) {
                    if ($oInfo->id !== $iNoEliminar) {
                        $aIdsEliminar[] = $oInfo->id;
                    }
                }
            }
        }

        if (count($aIdsEliminar) > 0) {
            sort($aIdsEliminar);
            $this->aExecute = [];

            $this->sSql = "DELETE FROM dbo.captacion_en_fuente_caracteristicas_abastecimiento WHERE id_principal IN (SELECT id FROM dbo.captacion_en_fuente WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . "))";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.uso_recurso_hidrico_tipo_construccion WHERE id_principal IN (SELECT id FROM dbo.uso_recurso_hidrico WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . "))";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.uso_recurso_hidrico_riego_predominante WHERE id_principal IN (SELECT id FROM dbo.uso_recurso_hidrico WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . "))";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.informacion_vertimiento_tipo_descarga WHERE id_principal IN (SELECT id FROM dbo.informacion_vertimiento WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . "))";
            $this->execQuery();
            
            $this->sSql = "DELETE FROM dbo.actividades_extraccion WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();
            
            $this->sSql = "DELETE FROM dbo.captacion_en_fuente WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.fuente_captacion WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.fuente_receptora_vertimiento WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.informacion_actividad_minera_cuerpos_agua WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.informacion_fuente_ocupacion_cauce WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.informacion_juridica_captacion WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.informacion_juridica_vertimiento WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.informacion_ocupacion_cauce_obra_hidraulica WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.informacion_vertimiento WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.predio WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.uso_recurso_hidrico WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.usuario_encuesta WHERE id_encuesta IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();

            $this->sSql = "DELETE FROM dbo.encuesta WHERE id IN (" . implode(', ', $aIdsEliminar) . ")";
            $this->execQuery();
        }
    }

    public function conteoEncuestasSesion() 
    {
        $aEstructura = $this->cargarEstructuraEncuesta();

        $aConteoEncuestas = [
            'iDatosTotal' => $this->catalogoTotalEncuestas(),
            'captaciones' => [],
            'vertimiento' => [],
            'ocupacion' => [],
            'minera' => [],
            'estados' => []
        ];    

        foreach ($aEstructura['estructura'] as $oModulo) {
            if ($oModulo->id_modulo > 0) {
                $aIdsEncuestasFiltro = [];

                $this->sSql = "
                    SELECT t." . $oModulo->id_validar . " AS id_encuesta, t.*
                    FROM dbo." . $oModulo->tabla . " t 
                    WHERE 1 = 1
                ";

                $this->aExecute = array();
                $this->execQuery();

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {          
                    if (!in_array((int) $r['id_encuesta'], $aIdsEncuestasFiltro)) {
                        $bEncuestaValida = false;

                        foreach ($r as $clave => $valor) {
                            if ($valor !== null && $clave !== 'id_encuesta' && $clave !== 'id' && !$bEncuestaValida) {
                                $bEncuestaValida = true;
                            }
                        }

                        if ($bEncuestaValida) {
                            $aIdsEncuestasFiltro[] = (int) $r['id_encuesta'];
                        }
                    }
                }

                if ($oModulo->id_modulo === 1) {
                    $aConteoEncuestas['captaciones'] = $aIdsEncuestasFiltro;
                } else if ($oModulo->id_modulo === 2) {
                    $aConteoEncuestas['vertimiento'] = $aIdsEncuestasFiltro;
                } else if ($oModulo->id_modulo === 3) {
                    $aConteoEncuestas['ocupacion'] = $aIdsEncuestasFiltro;
                } else if ($oModulo->id_modulo === 4) {
                    $aConteoEncuestas['minera'] = $aIdsEncuestasFiltro;
                }
            }
        }

        //Estado encuesta
        $this->sSql = "SELECT id, descripcion FROM dbo.param_sublista WHERE id_lista = 32";
        $this->aExecute = [];
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $aConteoEncuestas['estados'][$r['id']] = $r['descripcion'];
        }  
        
        @session_start();
        $_SESSION['CONTEO_ENCUESTAS'] = $aConteoEncuestas;

        return [];
    }

    public function catalogoEncuestas() : array
    {
        $aEncuestas = array();
        $this->aExecute = array();
        $this->sSql = "";

        $sSqlEncuesta = "SELECT e.*, ev.descripcion AS 'estado_validacion' FROM dbo.encuesta e LEFT JOIN dbo.param_sublista ev ON (e.id_estado_validacion = ev.id) WHERE 1 = 1";
        $aFiltroEncuesta = array();

        if (isset($this->oPostData->codigo_encuesta)) {
            $sSqlEncuesta .= " AND e.codigo_encuesta LIKE :codigoEncuesta";
            $aFiltroEncuesta[':codigoEncuesta'] = '%' . $this->oPostData->codigo_encuesta . '%';
        }

        if (isset($this->oPostData->fecha_diligenciamiento_desde)) {
            $sSqlEncuesta .= " AND e.fecha_diligenciamiento >= :fechaDiligenciamiento1";
            $aFiltroEncuesta[':fechaDiligenciamiento1'] = $this->oPostData->fecha_diligenciamiento_desde . ' 00:00:00';
        }

        if (isset($this->oPostData->fecha_diligenciamiento_hasta)) {
            $sSqlEncuesta .= " AND e.fecha_diligenciamiento <= :fechaDiligenciamiento2";
            $aFiltroEncuesta[':fechaDiligenciamiento2'] = $this->oPostData->fecha_diligenciamiento_hasta . ' 23:59:59';
        }

        $sSqlEncuesta .= " ORDER BY e.fecha_diligenciamiento DESC, e.id ASC";

        $this->sSql = $sSqlEncuesta;
        $this->aExecute = $aFiltroEncuesta;

        $this->execQuery();

        $aEncuestasDuplicadas = [];

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $bFiltro = true;
            $oEncuesta = (object) $r;

            if (isset($this->oPostData->nombre_encuestador)) {
                $sIdNombre = isset($oEncuesta->nombre_encuestador) ? mb_strtolower($this->oSesion->sanearString($oEncuesta->nombre_encuestador)) : null;
                $bFiltro = $sIdNombre !== $this->oPostData->nombre_encuestador ? false : $bFiltro;
            }

            if (isset($this->oPostData->codigo_cuenca_hidrografica)) {
                $sIdNombre = isset($oEncuesta->codigo_cuenca_hidrografica) ? mb_strtolower($this->oSesion->sanearString($oEncuesta->codigo_cuenca_hidrografica)) : null;
                $bFiltro = $sIdNombre !== $this->oPostData->codigo_cuenca_hidrografica ? false : $bFiltro;
            }

            if (isset($this->oPostData->ingeniero_campo)) {
                $sIdNombre = isset($oEncuesta->ingeniero_campo) ? mb_strtolower($this->oSesion->sanearString($oEncuesta->ingeniero_campo)) : null;
                $bFiltro = $sIdNombre !== $this->oPostData->ingeniero_campo ? false : $bFiltro;
            }

            if ($bFiltro) {
                $aEncuestas[] = $oEncuesta;
            }

            //duplicadas
            if (isset($oEncuesta->codigo_encuesta, $oEncuesta->fecha_diligenciamiento) && !isset($oEncuesta->koboid)) {
                $sIndice = $oEncuesta->codigo_encuesta . '_' . $oEncuesta->fecha_diligenciamiento;
                $aEncuestasDuplicadas[$sIndice] = isset($aEncuestasDuplicadas[$sIndice]) ? $aEncuestasDuplicadas[$sIndice] : [];
                $aEncuestasDuplicadas[$sIndice][] = (object) ['id' => (int) $oEncuesta->id, 'id_usuario_edicion' => $oEncuesta->id_usuario_edicion];
            }
        }

        //$this->encuestasDuplicadas($aEncuestasDuplicadas);

        return array('catalogo' => $aEncuestas);
    }

    public function catalogoEncuestasConsultas() : array
    {
        if (empty($this->oPostData->filtros)) {
            return $this->catalogoEncuestasSinFiltros();
        } else {
            return $this->catalogoEncuestasConFiltros();
        }
    }

    public function catalogoEncuestasSinFiltros() : array
    {
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aIdsEncuestasFiltro = [];
        $aIdsEncuestasTotal = [];
        $aEncuestas = array();
        $iDatosFiltrados = 0;
        $iContador = 1;
        $iPosicion = 0;
        $iDatosTotal = $this->catalogoTotalEncuestas();
        $ordenar = $this->catalogoColumnasOrdenar();

        $this->oPostData->modulos = isset($this->oPostData->modulos) ? $this->oPostData->modulos : [];

        @session_start();

        foreach ($aEstructura['estructura'] as $oModulo) {
            if (in_array($oModulo->id_modulo, $this->oPostData->modulos)) {
                if ($oModulo->id_modulo === 1) {
                    $aIdsEncuestasTotal = array_merge($aIdsEncuestasTotal, $_SESSION['CONTEO_ENCUESTAS']['captaciones']); 
                } else if ($oModulo->id_modulo === 2) {
                    $aIdsEncuestasTotal = array_merge($aIdsEncuestasTotal, $_SESSION['CONTEO_ENCUESTAS']['vertimiento']);
                } else if ($oModulo->id_modulo === 3) {
                    $aIdsEncuestasTotal = array_merge($aIdsEncuestasTotal, $_SESSION['CONTEO_ENCUESTAS']['ocupacion']);
                } else if ($oModulo->id_modulo === 4) {
                    $aIdsEncuestasTotal = array_merge($aIdsEncuestasTotal, $_SESSION['CONTEO_ENCUESTAS']['minera']);
                }       
            }
        }

        $aIdsEncuestasFiltro = array_values(array_unique($aIdsEncuestasTotal));

        if (!empty($aIdsEncuestasFiltro)) {
            $this->sSql = "
                SELECT e.id, e.id_edicion, e.codigo_encuesta, e.codigo_cuenca_hidrografica, e.fecha_diligenciamiento, e.id_estado_validacion
                FROM dbo.encuesta e
                ORDER BY e." . $ordenar->columna . " " . $ordenar->direccion . "
            ";

            /*
            WHERE e.id IN (" . implode(', ', array_fill(0, count($aIdsEncuestasFiltro), '?')) . ")
            ORDER BY e." . $ordenar->columna . " " . $ordenar->direccion . "
                OFFSET " . $this->oPostData->start . " ROWS
                FETCH NEXT " . $this->oPostData->length . " ROWS ONLY 
                */

            $this->aExecute = array();
            $this->execQuery();

            $iStart = 0;

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if (in_array($r['id'], $aIdsEncuestasFiltro)) {
                    if ($iStart >= $this->oPostData->start && count($aEncuestas) < $this->oPostData->length) {
                        $r['estado_validacion'] = isset($r['id_estado_validacion']) ? (isset($_SESSION['CONTEO_ENCUESTAS']['estados'][$r['id_estado_validacion']]) ? $_SESSION['CONTEO_ENCUESTAS']['estados'][$r['id_estado_validacion']] : "") : "";
                        $aEncuestas[] = $r;
                    }

                    $iStart++;
                }               
            }
        }

        return [
            'draw' => $this->oPostData->draw,
            'data' => $aEncuestas,
            'recordsFiltered' => count($aIdsEncuestasFiltro),
            'recordsTotal' => $_SESSION['CONTEO_ENCUESTAS']['iDatosTotal']
        ];
    }

    /*
    * Total de encuestas
    */
    public function catalogoTotalEncuestas() {
        $this->sSql = "SELECT COUNT(id) AS 'cantidad' FROM dbo.encuesta";        
        $this->aExecute = array();
        $this->execQuery();
        $iDatosTotal = 0;

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $iDatosTotal = (int) $r['cantidad'];
        }

        return $iDatosTotal;
    }

    /*
    * Determinar columnas a ordenar
    */
    public function catalogoColumnasOrdenar() {
        $bOrdenarColumna = isset($this->oPostData->order) ? (isset($this->oPostData->order[0]) ? (isset($this->oPostData->order[0]['column']) ? $this->oPostData->order[0]['column'] : false) : false) : false;
        $sDireccionOrdenar = isset($this->oPostData->order) ? (isset($this->oPostData->order[0]) ? (isset($this->oPostData->order[0]['dir']) ? $this->oPostData->order[0]['dir'] : 'desc') : 'desc') : 'desc';
        $sColumnaOrdenar = 'codigo_encuesta';

        if ($bOrdenarColumna) {
            switch((int) $this->oPostData->order[0]['column']) {
                case 0: $sColumnaOrdenar = 'codigo_encuesta'; break;
                case 1: $sColumnaOrdenar = 'codigo_cuenca_hidrografica'; break;
                case 2: $sColumnaOrdenar = 'fecha_diligenciamiento'; break;
                case 3: $sColumnaOrdenar = 'estado_validacion'; break;
            }
        }

        return (object) ['columna' => $sColumnaOrdenar, 'direccion' => $sDireccionOrdenar];
    }

    public function catalogoEncuestasSinFiltros2() : array
    {
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aIdsEncuestasFiltro = [];
        $aEncuestas = array();        

        foreach ($aEstructura['estructura'] as $oModulo) {
            if (in_array($oModulo->id_modulo, $this->oPostData->modulos)) {
                $aCamposFiltro = [];

                foreach ($oModulo->grupos as $oGrupo) {
                    if (isset($oGrupo->campos)) {
                        foreach ($oGrupo->campos as $oCampo) {
                            if (isset($oCampo->elementos)) {

                            } else {
                                $aCamposFiltro[] = "t." . $oCampo->bd . " IS NOT NULL";
                            }                            
                        }
                    }
                }

                $this->sSql = "SELECT t." . $oModulo->id_validar . " AS id_encuesta FROM dbo." . $oModulo->tabla . " t WHERE ";
                $this->aExecute = array();

                /*if (count($aIdsEncuestasFiltro) > 0) {
                    $this->sSql .= "t." . $oModulo->id_validar . " NOT IN (" . implode(', ', array_fill(0, count($aIdsEncuestasFiltro), '?')) . ") AND (" . implode(' OR ', $aCamposFiltro) . ")";
                    $this->aExecute = $aIdsEncuestasFiltro;
                } else {
                    $this->sSql .= implode(' OR ', $aCamposFiltro);
                }*/

                $this->sSql .= implode(' OR ', $aCamposFiltro);

                $this->execQuery();

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    if (!in_array((int) $r['id_encuesta'], $aIdsEncuestasFiltro)) {
                        $aIdsEncuestasFiltro[] = (int) $r['id_encuesta'];
                    }                    
                }
            }
        }

        if (!empty($aIdsEncuestasFiltro)) {
            $this->sSql = "SELECT e.*, ev.descripcion AS 'estado_validacion' FROM dbo.encuesta e LEFT JOIN dbo.param_sublista ev ON (e.id_estado_validacion = ev.id) WHERE e.id IN (" . implode(', ', array_fill(0, count($aIdsEncuestasFiltro), '?')) . ")";
            $this->aExecute = $aIdsEncuestasFiltro;
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $aEncuestas[] = $r;
            }
        }

        return $aEncuestas;
    }

    public function catalogoEncuestasConFiltros() : array 
    {
        @session_start();
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aIdsEncuestasFiltro = [];
        $aEncuestas = array();  
        $iDatosTotal = $this->catalogoTotalEncuestas();
        $ordenar = $this->catalogoColumnasOrdenar();      

        foreach ($aEstructura['estructura'] as $oModulo) {
            $this->aExecute = array();
            $aSelectorCampos = [];
            $aFiltrosListasCampos = [];
            $aFiltrarValores = [];
            $aFiltrarListas = [];

            foreach ($oModulo->grupos as $oGrupo) {
                if (isset($oGrupo->campos)) {
                    foreach ($oGrupo->campos as $oCampo) {
                        $sIndice = $oModulo->tabla . '_' . $oCampo->bd;

                        if (isset($oCampo->id_lista)) {
                            foreach ($this->oPostData->filtros as $aFiltro) {
                                $oFiltro = (object)  $aFiltro;

                                if ($oFiltro->id === $sIndice && count($oFiltro->seleccionados) > 0) {
                                    $aFiltrosListasCampos[] =  "t." . $oCampo->bd . " IN (" . implode(', ', array_fill(0, count($oFiltro->seleccionados), '?')) . ")";
                                    $aSelectorCampos[] = "t." . $oCampo->bd;
                                    $aFiltrarListas[$sIndice] = $oFiltro->seleccionados;

                                    foreach ($oFiltro->seleccionados as $iSeleccion) {
                                        $this->aExecute[] = $iSeleccion;
                                    }
                                }
                            }
                        } else if (isset($oCampo->elementos)) {
                            
                        } else {
                            foreach ($this->oPostData->filtros as $aFiltro) {
                                $oFiltro = (object)  $aFiltro;

                                if ($oFiltro->id === $sIndice && count($oFiltro->seleccionados) > 0) {
                                    $aSelectorCampos[] = "t." . $oCampo->bd;
                                    $aFiltrarValores[$sIndice] = $oFiltro->seleccionados;
                                }
                            }
                        }
                    }
                } else if (isset($oGrupo->filas)) {
                    foreach ($oGrupo->filas as $aCeldas) {
                        foreach ($aCeldas as $oCampo) {
                            $sIndice = $oModulo->tabla . '_' . $oCampo->bd;

                            if (isset($oCampo->id_lista)) {
                                foreach ($this->oPostData->filtros as $aFiltro) {
                                    $oFiltro = (object)  $aFiltro;

                                    if ($oFiltro->id === $sIndice && count($oFiltro->seleccionados) > 0) {
                                        $aFiltrosListasCampos[] =  "t." . $oCampo->bd . " IN (" . implode(', ', array_fill(0, count($oFiltro->seleccionados), '?')) . ")";
                                        $aSelectorCampos[] = "t." . $oCampo->bd;
                                        $aFiltrarListas[$sIndice] = $oFiltro->seleccionados;
    
                                        foreach ($oFiltro->seleccionados as $iSeleccion) {
                                            $this->aExecute[] = $iSeleccion;
                                        }
                                    }
                                }
                            } else {
                                foreach ($this->oPostData->filtros as $aFiltro) {
                                    $oFiltro = (object)  $aFiltro;

                                    if ($oFiltro->id === $sIndice && count($oFiltro->seleccionados) > 0) {
                                        $aSelectorCampos[] = "t." . $oCampo->bd;
                                        $aFiltrarValores[$sIndice] = $oFiltro->seleccionados;
                                    }
                                }
                            }
                        }                            
                    }
                }
            }

            if (count($aSelectorCampos) > 0) {
                $this->sSql = "SELECT t." . $oModulo->id_validar . "CAMPOS_TABLA_JOIN FROM dbo." . $oModulo->tabla . " t";

                if ($oModulo->tabla === 'encuesta') {
                    $this->sSql = str_replace("CAMPOS_TABLA_JOIN", "", $this->sSql);
                } else {
                    $this->sSql = str_replace("CAMPOS_TABLA_JOIN", ", " . implode(", ", $aSelectorCampos), $this->sSql);

                    if (count($aFiltrosListasCampos) > 0) {
                        $this->sSql .= " WHERE 1 = 1 OR " . implode(' OR ', $aFiltrosListasCampos);
                    }
                }

                if (count($aIdsEncuestasFiltro) > 0) {
                    $this->aExecute = array_merge($this->aExecute, $aIdsEncuestasFiltro);
                }

                $this->execQuery();

                $iStart = 0;

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $bFiltroValido = false;

                    if (!in_array($r['id_encuesta'], $aIdsEncuestasFiltro)) {
                        foreach($r as $clave => $valor) {
                            if (!$bFiltroValido) {
                                $sIndice = $oModulo->tabla . '_' . $clave;                    
    
                                if (isset($aFiltrarValores[$sIndice], $valor)) {
                                    $sValorBuscar = md5((string) $valor);
                                    $bFiltroValido = in_array($sValorBuscar, $aFiltrarValores[$sIndice]) ? true : $bFiltroValido;
                                } else if (isset($aFiltrarListas[$sIndice], $valor)) {
                                    $bFiltroValido = in_array((string) $valor, $aFiltrarListas[$sIndice]) ? true : $bFiltroValido;
                                }
                            }                        
                        }
                    }                   

                    if ($bFiltroValido) {
                        $aIdsEncuestasFiltro[] = $r['id_encuesta'];
                    }
                }
            }
        }

        if (!empty($aIdsEncuestasFiltro)) {
            $this->sSql = "
                SELECT e.id, e.id_edicion, e.codigo_encuesta, e.codigo_cuenca_hidrografica, e.fecha_diligenciamiento, e.id_estado_validacion
                FROM dbo.encuesta e
                ORDER BY e." . $ordenar->columna . " " . $ordenar->direccion . "
            ";

            $this->aExecute = array();
            $this->execQuery();

            $iStart = 0;

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if (in_array($r['id'], $aIdsEncuestasFiltro)) {
                    if ($iStart >= $this->oPostData->start && count($aEncuestas) < $this->oPostData->length) {
                        $r['estado_validacion'] = isset($r['id_estado_validacion']) ? (isset($_SESSION['CONTEO_ENCUESTAS']['estados'][$r['id_estado_validacion']]) ? $_SESSION['CONTEO_ENCUESTAS']['estados'][$r['id_estado_validacion']] : "") : "";
                        $aEncuestas[] = $r;
                    }

                    $iStart++;
                }               
            }
        }

        return [
            'draw' => $this->oPostData->draw,
            'data' => $aEncuestas,
            'recordsFiltered' => count($aIdsEncuestasFiltro),
            'recordsTotal' => $_SESSION['CONTEO_ENCUESTAS']['iDatosTotal']
        ];
    }

    public function informacionEncuesta() : array
    {
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aEstructuraEncuesta = $aEstructura['estructura'];
        $aIdsListas = [];
        $aListas = $aEstructura['listas'];
        $aOpcionesMultiples = [];
        $bModulo1 = true;
        $bModulo2 = true;
        $bModulo3 = true;
        $bModulo4 = true;

        $idEncuesta = 0;

        if (isset($this->oPostData->id)) {
            $idEncuesta = $this->oPostData->id;
        } else if (isset($this->oPostData->id_edicion)) {
            $this->sSql = "SELECT id FROM dbo.encuesta WHERE id_edicion = :idEncuestaEdicion";
            $this->aExecute = array(':idEncuestaEdicion' => $this->oPostData->id_edicion);
            $this->execQuery();
            
            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $idEncuesta = (int) $r['id'];
            }
        }

        if ($this->oPostData->accion === 'ver') {
            foreach ($aListas as $aLista) {
                $aIdsListas[$aLista['id']] = $aLista['descripcion'];
            }
        }

        //Tablas múltiple selección
        $aMultiple = [
            ['origen' => 'captacion_en_fuente', 'opciones' => 'captacion_en_fuente_caracteristicas_abastecimiento', 'campo' => 'caracteristicas_abastecimiento'], 
            ['origen' => 'uso_recurso_hidrico', 'opciones' => 'uso_recurso_hidrico_tipo_construccion', 'campo' => 'tipo_construccion'], 
            ['origen' => 'uso_recurso_hidrico', 'opciones' => 'uso_recurso_hidrico_riego_predominante', 'campo' => 'riego_predominante'],
            ['origen' => 'informacion_vertimiento', 'opciones' => 'informacion_vertimiento_tipo_descarga', 'campo' => 'tipo_descarga']
        ];

        foreach ($aMultiple as $aTablaOpcionesMultiple) {
            $this->sSql = "
                SELECT * 
                FROM dbo." . $aTablaOpcionesMultiple['origen'] . " o INNER JOIN dbo." . $aTablaOpcionesMultiple['opciones'] . " l ON (l.id_principal = o.id)
                WHERE o.id_encuesta = :idEncuesta
            ";

            $this->aExecute = array(':idEncuesta' => $idEncuesta);
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $sIndice = $aTablaOpcionesMultiple['origen'] . '_'  . $aTablaOpcionesMultiple['campo'];
                $aOpcionesMultiples[$sIndice] = isset($aOpcionesMultiples[$sIndice]) ? $aOpcionesMultiples[$sIndice] : [];
                $aOpcionesMultiples[$sIndice][] = (int) $r['id_sublista'];
            }
        }
        
        $this->aExecute = array(':idEncuesta' => $idEncuesta);

        foreach ($aEstructuraEncuesta as $i => $oTabla) {
            $this->sSql = "SELECT * FROM dbo." . $oTabla->tabla . " WHERE " . $oTabla->id_validar . " = :idEncuesta";
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if ($oTabla->tabla === 'encuesta') {
                    $aModulos = isset($r['modulos']) ? explode('|', $r['modulos']) : [];
                    $bModulo1 = in_array("1", $aModulos);
                    $bModulo2 = in_array("2", $aModulos);
                    $bModulo3 = in_array("3", $aModulos);
                    $bModulo4 = in_array("4", $aModulos);
                }

                foreach ($oTabla->grupos as $oGrupo) {
                    if (isset($oGrupo->campos)) {
                        foreach ($oGrupo->campos as $oCampoGrupo) {

                            if ($oCampoGrupo->tipo === 'numero' || $oCampoGrupo->tipo === 'decimal' || $oCampoGrupo->tipo === 'seleccion_simple_texto_editable') {
                                $oCampoGrupo->tipo = 'texto';

                                if (isset($oCampoGrupo->id_lista)) {
                                    $oCampoGrupo->id_lista = null;
                                }
                            }

                            if (isset($r[$oCampoGrupo->bd])) {
                                if (isset($oCampoGrupo->id_lista) && $this->oPostData->accion === 'ver') {
                                    $oCampoGrupo->valor = is_numeric($r[$oCampoGrupo->bd]) ? (isset($aIdsListas[$r[$oCampoGrupo->bd]]) ? $aIdsListas[$r[$oCampoGrupo->bd]] : null) : null;
                                } else {
                                    $oCampoGrupo->valor = $r[$oCampoGrupo->bd];

                                    if ($oCampoGrupo->tipo === 'imagen' && $oCampoGrupo->valor !== null) {
                                        $oCampoGrupo->url = $this->moverArchivoObtenerURL($oCampoGrupo->valor);
                                    }
                                }
                            } else if (isset($oCampoGrupo->elementos) && $this->oPostData->accion === 'ver') {
                                $sIndice = $oTabla->tabla . '_'  . $oCampoGrupo->bd;
                                $aValores = isset($aOpcionesMultiples[$sIndice]) ? $aOpcionesMultiples[$sIndice] : [];

                                $aDescripciones = [];

                                foreach ($aValores as $idValor) {
                                    if (isset($aIdsListas[$idValor])) {
                                        $aDescripciones[] = $aIdsListas[$idValor];
                                    }
                                }

                                $oCampoGrupo->valor = count($aDescripciones) > 0 ? implode(' ', $aDescripciones) : null;
                            } else if (isset($oCampoGrupo->elementos) && $this->oPostData->accion === 'editar') {
                                $sIndice = $oTabla->tabla . '_'  . $oCampoGrupo->bd;
                                $oCampoGrupo->valor = isset($aOpcionesMultiples[$sIndice]) ? $aOpcionesMultiples[$sIndice] : [];
                            }

                            if ($oCampoGrupo->tipo === 'fecha' && $oCampoGrupo->valor !== null && $oCampoGrupo->valor !== '') {
                                $oCampoGrupo->valor = substr($oCampoGrupo->valor, 0, 10);
                            }
                        }
                    } else if (isset($oGrupo->filas)) {
                        foreach ($oGrupo->filas as $aFilaCampos) {
                            foreach ($aFilaCampos as $oCampoGrupo) {
                                if (isset($r[$oCampoGrupo->bd])) {
                                    $oCampoGrupo->valor = $r[$oCampoGrupo->bd];
                                }
                            }
                        }
                    }
                }
            }

            $oTabla->mostrar = false;

            foreach ($oTabla->grupos as $oGrupo) {
                $oGrupo->mostrar = false;
                $oGrupo->latitud = null;
                $oGrupo->longitud = null;
                $oGrupo->contenedor = isset($oGrupo->contenedor) ? $oGrupo->contenedor : '';

                if (isset($oGrupo->campos)) {
                    foreach ($oGrupo->campos as $oCampoGrupo) {
                        if ($this->oPostData->accion === 'ver') {
                            $oCampoGrupo->mostrar = isset($oCampoGrupo->valor);
                            $oGrupo->mostrar = $oCampoGrupo->mostrar ? true : $oGrupo->mostrar;
                        } else if ($this->oPostData->accion === 'editar') {
                            $oGrupo->mostrar = !isset($oGrupo->dependiente);
                        }
    
                        if ($oGrupo->tipo === 'mapa' && $oCampoGrupo->bd === 'latitud' && is_numeric($oCampoGrupo->valor)) {
                            $oGrupo->latitud = (float) $oCampoGrupo->valor;
                        } else if ($oGrupo->tipo === 'mapa' && $oCampoGrupo->bd === 'longitud' && is_numeric($oCampoGrupo->valor)) {
                            $oGrupo->longitud = (float) $oCampoGrupo->valor;
                        }
                    }
                } else if (isset($oGrupo->filas)) {
                    foreach ($oGrupo->filas as $aFilaCampos) {
                        foreach ($aFilaCampos as $oCampoGrupo) {
                            if ($this->oPostData->accion === 'ver') {
                                $oCampoGrupo->mostrar = isset($oCampoGrupo->valor);
                                $oGrupo->mostrar = $oCampoGrupo->mostrar ? true : $oGrupo->mostrar;
                            } else if ($this->oPostData->accion === 'editar') {
                                $oGrupo->mostrar = !isset($oGrupo->dependiente);
                            }
                        }
                    }
                }
                
                $oTabla->mostrar = $oGrupo->mostrar ? true : $oTabla->mostrar;
            }

            $aEstructuraEncuesta[$i] = $oTabla;
        }

        return array(
            'id' => $idEncuesta, 
            'modulo1' => $bModulo1, 
            'modulo2' => $bModulo2, 
            'modulo3' => $bModulo3, 
            'modulo4' => $bModulo4, 
            'estructura' => $aEstructuraEncuesta, 
            'listas' => $aListas
        );
    }

    public function cargarEstructuraEncuesta() : array
    {
        $aListas = [];
        $this->estructuraEncuesta();

        if (isset($this->oPostData->listas)) {
            $aListasSoloNumeros = [38, 39, 40, 41];
            $this->sSql = "SELECT * FROM dbo.param_sublista WHERE id_lista NOT IN (" . implode(', ', $aListasSoloNumeros) . ") ORDER BY id_lista, descripcion";
            $this->aExecute = array();
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $aListas[] = array(
                    'id' => $r['id'],
                    'id_lista' => $r['id_lista'],
                    'descripcion' => $r['descripcion'],
                    'codigo' => $r['codigo'],
                    'id_kobo' => $r['id_kobo']
                );        
            }
            
            $this->sSql = "SELECT * FROM dbo.param_sublista WHERE id_lista IN (" . implode(', ', $aListasSoloNumeros) . ") ORDER BY id_lista, id";
            $this->aExecute = array();
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $aListas[] = array(
                    'id' => $r['id'],
                    'id_lista' => $r['id_lista'],
                    'descripcion' => $r['descripcion'],
                    'codigo' => $r['codigo'],
                    'id_kobo' => $r['id_kobo']
                );        
            }
        }

        return array('estructura' => $this->aEstructuraEncuesta, 'listas' => $aListas);
    }

    private function normalizar_ws(string $s) : string {
        $s = trim($s);
        return preg_replace('/\s+/u', ' ', $s);
    }

    private function construirPermutacionTitulos(array $titulosActuales, array $ordenDeseado, bool $incluirFaltantes = false) : array {
        $titNorm   = array_map([$this, 'normalizar_ws'], $titulosActuales);
        $ordenNorm = array_map([$this, 'normalizar_ws'], $ordenDeseado);
        $usados = array_fill(0, count($titNorm), false);
        $perm   = [];
        foreach ($ordenNorm as $wanted) {
            $idx = -1;
            for ($i = 0; $i < count($titNorm); $i++) {
                if (!$usados[$i] && $titNorm[$i] === $wanted) { $idx = $i; break; }
            }
            if ($idx >= 0) { $usados[$idx] = true; $perm[] = $idx; }
            else if ($incluirFaltantes) { $perm[] = -1; }
        }
        for ($i = 0; $i < count($titNorm); $i++) {
            if (!$usados[$i]) { $perm[] = $i; }
        }
        return $perm;
    }

    private function aplicarPermutacionFila(array $fila, array $perm) : array {
        if (empty($perm)) return $fila;
        $out = [];
        foreach ($perm as $idx) { $out[] = ($idx === -1) ? '' : ($fila[$idx] ?? ''); }
        return $out;
    }

    public function obtenerDatosEncuestasCSV() : array
    {
        /* ---------- 0. Parámetros generales ---------- */
        $batchSize            = 100;            // tamaño del lote que se usará después
        $this->oPostData->listas = true;

        /* ---------- 1. Cargar estructura y catálogos ---------- */
        $e                    = $this->cargarEstructuraEncuesta();
        $aEstructuraEncuesta  = $e['estructura'];
        $aIdsListas           = [];
        foreach ($e['listas'] as $l) {
            $aIdsListas[$l['id']] = $l['descripcion'];
        }

        /* ---------- 2. Campos solicitados por tabla ---------- */
        $aTablasCampo = [];
        foreach ($this->oPostData->campos as $c) {
            $aTablasCampo[$c->tabla][] = $c->campo;
        }

        /* ---------- 3. IDs solicitados, para filtrar rápido ---------- */
        $idsSolicitados = array_map('intval', $this->oPostData->ids);
        $setSolicitados = array_flip($idsSolicitados);

        /* ---------- 4. Obtener **todos** los id ordenados ---------- */
        $this->sSql = "
            SELECT id, codigo_encuesta
            FROM dbo.encuesta
            ORDER BY codigo_encuesta ASC
        ";
        $this->aExecute = [];
        $this->execQuery();
        $idsOrdenados = [];
        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if (isset($setSolicitados[$r['id']])) {
                $idsOrdenados[] = (int)$r['id'];
            }
        }

        /* ---------- 5. Definición de tablas con selección múltiple ---------- */
        $aMultiple = [
            ['origen'=>'captacion_en_fuente',     'opciones'=>'captacion_en_fuente_caracteristicas_abastecimiento', 'campo'=>'caracteristicas_abastecimiento'],
            ['origen'=>'uso_recurso_hidrico',     'opciones'=>'uso_recurso_hidrico_tipo_construccion',             'campo'=>'tipo_construccion'],
            ['origen'=>'uso_recurso_hidrico',     'opciones'=>'uso_recurso_hidrico_riego_predominante',            'campo'=>'riego_predominante'],
            ['origen'=>'informacion_vertimiento', 'opciones'=>'informacion_vertimiento_tipo_descarga',             'campo'=>'tipo_descarga'],
        ];

        /* ---------- 6. Preparar el archivo CSV y los encabezados UNA SOLA VEZ ---------- */
        $archivo = date('YmdHis') . 'encuestas.csv';
        $fp      = fopen(PATH_TEMP . $archivo, 'wb');
        if (!$fp) {
            throw new RuntimeException('No se pudo crear el CSV.');
        }
        // BOM UTF-8
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

        // Construyo el array de títulos SOLO UNA VEZ
        $aTitulos = [];
        foreach ($aEstructuraEncuesta as $oTabla) {
            if (empty($aTablasCampo[$oTabla->tabla])) continue;
            foreach ($oTabla->grupos as $oGrupo) {
                $campos = isset($oGrupo->campos)
                        ? $oGrupo->campos
                        : (isset($oGrupo->filas) ? array_merge(...$oGrupo->filas) : []);
                foreach ($campos as $oCampo) {
                    if (!in_array($oCampo->bd, $aTablasCampo[$oTabla->tabla], true)) continue;
                    $aTitulos[] = $oCampo->titulo;
                }
            }
        }

        // Escribo la fila de encabezados
        $__ORDEN_DESEADO__ = [
            'MODULO',
            '0. Código encuesta',
            '1. Código asociado a la cuenca hidrográfica nivel I',
            '2. Fecha diligenciamiento',
            '4. Tipo de usuario',
            '5. Nombre del usuario',
            '6. Tipo de identificación',
            '7. Número de documento',
            '8. Representante legal o administrador',
            '9. Tipo documento identificación representante legal',
            '10. Número de documento',
            '11a. Dirección domicilio usuario, Vereda',
            '11b. Dirección domicilio usuario, Referencia',
            '11c. Dirección domicilio usuario, Nombre del predio',
            '12. Teléfono(s) domicilio',
            '13. Dirección correspondencia usuario',
            '14. Teléfono(s) correspondencia',
            '15. Nombre Predio',
            '16. Área (Has)',
            '17. Dirección del Predio',
            '18. Departamento',
            '19. Código DANE departamento',
            '20. Corregimiento',
            '21. Municipio',
            '22. Codigo DANE municipio',
            '23. Vereda',
            '24. Cédula Catastral',
            '25. Tenencia tipo',
            '26. Latitud (x.y °)',
            '26. Longitud (x.y °)',
            '26. Altitud (m)',
            '26. Precisión (m)',
            '26a. Este- x(m)',
            '26a. Norte-y(m)',
            '26a. Altura(m.s.n.m)',
            '27. Fuente superficial',
            '¿Cuál?',
            '28. Nombre de la fuente',
            '29. Observaciones',
            '30. Departamento',
            '31. Código DANE departamento',
            '32. Municipio',
            '33. Codigo DANE municipio',
            '34. Corregimiento',
            '35. Vereda',
            '36. Nombre unidad hidrográfica nivel I',
            '37. Código unidad hidrográfica nivel I',
            '38. Nombre unidad hidrográfica nivel II',
            '39. Código unidad hidrográfica nivel II',
            '40. Tipo de captación',
            '¿Cuál?',
            '41. Estado',
            '42. Diámetro (pulgada)',
            '42. Observaciones diámetro (pulgada)',
            '43. Ancho (m)',
            '43. Observaciones ancho (m)',
            '44. Alto (m)',
            '44. Observaciones alto (m)',
            '45. Profundidad (m)',
            '45. Observaciones profundidad (m)',
            '46. Tiempo captación (Hr/día)',
            '46. Observaciones tiempo captación (Hr/día)',
            '47. Frecuencia (día/mes)',
            '47. Observaciones frecuencia (día/mes)',
            '48. Capacidad bomba (HP)',
            '48. Observaciones capacidad bomba (HP)',
            '49. Capacidad Instalada (l/s)',
            '49. Observaciones capacidad Instalada (l/s)',
            'Características sistema de abastecimiento',
            '56. Observaciones de la captación',
            '57. Latitud (x.y °)',
            '57. Longitud (x.y °)',
            '57. Altitud (m)',
            '57. Precisión (m)',
            '57a. Este- x(m)',
            '57a. Norte-y(m)',
            '57a. Altura(m.s.n.m)',
            '59. Esquema de localización y/o punto de captación',
            '60. Uso doméstico',
            '61. Número de personas permanentes',
            '62. Número de personas transitorias',
            '63. Días/mes',
            '64. Consumo (l/s)',
            '65. Menores de 5 años',
            '66. Mayores de 60 años',
            '67. Tipo de construcción',
            '¿Cuál?',
            '68. Uso pecuario',
            'Uso pecuario I Tipo',
            'Uso pecuario I Número',
            'Uso pecuario I Consumo (l/s)',
            'Uso pecuario II Tipo',
            'Uso pecuario II Número',
            'Uso pecuario II Consumo (l/s)',
            'Uso pecuario III Tipo',
            'Uso pecuario III Número',
            'Uso pecuario III Consumo (l/s)',
            'Uso pecuario IV Tipo',
            'Uso pecuario IV Número',
            'Uso pecuario IV Consumo (l/s)',
            '81. Uso acuícola',
            'Uso acuícola I Tipo',
            'Uso acuícola I Número',
            'Uso acuícola I Consumo (l/s)',
            'Uso acuícola II Tipo',
            'Uso acuícola II Número',
            'Uso acuícola II Consumo (l/s)',
            'Uso acuícola III Tipo',
            'Uso acuícola III Número',
            'Uso acuícola III Consumo (l/s)',
            'Uso acuícola IV Tipo',
            'Uso acuícola IV Número',
            'Uso acuícola IV Consumo (l/s)',
            '94. Uso agrícola y silvícola',
            'Uso agrícola y silvícola I Tipo',
            'Uso agrícola y silvícola I Número',
            'Uso agrícola y silvícola I Consumo (l/s)',
            'Uso agrícola y silvícola II Tipo',
            'Uso agrícola y silvícola II Número',
            'Uso agrícola y silvícola II Consumo (l/s)',
            'Uso agrícola y silvícola III Tipo',
            'Uso agrícola y silvícola III Número',
            'Uso agrícola y silvícola III Consumo (l/s)',
            'Uso agrícola y silvícola IV Tipo',
            'Uso agrícola y silvícola IV Número',
            'Uso agrícola y silvícola IV Consumo (l/s)',
            '116. Tipo de riego predominante',
            '¿Cuál?',
            '117. Uso industrial',
            '118. Uso industrial características',
            '119. Uso industrial consumo (l/s)',
            '120. Uso minero',
            '121. Uso minero características',
            '122. Uso minero consumo (l/s)',
            '123. Uso generación hidroeléctrica',
            '124. Uso generacion hidroeléctrica características',
            '125. Uso generacion hidroeléctrica consumo (l/s)',
            '126. Uso recreacional',
            '127. Uso recreacional características',
            '128. Uso recreacional consumo (l/s)',
            '129. Uso servicios',
            '130. Uso servicios características',
            '131. Uso servicios consumo (l/s)',
            '132. Uso explotación petrolera',
            '133. Uso explotación petrolera características',
            '134. Uso explotación petrolera consumo (l/s)',
            '135. Otros usos',
            '136. Otros usos características',
            '137. Otros usos consumo (l/s)',
            '138. Permiso de concesión de uso del agua',
            '139. Número del expediente que maneja la CAR correspondiente al número con el que se le otorgó el caudal, bajo Resolución',
            '140. Fecha: (año/mes/dia)',
            '141. Número de resolución',
            '142. Fecha: (año/mes/dia)',
            '143. Vigencia (años)',
            '144. Caudal Concesionado (l/s)',
            '145. V1, t1',
            '145. V2, t2',
            '145. V3, t3',
            '145a. Promedio caudal utilizado',
            '146. Observaciones',
            '147. Cuerpo receptor',
            '¿Cuál?',
            '148. Nombre de la fuente receptora',
            '149. Observaciones',
            '150. Objetivo de calidad del cuerpo receptor (Uso/destinación)',
            '151. Departamento',
            '152. Código DANE departamento',
            '153. Municipio',
            '154. Código DANE',
            '155. Corregimiento',
            '156. Vereda',
            '157. Nombre unidad hidrográfica nivel I',
            '158. Código unidad hidrográfica nivel I',
            '159. Nombre unidad hidrográfica nivel II',
            '160. Código unidad hidrográfica nivel II',
            '161. Actividad que genera el vertimiento',
            '¿Cuál?',
            '162. Tipo de descarga',
            '163. Caudal vertido (l/s)',
            '164. Tiempo descarga (hr/día)',
            '165. Frecuencia (días/mes)',
            '166. Disposición del Vertimiento',
            '¿Cuál?',
            '167. Estado',
            '168. Latitud (x.y °)',
            '168. Longitud (x.y °)',
            '168. Altitud (m)',
            '168. Precisión (m)',
            '168a. Este- x(m)',
            '168a. Norte-y(m)',
            '168a. Altura(m.s.n.m)',
            '170. Observaciones del vertimiento',
            '171. ¿Presenta algún tipo de sistema de tratamiento? - Sistemas de Tratamiento',
            '172. Sistemas de Tratamiento',
            '¿Cuál?',
            '173. Esquema de localización punto de vertimiento',
            '174. Si el predio cuenta con pozo séptico o campo de infiltración, ¿este se encuentra a menos de 100 mts una fuente hídrica?',
            '175. Permiso de vertimiento',
            '176. Número de expediente',
            '177. Fecha: (año/mes/dia) de expediente',
            '178. Número de resolución',
            '179. Fecha: (año/mes/dia) de resolución',
            '180. Vigencia',
            '181. Caudal de Vertido Autorizado (l/s)',
            '182. Norma que debe cumplir el vertimiento',
            '183. Observaciones',
            '184. Cuerpo de agua con ocupación de cauce - Obras hidráulicas',
            '¿Cuál?',
            '185. Nombre de la fuente',
            '186. Observaciones',
            '187. Uso de la fuente en el área de influencia',
            '188. Pendiente del cauce %',
            '189. Patrón de drenaje del cauce',
            '¿Cuál?',
            '190. Características del lecho',
            '¿Cuál?',
            '191. Departamento',
            '192. Código DANE departamento',
            '193. Municipio',
            '194. Código DANE',
            '195. Corregimiento',
            '196. Vereda',
            '197. Nombre unidad hidrográfica nivel I',
            '198. Código unidad hidrográfica nivel I',
            '199. Nombre unidad hidrográfica nivel II',
            '200. Código unidad hidrográfica nivel II',
            '201. Descripción de la obra',
            '202. Tipo de obra',
            '¿Cuál?',
            '203. Estado',
            '204. Tipo de material',
            '205. Altura (m)',
            '206. Ancho (m)',
            '207. Longitud (m)',
            '208. Profundidad (m)',
            '209. Área (m2)',
            '210. Diámetro (m)',
            '211. Latitud (x.y °)',
            '211. Longitud (x.y °)',
            '211. Altitud (m)',
            '211. Precisión (m)',
            '211a. Este- x(m)',
            '211a. Norte-y(m)',
            '211a. Altura(m.s.n.m)',
            '213. Esquema de localización de la obra',
            '214. Permiso de ocupación de cauce',
            '215. Número de expediente',
            '216. Fecha de expediente',
            '217. Número de resolución',
            '218. Fecha de resolución',
            '219. Vigencia (años)',
            '220. Cuerpo de agua utilizado para actividades mineras (material de arrastre o canteras)',
            '¿Cuál?',
            '221. Nombre de la fuente',
            '222. Descripción de la actividad',
            '223. Características del lecho',
            '¿Cuál?',
            '224. Departamento',
            '225. Código DANE departamento',
            '226. Municipio',
            '227. Código DANE municipio',
            '228. Corregimiento',
            '229. Vereda',
            '230. Nombre unidad hidrográfica nivel I',
            '231. Código unidad hidrográfica nivel I',
            '232. Nombre unidad hidrográfica nivel II',
            '233. Código unidad hidrográfica nivel II',
            '234. Tipo de material de arrastre',
            '235. Volumen de extracción Autorizado (m3/año)',
            '236. Volumen de extracción real (m3/año)',
            '237. Título minero',
            'Numero de resolución de título minero',
            '239. Fecha de resolución título minero',
            '240. Licencia o permiso ambiental',
            '241. Número de resolución permiso ambiental',
            '242. Fecha de resolución permiso ambiental',
            '243. Latitud (x.y °)',
            '243. Longitud (x.y °)',
            '243. Altitud (m)',
            '243. Precisión (m)',
            '243a. Este- x(m)',
            '243a. Norte-y(m)',
            '243a. Altura(m.s.n.m)',
            '245. Esquema de localización y/o punto de extracción',
            '246. Doméstico',
            '246. Observaciones',
            '247. Pecuario',
            '247. Observaciones',
            '248. Acuícola',
            '248. Observaciones',
            '249. Agrícola',
            '249. Observaciones',
            '250. Industrial',
            '250. Observaciones',
            '251. Minería',
            '251. Observaciones',
            '252. Generación electrica',
            '252. Observaciones',
            '253. Otros',
            '253. Observaciones',
            '254. Total(l/s)',
            '254. Observaciones',
            '255. Observaciones generales',
            '256. Nombre encuestador',
            '256.1. Cédula encuestador',
            '258. Ingeniero de campo',
            '257. Nombre encuestado',
            '259. Coordinador de proyecto',
            '260. Fecha de procesamiento',
            '261. Observaciones adicionales',
            'Firma Autoridad Ambiental Competente',
            'Estado de la validación'
        ];
        if (!in_array('MODULO', $aTitulos, true)) { array_unshift($aTitulos, 'MODULO'); }
        $__perm__ = $this->construirPermutacionTitulos($aTitulos, $__ORDEN_DESEADO__, false);
        $aTitulos = $this->aplicarPermutacionFila($aTitulos, $__perm__);
        fputcsv($fp, $aTitulos, ';');

        try {
            /* ---------- 7. Procesamiento por lotes de datos ---------- */
            foreach (array_chunk($idsOrdenados, $batchSize) as $batchIds) {

                // Reinicio las opciones múltiples para ESTE lote
                $aOpcionesMult = [];

                /* -- 7.1  Resolver valores de selección múltiple -- */
                foreach ($aMultiple as $def) {
                    $ph             = implode(',', array_fill(0, count($batchIds), '?'));
                    $this->sSql     = "
                        SELECT l.id_sublista, l.id_principal
                        FROM dbo.{$def['origen']} o
                    JOIN dbo.{$def['opciones']} l ON l.id_principal = o.id
                        WHERE o.id_encuesta IN ($ph)
                    ";
                    $this->aExecute = $batchIds;
                    $this->execQuery();

                    foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                        $idx = "{$def['origen']}_{$def['campo']}_{$r['id_principal']}";
                        $aOpcionesMult[$idx][] = (int)$r['id_sublista'];
                    }
                }

                /* -- 7.2  Recorrer cada tabla y armar las filas de datos -- */
                $aFilasEncuestas = [];  // id → array de valores

                foreach ($aEstructuraEncuesta as $oTabla) {
                    if (empty($aTablasCampo[$oTabla->tabla])) continue;

                    $ph             = implode(',', array_fill(0, count($batchIds), '?'));
                    $this->sSql     = "
                        SELECT * FROM dbo.{$oTabla->tabla}
                        WHERE {$oTabla->id_validar} IN ($ph)
                    ";
                    $this->aExecute = $batchIds;
                    $this->execQuery();

                    foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                        foreach ($oTabla->grupos as $oGrupo) {
                            $campos = isset($oGrupo->campos)
                                    ? $oGrupo->campos
                                    : (isset($oGrupo->filas) ? array_merge(...$oGrupo->filas) : []);
                            foreach ($campos as $oCampo) {
                                if (!in_array($oCampo->bd, $aTablasCampo[$oTabla->tabla], true)) continue;

                                // Resolver valor
                                if (in_array($oCampo->tipo, ['numero','decimal','seleccion_simple_texto_editable'])) {
                                    $oCampo->tipo = 'texto';
                                    unset($oCampo->id_lista);
                                }

                                if (isset($oCampo->id_lista)) {
                                    $valor = is_numeric($r[$oCampo->bd]) && isset($aIdsListas[$r[$oCampo->bd]])
                                        ? $aIdsListas[$r[$oCampo->bd]] : '';
                                } elseif (isset($oCampo->elementos)) {
                                    $idx   = "{$oTabla->tabla}_{$oCampo->bd}_{$r['id']}";
                                    $ids   = $aOpcionesMult[$idx] ?? [];
                                    $valor = implode(' ', array_intersect_key($aIdsListas, array_flip($ids)));
                                } else {
                                    $valor = $r[$oCampo->bd] ?? '';
                                }

                                $idEncuesta = (int)$r[$oTabla->id_validar];
                                $cell = is_numeric($valor)
                                ? str_replace(['.', ';'], [',', ','], (string)$valor)
                                : str_replace(';', ',', (string)$valor);

                            // si el string empieza con ',', le añadimos el '0' delante
                            if (isset($cell[0]) && $cell[0] === ',') {
                                $cell = '0' . $cell;
                            }
                            if ($oCampo->bd === 'cedula_catastral') {
                                // Excel lo tomará como fórmula y mostrará el número completo
                                $cell = '="' . $cell . '"';
                            }

                            if ($cell === '') {
                                $cell = '=""';
                            }

                            $aFilasEncuestas[$idEncuesta][] = $cell;
                            }
                        }
                    }
                }

                /* -- 7.2.5  Obtener y mapear MODULO para este lote -- */
                $phModulo       = implode(',', array_fill(0, count($batchIds), '?'));
                $this->sSql     = "SELECT id, modulos FROM dbo.encuesta WHERE id IN ($phModulo)";
                $this->aExecute = $batchIds;
                $this->execQuery();
                $aModuloPorId   = [];
                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $rm) {
                    $idM = (int)$rm['id'];
                    $m   = (int)($rm['modulos'] ?? 0);
                    switch ($m) {
                        case 1: $aModuloPorId[$idM] = 'Captaciones'; break;
                        case 2: $aModuloPorId[$idM] = 'Vertimientos'; break;
                        case 3: $aModuloPorId[$idM] = 'Ocupación de Cauce'; break;
                        case 4: $aModuloPorId[$idM] = 'Minería'; break;
                        default: $aModuloPorId[$idM] = ''; break;
                    }
                }

                /* -- 7.3  Volcar las filas de ESTE lote al CSV -- */
                foreach ($batchIds as $idOrd) {
                    if (isset($aFilasEncuestas[$idOrd])) {
                        $mod = $aModuloPorId[$idOrd] ?? '';
                        array_unshift($aFilasEncuestas[$idOrd], $mod);
                        $filaOrdenada = $this->aplicarPermutacionFila($aFilasEncuestas[$idOrd], $__perm__);
                        fputcsv($fp, $filaOrdenada, ';');
                    }
                }
            }
        } catch (Throwable $e) {
            fclose($fp);
            @unlink(PATH_TEMP . $archivo);
            throw new RuntimeException('Error al generar CSV: '.$e->getMessage(), 0, $e);
        }

        fclose($fp);
        return ['url' => BASE_URL . 'temp/' . $archivo];
    }

    public function filtrosEncuestasConsolidar($aEstructura, $aDatosFiltroEncuesta, $aTablas) {
        $aFiltrosTotal = [];

        foreach ($aEstructura as $oModulo) {
            if (in_array($oModulo->tabla, $aTablas)) {
                foreach ($oModulo->grupos as $oGrupo) {
                    if (isset($oGrupo->campos)) {
                        foreach ($oGrupo->campos as $oCampo) {
                            $sIndice = $oModulo->tabla . '_' . $oCampo->bd;                           

                            $bDatos = isset($aDatosFiltroEncuesta[$sIndice]) ? count($aDatosFiltroEncuesta[$sIndice]) > 0 : [];

                            if ($bDatos) {
                                $aFiltrosTotal[] = ['tipo' => 'select', 'titulo' => $oCampo->titulo, 'id' => $sIndice, 'elementos' => $aDatosFiltroEncuesta[$sIndice], 'seleccionados' => []];
                            }
                        }
                    } else if (isset($oGrupo->filas)) {
                        foreach ($oGrupo->filas as $aCeldas) {
                            foreach ($aCeldas as $oCampo) {
                                $sIndice = $oModulo->tabla . '_' . $oCampo->bd;
                                $bDatos = isset($aDatosFiltroEncuesta[$sIndice]) ? count($aDatosFiltroEncuesta[$sIndice]) > 0 : [];

                                if ($bDatos) {
                                    $aFiltrosTotal[] = ['tipo' => 'select', 'titulo' => $oCampo->titulo, 'id' => $sIndice, 'elementos' => $aDatosFiltroEncuesta[$sIndice], 'seleccionados' => []];
                                }
                            }                            
                        }
                    }
                }
            }
        }

        return $aFiltrosTotal;
    }

    public function agruparIdsListasFiltros($aEstructura) {
        $aIdsListaFiltros = [];

        foreach ($aEstructura as $oModulo) {
            foreach ($oModulo->grupos as $oGrupo) {
                if (isset($oGrupo->campos)) {
                    foreach ($oGrupo->campos as $oCampo) {
                        if (isset($oCampo->id_lista)) {
                            $sIndice = $oModulo->tabla . '_' . $oCampo->bd;
                            $aIdsListaFiltros[$sIndice] = $oCampo->id_lista;
                        } else if (isset($oCampo->elementos)) {
                            $sIndice = $oModulo->tabla . '_' . $oCampo->bd;
                            $aIdsListaFiltros[$sIndice] = $oCampo->elementos;
                        }
                    }
                } else if (isset($oGrupo->filas)) {
                    foreach ($oGrupo->filas as $aCeldas) {
                        foreach ($aCeldas as $oCampo) {
                            if (isset($oCampo->id_lista)) {
                                $sIndice = $oModulo->tabla . '_' . $oCampo->bd;
                                $aIdsListaFiltros[$sIndice] = $oCampo->id_lista;
                            }
                        }                            
                    }
                }
            }
        }

        return $aIdsListaFiltros;
    }

    public function filtrosEncuestasDatosBasicos() {
        $this->oPostData->listas = true;
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aIdsListaFiltros = $this->agruparIdsListasFiltros($aEstructura['estructura']);

        $aElementosEncuesta = array(
            (object) ['tabla' => 'encuesta', 'campos' => ['codigo_cuenca_hidrografica']],
            (object) ['tabla' => 'usuario_encuesta', 'campos' => ['nombre_usuario', 'documento', 'representante_legal', 'documento_rl', 'direccion_domicilio', 'direccion_domicilio_referencia', 'direccion_domicilio_predio']],
            (object) ['tabla' => 'predio', 'campos' => ['nombre', 'area', 'corregimiento', 'vereda', 'cedula_catastral']],
        );

        $aDatosFiltroEncuesta = array();

        foreach ($aElementosEncuesta as $oTabla) {
            foreach ($oTabla->campos as $campo) {
                $this->sSql = "SELECT DISTINCT " . $campo . " FROM dbo." . $oTabla->tabla . " WHERE " . $campo . " IS NOT NULL ORDER BY " . $campo;
                $this->aExecute = array();
                $this->execQuery();

                $sIndice = $oTabla->tabla . '_' . $campo;
                $aDatosFiltroEncuesta[$sIndice] = [];

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    if ($r[$campo] !== null && trim($r[$campo]) !== '') {
                        $sId = md5((string) $r[$campo]);
                        $sIndice = $oTabla->tabla . '_' . $campo;
                        $aDatosFiltroEncuesta[$sIndice][] = array('id' => $sId, 'descripcion' => $r[$campo]);
                    }
                }
            }
        }

        //LISTAS
        $aIndices = ['usuario_encuesta_id_tipo_usuario', 'usuario_encuesta_id_tipo_identificacion', 'usuario_encuesta_id_tipo_identificacion_rl', 'predio_id_departamento', 'predio_id_municipio'];
        
        foreach ($aIndices as $sIndice) {
            $aDatosFiltroEncuesta[$sIndice] = [];

            foreach ($aEstructura['listas'] as $e) {
                if ($e['id_lista'] === $aIdsListaFiltros[$sIndice]) {
                    $aDatosFiltroEncuesta[$sIndice][] = $e;
                }
            }
        }

        return $this->filtrosEncuestasConsolidar($aEstructura['estructura'], $aDatosFiltroEncuesta, ['encuesta', 'usuario_encuesta', 'predio']);
    }

    public function filtrosEncuestasCaptaciones() {
        $this->oPostData->listas = true;
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aIdsListaFiltros = $this->agruparIdsListasFiltros($aEstructura['estructura']);

        $aElementosEncuesta = array(
            (object) ['tabla' => 'fuente_captacion', 'campos' => ['nombre_fuente']],         
            (object) ['tabla' => 'captacion_en_fuente', 'campos' => ['capacidad_bomba', 'este', 'norte', 'altura']],         
            (object) ['tabla' => 'uso_recurso_hidrico', 'campos' => [
                'personas_permanentes', 
                'personas_transitorias', 
                'consumo', 
                'uso_pecuario_1_numero', 
                'uso_pecuario_1_consumo',
                'uso_pecuario_2_numero', 
                'uso_pecuario_2_consumo',
                'uso_pecuario_3_numero', 
                'uso_pecuario_3_consumo',
                'uso_pecuario_4_numero', 
                'uso_pecuario_4_consumo',
                'uso_acuicola_1_tipo',
                'uso_acuicola_1_numero',
                'uso_acuicola_1_consumo',
                'uso_acuicola_2_tipo',
                'uso_acuicola_2_numero',
                'uso_acuicola_2_consumo',
                'uso_acuicola_3_tipo',
                'uso_acuicola_3_numero',
                'uso_acuicola_3_consumo',
                'uso_acuicola_4_tipo',
                'uso_acuicola_4_numero',
                'uso_acuicola_4_consumo',
                'uso_agricola_silvicola_1_area',
                'uso_agricola_silvicola_1_consumo',
                'uso_agricola_silvicola_2_area',
                'uso_agricola_silvicola_2_consumo',
                'uso_agricola_silvicola_3_area',
                'uso_agricola_silvicola_3_consumo',
                'uso_agricola_silvicola_4_area',
                'uso_agricola_silvicola_4_consumo',
                'uso_industrial_consumo',
                'uso_minero_consumo',
                'uso_generacion_hidroelectrica_caracteristicas',
                'uso_generacion_hidroelectrica_consumo',
                'uso_recreacional_caracteristicas',
                'uso_recreacional_consumo',
                'uso_servicios_consumo',
                'uso_explotacion_petrolera_caracteristicas',
                'uso_explotacion_petrolera_consumo',
                'otros_usos_caracteristicas',
                'otros_usos_consumo',
                'total'
                ]
            ],
            (object) ['tabla' => 'informacion_juridica_captacion', 'campos' => [
                'numero_expediente', 
                'numero_resolucion', 
                'vigencia_anios', 
                'caudal_concesionado',
                'caudal_utilizado_1',
                'caudal_utilizado_2',
                'caudal_utilizado_3',
                'prom_caudal_utilizado'
,                ]
            ],
        );

        $aDatosFiltroEncuesta = array();

        foreach ($aElementosEncuesta as $oTabla) {
            foreach ($oTabla->campos as $campo) {
                $this->sSql = "SELECT DISTINCT " . $campo . " FROM dbo." . $oTabla->tabla . " WHERE " . $campo . " IS NOT NULL ORDER BY " . $campo;
                $this->aExecute = array();
                $this->execQuery();

                $sIndice = $oTabla->tabla . '_' . $campo;
                $aDatosFiltroEncuesta[$sIndice] = [];

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    if ($r[$campo] !== null && trim($r[$campo]) !== '') {
                        $sId = md5((string) $r[$campo]);
                        $sIndice = $oTabla->tabla . '_' . $campo;
                        $aDatosFiltroEncuesta[$sIndice][] = array('id' => $sId, 'descripcion' => $r[$campo]);
                    }
                }
            }
        }

        //LISTAS
        $aIndices = [
            'fuente_captacion_id_fuente_superficial', 
            'fuente_captacion_id_nombre_und_geografica_nivel1', 
            'fuente_captacion_id_codigo_und_geografica_nivel1', 
            'fuente_captacion_id_nombre_und_geografica_nivel2', 
            'fuente_captacion_id_codigo_und_geografica_nivel2',
            'captacion_en_fuente_id_tipo_captacion',
            'captacion_en_fuente_id_estado',
            'captacion_en_fuente_diametro',
            'captacion_en_fuente_frecuencia',
            'captacion_en_fuente_caracteristicas_abastecimiento',
            'uso_recurso_hidrico_id_uso_domestico',
            'uso_recurso_hidrico_dias_mes',
            'uso_recurso_hidrico_menores_cinco_anios',
            'uso_recurso_hidrico_mayores_sesenta_anios',
            'uso_recurso_hidrico_tipo_construccion',
            'uso_recurso_hidrico_id_uso_pecuario',
            'uso_recurso_hidrico_uso_pecuario_1_tipo',
            'uso_recurso_hidrico_uso_pecuario_2_tipo',
            'uso_recurso_hidrico_uso_pecuario_3_tipo',
            'uso_recurso_hidrico_id_uso_acuicola',
            'uso_recurso_hidrico_id_uso_agricola_silvicola',
            'uso_recurso_hidrico_uso_agricola_silvicola_1_cultivo',
            'uso_recurso_hidrico_uso_agricola_silvicola_2_cultivo',
            'uso_recurso_hidrico_uso_agricola_silvicola_3_cultivo',
            'uso_recurso_hidrico_uso_agricola_silvicola_4_cultivo',
            'uso_recurso_hidrico_riego_predominante',
            'uso_recurso_hidrico_id_uso_industrial',
            'uso_recurso_hidrico_uso_industrial_caracteristicas',
            'uso_recurso_hidrico_id_uso_minero',
            'uso_recurso_hidrico_uso_minero_caracteristicas',
            'uso_recurso_hidrico_id_uso_generacion_hidroelectrica',
            'uso_recurso_hidrico_id_uso_recreacional',
            'uso_recurso_hidrico_id_uso_servicios',
            'uso_recurso_hidrico_uso_servicios_caracteristicas',
            'uso_recurso_hidrico_id_uso_explotacion_petrolera',
            'uso_recurso_hidrico_id_otros_usos',
            'informacion_juridica_captacion_id_permiso_concesion_uso_agua',
        ];
        
        foreach ($aIndices as $sIndice) {
            $aDatosFiltroEncuesta[$sIndice] = [];

            foreach ($aEstructura['listas'] as $e) {
                if ($e['id_lista'] === $aIdsListaFiltros[$sIndice]) {
                    $aDatosFiltroEncuesta[$sIndice][] = $e;
                }
            }
        }

        return $this->filtrosEncuestasConsolidar($aEstructura['estructura'], $aDatosFiltroEncuesta, ['fuente_captacion', 'captacion_en_fuente', 'uso_recurso_hidrico', 'informacion_juridica_captacion']);
    }

    public function filtrosEncuestasVertimiento() {
        $this->oPostData->listas = true;
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aIdsListaFiltros = $this->agruparIdsListasFiltros($aEstructura['estructura']);

        $aElementosEncuesta = array(
            (object) ['tabla' => 'fuente_receptora_vertimiento', 'campos' => ['fuente_receptora']],            
            (object) ['tabla' => 'informacion_vertimiento', 'campos' => ['caudal_vertido', 'este', 'norte', 'altura']],            
            (object) ['tabla' => 'informacion_juridica_vertimiento', 'campos' => ['numero_expediente', 'numero_resolucion', 'vigencia_anios', 'caudal_vertido_autorizado']],            
        );

        $aDatosFiltroEncuesta = array();

        foreach ($aElementosEncuesta as $oTabla) {
            foreach ($oTabla->campos as $campo) {
                $this->sSql = "SELECT DISTINCT " . $campo . " FROM dbo." . $oTabla->tabla . " WHERE " . $campo . " IS NOT NULL ORDER BY " . $campo;
                $this->aExecute = array();
                $this->execQuery();

                $sIndice = $oTabla->tabla . '_' . $campo;
                $aDatosFiltroEncuesta[$sIndice] = [];

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    if ($r[$campo] !== null && trim($r[$campo]) !== '') {
                        $sId = md5((string) $r[$campo]);
                        $sIndice = $oTabla->tabla . '_' . $campo;
                        $aDatosFiltroEncuesta[$sIndice][] = array('id' => $sId, 'descripcion' => $r[$campo]);
                    }
                }
            }
        }

        //LISTAS
        $aIndices = [
            'fuente_receptora_vertimiento_id_cuerpo_receptor',
            'fuente_receptora_vertimiento_id_objetivo_calidad_receptor',
            'fuente_receptora_vertimiento_id_nombre_und_geografica_nivel1',
            'fuente_receptora_vertimiento_id_codigo_und_geografica_nivel1',
            'fuente_receptora_vertimiento_id_nombre_und_geografica_nivel2',
            'fuente_receptora_vertimiento_id_codigo_und_geografica_nivel2',
            'informacion_vertimiento_id_actividad_vertimiento',
            'informacion_vertimiento_tipo_descarga',
            'informacion_vertimiento_tiempo_descarga',
            'informacion_vertimiento_frecuencia',
            'informacion_vertimiento_id_disposicion_vertimiento',
            'informacion_vertimiento_id_estado',
            'informacion_vertimiento_id_presenta_sistema_tratamiento',
            'informacion_vertimiento_id_sistema_tratamiento',
            'informacion_vertimiento_id_pozo_septico_campo_infiltracion_menos_100_mts',
            'informacion_juridica_vertimiento_id_permiso_vertimiento'
        ];

        foreach ($aIndices as $sIndice) {
            $aDatosFiltroEncuesta[$sIndice] = [];

            foreach ($aEstructura['listas'] as $e) {
                if ($e['id_lista'] === $aIdsListaFiltros[$sIndice]) {
                    $aDatosFiltroEncuesta[$sIndice][] = $e;
                }
            }
        }

        return $this->filtrosEncuestasConsolidar($aEstructura['estructura'], $aDatosFiltroEncuesta, ['fuente_receptora_vertimiento', 'informacion_vertimiento', 'informacion_juridica_vertimiento']);
    }

    public function filtrosEncuestasOcupacion() {
        $this->oPostData->listas = true;
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aIdsListaFiltros = $this->agruparIdsListasFiltros($aEstructura['estructura']);

        $aElementosEncuesta = array(
            (object) ['tabla' => 'informacion_fuente_ocupacion_cauce', 'campos' => ['nombre_fuente', 'fuente_area_influcencia']],            
            (object) ['tabla' => 'informacion_ocupacion_cauce_obra_hidraulica', 'campos' => [
                'descripcion_obra',
                'tipo_material', 
                'area', 
                'este', 
                'norte',
                'altura',
                'numero_expediente',
                'numero_resolucion',
                'vigencia_anios'
                ]
            ],            
        );

        $aDatosFiltroEncuesta = array();

        foreach ($aElementosEncuesta as $oTabla) {
            foreach ($oTabla->campos as $campo) {
                $this->sSql = "SELECT DISTINCT " . $campo . " FROM dbo." . $oTabla->tabla . " WHERE " . $campo . " IS NOT NULL ORDER BY " . $campo;
                $this->aExecute = array();
                $this->execQuery();

                $sIndice = $oTabla->tabla . '_' . $campo;
                $aDatosFiltroEncuesta[$sIndice] = [];

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    if ($r[$campo] !== null && trim($r[$campo]) !== '') {
                        $sId = md5((string) $r[$campo]);
                        $sIndice = $oTabla->tabla . '_' . $campo;
                        $aDatosFiltroEncuesta[$sIndice][] = array('id' => $sId, 'descripcion' => $r[$campo]);
                    }
                }
            }
        }

        //LISTAS
        $aIndices = [
            'informacion_fuente_ocupacion_cauce_id_cuerpo_agua',
            'informacion_fuente_ocupacion_cauce_id_tipo_cauce',
            'informacion_fuente_ocupacion_cauce_id_tipo_lecho',
            'informacion_fuente_ocupacion_cauce_id_nombre_und_geografica_nivel1',
            'informacion_fuente_ocupacion_cauce_id_codigo_und_geografica_nivel1',
            'informacion_fuente_ocupacion_cauce_id_nombre_und_geografica_nivel2',
            'informacion_fuente_ocupacion_cauce_id_codigo_und_geografica_nivel2',
            'informacion_ocupacion_cauce_obra_hidraulica_id_tipo_obra',
            'informacion_ocupacion_cauce_obra_hidraulica_id_estado',
            'informacion_ocupacion_cauce_obra_hidraulica_id_permiso_ocupacion_cauce',
        ];

        foreach ($aIndices as $sIndice) {
            $aDatosFiltroEncuesta[$sIndice] = [];

            foreach ($aEstructura['listas'] as $e) {
                if ($e['id_lista'] === $aIdsListaFiltros[$sIndice]) {
                    $aDatosFiltroEncuesta[$sIndice][] = $e;
                }
            }
        }

        return $this->filtrosEncuestasConsolidar($aEstructura['estructura'], $aDatosFiltroEncuesta, ['informacion_fuente_ocupacion_cauce', 'informacion_ocupacion_cauce_obra_hidraulica']);
    }

    public function filtrosEncuestasMinera() {
        $this->oPostData->listas = true;
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aIdsListaFiltros = $this->agruparIdsListasFiltros($aEstructura['estructura']);

        $aElementosEncuesta = array(
            (object) ['tabla' => 'informacion_actividad_minera_cuerpos_agua', 'campos' => ['nombre_fuente']],          
            (object) ['tabla' => 'actividades_extraccion', 'campos' => [
                'volumen_extraccion_atorizado', 
                'volumen_extraccion_real', 
                'numero_resolucion_titulo_minero',
                'numero_resolucion_permiso_ambiental',
                'este',
                'norte',
                'altura'
                ]
            ],          
        );

        $aDatosFiltroEncuesta = array();

        foreach ($aElementosEncuesta as $oTabla) {
            foreach ($oTabla->campos as $campo) {
                $this->sSql = "SELECT DISTINCT " . $campo . " FROM dbo." . $oTabla->tabla . " WHERE " . $campo . " IS NOT NULL ORDER BY " . $campo;
                $this->aExecute = array();
                $this->execQuery();

                $sIndice = $oTabla->tabla . '_' . $campo;
                $aDatosFiltroEncuesta[$sIndice] = [];

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    if ($r[$campo] !== null && trim($r[$campo]) !== '') {
                        $sId = md5((string) $r[$campo]);
                        $sIndice = $oTabla->tabla . '_' . $campo;
                        $aDatosFiltroEncuesta[$sIndice][] = array('id' => $sId, 'descripcion' => $r[$campo]);
                    }
                }
            }
        }

        //LISTAS
        $aIndices = [
            'informacion_actividad_minera_cuerpos_agua_id_cuerpo_agua',
            'informacion_actividad_minera_cuerpos_agua_descripcion_actividad',
            'informacion_actividad_minera_cuerpos_agua_id_caracteristicas_lecho',
            'informacion_actividad_minera_cuerpos_agua_id_nombre_und_geografica_nivel1',
            'informacion_actividad_minera_cuerpos_agua_id_codigo_und_geografica_nivel1',
            'informacion_actividad_minera_cuerpos_agua_id_nombre_und_geografica_nivel2',
            'informacion_actividad_minera_cuerpos_agua_id_codigo_und_geografica_nivel2',
            'actividades_extraccion_id_tipo_material_arrastre',
            'actividades_extraccion_id_tipo_material_arrastre',
            'actividades_extraccion_id_titulo_minero',
            'actividades_extraccion_id_licencia_permiso_ambiental'
        ];

        foreach ($aIndices as $sIndice) {
            $aDatosFiltroEncuesta[$sIndice] = [];

            foreach ($aEstructura['listas'] as $e) {
                if ($e['id_lista'] === $aIdsListaFiltros[$sIndice]) {
                    $aDatosFiltroEncuesta[$sIndice][] = $e;
                }
            }
        }

        return $this->filtrosEncuestasConsolidar($aEstructura['estructura'], $aDatosFiltroEncuesta, ['informacion_actividad_minera_cuerpos_agua', 'actividades_extraccion']);
    }

    public function filtrosEncuestas()
    {
        $aNombreEncuestador = array();
        $aCodigoCuenca = array();
        $aIngenieroCampo = array();

        $aIdsNombreEncuestador = [];
        $aIdsCodigoCuenca = [];
        $aIdsIngenieroCampo = [];

        $this->sSql = "SELECT codigo_cuenca_hidrografica, nombre_encuestador, ingeniero_campo FROM dbo.encuesta WHERE nombre_encuestador IS NOT NULL OR codigo_cuenca_hidrografica IS NOT NULL OR ingeniero_campo IS NOT NULL";
        $this->aExecute = array();
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $sIdNombreEncuestador = isset($r['nombre_encuestador']) ? mb_strtolower($this->oSesion->sanearString($r['nombre_encuestador'])) : null;
            $sIdCodigoCuenca = isset($r['codigo_cuenca_hidrografica']) ? mb_strtolower($this->oSesion->sanearString($r['codigo_cuenca_hidrografica'])) : null;
            $sIdIngenieroCampo = isset($r['ingeniero_campo']) ? mb_strtolower($this->oSesion->sanearString($r['ingeniero_campo'])) : null;

            if (!in_array($sIdNombreEncuestador, $aIdsNombreEncuestador) && !is_null($sIdNombreEncuestador)) {
                $aNombreEncuestador[] = array('id' => $sIdNombreEncuestador, 'nombre' => $r['nombre_encuestador']);
                $aIdsNombreEncuestador[] = $sIdNombreEncuestador;
            }

            if (!in_array($sIdCodigoCuenca, $aIdsCodigoCuenca) && !is_null($sIdCodigoCuenca)) {
                $aCodigoCuenca[] = array('id' => $sIdCodigoCuenca, 'nombre' => $r['codigo_cuenca_hidrografica']);
                $aIdsCodigoCuenca[] = $sIdCodigoCuenca;
            }

            if (!in_array($sIdIngenieroCampo, $aIdsIngenieroCampo) && !is_null($sIdIngenieroCampo)) {
                $aIngenieroCampo[] = array('id' => $sIdIngenieroCampo, 'nombre' => $r['ingeniero_campo']);
                $aIdsIngenieroCampo[] = $sIdIngenieroCampo;
            }
        }

        return array('nombre_encuestador' => $aNombreEncuestador, 'codigo_cuenca_hidrografica' => $aCodigoCuenca, 'ingeniero_campo' => $aIngenieroCampo);
    }

    public function relacionarCamposElementos(string $sTabla, string $sCampo, array $aValores, int $idEncuesta)
	{
        $this->sSql = "SELECT id FROM dbo." . $sTabla . " WHERE id_encuesta = :idEncuesta";	
        $this->aExecute = array(':idEncuesta' => $idEncuesta);
        $this->execQuery();
        $id = 0;
        $aListaActual = array();
        $aListaNueva = array(0);
        $aListaGuardar = array();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oDatos = (object) $r;
            $id = (int) $oDatos->id;
        }

        if ($id > 0) {
            $this->sSql = "SELECT id, id_sublista FROM dbo." . $sTabla . "_" . $sCampo . " WHERE id_principal = :idPrincipal";	
            $this->aExecute = array(':idPrincipal' => $id);
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $oDatos = (object) $r;
                $aListaActual[] = $oDatos->id_sublista;
            }

            foreach ($aValores as $iValor) {
                if (in_array($iValor, $aListaActual)) {
                    $aListaNueva[] = $iValor;
                } else {
                    $aListaGuardar[] = ['id_principal' => $id, 'id_sublista' => $iValor];
                    $aListaNueva[] = $iValor;
                }
            }

            $this->deleteSingle($sTabla . "_" . $sCampo, ['id_principal' => $id], [], ['id_sublista' => $aListaNueva]);
        }

        if (count($aListaGuardar) > 0) {
            $this->iniciarInsertMultiple($sTabla . "_" . $sCampo, $aListaGuardar);
        }
    }

    public function guardarEncuestaServidor() 
    {
        $bProceso = true;
        $idEncuesta = 0;
        $aDatosTablas = [];

        //encuestas existentes
        $bEncuestaExiste = false;
        $sCodigoEncuesta = null;
        $sFechaDiligenciamiento = null;
        $aEncuestasExistentes = [];
        $this->sSql = "SELECT e.codigo_encuesta, e.fecha_diligenciamiento FROM dbo.encuesta e WHERE e.codigo_encuesta IS NOT NULL AND e.fecha_diligenciamiento IS NOT NULL AND e.koboid IS NULL";
        $this->aExecute = [];
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oEncuesta = (object) $r;
            $sIndice = $oEncuesta->codigo_encuesta . '_' . $oEncuesta->fecha_diligenciamiento;
            $aEncuestasExistentes[] = $sIndice;
        }

        foreach ($this->oPostData->datos->estructura as $oRegistro) {
            if ($oRegistro->tabla === 'encuesta') {
                foreach ($oRegistro->grupos as $oGrupo) {
                    foreach ($oGrupo->campos as $oCampo) {
                        if ($oCampo->bd === 'codigo_encuesta' && isset($oCampo->valor)) {
                            $sCodigoEncuesta = $oCampo->valor;
                        } else if ($oCampo->bd === 'fecha_diligenciamiento' && isset($oCampo->valor)) {
                            $sFechaDiligenciamiento = $oCampo->valor;
                        }
                    }
                }
            }
        }

        if (isset($sCodigoEncuesta, $sFechaDiligenciamiento)) {
            $sIndice = $sCodigoEncuesta . '_' . $sFechaDiligenciamiento;
            $bEncuestaExiste = in_array($sIndice, $aEncuestasExistentes);
        }

        if (!$bEncuestaExiste) {
            foreach ($this->oPostData->datos->estructura as $oRegistro) {
                $aData = [];
                    
                foreach ($oRegistro->grupos as $oGrupo) {
                    if (isset($oGrupo->campos) && ($oGrupo->tipo === 'informacion' || $oGrupo->tipo === 'mapa' || $oGrupo->tipo === 'imagen')) {
                        foreach ($oGrupo->campos as $oCampo) {
                            if ($oCampo->tipo !== 'imagen' && $oCampo->tipo !== 'seleccion_multiple') {
                                $aData[$oCampo->bd] = $oCampo->valor !== null ? (strlen(trim((string) $oCampo->valor)) > 0 ? $oCampo->valor : null) : null;        
                            } else if ($oCampo->tipo === 'imagen') {
                                $aData[str_replace('_url', '', $oCampo->bd)] = null;
    
                                if (isset($oCampo->valor)) {
                                    $sNombre = $oRegistro->tabla . '_' . $oCampo->bd . '_' . $this->generarID(64) . '.jpg';
                                    $aData[str_replace('_url', '', $oCampo->bd)] = $sNombre;
                                    $this->base64AJpeg($oCampo->valor, PATHFILES . $sNombre);
                                }
                            }
                        }
                    } else if (isset($oGrupo->filas) && $oGrupo->tipo === 'tabla') {
                        foreach ($oGrupo->filas as $aFilaCampos) {
                            foreach ($aFilaCampos as $oCampo) {
                                if ($oCampo->tipo !== 'imagen' && $oCampo->tipo !== 'seleccion_multiple') {
                                    $aData[$oCampo->bd] = $oCampo->valor !== null ? (strlen(trim((string) $oCampo->valor)) > 0 ? $oCampo->valor : null) : null;        
                                }
                            }
                        }
                    }
                }
    
                if ($oRegistro->tabla === 'encuesta') {
                    $aListaModulos = [];
    
                    if ($this->oPostData->datos->modulo1) {
                        $aListaModulos[] = "1";
                    }
    
                    if ($this->oPostData->datos->modulo2) {
                        $aListaModulos[] = "2";
                    }
    
                    if ($this->oPostData->datos->modulo3) {
                        $aListaModulos[] = "3";
                    }
    
                    if ($this->oPostData->datos->modulo4) {
                        $aListaModulos[] = "4";
                    }
    
                    $aData['fecha_registro'] = date('Y-m-d H:i:s');
                    $aData['id_edicion'] = $this->generarID(128);
                    $aData['id_usuario_registro'] = $this->oSesion->idUsuario();
                    $aData['modulos'] = count($aListaModulos) > 0 ? implode('|', $aListaModulos) : null;
    
                    $bProceso = !$this->insertSingle('encuesta', $aData) ? false : $bProceso;
    
                    if ($bProceso) {
                        $idEncuesta = $this->iLastInsertId;
                    }
                } else {
                    $aDatosTablas[$oRegistro->tabla] = $aData;
                }
            }
    
            if ($idEncuesta > 0) {
                foreach ($aDatosTablas as $sTabla => $aDataCampos) {
                    $aDataCampos['id_encuesta'] = $idEncuesta;
                    $this->insertSingle($sTabla, $aDataCampos);
                }
    
                foreach ($this->oPostData->datos->estructura as $oRegistro) {
                    foreach ($oRegistro->grupos as $oGrupo) {
                        if (isset($oGrupo->campos)) {
                            foreach ($oGrupo->campos as $oCampo) {
                                if ($oCampo->tipo === 'seleccion_multiple') {
                                    if (is_string($oCampo->valor)) {
                                        $oCampo->valor = strlen($oCampo->valor) > 0 ? explode(',', $oCampo->valor) : [];
                                    }
        
                                    if (!is_array($oCampo->valor)) {
                                        $oCampo->valor = [];
                                    }
        
                                    $this->relacionarCamposElementos($oRegistro->tabla, $oCampo->bd, $oCampo->valor, $idEncuesta);
                                }
                            }
                        } else if (isset($oGrupo->filas) && $oGrupo->tipo === 'tabla') {
                            foreach ($oGrupo->filas as $aFilaCampos) {
                                foreach ($aFilaCampos as $oCampo) {
                                    if ($oCampo->tipo === 'seleccion_multiple') {
                                        $this->relacionarCamposElementos($oRegistro->tabla, $oCampo->bd, $oCampo->valor, $idEncuesta);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return array('bProceso' => $bProceso, 'bEncuestaExiste' => $bEncuestaExiste);
    }

    public function guardarCambiosEncuesta() 
    {
        $bProceso = true;

        $this->sSql = "
            SELECT 
                e.id, 
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
            WHERE e.id = :idEncuesta
        ";

        $this->aExecute = array(':idEncuesta' => $this->oPostData->datos->id);
        $this->execQuery();
        $aEncuesta = [];

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $aEncuesta = $r;
        }

        if (isset($aEncuesta['id'])) {
            foreach ($this->oPostData->datos->estructura as $oRegistro) {
                $aData = [];
                    
                foreach ($oRegistro->grupos as $oGrupo) {
                    if (isset($oGrupo->campos) && ($oGrupo->tipo === 'informacion' || $oGrupo->tipo === 'mapa' || $oGrupo->tipo === 'imagen')) {
                        foreach ($oGrupo->campos as $oCampo) {
                            if ($oCampo->tipo !== 'imagen' && $oCampo->tipo !== 'disabled_edit' && $oCampo->tipo !== 'seleccion_multiple') {
                                $aData[$oCampo->bd] = $oCampo->valor !== null ? (strlen(trim((string) $oCampo->valor)) > 0 ? $oCampo->valor : null) : null;        
                            } else if ($oCampo->tipo === 'imagen' && isset($oCampo->archivo)) {
                                $aData[str_replace('_url', '', $oCampo->bd)] = null;
    
                                if (isset($oCampo->valor) && $oCampo->archivo) {
                                    $sNombre = $oRegistro->tabla . '_' . $oCampo->bd . '_' . $this->generarID(64) . '.jpg';
                                    $aData[str_replace('_url', '', $oCampo->bd)] = $sNombre;
                                    $this->base64AJpeg($oCampo->valor, PATHFILES . $sNombre);
                                }
                            }
                        }
                    } else if (isset($oGrupo->filas) && $oGrupo->tipo === 'tabla') {
                        foreach ($oGrupo->filas as $aFilaCampos) {
                            foreach ($aFilaCampos as $oCampo) {
                                if ($oCampo->tipo !== 'imagen' && $oCampo->tipo !== 'disabled_edit' && $oCampo->tipo !== 'seleccion_multiple') {
                                    $aData[$oCampo->bd] = $oCampo->valor !== null ? (strlen(trim((string) $oCampo->valor)) > 0 ? $oCampo->valor : null) : null;        
                                }
                            }
                        }
                    }
                }

                if ($oRegistro->tabla === 'encuesta') {
                    $aListaModulos = [];
    
                    if ($this->oPostData->datos->modulo1) {
                        $aListaModulos[] = "1";
                    }
    
                    if ($this->oPostData->datos->modulo2) {
                        $aListaModulos[] = "2";
                    }
    
                    if ($this->oPostData->datos->modulo3) {
                        $aListaModulos[] = "3";
                    }
    
                    if ($this->oPostData->datos->modulo4) {
                        $aListaModulos[] = "4";
                    }

                    $aData['modulos'] = count($aListaModulos) > 0 ? implode('|', $aListaModulos) : null;

                    $aData['id_usuario_edicion'] = $this->oSesion->idUsuario();
                    $aData['fecha_edicion_visor'] = date('Y-m-d H:i:s');
                    $aWhere = ['id' => $this->oPostData->datos->id];
                    $bProceso = !$this->updateSingle($oRegistro->tabla, $aData, $aWhere) ? false : $bProceso;
                } else {
                    $idTabla = isset($aEncuesta['id_' . $oRegistro->tabla]) ? $aEncuesta['id_' . $oRegistro->tabla] : 0;
                    if ($idTabla > 0) {
                        $aWhere = ['id_encuesta' => $this->oPostData->datos->id];
                        $bProceso = !$this->updateSingle($oRegistro->tabla, $aData, $aWhere) ? false : $bProceso;
                    } else {
                        $aData['id_encuesta'] = $this->oPostData->datos->id;
                        $bProceso = $this->insertSingle($oRegistro->tabla, $aData);
                    }
                }

                foreach ($oRegistro->grupos as $oGrupo) {
                    if (isset($oGrupo->campos) && ($oGrupo->tipo === 'informacion' || $oGrupo->tipo === 'mapa')) {
                        foreach ($oGrupo->campos as $oCampo) {
                            if ($oCampo->tipo !== 'imagen' && $oCampo->tipo !== 'disabled_edit' && $oCampo->tipo === 'seleccion_multiple') {
                                if (is_string($oCampo->valor)) {
                                    $oCampo->valor = strlen($oCampo->valor) > 0 ? explode(',', $oCampo->valor) : [];
                                }

                                if (!is_array($oCampo->valor)) {
                                    $oCampo->valor = [];
                                }

                                $this->relacionarCamposElementos($oRegistro->tabla, $oCampo->bd, $oCampo->valor, $this->oPostData->datos->id);
                            }
                        }
                    } else if (isset($oGrupo->filas) && $oGrupo->tipo === 'tabla') {
                        foreach ($oGrupo->filas as $aFilaCampos) {
                            foreach ($aFilaCampos as $oCampo) {
                                if ($oCampo->tipo !== 'imagen' && $oCampo->tipo !== 'disabled_edit' && $oCampo->tipo === 'seleccion_multiple') {
                                    $this->relacionarCamposElementos($oRegistro->tabla, $oCampo->bd, $oCampo->valor, $this->oPostData->datos->id);
                                }
                            }
                        }
                    }
                }
            }
        }

        return array('bProceso' => $bProceso);
    }

    private function generarID(int $iPasswordLength) : string 
	{
        $sCharsetPassword = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $sRandString = '';

        for ($i = 0; $i < $iPasswordLength; $i++) {
            $sRandString .= $sCharsetPassword[mt_rand(0, strlen($sCharsetPassword) - 1)];
        }

        return $sRandString;
    }

    private function base64AJpeg($base64_string, $output_file) 
    {
        // open the output file for writing
        $ifp = fopen( $output_file, 'wb' ); 
    
        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode( ',', $base64_string );
    
        // we could add validation here with ensuring count( $data ) > 1
        fwrite( $ifp, base64_decode( $data[ 1 ] ) );
    
        // clean up the file resource
        fclose( $ifp );

        $this->oArchivos->listarMoverArchivosEncuestasS3();
    }

    private function moverArchivoObtenerURL(string $sArchivo) 
    {
        return $this->oArchivos->getUrlArchivo('image', $sArchivo, 30);
    }

    public function generarPDF() {
        $aEstructura = $this->cargarEstructuraEncuesta();
        $aContenidoPDF = [
            '<!doctype html>',
            '<html lang="en">',
            '<head>',
            '<meta charset="utf-8">',
            '<title></title>',
            '<style type="text/css">',
            '</style>',
            '</head>',
            '<body>',
            '<div id="wrapper">',
            '<div class="content-body">',
            '<div class="col-md-12">'
        ];
        $sNombreArchivoEncuesta = '';

        foreach ($this->oPostData->encuesta->estructura as $oRegistro) {
            $bContenidoModulo = false;

            $aContenidoModulo = [
                '<div class="card dv-sup">',
                '<div class="card-header">',
                '<h4 class="card-title">' . $oRegistro->titulo . '</h4>',
                '</div>',
                '<div class="card-body">'
            ];

            foreach ($oRegistro->grupos as $oGrupo) {
                $aContenidoModulo = array_merge_recursive($aContenidoModulo, [
                    '<div class="list-group">'
                ]);

                if ($oGrupo->tipo === 'informacion') {
                    foreach ($oGrupo->campos as $oCampo) {
                        if ($oCampo->valor !== null && count($oGrupo->campos) === 1) {
                            $bContenidoModulo = true;

                            $aContenidoModulo = array_merge_recursive($aContenidoModulo, [
                                '<div class="list-group-item">',
                                '<div>',
                                '<h5 class="text-bold-600">' . $oCampo->titulo . ':</h5>',
                                '<small class="text-muted">' . $oCampo->valor . '</small>',
                                '</div>',
                                '</div>'
                            ]);

                            if ($oCampo->bd === 'codigo_encuesta') {
                                $sNombreArchivoEncuesta = $this->oSesion->sanearString($oCampo->valor);
                            }
                        }
                    }
                } else if ($oGrupo->tipo === 'imagen') {
                    foreach ($oGrupo->campos as $oCampo) {
                        if ($oCampo->valor !== null) {
                            $bContenidoModulo = true;

                            $aContenidoModulo = array_merge_recursive($aContenidoModulo, [
                                '<div class="list-group-item">',
                                '<img src="' . $oCampo->valor . '" style="max-height: 400px" />',
                                '</div>'
                            ]);
                        }
                    }
                } else if ($oGrupo->tipo === 'mapa') {
                    $latitud = null;
                    $longitud = null;

                    foreach ($oGrupo->campos as $oCampo) {
                        if ($oCampo->bd === 'latitud') {
                            $latitud = $oCampo->valor;
                        }

                        if ($oCampo->bd === 'longitud') {
                            $longitud = $oCampo->valor;
                        }
                    }

                    $bCoordenadasValidas = true;

                    if ($latitud < -90 || $latitud > 90) {
                        $bCoordenadasValidas = false;
                    }

                    if ($longitud < -180 || $longitud > 180) {
                        $bCoordenadasValidas = false;
                    }

                    if ($bCoordenadasValidas) {
                        $bContenidoModulo = true;

                        $aContenidoModulo = array_merge_recursive($aContenidoModulo, [
                            '<div class="list-group-item">',
                            '<img src="' . $this->generarImagenMapa($oGrupo->latitud, $oGrupo->longitud) . '" style="max-height: 400px" />',
                            '<br/>',
                            '</div>'
                        ]);
                    }
                } else if ($oGrupo->tipo === 'tabla') {
                    $aTabla = [
                        '<div class="list-group-item">',
                        '<br/>',
                        '<table class="table" style="width: 100%; font-size: 12px; text-align: center;">',
                    ];

                    $bValores = false;

                    foreach ($oGrupo->filas as $aCampos) {
                        $aTabla = array_merge_recursive($aTabla, [
                            '<tr>'
                        ]);

                        foreach ($aCampos as $oCampo) {
                            $aTabla = array_merge_recursive($aTabla, [
                                '<th>' . $oCampo->titulo . '</th>'
                            ]);
                        }

                        $aTabla = array_merge_recursive($aTabla, [
                            '</tr>',
                            '<tr>'
                        ]);

                        foreach ($aCampos as $oCampo) {
                            $aTabla = array_merge_recursive($aTabla, [
                                '<td>' . $oCampo->valor . '</td>'
                            ]);

                            if (isset($oCampo->valor)) {
                                $bValores = true;
                            }
                        }

                        $aTabla = array_merge_recursive($aTabla, [
                            '</tr>'
                        ]);
                    }

                    $aTabla = array_merge_recursive($aTabla, [
                        '</table>',
                        '<br/>',
                        '</div>'
                    ]);

                    if ($bValores) {
                        $bContenidoModulo = true;
                        $aContenidoModulo = array_merge_recursive($aContenidoModulo, $aTabla);
                    }                        
                }

                $aContenidoModulo = array_merge_recursive($aContenidoModulo, [
                    '</div>'
                ]);
            }

            $aContenidoModulo = array_merge_recursive($aContenidoModulo, [
                '</div>',
                '</div>'
            ]);

            if ($bContenidoModulo) {
                $aContenidoPDF = array_merge_recursive($aContenidoPDF, $aContenidoModulo);
            }
        }

        $aContenidoPDF = array_merge_recursive($aContenidoPDF, [
            '</div>',
            '</div>',
            '</div>',
            '</body>',
            '</html>'
        ]);

        //echo implode('', $aContenidoPDF);exit();

        $this->oArchivoPDF = new PDF($this->oSesion);
        $this->oArchivoPDF->configurarColumnas(1, 'J', 3);
        $this->oArchivoPDF->oEncabezado->sContenido1 = '<img src="' . BASE_URL . 'image/logo1.png" style="max-height: 60px" />';

        $this->oArchivoPDF->encabezadoPDF();
        $sNombreArchivo = 'encuesta_' . $sNombreArchivoEncuesta . '_' . time();

        $this->oArchivoPDF->generarPDFArray($aContenidoPDF, PATH_TEMP . $sNombreArchivo . '.pdf');

        ob_clean(); 
        return array('file' => BASE_URL . 'temp/' . $sNombreArchivo . '.pdf');
    }

    public function generarImagenMapa($lat, $lng) {
        $zoom = 15;
        $width = 400;
        $height = 300;
        $name = rand(1, 100000000);
        $apiKey = 'AIzaSyC9O_0TE0QcMPctgeWm0_F3_MNz4BCvMnM';
        $url = "https://maps.googleapis.com/maps/api/staticmap?center=$lat,$lng&zoom=$zoom&size={$width}x{$height}&markers=color:red%7Clabel:A%7C$lat,$lng&key=$apiKey";
        $image = file_get_contents($url);
        file_put_contents("../temp/{$name}.png", $image);        
        return BASE_URL . 'temp/' . $name . '.png';
    }

    public function puntosMapa() {
        $aDatos = [];
        $bCaptaciones = (bool)($this->oPostData->modulos?->captaciones ?? false);
        $bVertimiento = (bool)($this->oPostData->modulos?->vertimiento ?? false);
        $bOcupacion   = (bool)($this->oPostData->modulos?->ocupacion   ?? false);
        $bMinera      = (bool)($this->oPostData->modulos?->minera      ?? false);

        if ($bCaptaciones) { $aDatos['captaciones'] = $this->puntosMapaPredio(1); }
        if ($bVertimiento) { $aDatos['vertimiento'] = $this->puntosMapaPredio(2); }
        if ($bOcupacion)   { $aDatos['ocupacion']   = $this->puntosMapaPredio(3); }
        if ($bMinera)      { $aDatos['minera']      = $this->puntosMapaPredio(4); }

        return $aDatos;
    }

    public function puntosMapaPredio(?int $idModuloOverride = null) 
    {
        $aIdEncuestas = [];

        $idModulo    = $idModuloOverride ?? (isset($this->oPostData->idModulo) ? (int)$this->oPostData->idModulo : 0);
        if ($idModulo === null || $idModulo < 1 || $idModulo > 4) {
            // Si envían null o >4, devolver lista vacía y NO ejecutar query
            return [];
        }
        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;

        switch ($idModulo) {
            case 1:
                $this->sSql = "
                    SELECT 
                        p.id_encuesta,
                        p.latitud, 
                        p.longitud,
                        p.este,
                        p.norte,
                        dep.descripcion AS departamento,
                        e.codigo_encuesta,
                        e.codigo_cuenca_hidrografica,
                        p.nombre as nombre_predio,
                        mun.descripcion as municipio,
                        urh.total as valor_captado,
                        urh.domestico as valor_domestico,
                        urh.pecuario as valor_pecuario,
                        urh.acuicola as valor_acuicola,
                        urh.agricola as valor_agricola,
                        urh.industrial as valor_industrial,
                        urh.mineria as valor_mineria,
                        urh.generacion_electrica as valor_generacion_electrica,
                        urh.otros as valor_otros,
                        uhn1.descripcion as n_unidad_1,
                        uhn2.descripcion as n_unidad_2,
                        chn1.descripcion as c_unidad_1,
                        chn2.descripcion as c_unidad_2,
                        cef.este AS este_m,
                        cef.norte AS norte_m,
                        dep_m.descripcion AS departamento_m,
                        mun_m.descripcion AS municipio_m,
                        fc.id_nombre_und_geografica_nivel1 AS id_uhn1,
                        fc.id_nombre_und_geografica_nivel2 AS id_uhn2,
                        fc.id_municipio                     AS id_municipio
                    FROM dbo.predio p
                    INNER JOIN dbo.encuesta e ON p.id_encuesta = e.id
                    LEFT JOIN dbo.fuente_captacion fc ON fc.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.uso_recurso_hidrico urh ON urh.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.param_sublista mun ON p.id_municipio = mun.id
                    LEFT JOIN dbo.param_sublista dep ON p.id_departamento = dep.id
                    LEFT JOIN dbo.param_sublista uhn1 ON fc.id_nombre_und_geografica_nivel1 = uhn1.id
                    LEFT JOIN dbo.param_sublista uhn2 ON fc.id_nombre_und_geografica_nivel2 = uhn2.id
                    LEFT JOIN dbo.param_sublista chn1 ON fc.id_codigo_und_geografica_nivel1 = chn1.id
                    LEFT JOIN dbo.param_sublista chn2 ON fc.id_codigo_und_geografica_nivel2 = chn2.id
                    LEFT JOIN dbo.param_sublista dep_m ON fc.id_departamento = dep_m.id
                    LEFT JOIN dbo.param_sublista mun_m ON fc.id_municipio = mun_m.id
                    LEFT JOIN dbo.captacion_en_fuente cef ON cef.id_encuesta = p.id_encuesta
                    WHERE e.modulos = '1'
                ";
            break;
            
            case 2:
                $this->sSql = "
                    SELECT 
                        p.id_encuesta,
                        p.latitud, 
                        p.longitud,
                        p.este,
                        p.norte,
                        dep.descripcion AS departamento,
                        e.codigo_encuesta,
                        e.codigo_cuenca_hidrografica,
                        p.nombre as nombre_predio,
                        mun.descripcion as municipio,
                        urh.total as valor_captado,
                        uhn1.descripcion as n_unidad_1,
                        uhn2.descripcion as n_unidad_2,
                        chn1.descripcion as c_unidad_1,
                        chn2.descripcion as c_unidad_2,
                        iv.este AS este_m,
                        iv.norte AS norte_m,
                        dep_m.descripcion AS departamento_m,
                        mun_m.descripcion AS municipio_m,
                        ocr.descripcion as calidad_cr,
                        av.descripcion as actividad_vertimiento,
                        cv.descripcion as caudal_vertido, 
                        frv.id_nombre_und_geografica_nivel1 AS id_uhn1, 
                        frv.id_nombre_und_geografica_nivel2 AS id_uhn2, 
                        frv.id_municipio                      AS id_municipio
                    FROM dbo.predio p
                    INNER JOIN dbo.encuesta e ON p.id_encuesta = e.id
                    LEFT JOIN dbo.fuente_receptora_vertimiento frv ON p.id_encuesta = frv.id_encuesta
                    LEFT JOIN dbo.fuente_captacion fc ON fc.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.uso_recurso_hidrico urh ON urh.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.param_sublista mun ON p.id_municipio = mun.id
                    LEFT JOIN dbo.param_sublista dep ON p.id_departamento = dep.id
                    LEFT JOIN dbo.param_sublista uhn1 ON frv.id_nombre_und_geografica_nivel1 = uhn1.id
                    LEFT JOIN dbo.param_sublista uhn2 ON frv.id_nombre_und_geografica_nivel2 = uhn2.id
                    LEFT JOIN dbo.param_sublista chn1 ON frv.id_codigo_und_geografica_nivel1 = chn1.id
                    LEFT JOIN dbo.param_sublista chn2 ON frv.id_codigo_und_geografica_nivel2 = chn2.id
                    LEFT JOIN dbo.param_sublista dep_m ON frv.id_departamento = dep_m.id
                    LEFT JOIN dbo.param_sublista mun_m ON frv.id_municipio = mun_m.id
                    LEFT JOIN dbo.param_sublista ocr ON frv.id_objetivo_calidad_receptor = ocr.id
                    LEFT JOIN dbo.informacion_vertimiento iv ON iv.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.param_sublista av ON iv.id_actividad_vertimiento = av.id
                    LEFT JOIN dbo.param_sublista cv ON iv.caudal_vertido = cv.id
                    WHERE e.modulos = '2'
                ";
            break;

            case 3:
                $this->sSql = "
                    SELECT 
                        p.id_encuesta,
                        p.latitud, 
                        p.longitud,
                        p.este,
                        p.norte,
                        dep.descripcion AS departamento,
                        e.codigo_encuesta,
                        e.codigo_cuenca_hidrografica,
                        p.nombre as nombre_predio,
                        mun.descripcion as municipio,
                        urh.total as valor_captado,
                        uhn1.descripcion as n_unidad_1,
                        uhn2.descripcion as n_unidad_2,
                        chn1.descripcion as c_unidad_1,
                        chn2.descripcion as c_unidad_2,
                        iocoh.este AS este_m,
                        iocoh.norte AS norte_m,
                        dep_m.descripcion AS departamento_m,
                        mun_m.descripcion AS municipio_m,
                        ca.descripcion AS cuerpo_agua,
                        ifoc.nombre_fuente,
                        tipo_o.descripcion AS tipo_obra, 
                        ifoc.id_nombre_und_geografica_nivel1 AS id_uhn1, 
                        ifoc.id_nombre_und_geografica_nivel2 AS id_uhn2, 
                        ifoc.id_municipio                       AS id_municipio
                    FROM dbo.predio p
                    INNER JOIN dbo.encuesta e ON p.id_encuesta = e.id
                    LEFT JOIN dbo.informacion_fuente_ocupacion_cauce ifoc ON p.id_encuesta = ifoc.id_encuesta
                    LEFT JOIN dbo.fuente_captacion fc ON fc.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.uso_recurso_hidrico urh ON urh.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.param_sublista mun ON p.id_municipio = mun.id
                    LEFT JOIN dbo.param_sublista dep ON p.id_departamento = dep.id
                    LEFT JOIN dbo.param_sublista uhn1 ON ifoc.id_nombre_und_geografica_nivel1 = uhn1.id
                    LEFT JOIN dbo.param_sublista uhn2 ON ifoc.id_nombre_und_geografica_nivel2 = uhn2.id
                    LEFT JOIN dbo.param_sublista chn1 ON ifoc.id_codigo_und_geografica_nivel1 = chn1.id
                    LEFT JOIN dbo.param_sublista chn2 ON ifoc.id_codigo_und_geografica_nivel2 = chn2.id
                    LEFT JOIN dbo.param_sublista dep_m ON ifoc.id_departamento = dep_m.id
                    LEFT JOIN dbo.param_sublista mun_m ON ifoc.id_municipio = mun_m.id
                    LEFT JOIN dbo.param_sublista ca ON ifoc.id_cuerpo_agua = ca.id
                    LEFT JOIN dbo.informacion_ocupacion_cauce_obra_hidraulica iocoh ON iocoh.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.param_sublista tipo_o ON iocoh.id_tipo_obra = tipo_o.id
                    WHERE e.modulos = '3'
                ";
            break;

            case 4:
                $this->sSql = "
                    SELECT 
                        p.id_encuesta,
                        p.latitud, 
                        p.longitud,
                        p.este,
                        p.norte,
                        dep.descripcion AS departamento,
                        e.codigo_encuesta,
                        e.codigo_cuenca_hidrografica,
                        p.nombre as nombre_predio,
                        mun.descripcion as municipio,
                        urh.total as valor_captado,
                        uhn1.descripcion as n_unidad_1,
                        uhn2.descripcion as n_unidad_2,
                        chn1.descripcion as c_unidad_1,
                        chn2.descripcion as c_unidad_2,
                        act_ext.este AS este_m,
                        act_ext.norte AS norte_m,
                        dep_m.descripcion AS departamento_m,
                        mun_m.descripcion AS municipio_m,
                        iamca.nombre_fuente,
                        iamca.descripcion_actividad,
                        tma.descripcion AS material_arrastre, 
                        iamca.id_nombre_und_geografica_nivel1 AS id_uhn1, 
                        iamca.id_nombre_und_geografica_nivel2 AS id_uhn2, 
                        iamca.id_municipio                        AS id_municipio
                    FROM dbo.predio p
                    INNER JOIN dbo.encuesta e ON p.id_encuesta = e.id
                    LEFT JOIN dbo.informacion_actividad_minera_cuerpos_agua iamca ON p.id_encuesta = iamca.id_encuesta
                    LEFT JOIN dbo.fuente_captacion fc ON fc.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.uso_recurso_hidrico urh ON urh.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.param_sublista mun ON p.id_municipio = mun.id
                    LEFT JOIN dbo.param_sublista dep ON p.id_departamento = dep.id
                    LEFT JOIN dbo.param_sublista uhn1 ON iamca.id_nombre_und_geografica_nivel1 = uhn1.id
                    LEFT JOIN dbo.param_sublista uhn2 ON iamca.id_nombre_und_geografica_nivel2 = uhn2.id
                    LEFT JOIN dbo.param_sublista chn1 ON iamca.id_codigo_und_geografica_nivel1 = chn1.id
                    LEFT JOIN dbo.param_sublista chn2 ON iamca.id_codigo_und_geografica_nivel2 = chn2.id
                    LEFT JOIN dbo.param_sublista dep_m ON iamca.id_departamento = dep_m.id
                    LEFT JOIN dbo.param_sublista mun_m ON iamca.id_municipio = mun_m.id
                    LEFT JOIN dbo.actividades_extraccion act_ext ON act_ext.id_encuesta = p.id_encuesta
                    LEFT JOIN dbo.param_sublista tma ON act_ext.id_tipo_material_arrastre = tma.id
                    WHERE e.modulos = '4'
                ";
            break;

            }

        $this->aExecute = [];
        $this->execQuery();
        $aPuntos = [];

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $bContinuar = count($aIdEncuestas) > 0 ? in_array((int) $r['id_encuesta'], $aIdEncuestas) : true;

            if ($bContinuar) {
                $Punto = [
                    'id_encuesta' => (int) $r['id_encuesta'],
                    'latitud' => (float) $r['latitud'],
                    'longitud' => (float) $r['longitud'],
                    'codigo_encuesta' => $r['codigo_encuesta'] ?: 'No disponible',
                    'cod_cuenca_h' => $r['codigo_cuenca_hidrografica'] ?: 'No disponible',
                    'ubicacion' => $r['nombre_predio'] ?: 'No disponible',
                    'municipio' => $r['municipio'] ?: 'No disponible',
                    'nombre_uhn1' => $r['n_unidad_1'] ?: 'No disponible',
                    'nombre_uhn2' => $r['n_unidad_2'] ?: 'No disponible',
                    'cod_uhn1' => $r['c_unidad_1'] ?: 'No disponible',
                    'cod_uhn2' => $r['c_unidad_2'] ?: 'No disponible',
                    'valor_captado' => (float)$r['valor_captado'] ?: 0,
                    'este_u' => (int) $r['este']?: 0,
                    'norte_u' => (int) $r['norte']?: 0,
                    'departamento' => $r['departamento']?: 'No disponible',
                    'depto_m' => $r['departamento_m']?: 'No disponible',
                    'municipio_m' => $r['municipio_m']?: 'No disponible',
                    'este_modulo' => (int) $r['este_m']?: 0,
                    'norte_modulo' => (int) $r['norte_m']?: 0,
                    // IDs para filtros
                    'id_uhn1'       => isset($r['id_uhn1']) ? (int)$r['id_uhn1'] : null,
                    'id_uhn2'       => isset($r['id_uhn2']) ? (int)$r['id_uhn2'] : null,
                    'id_municipio'  => isset($r['id_municipio']) ? (int)$r['id_municipio'] : null
                ];
            }
            switch ($idModulo) {
                case 1:
                    $Punto['valor_domestico'] = (float)$r['valor_domestico'] ?: 0;
                    $Punto['valor_pecuario'] = (float)$r['valor_pecuario'] ?: 0;
                    $Punto['valor_acuicola'] = (float)$r['valor_acuicola'] ?: 0;
                    $Punto['valor_agricola'] = (float)$r['valor_agricola'] ?: 0;
                    $Punto['valor_industrial'] = (float)$r['valor_industrial'] ?: 0;
                    $Punto['valor_mineria'] = (float)$r['valor_mineria'] ?: 0;
                    $Punto['valor_generacion_electrica'] = (float)$r['valor_generacion_electrica'] ?: 0;
                    $Punto['valor_otros'] = (float)$r['valor_otros'] ?: 0;
                break;

                case 2:
                    $Punto['calidad_cuerpo_receptor'] = $r['calidad_cr']?: 'No disponible';
                    $Punto['actividad_vertimiento'] = $r['actividad_vertimiento']?: 'No disponible';
                    $Punto['caudal_vertido'] = $r['caudal_vertido']?: 'No disponible';    
                break;

                case 3:
                    $Punto['cuerpo_agua'] = $r['cuerpo_agua']?: 'No disponible';
                    $Punto['nombre_fuente'] = $r['nombre_fuente']?: 'No disponible';
                    $Punto['tipo_obra'] = $r['tipo_obra']?: 'No disponible';      
                break;
                
                case 4:
                    $Punto['nombre_fuente'] = $r['nombre_fuente']?: 'No disponible';
                    $Punto['descripcion_actividad'] = $r['descripcion_actividad']?: 'No disponible';
                    $Punto['material_arrastre'] = $r['material_arrastre']?: 'No disponible';
                break;
                
            }

            $aPuntos[] = $Punto;
            
        }

        $filters = [
            'id_uhn1'      => $UHN1,
            'id_uhn2'      => $UHN2,
            'id_municipio' => $idMunicipio,
        ];

        $aPuntos = array_values(array_filter($aPuntos, function ($p) use ($filters) {
            foreach ($filters as $key => $val) {
                if ($val !== null && $val !== 0) {
                    if (!isset($p[$key]) || (int)$p[$key] !== (int)$val) {
                        return false;
                    }
                }
            }
            return true;
        }));

        return $aPuntos;
    }

    public function conteoModulo($idModulo) {
        $aEstructura = $this->cargarEstructuraEncuesta();
        $this->aExecute = [];
        $aEncuestas = [];

        foreach ($aEstructura['estructura'] as $oRegistro) {
            if ($oRegistro->id_modulo === $idModulo) {
                $aNombresCampos = [];

                foreach ($oRegistro->grupos as $oGrupo) {
                    if ($oGrupo->tipo !== 'tabla') {
                        foreach ($oGrupo->campos as $oCampo) {
                            if ($oCampo->bd !== $oRegistro->id_validar && $oCampo->tipo === 'seleccion_multiple') {
                                $aNombresCampos[] = "id_" . $oCampo->bd;
                            } else if ($oCampo->bd !== $oRegistro->id_validar) {
                                $aNombresCampos[] = $oCampo->bd;
                            }
                        }
                    } else {
                        foreach ($oGrupo->filas as $aCampos) {
                            foreach ($aCampos as $oCampo) {
                                $aNombresCampos[] = $oCampo->bd;
                            }
                        }
                    }
                }

                $this->sSql = "
                    SELECT DISTINCT $oRegistro->id_validar AS 'id' 
                    FROM dbo." . $oRegistro->tabla . "
                    WHERE " . implode(' IS NOT NULL OR ', $aNombresCampos) . " IS NOT NULL
                ";
                
                $this->execQuery();

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    if (!in_array((int) $r['id'], $aEncuestas)) {
                        $aEncuestas[] = (int) $r['id'];
                    }
                }
            }
        }

        return count($aEncuestas);
    }

    public function conteoUsos() 
    {
        $aValores = [];
        $aSumaPorEncuesta = [];
        $this->aExecute = [];
        $aCamposUsosGrupo = [
            'uso_pecuario_1_consumo', 'uso_pecuario_2_consumo', 'uso_pecuario_3_consumo', 'uso_pecuario_4_consumo',
            'uso_acuicola_1_consumo', 'uso_acuicola_2_consumo', 'uso_acuicola_3_consumo', 'uso_acuicola_4_consumo',
            'uso_agricola_silvicola_1_consumo', 'uso_agricola_silvicola_2_consumo', 'uso_agricola_silvicola_3_consumo', 'uso_agricola_silvicola_4_consumo',
            'uso_industrial_consumo',
            'uso_minero_consumo',
            'uso_generacion_hidroelectrica_consumo',
            'uso_recreacional_consumo',
            'uso_servicios_consumo',
            'uso_explotacion_petrolera_consumo',
            'otros_usos_consumo'
        ];

        $this->sSql = "
            SELECT id_encuesta, " . implode(',', $aCamposUsosGrupo) . "
            FROM dbo.uso_recurso_hidrico
            WHERE " . implode(' IS NOT NULL OR ', $aCamposUsosGrupo) . " IS NOT NULL
        ";       
        
        $this->execQuery();       

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $aSumaPorEncuesta[(int) $r['id_encuesta']] = isset($aSumaPorEncuesta[(int) $r['id_encuesta']]) ? $aSumaPorEncuesta[(int) $r['id_encuesta']] : 0;

            foreach ($aCamposUsosGrupo as $sCampoUso) {
                if (is_numeric($r[$sCampoUso])) {
                    $aValores[] = (float) $r[$sCampoUso];
                    $aSumaPorEncuesta[(int) $r['id_encuesta']] = $aSumaPorEncuesta[(int) $r['id_encuesta']] + (float) $r[$sCampoUso];
                }
            }
        }

        return ['total' => array_sum($aValores), 'encuesta' => $aSumaPorEncuesta];
    }

    public function conteoUsosDistinto() {
        $this->aCamposUsosGrupo = [
            (object) [ 'bd' => 'id_uso_domestico', 'nombre' => 'Domestico', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_pecuario', 'nombre' => 'Pecuario', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_acuicola', 'nombre' => 'Acuícola', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_agricola_silvicola', 'nombre' => 'Agrícola, silvícola', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_industrial', 'nombre' => 'Industrial', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_minero', 'nombre' => 'Minero', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_generacion_hidroelectrica', 'nombre' => 'Generación hidroeléctrica', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_recreacional', 'nombre' => 'Recreacional', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_servicios', 'nombre' => 'Servicios', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_uso_explotacion_petrolera', 'nombre' => 'Explotación petrolera', 'cantidad' => 0 ],
            (object) [ 'bd' => 'id_otros_usos', 'nombre' => 'Otros usos', 'cantidad' => 0 ]
        ];

        // 2) Filtros entrantes (mismos nombres que usas en otros métodos)
        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;
        
        foreach($this->aCamposUsosGrupo as $oCampoUso) {
            $this->sSql = "
                SELECT COUNT(DISTINCT urh.id) AS cantidad
                FROM dbo.uso_recurso_hidrico urh
                INNER JOIN dbo.encuesta e ON e.id = urh.id_encuesta
                LEFT JOIN dbo.fuente_captacion fc ON fc.id_encuesta = urh.id_encuesta
                WHERE
                    e.modulos = '1'
                    AND (urh.{$oCampoUso->bd} IN (439, 446))
                    AND (? IS NULL OR fc.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR fc.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR fc.id_municipio = ?)
            ";

            $this->aExecute = [
                $UHN1, $UHN1,
                $UHN2, $UHN2,
                $idMunicipio, $idMunicipio
            ];

            $this->execQuery();              

            $row = $this->stm->fetch(PDO::FETCH_ASSOC);
            $oCampoUso->cantidad = isset($row['cantidad']) ? (int)$row['cantidad'] : 0;
        }
    }

    public function sumaDemandaHidrica() {
        $this->aCamposUsosDemanda = [
            (object) [ 'bd' => 'id_uso_domestico', 'nombre' => 'Domestico', 'cantidad' => 0, 'elementos' => ['domestico'] ],
            (object) [ 'bd' => 'id_uso_pecuario', 'nombre' => 'Pecuario', 'cantidad' => 0, 'elementos' => ['pecuario'] ],
            (object) [ 'bd' => 'id_uso_acuicola', 'nombre' => 'Acuícola', 'cantidad' => 0, 'elementos' => ['acuicola'] ],
            (object) [ 'bd' => 'id_uso_agricola_silvicola', 'nombre' => 'Agrícola, silvícola', 'cantidad' => 0, 'elementos' => ['agricola'] ],
            (object) [ 'bd' => 'id_uso_industrial', 'nombre' => 'Industrial', 'cantidad' => 0, 'elementos' => ['industrial'] ],
            (object) [ 'bd' => 'id_uso_minero', 'nombre' => 'Minero', 'cantidad' => 0, 'elementos' => ['mineria'] ],
            (object) [ 'bd' => 'id_uso_generacion_hidroelectrica', 'nombre' => 'Generación hidroeléctrica', 'cantidad' => 0, 'elementos' => ['generacion_electrica'] ],
            (object) [ 'bd' => 'id_otros_usos', 'nombre' => 'Otros usos', 'cantidad' => 0, 'elementos' => ['otros'] ]
            #(object) [ 'bd' => 'id_uso_pecuario', 'nombre' => 'Pecuario', 'cantidad' => 0, 'elementos' => ['uso_pecuario_1_consumo', 'uso_pecuario_2_consumo', 'uso_pecuario_3_consumo', 'uso_pecuario_4_consumo'] ],
            #(object) [ 'bd' => 'id_uso_acuicola', 'nombre' => 'Acuícola', 'cantidad' => 0, 'elementos' => ['uso_acuicola_1_consumo', 'uso_acuicola_2_consumo', 'uso_acuicola_3_consumo', 'uso_acuicola_4_consumo'] ],
            #(object) [ 'bd' => 'id_uso_agricola_silvicola', 'nombre' => 'Agrícola, silvícola', 'cantidad' => 0, 'elementos' => ['uso_agricola_silvicola_1_consumo', 'uso_agricola_silvicola_2_consumo', 'uso_agricola_silvicola_3_consumo', 'uso_agricola_silvicola_4_consumo'] ],
            #(object) [ 'bd' => 'id_uso_industrial', 'nombre' => 'Industrial', 'cantidad' => 0, 'elementos' => ['uso_industrial_consumo'] ],
            #(object) [ 'bd' => 'id_uso_minero', 'nombre' => 'Minero', 'cantidad' => 0, 'elementos' => ['uso_minero_consumo'] ],
            #(object) [ 'bd' => 'id_uso_generacion_hidroelectrica', 'nombre' => 'Generación hidroeléctrica', 'cantidad' => 0, 'elementos' => ['uso_generacion_hidroelectrica_consumo'] ],
            #(object) [ 'bd' => 'id_uso_recreacional', 'nombre' => 'Recreacional', 'cantidad' => 0, 'elementos' => ['uso_recreacional_consumo'] ],
            #(object) [ 'bd' => 'id_uso_servicios', 'nombre' => 'Servicios', 'cantidad' => 0, 'elementos' => ['uso_servicios_consumo'] ],
            #(object) [ 'bd' => 'id_uso_explotacion_petrolera', 'nombre' => 'Explotación petrolera', 'cantidad' => 0, 'elementos' => ['uso_explotacion_petrolera_consumo'] ],
            #(object) [ 'bd' => 'id_otros_usos', 'nombre' => 'Otros usos', 'cantidad' => 0, 'elementos' => ['otros_usos_consumo'] ]
        ];

        $aEncuestas = [];

        // 2) Filtros entrantes (mismos nombres que usas en otros métodos)
        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;

        foreach($this->aCamposUsosDemanda as $oCampoUso) {
            $this->sSql = "
                SELECT urh.id_encuesta as id_encuesta, " . $oCampoUso->bd . ", " . implode(', ', $oCampoUso->elementos) . "
                FROM dbo.uso_recurso_hidrico urh
                LEFT JOIN dbo.fuente_captacion fc ON fc.id_encuesta = urh.id_encuesta
                WHERE (" . $oCampoUso->bd . " = 439 OR " . $oCampoUso->bd . " = 446)
                AND (? IS NULL OR fc.id_nombre_und_geografica_nivel1 = ?)
                AND (? IS NULL OR fc.id_nombre_und_geografica_nivel2 = ?)
                AND (? IS NULL OR fc.id_municipio = ?)
            ";

            $this->aExecute = [
                $UHN1, $UHN1,
                $UHN2, $UHN2,
                $idMunicipio, $idMunicipio
            ];
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $sumaDemanda = 0;

                foreach($oCampoUso->elementos as $campo) {
                    $sumaDemanda += isset($r[$campo]) ? (float) $r[$campo] : 0;
                }

                $oCampoUso->cantidad += $sumaDemanda;

                if ($sumaDemanda > 0) {
                    $aEncuestas[$r['id_encuesta']] = isset($aEncuestas[$r['id_encuesta']]) ? $aEncuestas[$r['id_encuesta']] : 0;
                    $aEncuestas[$r['id_encuesta']] = $sumaDemanda + $aEncuestas[$r['id_encuesta']];
                }

                //unset($oCampoUso->bd);
                //unset($oCampoUso->elementos);
            }
        }

        return $aEncuestas;
    }

    public function sumaDemandaVertimientos() {
        $aEncuestas = [];
        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;

        $this->sSql = "
            SELECT
                SUM(iv.caudal_vertido) AS consumo,
                a.descripcion
            FROM dbo.informacion_vertimiento iv
            INNER JOIN dbo.param_sublista a ON a.id = iv.id_actividad_vertimiento
            LEFT JOIN dbo.fuente_receptora_vertimiento frv ON frv.id_encuesta = iv.id_encuesta
                AND (? IS NULL OR frv.id_nombre_und_geografica_nivel1 = ?)
                AND (? IS NULL OR frv.id_nombre_und_geografica_nivel2 = ?)
                AND (? IS NULL OR frv.id_municipio = ?)
            GROUP BY iv.id_actividad_vertimiento, a.descripcion
            ORDER BY a.descripcion
        ";

        // Cada filtro se usa dos veces
        $this->aExecute = [
            $UHN1, $UHN1,
            $UHN2, $UHN2,
            $idMunicipio, $idMunicipio,
        ];
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $aEncuestas[] = (object) [
                'nombre' => $r['descripcion'], 
                'cantidad' => (float) $r['consumo']
            ];
        }

        return $aEncuestas;
    }

    public function conteoActividadVertimiento() {
        $this->aCamposActividadVertimiento = [];

        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;

        $this->sSql = "
            SELECT
                COUNT(DISTINCT iv.id) AS cantidad,
                iv.id_actividad_vertimiento,
                a.descripcion
            FROM dbo.informacion_vertimiento iv
            INNER JOIN dbo.param_sublista a ON a.id = iv.id_actividad_vertimiento
            INNER JOIN dbo.encuesta e ON e.id = iv.id_encuesta
            LEFT JOIN dbo.fuente_receptora_vertimiento frv ON frv.id_encuesta = iv.id_encuesta
            WHERE
                e.modulos = '2'
                AND (? IS NULL OR frv.id_nombre_und_geografica_nivel1 = ?)
                AND (? IS NULL OR frv.id_nombre_und_geografica_nivel2 = ?)
                AND (? IS NULL OR frv.id_municipio = ?)
            GROUP BY iv.id_actividad_vertimiento, a.descripcion
            ORDER BY a.descripcion
        ";

        // Cada filtro se usa dos veces
        $this->aExecute = [
            $UHN1, $UHN1,
            $UHN2, $UHN2,
            $idMunicipio, $idMunicipio,
        ];
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $this->aCamposActividadVertimiento[] = (object) [
                'nombre' => $r['descripcion'], 
                'cantidad' => (int) $r['cantidad']
            ];
        }
    }

    public function conteoOcupacionesCauceTipoObra() {
        $this->aCamposOcupacionesCauceTipoObra = [];

        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;

        $this->sSql = "
            SELECT
                COUNT(DISTINCT oc.id) AS cantidad,
                oc.id_tipo_obra,
                a.descripcion
            FROM dbo.informacion_ocupacion_cauce_obra_hidraulica oc
            INNER JOIN dbo.param_sublista a ON a.id = oc.id_tipo_obra
            INNER JOIN dbo.encuesta e ON e.id = oc.id_encuesta
            LEFT JOIN dbo.informacion_fuente_ocupacion_cauce ifoc ON ifoc.id_encuesta = oc.id_encuesta
            WHERE
                e.modulos = '3'
                AND (? IS NULL OR ifoc.id_nombre_und_geografica_nivel1 = ?)
                AND (? IS NULL OR ifoc.id_nombre_und_geografica_nivel2 = ?)
                AND (? IS NULL OR ifoc.id_municipio = ?)
            GROUP BY oc.id_tipo_obra, a.descripcion
            ORDER BY a.descripcion
        ";

        // Cada filtro se usa dos veces
        $this->aExecute = [
            $UHN1, $UHN1,
            $UHN2, $UHN2,
            $idMunicipio, $idMunicipio,
        ];
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $this->aCamposOcupacionesCauceTipoObra[] = (object) [
                'nombre' => $r['descripcion'], 
                'cantidad' => (int) $r['cantidad']
            ];
        }
    }

    public function conteoMineria() {
        $this->aCamposMineria = [];

        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;

        $this->sSql = "
            SELECT
                COUNT(DISTINCT ae.id) AS cantidad,
                ae.id_tipo_material_arrastre,
                a.descripcion
            FROM dbo.actividades_extraccion ae
            INNER JOIN dbo.param_sublista a ON a.id = ae.id_tipo_material_arrastre
            INNER JOIN dbo.encuesta e ON e.id = ae.id_encuesta
            LEFT JOIN dbo.informacion_actividad_minera_cuerpos_agua iamca ON iamca.id_encuesta = ae.id_encuesta
            WHERE
                e.modulos = '4'
                AND (? IS NULL OR iamca.id_nombre_und_geografica_nivel1 = ?)
                AND (? IS NULL OR iamca.id_nombre_und_geografica_nivel2 = ?)
                AND (? IS NULL OR iamca.id_municipio = ?)
            GROUP BY ae.id_tipo_material_arrastre, a.descripcion
            ORDER BY a.descripcion
        ";

        // Cada filtro se usa dos veces
        $this->aExecute = [
            $UHN1, $UHN1,
            $UHN2, $UHN2,
            $idMunicipio, $idMunicipio,
        ];
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $this->aCamposMineria[] = (object) [
                'nombre' => $r['descripcion'], 
                'cantidad' => (int) $r['cantidad']
            ];
        }
    }

    private function conteoPorModulo(int $modulo) : int
    {
        // Filtros entrantes
        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;

        // Cambian las tablas de soporte según módulo:
        // 1=Captaciones -> fuente_captacion (fc)
        // 2=Vertimientos -> fuente_receptora_vertimiento (frv)
        // 3=Ocupaciones -> informacion_fuente_ocupacion_cauce (ifoc)
        // 4=Minería -> informacion_actividad_minera_cuerpos_agua (iamca)
        switch ($modulo) {
            case 1:
                $join = "LEFT JOIN dbo.fuente_captacion fc ON fc.id_encuesta = e.id";
                $whereFiltros = "
                    AND (? IS NULL OR fc.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR fc.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR fc.id_municipio = ?)
                ";
                $exec = [$UHN1,$UHN1,$UHN2,$UHN2,$idMunicipio,$idMunicipio];
                break;

            case 2:
                $join = "LEFT JOIN dbo.fuente_receptora_vertimiento frv ON frv.id_encuesta = e.id";
                $whereFiltros = "
                    AND (? IS NULL OR frv.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR frv.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR frv.id_municipio = ?)
                ";
                $exec = [$UHN1,$UHN1,$UHN2,$UHN2,$idMunicipio,$idMunicipio];
                break;

            case 3:
                $join = "LEFT JOIN dbo.informacion_fuente_ocupacion_cauce ifoc ON ifoc.id_encuesta = e.id";
                $whereFiltros = "
                    AND (? IS NULL OR ifoc.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR ifoc.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR ifoc.id_municipio = ?)
                ";
                $exec = [$UHN1,$UHN1,$UHN2,$UHN2,$idMunicipio,$idMunicipio];
                break;

            case 4:
                // OJO: aquí corregimos el bug típico -> join por id_encuesta
                $join = "LEFT JOIN dbo.informacion_actividad_minera_cuerpos_agua iamca ON iamca.id_encuesta = e.id";
                $whereFiltros = "
                    AND (? IS NULL OR iamca.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR iamca.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR iamca.id_municipio = ?)
                ";
                $exec = [$UHN1,$UHN1,$UHN2,$UHN2,$idMunicipio,$idMunicipio];
                break;

            default:
                return 0;
        }

        $this->sSql = "
            SELECT COUNT(DISTINCT e.id) AS cantidad
            FROM dbo.encuesta e
            $join
            WHERE e.modulos = ?
            $whereFiltros
        ";

        // Agregamos el módulo al final del arreglo de parámetros (o al inicio; solo sé consistente)
        // En este caso lo pondremos primero para que sea fácil leer:
        $this->aExecute = array_merge([$modulo], $exec);

        $this->execQuery();
        $row = $this->stm->fetch(PDO::FETCH_ASSOC);
        return isset($row['cantidad']) ? (int)$row['cantidad'] : 0;
    }

    private function resumenModulos() : array
    {
        $capt = (int) ($this->conteoPorModulo(1) ?? 0);
        $vert = (int) ($this->conteoPorModulo(2) ?? 0);
        $ocup = (int) ($this->conteoPorModulo(3) ?? 0);
        $mine = (int) ($this->conteoPorModulo(4) ?? 0);

        return [
            'CAPTACIONES'  => $capt,
            'VERTIMIENTOS' => $vert,
            'OCUPACIONES'  => $ocup,
            'MINERIA'      => $mine,
            'TOTAL'        => $capt + $vert + $ocup + $mine
        ];
    }

    public function resumenEncuestas() : array
    {
        $capt = (int) ($this->conteoPorModulo(1) ?? 0);
        $vert = (int) ($this->conteoPorModulo(2) ?? 0);
        $ocup = (int) ($this->conteoPorModulo(3) ?? 0);
        $mine = (int) ($this->conteoPorModulo(4) ?? 0);

        $total = $capt + $vert + $ocup + $mine;

        $this->oPostData->idLista = 4;
        $municipios = $this->subLista();
        $this->oPostData->idLista = 8;
        $UHN1 = $this->subLista();

        $numMunicipios = is_countable($municipios) ? count($municipios) : 0;
        $numUHN1 = is_countable($UHN1) ? count($UHN1) : 0;

        return [
            'ENCUESTAS'     => $total,
            'USUARIOS'      => $total,
            'USOS'          => 10,
            'UNHI'          => $numUHN1,
            'MUNICIPIOS'    => $numMunicipios,
            'CAPTACIONES'   => $capt,
            'VERTIMIENTOS'  => $vert,
            'OCUPACIONES'   => $ocup,
            'MINERIA'       => $mine,
            'TOTAL'         => $total
        ];
    }

    public function conteoCampoUnico($tabla, $campo) {
        $this->sSql = "
            SELECT COUNT(DISTINCT " . $campo . ") AS 'cantidad'
            FROM dbo." . $tabla . "
            WHERE " . $campo . " IS NOT NULL
        ";       
        
        $this->execQuery();
        $this->aExecute = [];
        $iCantidad = 0;   

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $iCantidad = (int) $r['cantidad'];
        }

        return $iCantidad;
    }

    public function sesionReporte1() 
    {
        $iConteoUsos = 0;
        $this->conteoUsosDistinto();

        foreach($this->aCamposUsosGrupo as $oCampoUso) {
            if ($oCampoUso->cantidad > 0) {
                $iConteoUsos++;
            }
        }

        return [
            'predios' => $this->puntosMapaPredio(),
            'total' => $this->catalogoTotalEncuestas(),
            'usuarios' => $this->conteoModulo(1),
            'usos' => $iConteoUsos,
            'uh' => $this->conteoCampoUnico('fuente_captacion', 'id_nombre_und_geografica_nivel1'),
            'municipios' => $this->conteoCampoUnico('predio', 'id_municipio'),
        ];
    }

    public function sesionReporte2() 
    {
        $this->oPostData->idLista = 8;
        $aUndHNivel1 = $this->subLista();

        $this->oPostData->idLista = 10;
        $aUndHNivel2 = $this->subLista();

        $this->oPostData->idLista = 3;
        $aDepartamento = $this->subLista();

        $this->oPostData->idLista = 4;
        $aMunicipio = $this->subLista();

        $resumen = $this->resumenModulos();

        #if (!isset($_SESSION['CONTEO_ENCUESTAS'])) {
        #    $this->conteoEncuestasSesion();
        #}

        return [
            'unidad_hidrografica_nivel_1' => $aUndHNivel1,
            'unidad_hidrografica_nivel_2' => $aUndHNivel2,
            'departamento' => $aDepartamento,
            'municipio' => $aMunicipio,
            'captaciones' => $resumen['CAPTACIONES'],
            'vertimiento' => $resumen['VERTIMIENTOS'],
            'ocupacion' => $resumen['OCUPACIONES'],
            'minera' => $resumen['MINERIA']
        ];
    }

    private function actualizaMunicipioUH()
    {
        $aInfoACtualizar = [
            ['2120-01', 'Río Bajo Bogotá', 'Viota'],
            ['2120-01', 'Río Bajo Bogotá', 'Agua de Dios'],
            ['2120-01', 'Río Bajo Bogotá', 'Ricaurte'],
            ['2120-01', 'Río Bajo Bogotá', 'Girardot'],
            ['2120-01', 'Río Bajo Bogotá', 'Tocaima'],
            ['2120-01', 'Río Bajo Bogotá', 'Apulo'],
            ['2120-02', 'Río Calandaima', 'Viota'],
            ['2120-02', 'Río Calandaima', 'El Colegio'],
            ['2120-02', 'Río Calandaima', 'Apulo'],
            ['2120-02', 'Río Calandaima', 'Anapoima'],
            ['2120-03', 'Sector Salto - Apulo', 'Bojacá'],
            ['2120-03', 'Sector Salto - Apulo', 'Tena'],
            ['2120-03', 'Sector Salto - Apulo', 'Soacha'],
            ['2120-03', 'Sector Salto - Apulo', 'San Antonio del Tequendama'],
            ['2120-03', 'Sector Salto - Apulo', 'La Mesa'],
            ['2120-03', 'Sector Salto - Apulo', 'El Colegio'],
            ['2120-03', 'Sector Salto - Apulo', 'Anapoima'],
            ['2120-03', 'Sector Salto - Apulo', 'Apulo'],
            ['2120-04', 'Sector Soacha - Salto', 'Bojacá'],
            ['2120-04', 'Sector Soacha - Salto', 'Mosquera'],
            ['2120-04', 'Sector Soacha - Salto', 'Soacha'],
            ['2120-04', 'Sector Soacha - Salto', 'Sibate'],
            ['2120-04', 'Sector Soacha - Salto', 'Granada'],
            ['2120-05', 'Embalse del Muña', 'Sibate'],
            ['2120-05', 'Embalse del Muña', 'Soacha'],
            ['2120-05', 'Embalse del Muña', 'Granada'],
            ['2120-06', 'Río Tunjuelo', 'Soacha'],
            ['2120-06', 'Río Tunjuelo', 'Bogotá'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Zipaquita'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Tocancipa'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Cajica'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Sopo'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Chia'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Cota'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Funza'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Bogotá, D.C.'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Mosquera'],
            ['2120-07', 'Sector Tibitoc - Soacha', 'Soacha'],
            ['2120-08', 'Río Teusacá', 'La Calera'],
            ['2120-08', 'Río Teusacá', 'Sopo'],
            ['2120-08', 'Río Teusacá', 'Guasca'],
            ['2120-08', 'Río Teusacá', 'Tocancipa'],
            ['2120-08', 'Río Teusacá', 'Bogotá'],
            ['2120-09', 'Embalse Tominé', 'Sesquile '],
            ['2120-09', 'Embalse Tominé', 'Guatavita'],
            ['2120-09', 'Embalse Tominé', 'Guasca'],
            ['2120-10', 'Embalse Sisga', 'Chocontá'],
            ['2120-10', 'Embalse Sisga', 'Sesquile'],
            ['2120-10', 'Embalse Sisga', 'Guatavita'],
            ['2120-11', 'Río Alto Bogotá', 'Chocontá'],
            ['2120-11', 'Río Alto Bogotá', 'Villapinzón'],
            ['2120-11', 'Río Alto Bogotá', 'Suesca (10%)'],
            ['2120-12', 'Sector Sisga - Tibitoc', 'Suesca'],
            ['2120-12', 'Sector Sisga - Tibitoc', 'Gachancipa'],
            ['2120-12', 'Sector Sisga - Tibitoc', 'Sesquile'],
            ['2120-12', 'Sector Sisga - Tibitoc', 'Tocancipá'],
            ['2120-12', 'Sector Sisga - Tibitoc', 'Nemocón'],
            ['2120-12', 'Sector Sisga - Tibitoc', 'Chocontá'],
            ['2120-12', 'Sector Sisga - Tibitoc', 'Zipaquirá'],
            ['2120-13', 'Río Neusa', 'Cogua'],
            ['2120-13', 'Río Neusa', 'Tausa'],
            ['2120-13', 'Río Neusa', 'Nemocón'],
            ['2120-13', 'Río Neusa', 'Zipaquirá'],
            ['2120-14', 'Río Frio', 'Zipaquira'],
            ['2120-14', 'Río Frio', 'Tabio'],
            ['2120-14', 'Río Frio', 'Cajica'],
            ['2120-14', 'Río Frio', 'Chia'],
            ['2120-14', 'Río Frio', 'Subachoque'],
            ['2120-15', 'Río Balsillas', 'Subachoque'],
            ['2120-15', 'Río Balsillas', 'El Rosal'],
            ['2120-15', 'Río Balsillas', 'Facatativa'],
            ['2120-15', 'Río Balsillas', 'Madrid'],
            ['2120-15', 'Río Balsillas', 'Funza'],
            ['2120-15', 'Río Balsillas', 'Mosquera'],
            ['2120-15', 'Río Balsillas', 'Bojaca'],
            ['2120-15', 'Río Balsillas', 'Zipacón'],
            ['2120-16', 'Río Apulo', 'Cachipay'],
            ['2120-16', 'Río Apulo', 'Zipacón'],
            ['2120-16', 'Río Apulo', 'Anolaima'],
            ['2120-16', 'Río Apulo', 'Quipile'],
            ['2120-16', 'Río Apulo', 'La Mesa'],
            ['2120-16', 'Río Apulo', 'Bojacá'],
            ['2120-16', 'Río Apulo', 'Anapoima'],
            ['2120-16', 'Río Apulo', 'Apulo'],
            ['2120-17', 'Río Soacha', 'Soacha'],
            ['2120-17', 'Río Soacha', 'Bogotá'],
            ['2120-18', 'Río Negro', 'Zipaquira'],
            ['2120-19', 'Río Chicú', 'Tenjo'],
            ['2120-19', 'Río Chicú', 'Tabio'],
            ['2120-19', 'Río Chicú', 'Cota'],
        ];

        $this->sSql = "SELECT * FROM dbo.param_sublista WHERE id_lista  = 4 AND unidad_geografica IS NULL";
        $this->aExecute = [];
        $this->execQuery();
        $aMunicipios = [];

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oInfo = (object) $r;
            $aMunicipios[$oInfo->id] = mb_strtolower($this->oSesion->sanearString($oInfo->descripcion));
        }

        $this->sSql = "SELECT * FROM dbo.param_sublista WHERE id_lista  = 8";
        $this->aExecute = [];
        $this->execQuery();
        $aUH = [];

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oInfo = (object) $r;
            $aUH[$oInfo->id] = mb_strtolower($this->oSesion->sanearString($oInfo->descripcion));
        }

        foreach ($aMunicipios as $idMunicipio => $sMunicipio) {
            $idUH = null;

            foreach ($aInfoACtualizar as $aInfoMunUH) {
                $sUH = mb_strtolower($this->oSesion->sanearString($aInfoMunUH[1]));
                $sMun = mb_strtolower($this->oSesion->sanearString($aInfoMunUH[2]));

                if ($sMunicipio === $sMun) {
                    foreach ($aUH as $idUnidadH => $sUnidadH) {
                        if ($sUnidadH === $sUH) {
                            $idUH = $idUnidadH;
                        }
                    }
                }
            }

            if (!is_null($idUH)) {
                $this->updateSingle('param_sublista', ['unidad_geografica' => $idUH], ['id' => $idMunicipio]);
            }
        }

        exit;
    }

    public function subLista() 
    {
        $aLista = [];
        $this->sSql = "SELECT id, descripcion, codigo, unidad_geografica FROM dbo.param_sublista WHERE id_lista = :idLista ORDER BY descripcion";
        $this->aExecute = [':idLista' => $this->oPostData->idLista];
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $r['unidad_geografica'] = is_numeric($r['unidad_geografica']) ? (int) $r['unidad_geografica'] : $r['unidad_geografica'];
            $r['id'] = (int) $r['id'];
            $aLista[] = $r;
        }
        
        return $aLista;
    }

    public function reporteConteoModulo() {
        $aReporte = [];

        if ($this->oPostData->idModulo === 1) {
            $this->conteoUsosDistinto();
            $aReporte = $this->aCamposUsosGrupo;
            $this->sumaDemandaHidrica();
            $aConsumo = $this->aCamposUsosDemanda;
        } else if ($this->oPostData->idModulo === 2) {
            $this->conteoActividadVertimiento();
            $aReporte = $this->aCamposActividadVertimiento;
            $aConsumo = $this->sumaDemandaVertimientos();
        } else if ($this->oPostData->idModulo === 3) {
            $this->conteoOcupacionesCauceTipoObra();
            $aReporte = $this->aCamposOcupacionesCauceTipoObra;
            $aConsumo = [];
            #$aConsumo = $this->sumaDemandaOcupacion();
        } else if ($this->oPostData->idModulo === 4) {
            $this->conteoMineria();
            $aReporte = $this->aCamposMineria;
            $aConsumo = [];
        }

        // --- Fusionar consumo dentro de detalle (match por 'bd') ---

        // 1) Indexar consumo por 'bd'
        $idxConsumo = [];
        if (!empty($aConsumo) && is_array($aConsumo)) {
            foreach ($aConsumo as $it) {
                $nombre   = is_array($it) ? ($it['nombre'] ?? null) : ($it->nombre ?? null);
                $cant = is_array($it) ? ($it['cantidad'] ?? null) : ($it->cantidad ?? null);
                if ($nombre !== null) {
                    $idxConsumo[$nombre] = [
                        'consumo'   => (float)$cant,
                    ];
                }
            }
        }

        // 2) Inyectar en cada fila de detalle
        foreach ($aReporte as &$fila) {
            $isArr    = is_array($fila);
            $nombre       = $isArr ? ($fila['nombre'] ?? null) : ($fila->nombre ?? null);
            $cantidad = (float)($isArr ? ($fila['cantidad'] ?? 0) : ($fila->cantidad ?? 0));

            if ($nombre !== null && isset($idxConsumo[$nombre])) {
                $consumo   = $idxConsumo[$nombre]['consumo'];
            } else {
                // sin match: consumo = cantidad de detalle, elementos vacío
                $consumo   = $cantidad;
            }

            if ($isArr) {
                $fila['consumo']   = $consumo;
            } else {
                $fila->consumo   = $consumo;
            }
        }
        unset($fila);

        $resumen = $this->resumenModulos();

        return [
            'resumen' => $resumen,
            'detalle' => $aReporte
        ];
    }

    public function sesionReporte4() {
        $aEncuestasSumaDemanda = $this->sumaDemandaHidrica();
        $aIdEncuestas = array_keys($aEncuestasSumaDemanda);

        $this->sSql = "
            SELECT id_encuesta, latitud, longitud
            FROM dbo.predio
            WHERE TRY_CAST(latitud AS FLOAT) >= 3 AND TRY_CAST(latitud AS FLOAT) <= 6 AND TRY_CAST(longitud AS FLOAT) >= -75 AND TRY_CAST(longitud AS FLOAT) <= -72
        ";

        $this->aExecute = [];
        $this->execQuery();
        $aPuntos = [];
        $min = 0;
        $max = 0; 

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $idEncuesta = (int) $r['id_encuesta'];
            $bContinuar = count($aIdEncuestas) > 0 ? in_array($idEncuesta, $aIdEncuestas) : false;

            if ($bContinuar) {   
                $fDemanda = $aEncuestasSumaDemanda[$idEncuesta];
                $min = $min === 0 || $fDemanda < $min ? $fDemanda : $min;
                $max = $max === 0 || $fDemanda > $max ? $fDemanda : $max;
                
                $aPuntos[] = [
                    'demanda' => $aEncuestasSumaDemanda[$idEncuesta],
                    'latitud' => (float) $r['latitud'],
                    'longitud' => (float) $r['longitud']
                ];
            }
        }

        return ['puntos' => $aPuntos, 'min' => $min, 'max' => $max, 'demanda' => $this->aCamposUsosDemanda];
    }

    public function grandes_usuarios() {

        $idModulo    = isset($this->oPostData->idModulo) ? (int)$this->oPostData->idModulo : 1;
        $UHN1        = isset($this->oPostData->UHN1) ? (int)$this->oPostData->UHN1 : null;
        $UHN2        = isset($this->oPostData->UHN2) ? (int)$this->oPostData->UHN2 : null;
        $idMunicipio = isset($this->oPostData->idMunicipio) ? (int)$this->oPostData->idMunicipio : null;

        switch ($idModulo) {
            case 1:
                $this->sSql = "
                    SELECT TOP 10 
                    ue.nombre_usuario,
                    SUM(TRY_CAST(urh.total AS FLOAT)) AS litros_totales
                    FROM KOBO.dbo.uso_recurso_hidrico urh
                    JOIN KOBO.dbo.usuario_encuesta ue ON urh.id_encuesta = ue.id_encuesta
                    INNER JOIN KOBO.dbo.encuesta e ON e.id = urh.id_encuesta
                    LEFT JOIN KOBO.dbo.fuente_captacion fc ON fc.id_encuesta = urh.id_encuesta
                    WHERE nombre_usuario IS NOT NULL
                    AND e.modulos = '1'
                    AND (? IS NULL OR fc.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR fc.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR fc.id_municipio = ?)
                    GROUP BY ue.nombre_usuario
                    ORDER BY litros_totales DESC";
            break;

            case 2:
                $this->sSql = "
                    SELECT TOP 10 
                    ue.nombre_usuario,
                    SUM(TRY_CAST(urh.total AS FLOAT)) AS litros_totales
                    FROM KOBO.dbo.uso_recurso_hidrico urh
                    JOIN KOBO.dbo.usuario_encuesta ue ON urh.id_encuesta = ue.id_encuesta
                    INNER JOIN KOBO.dbo.encuesta e ON e.id = urh.id_encuesta
                    LEFT JOIN KOBO.dbo.fuente_receptora_vertimiento frv ON frv.id_encuesta = urh.id_encuesta
                    WHERE nombre_usuario IS NOT NULL
                    AND e.modulos = '2'
                    AND (? IS NULL OR frv.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR frv.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR frv.id_municipio = ?)
                    GROUP BY ue.nombre_usuario
                    ORDER BY litros_totales DESC";
            break;

            case 3:
                $this->sSql = "
                    SELECT TOP 10 
                    ue.nombre_usuario,
                    SUM(TRY_CAST(urh.total AS FLOAT)) AS litros_totales
                    FROM KOBO.dbo.uso_recurso_hidrico urh
                    JOIN KOBO.dbo.usuario_encuesta ue ON urh.id_encuesta = ue.id_encuesta
                    INNER JOIN KOBO.dbo.encuesta e ON e.id = urh.id_encuesta
                    LEFT JOIN KOBO.dbo.informacion_fuente_ocupacion_cauce ifoc ON ifoc.id_encuesta = urh.id_encuesta
                    WHERE nombre_usuario IS NOT NULL
                    AND e.modulos = '3'
                    AND (? IS NULL OR ifoc.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR ifoc.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR ifoc.id_municipio = ?)
                    GROUP BY ue.nombre_usuario
                    ORDER BY litros_totales DESC";
            break;

            case 4:
                $this->sSql = "
                    SELECT TOP 10 
                    ue.nombre_usuario,
                    SUM(TRY_CAST(urh.total AS FLOAT)) AS litros_totales
                    FROM KOBO.dbo.uso_recurso_hidrico urh
                    JOIN KOBO.dbo.usuario_encuesta ue ON urh.id_encuesta = ue.id_encuesta
                    INNER JOIN KOBO.dbo.encuesta e ON e.id = urh.id_encuesta
                    LEFT JOIN KOBO.dbo.informacion_actividad_minera_cuerpos_agua iamca ON iamca.id_encuesta = urh.id_encuesta
                    WHERE nombre_usuario IS NOT NULL
                    AND e.modulos = '4'
                    AND (? IS NULL OR iamca.id_nombre_und_geografica_nivel1 = ?)
                    AND (? IS NULL OR iamca.id_nombre_und_geografica_nivel2 = ?)
                    AND (? IS NULL OR iamca.id_municipio = ?)
                    GROUP BY ue.nombre_usuario
                    ORDER BY litros_totales DESC";
            break;
        }

        $this->aExecute = [
            $UHN1, $UHN1,
            $UHN2, $UHN2,
            $idMunicipio, $idMunicipio
        ];
        $this->execQuery();
        return $this->stm->fetchAll(PDO::FETCH_ASSOC);
    }
/*
    public function exportarMapa() : array
    {
        // 0) Entradas (POST preferido para AJAX; GET como respaldo)
        $format  = strtoupper($this->oPostData->format ?? ($_POST['format'] ?? $_GET['format'] ?? 'KML')); // SHP|KML|KMZ

        // Compatibilidad: si el router no pobló oPostData, leer de POST
        if (!isset($this->oPostData->filtros)) {
            $filtrosPost = $_POST['filtros'] ?? null;
            if ($filtrosPost) {
                $this->oPostData->filtros = is_string($filtrosPost) ? json_decode($filtrosPost, true) : $filtrosPost;
            }
        }
        if (!isset($this->oPostData->modulos)) {
            $modulosPost = $_POST['modulos'] ?? null;
            if ($modulosPost) {
                $this->oPostData->modulos = is_string($modulosPost) ? json_decode($modulosPost, true) : $modulosPost;
            }
        }

        // 1) Datos (usa tus filtros/switches ya presentes en oPostData)
        $puntos = $this->puntosMapa();

        // 2) GeoJSON
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => []
        ];
        foreach ($puntos as $grupo => $arr) {
            if (!is_array($arr)) continue;
            foreach ($arr as $p) {
                if (!isset($p['longitud']) || !isset($p['latitud'])) continue;
                $lon = (float)$p['longitud'];
                $lat = (float)$p['latitud'];
                if (!is_finite($lon) || !is_finite($lat)) continue;

                $geojson['features'][] = [
                    'type' => 'Feature',
                    'properties' => $p,
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [ $lon, $lat ]
                    ],
                ];
            }
        }

        if (empty($geojson['features'])) {
            throw new RuntimeException('No hay datos para exportar con los filtros seleccionados.');
        }

        $base     = 'mapa_geodim_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $tmpDir   = PATH_TEMP;
        $gjFile   = $base . '.geojson';
        $gjPath   = $tmpDir . $gjFile;

        $created  = []; // para limpiar en caso de error

        try {
            // 3) Guardar GeoJSON temporal
            if (!file_put_contents($gjPath, json_encode($geojson, JSON_UNESCAPED_UNICODE))) {
                throw new RuntimeException('No se pudo crear el GeoJSON temporal.');
            }
            $created[] = $gjPath;

            // 4) Salidas por formato (dejar archivo en /temp y devolver URL)
            if ($format === 'KML') {
                $outFile = $base . '.kml';
                $outPath = $tmpDir . $outFile;
                $this->geojsonToKml($gjPath, $outPath);
                if (!is_file($outPath)) throw new RuntimeException('No se generó el archivo KML.');
                return ['url' => BASE_URL . 'temp/' . $outFile];
            }

            if ($format === 'KMZ') {
                $kmlPath = $tmpDir . $base . '.kml';
                $this->geojsonToKml($gjPath, $kmlPath);
                if (!is_file($kmlPath)) throw new RuntimeException('No se generó el KML base para el KMZ.');

                $kmzFile = $base . '.kmz';
                $kmzPath = $tmpDir . $kmzFile;

                $zip = new ZipArchive();
                if ($zip->open($kmzPath, ZipArchive::CREATE) !== true) {
                    throw new RuntimeException('No se pudo crear el KMZ.');
                }
                $zip->addFile($kmlPath, 'doc.kml');
                $zip->close();

                if (!is_file($kmzPath)) throw new RuntimeException('No se generó el archivo KMZ.');
                return ['url' => BASE_URL . 'temp/' . $kmzFile];
            }

            if ($format === 'SHP') {
                // Requiere GDAL/ogr2ogr instalado en el servidor
                $outDir = $tmpDir . $base;
                if (!@mkdir($outDir) && !is_dir($outDir)) {
                    throw new RuntimeException('No se pudo crear el directorio temporal para SHP.');
                }

                // Si necesitas reproyección, añade:  -t_srs EPSG:4326
                $cmd = "ogr2ogr -f 'ESRI Shapefile' " . escapeshellarg($outDir) . ' ' . escapeshellarg($gjPath) . " -nln puntos -overwrite";
                exec($cmd, $o, $ret);
                if ($ret !== 0) {
                    throw new RuntimeException('Error al generar SHP. Verifica que GDAL/ogr2ogr esté instalado.');
                }

                $zipFile = $base . '.zip';
                $zipPath = $tmpDir . $zipFile;
                $zip = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                    throw new RuntimeException('No se pudo crear el ZIP del SHP.');
                }
                foreach (glob($outDir . '/*') as $f) {
                    $zip->addFile($f, basename($f));
                }
                $zip->close();

                if (!is_file($zipPath)) throw new RuntimeException('No se generó el archivo ZIP del SHP.');
                return ['url' => BASE_URL . 'temp/' . $zipFile];
            }

            throw new InvalidArgumentException('Formato no soportado: ' . $format);
        } catch (Throwable $e) {
            // Limpieza si hubo error
            foreach ($created as $f) {
                if (is_file($f)) @unlink($f);
                if (is_dir($f))  @rmdir($f);
            }
            throw new RuntimeException('Error al exportar mapa: ' . $e->getMessage(), 0, $e);
        } finally {
            if (is_file($gjPath)) { @unlink($gjPath); }
        }
    }

    private function geojsonToKml(string $geojsonPath, string $kmlOut): string {
        $gj = json_decode(file_get_contents($geojsonPath), true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $kml = $doc->createElement('kml');
        $kml->setAttribute('xmlns', 'http://www.opengis.net/kml/2.2');
        $doc->appendChild($kml);

        $d = $doc->createElement('Document');
        $kml->appendChild($d);

        foreach ($gj['features'] as $f) {
            $pm = $doc->createElement('Placemark');

            // name (si existe)
            $nameVal = $f['properties']['codigo_encuesta'] ?? null;
            if ($nameVal) {
                $name = $doc->createElement('name', htmlspecialchars((string)$nameVal));
                $pm->appendChild($name);
            }

            // description (JSON de propiedades)
            $desc = $doc->createElement('description');
            $desc->appendChild($doc->createCDATASection(json_encode($f['properties'], JSON_UNESCAPED_UNICODE)));
            $pm->appendChild($desc);

            // geometría (punto)
            if (($f['geometry']['type'] ?? '') === 'Point') {
                $coords = $f['geometry']['coordinates'];
                $pt = $doc->createElement('Point');
                $coord = $doc->createElement('coordinates', $coords[0].','.$coords[1].',0');
                $pt->appendChild($coord);
                $pm->appendChild($pt);
            }

            $d->appendChild($pm);
        }

        $doc->save($kmlOut);
        return $kmlOut;
    }

    private function downloadFile(string $path, string $filename, string $mime) {
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.filesize($path));
        readfile($path);
        // opcional: unlink($path) si quieres limpiar al vuelo
    }
*/
    private function estructuraEncuesta() 
    {
        $this->aEstructuraEncuesta = array(
            (object) array(
                'titulo' => 'Datos básicos de la encuesta',
                'tabla' => 'encuesta',
                'id_validar' => 'id',
                'seleccionado' => true,
                'id_modulo' => 0,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => true, 'tipo' => 'texto', 'titulo' => '0. Código encuesta', 'bd' => 'codigo_encuesta', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '1. Código asociado a la cuenca hidrográfica nivel I', 'bd' => 'codigo_cuenca_hidrografica', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => true, 'tipo' => 'fecha_hora', 'titulo' => '2. Fecha diligenciamiento', 'bd' => 'fecha_diligenciamiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '255. Observaciones generales', 'bd' => 'observaciones_generales', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '256. Nombre encuestador', 'bd' => 'nombre_encuestador', 'valor' => null, 'seleccionado' => true, 'id_lista' => 33))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '256.1. Cédula encuestador', 'bd' => 'cedula_encuestador', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '258. Ingeniero de campo', 'bd' => 'ingeniero_campo', 'valor' => null, 'seleccionado' => true, 'id_lista' => 34))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '257. Nombre encuestado', 'bd' => 'nombre_encuestado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '259. Coordinador de proyecto', 'bd' => 'coordinador_proyecto', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha_hora', 'titulo' => '260. Fecha de procesamiento', 'bd' => 'fecha_procesamiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '261. Observaciones adicionales', 'bd' => 'observaciones_adicionales', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => 'Foto Adicional 1', 'bd' => 'foto_adicional1', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => 'Foto Adicional 2', 'bd' => 'foto_adicional2', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => 'Firma Autoridad Ambiental Competente', 'bd' => 'firma_autoridad_ambiental_competente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => 'Estado de la validación', 'bd' => 'id_estado_validacion', 'valor' => null, 'seleccionado' => true, 'id_lista' => 32))),
                )
            ),
    
            (object) array(
                'titulo' => 'Identificación del usuario',
                'tabla' => 'usuario_encuesta',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 0,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '4. Tipo de usuario', 'bd' => 'id_tipo_usuario', 'valor' => null, 'seleccionado' => true, 'id_lista' => 1))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'disabled_edit', 'titulo' => '5. Nombre del usuario', 'bd' => 'nombre_usuario', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '6. Tipo de identificación', 'bd' => 'id_tipo_identificacion', 'valor' => null, 'seleccionado' => true, 'id_lista' => 2))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 1, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '7. Número de documento', 'bd' => 'documento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '8. Representante legal o administrador', 'bd' => 'representante_legal', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '9. Tipo documento identificación representante legal', 'bd' => 'id_tipo_identificacion_rl', 'valor' => null, 'seleccionado' => true, 'id_lista' => 2))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 1, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '10. Número de documento', 'bd' => 'documento_rl', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '11a. Dirección domicilio usuario, Vereda', 'bd' => 'direccion_domicilio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '11b. Dirección domicilio usuario, Referencia', 'bd' => 'direccion_domicilio_referencia', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '11c. Dirección domicilio usuario, Nombre del predio', 'bd' => 'direccion_domicilio_predio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 1, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '12. Teléfono(s) domicilio', 'bd' => 'telefono_domicilio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '13. Dirección correspondencia usuario', 'bd' => 'direccion_correspondencia', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 1, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '14. Teléfono(s) correspondencia', 'bd' => 'telefono_correspondencia', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Identificación del predio',
                'tabla' => 'predio',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 0,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '15. Nombre Predio', 'bd' => 'nombre', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 2, 'minlength' => 0, 'maxlength' => 6,  'titulo' => '16. Área (Has)', 'bd' => 'area', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '17. Dirección del Predio', 'bd' => 'direccion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '18. Departamento', 'bd' => 'id_departamento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 3))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_departamento', 'valor_dependiente' => 7, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'disabled_edit', 'titulo' => '19. Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '20. Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '21. Municipio', 'bd' => 'id_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 4))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '22. Codigo DANE municipio', 'bd' => 'id_dane_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 5))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '23. Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 1, 'minlength' => 0, 'maxlength' => 25, 'titulo' => '24. Cédula Catastral', 'bd' => 'cedula_catastral', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '25. Tenencia tipo', 'bd' => 'id_tenencia_tipo', 'valor' => null, 'seleccionado' => true, 'id_lista' => 6))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_predio',
                        'campos' => array(
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '26. Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '26. Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '26. Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '26. Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'predio_coordenadas_1',
                        'filas' => array(
                            array(
                                (object) array('fila' => 'predio_coordenadas_26', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '26a. Este- x(m)', 'bd' => 'este', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'predio_coordenadas_26', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '26a. Norte-y(m)', 'bd' => 'norte', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'predio_coordenadas_26', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 4, 'titulo' => '26a. Altura(m.s.n.m)', 'bd' => 'altura', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => 'Imagen del punto referenciado (GPS)', 'bd' => 'imagen_punto_referenciado_gps', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => 'Imagen del punto referenciado (PREDIO - IMAGEN1)', 'bd' => 'imagen_punto_referenciado_predio1', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => 'Imagen del punto referenciado (PREDIO - IMAGEN2)', 'bd' => 'imagen_punto_referenciado_predio2', 'valor' => null, 'seleccionado' => false)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la fuente de captación',
                'tabla' => 'fuente_captacion',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 1,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '27. Fuente superficial', 'bd' => 'id_fuente_superficial', 'valor' => null, 'seleccionado' => true, 'id_lista' => 7))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_fuente_superficial', 'valor_dependiente' => 122, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otra_fuente_superficial', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '28. Nombre de la fuente', 'bd' => 'nombre_fuente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '29. Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '30. Departamento', 'bd' => 'id_departamento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 3))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_departamento', 'valor_dependiente' => 7, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'disabled_edit', 'titulo' => '31. Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '32. Municipio', 'bd' => 'id_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 4))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '33. Codigo DANE municipio', 'bd' => 'id_dane_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 5))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '34. Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '35. Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '36. Nombre unidad hidrográfica nivel I', 'bd' => 'id_nombre_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true, 'id_lista' => 8))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '37. Código unidad hidrográfica nivel I', 'bd' => 'id_codigo_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true, 'id_lista' => 9))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '38. Nombre unidad hidrográfica nivel II', 'bd' => 'id_nombre_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true, 'id_lista' => 10))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '39. Código unidad hidrográfica nivel II', 'bd' => 'id_codigo_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true, 'id_lista' => 11)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la captación en la fuente',
                'tabla' => 'captacion_en_fuente',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 1,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '40. Tipo de captación', 'bd' => 'id_tipo_captacion', 'valor' => null, 'seleccionado' => true, 'id_lista' => 12))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_tipo_captacion', 'valor_dependiente' => 428, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_tipo_captacion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '41. Estado', 'bd' => 'id_estado', 'valor' => null, 'seleccionado' => true, 'id_lista' => 13))),
                    (object) array(
                        'tipo' => 'tabla',
                        'tabla' => 'captacion_en_fuente_opciones',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'filas' => array(
                            array(
                                (object) array('fila' => 'captacion_en_fuente_42', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '42. Diámetro (pulgada)', 'bd' => 'diametro', 'valor' => null, 'seleccionado' => true, 'id_lista' => 38),
                                (object) array('fila' => 'captacion_en_fuente_42', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '42. Observaciones diámetro (pulgada)', 'bd' => 'diametro_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'captacion_en_fuente_43', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 2, 'titulo' => '43. Ancho (m)', 'bd' => 'ancho', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'captacion_en_fuente_43', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '43. Observaciones ancho (m)', 'bd' => 'ancho_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'captacion_en_fuente_44', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 2, 'titulo' => '44. Alto (m)', 'bd' => 'alto', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'captacion_en_fuente_44', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '44. Observaciones alto (m)', 'bd' => 'alto_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'captacion_en_fuente_45', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 2, 'titulo' => '45. Profundidad (m)', 'bd' => 'profundidad', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'captacion_en_fuente_45', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '45. Observaciones profundidad (m)', 'bd' => 'profundidad_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'captacion_en_fuente_46', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '46. Tiempo captación (Hr/día)', 'bd' => 'tiempo_captacion', 'valor' => null, 'seleccionado' => true, 'id_lista' => 39),
                                (object) array('fila' => 'captacion_en_fuente_46', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '46. Observaciones tiempo captación (Hr/día)', 'bd' => 'tiempo_captacion_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'captacion_en_fuente_47', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '47. Frecuencia (día/mes)', 'bd' => 'frecuencia', 'valor' => null, 'seleccionado' => true, 'id_lista' => 40),
                                (object) array('fila' => 'captacion_en_fuente_47', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '47. Observaciones frecuencia (día/mes)', 'bd' => 'frecuencia_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'captacion_en_fuente_48', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 2, 'titulo' => '48. Capacidad bomba (HP)', 'bd' => 'capacidad_bomba', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'captacion_en_fuente_48', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '48. Observaciones capacidad bomba (HP)', 'bd' => 'capacidad_bomba_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'captacion_en_fuente_49', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '49. Capacidad Instalada (l/s)', 'bd' => 'capacidad_instalada', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'captacion_en_fuente_49', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '49. Observaciones capacidad Instalada (l/s)', 'bd' => 'capacidad_instalada_observaciones', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_multiple','titulo' => 'Características sistema de abastecimiento', 'bd' => 'caracteristicas_abastecimiento', 'valor' => null, 'seleccionado' => true, 'elementos' => 14))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '56. Observaciones de la captación', 'bd' => 'observaciones_captacion', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_captacion_en_fuente',
                        'campos' => array(
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '57. Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '57. Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '57. Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '57. Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'captacion_en_fuente_coordenadas_57',
                        'filas' => array(
                            array(
                                (object) array('fila' => 'captacion_en_fuente_57', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '57a. Este- x(m)', 'bd' => 'este', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'captacion_en_fuente_57', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '57a. Norte-y(m)', 'bd' => 'norte', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'captacion_en_fuente_57', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 4, 'titulo' => '57a. Altura(m.s.n.m)', 'bd' => 'altura', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '58. Imagen del punto referenciado (GPS)', 'bd' => 'imagen_punto_referenciado_gps', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '58. Imagen del punto referenciado(CAPTACION - IMAGEN1)', 'bd' => 'imagen_punto_referenciado_captacion1', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '58. Imagen del punto referenciado (CAPTACION - IMAGEN2)', 'bd' => 'imagen_punto_referenciado_captacion2', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '59. Esquema de localización y/o punto de captación', 'bd' => 'esquema_localizacion', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información del uso y/o aprovechamiento del recurso hídrico',
                'tabla' => 'uso_recurso_hidrico',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 1,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '60. Uso doméstico', 'bd' => 'id_uso_domestico', 'valor' => null, 'seleccionado' => true, 'id_lista' => 15))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 2, 'titulo' => '61. Número de personas permanentes', 'bd' => 'personas_permanentes', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 2, 'titulo' => '62. Número de personas transitorias', 'bd' => 'personas_transitorias', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '63. Días/mes', 'bd' => 'dias_mes', 'valor' => null, 'seleccionado' => true, 'id_lista' => 40))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'disabled_edit', 'titulo' => '64. Consumo (l/s)', 'bd' => 'consumo', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '65. Menores de 5 años', 'bd' => 'menores_cinco_anios', 'valor' => null, 'seleccionado' => true, 'id_lista' => 41))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '66. Mayores de 60 años', 'bd' => 'mayores_sesenta_anios', 'valor' => null, 'seleccionado' => true, 'id_lista' => 41))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_multiple', 'titulo' => '67. Tipo de construcción', 'bd' => 'tipo_construccion', 'valor' => null, 'seleccionado' => true, 'elementos' => 16))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'tipo_construccion', 'valor_dependiente' => 445, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_tipo_construccion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '68. Uso pecuario', 'bd' => 'id_uso_pecuario', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_1',
                        'dependiente' => 'id_uso_pecuario', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_1_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => 'Uso pecuario I Tipo', 'bd' => 'uso_pecuario_1_tipo', 'valor' => null, 'seleccionado' => true, 'columna' => 1, 'id_lista' => 42),
                                (object) array('fila' => 'uso_recurso_hidrico_1_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso pecuario I Número', 'bd' => 'uso_pecuario_1_numero', 'valor' => null, 'seleccionado' => true, 'columna' => 2),
                                (object) array('fila' => 'uso_recurso_hidrico_1_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso pecuario I Consumo (l/s)', 'bd' => 'uso_pecuario_1_consumo', 'valor' => null, 'seleccionado' => true, 'columna' => 3)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_1_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => 'Uso pecuario II Tipo', 'bd' => 'uso_pecuario_2_tipo', 'valor' => null, 'seleccionado' => true, 'columna' => 1, 'id_lista' => 42),
                                (object) array('fila' => 'uso_recurso_hidrico_1_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso pecuario II Número', 'bd' => 'uso_pecuario_2_numero', 'valor' => null, 'seleccionado' => true, 'columna' => 2),
                                (object) array('fila' => 'uso_recurso_hidrico_1_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso pecuario II Consumo (l/s)', 'bd' => 'uso_pecuario_2_consumo', 'valor' => null, 'seleccionado' => true, 'columna' => 3)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_1_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => 'Uso pecuario III Tipo', 'bd' => 'uso_pecuario_3_tipo', 'valor' => null, 'seleccionado' => true, 'columna' => 1, 'id_lista' => 42),
                                (object) array('fila' => 'uso_recurso_hidrico_1_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso pecuario III Número', 'bd' => 'uso_pecuario_3_numero', 'valor' => null, 'seleccionado' => true, 'columna' => 2),
                                (object) array('fila' => 'uso_recurso_hidrico_1_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso pecuario III Consumo (l/s)', 'bd' => 'uso_pecuario_3_consumo', 'valor' => null, 'seleccionado' => true, 'columna' => 3)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_1_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => 'Uso pecuario IV Tipo', 'bd' => 'uso_pecuario_4_tipo', 'valor' => null, 'seleccionado' => true, 'columna' => 1, 'id_lista' => 42),
                                (object) array('fila' => 'uso_recurso_hidrico_1_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso pecuario IV Número', 'bd' => 'uso_pecuario_4_numero', 'valor' => null, 'seleccionado' => true, 'columna' => 2),
                                (object) array('fila' => 'uso_recurso_hidrico_1_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso pecuario IV Consumo (l/s)', 'bd' => 'uso_pecuario_4_consumo', 'valor' => null, 'seleccionado' => true, 'columna' => 3)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '81. Uso acuícola', 'bd' => 'id_uso_acuicola', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_2',
                        'dependiente' => 'id_uso_acuicola', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_2_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => 'Uso acuícola I Tipo', 'bd' => 'uso_acuicola_1_tipo', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_2_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso acuícola I Número', 'bd' => 'uso_acuicola_1_numero', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_2_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso acuícola I Consumo (l/s)', 'bd' => 'uso_acuicola_1_consumo', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_2_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => 'Uso acuícola II Tipo', 'bd' => 'uso_acuicola_2_tipo', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_2_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso acuícola II Número', 'bd' => 'uso_acuicola_2_numero', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_2_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso acuícola II Consumo (l/s)', 'bd' => 'uso_acuicola_2_consumo', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_2_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => 'Uso acuícola III Tipo', 'bd' => 'uso_acuicola_3_tipo', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_2_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso acuícola III Número', 'bd' => 'uso_acuicola_3_numero', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_2_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso acuícola III Consumo (l/s)', 'bd' => 'uso_acuicola_3_consumo', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_2_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => 'Uso acuícola IV Tipo', 'bd' => 'uso_acuicola_4_tipo', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_2_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso acuícola IV Número', 'bd' => 'uso_acuicola_4_numero', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_2_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso acuícola IV Consumo (l/s)', 'bd' => 'uso_acuicola_4_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '94. Uso agrícola y silvícola', 'bd' => 'id_uso_agricola_silvicola', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_3',
                        'dependiente' => 'id_uso_agricola_silvicola', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_3_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => 'Uso agrícola y silvícola I Tipo', 'bd' => 'uso_agricola_silvicola_1_cultivo', 'valor' => null, 'seleccionado' => true, 'id_lista' => 35),
                                (object) array('fila' => 'uso_recurso_hidrico_3_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso agrícola y silvícola I Número', 'bd' => 'uso_agricola_silvicola_1_area', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_3_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso agrícola y silvícola I Consumo (l/s)', 'bd' => 'uso_agricola_silvicola_1_consumo', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_3_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => 'Uso agrícola y silvícola II Tipo', 'bd' => 'uso_agricola_silvicola_2_cultivo', 'valor' => null, 'seleccionado' => true, 'id_lista' => 35),
                                (object) array('fila' => 'uso_recurso_hidrico_3_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso agrícola y silvícola II Número', 'bd' => 'uso_agricola_silvicola_2_area', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_3_2', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso agrícola y silvícola II Consumo (l/s)', 'bd' => 'uso_agricola_silvicola_2_consumo', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_3_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => 'Uso agrícola y silvícola III Tipo', 'bd' => 'uso_agricola_silvicola_3_cultivo', 'valor' => null, 'seleccionado' => true, 'id_lista' => 35),
                                (object) array('fila' => 'uso_recurso_hidrico_3_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso agrícola y silvícola III Número', 'bd' => 'uso_agricola_silvicola_3_area', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_3_3', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso agrícola y silvícola III Consumo (l/s)', 'bd' => 'uso_agricola_silvicola_3_consumo', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_3_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => 'Uso agrícola y silvícola IV Tipo', 'bd' => 'uso_agricola_silvicola_4_cultivo', 'valor' => null, 'seleccionado' => true, 'id_lista' => 35),
                                (object) array('fila' => 'uso_recurso_hidrico_3_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso agrícola y silvícola IV Número', 'bd' => 'uso_agricola_silvicola_4_area', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_3_4', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => 'Uso agrícola y silvícola IV Consumo (l/s)', 'bd' => 'uso_agricola_silvicola_4_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_multiple', 'titulo' => '116. Tipo de riego predominante', 'bd' => 'riego_predominante', 'valor' => null, 'seleccionado' => true, 'elementos' => 18))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'riego_predominante', 'valor_dependiente' => 453, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_riego_predominante', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '117. Uso industrial', 'bd' => 'id_uso_industrial', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_4',
                        'dependiente' => 'id_uso_industrial', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_4_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '118. Uso industrial características', 'bd' => 'uso_industrial_caracteristicas', 'valor' => null, 'seleccionado' => true, 'id_lista' => 36),
                                (object) array('fila' => 'uso_recurso_hidrico_4_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '119. Uso industrial consumo (l/s)', 'bd' => 'uso_industrial_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '120. Uso minero', 'bd' => 'id_uso_minero', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_5',
                        'dependiente' => 'id_uso_minero', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_5_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '121. Uso minero características', 'bd' => 'uso_minero_caracteristicas', 'valor' => null, 'seleccionado' => true, 'id_lista' => 37),
                                (object) array('fila' => 'uso_recurso_hidrico_5_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '122. Uso minero consumo (l/s)', 'bd' => 'uso_minero_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '123. Uso generación hidroeléctrica', 'bd' => 'id_uso_generacion_hidroelectrica', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_6',
                        'dependiente' => 'id_uso_generacion_hidroelectrica', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_6_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '124. Uso generacion hidroeléctrica características', 'bd' => 'uso_generacion_hidroelectrica_caracteristicas', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_6_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '125. Uso generacion hidroeléctrica consumo (l/s)', 'bd' => 'uso_generacion_hidroelectrica_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '126. Uso recreacional', 'bd' => 'id_uso_recreacional', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_7',
                        'dependiente' => 'id_uso_recreacional', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_7_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '127. Uso recreacional características', 'bd' => 'uso_recreacional_caracteristicas', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_7_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '128. Uso recreacional consumo (l/s)', 'bd' => 'uso_recreacional_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '129. Uso servicios', 'bd' => 'id_uso_servicios', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_8',
                        'dependiente' => 'id_uso_servicios', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array (
                                (object) array('fila' => 'uso_recurso_hidrico_8_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '130. Uso servicios características', 'bd' => 'uso_servicios_caracteristicas', 'valor' => null, 'seleccionado' => true, 'id_lista' => 43),
                                (object) array('fila' => 'uso_recurso_hidrico_8_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '131. Uso servicios consumo (l/s)', 'bd' => 'uso_servicios_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '132. Uso explotación petrolera', 'bd' => 'id_uso_explotacion_petrolera', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_9',
                        'dependiente' => 'id_uso_explotacion_petrolera', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array (
                                (object) array('fila' => 'uso_recurso_hidrico_9_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '133. Uso explotación petrolera características', 'bd' => 'uso_explotacion_petrolera_caracteristicas', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_9_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '134. Uso explotación petrolera consumo (l/s)', 'bd' => 'uso_explotacion_petrolera_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '135. Otros usos', 'bd' => 'id_otros_usos', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_10',
                        'dependiente' => 'id_otros_usos', 
                        'valor_dependiente' => 446,
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_10_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '136. Otros usos características', 'bd' => 'otros_usos_caracteristicas', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_10_1', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 10, 'titulo' => '137. Otros usos consumo (l/s)', 'bd' => 'otros_usos_consumo', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-6 col-sm-12',
                        'tabla' => 'uso_recurso_hidrico_opciones_11',
                        'filas' => array(
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_246', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '246. Doméstico', 'bd' => 'domestico', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_246', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '246. Observaciones', 'bd' => 'domestico_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_247', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '247. Pecuario', 'bd' => 'pecuario', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_247', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '247. Observaciones', 'bd' => 'pecuario_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_248', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '248. Acuícola', 'bd' => 'acuicola', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_248', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '248. Observaciones', 'bd' => 'acuicola_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_249', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '249. Agrícola', 'bd' => 'agricola', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_249', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '249. Observaciones', 'bd' => 'agricola_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_250', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '250. Industrial', 'bd' => 'industrial', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_250', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '250. Observaciones', 'bd' => 'industrial_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_251', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '251. Minería', 'bd' => 'mineria', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_251', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '251. Observaciones', 'bd' => 'mineria_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_252', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '252. Generación electrica', 'bd' => 'generacion_electrica', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_252', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '252. Observaciones', 'bd' => 'generacion_electrica_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_253', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '253. Otros', 'bd' => 'otros', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_253', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '253. Observaciones', 'bd' => 'otros_observaciones', 'valor' => null, 'seleccionado' => true)
                            ),
                            array(
                                (object) array('fila' => 'uso_recurso_hidrico_254', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '254. Total(l/s)', 'bd' => 'total', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'uso_recurso_hidrico_254', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '254. Observaciones', 'bd' => 'total_observaciones', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    )
                )
            ),

            (object) array(
                'titulo' => 'Información jurídica de la captación',
                'tabla' => 'informacion_juridica_captacion',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 1,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '138. Permiso de concesión de uso del agua', 'bd' => 'id_permiso_concesion_uso_agua', 'valor' => null, 'seleccionado' => true, 'id_lista' => 19))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '139. Número del expediente que maneja la CAR correspondiente al número con el que se le otorgó el caudal, bajo Resolución', 'bd' => 'numero_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha', 'titulo' => '140. Fecha: (año/mes/dia)', 'bd' => 'fecha_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '141. Número de resolución', 'bd' => 'numero_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha', 'titulo' => '142. Fecha: (año/mes/dia)', 'bd' => 'fecha_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '143. Vigencia (años)', 'bd' => 'vigencia_anios', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '144. Caudal Concesionado (l/s)', 'bd' => 'caudal_concesionado', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'informacion_juridica_captacion_1',
                        'filas' => array(
                            array(
                                (object) array('fila' => 'informacion_juridica_captacion_145', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '145. V1, t1', 'bd' => 'caudal_utilizado_1', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'informacion_juridica_captacion_145', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '145. V2, t2', 'bd' => 'caudal_utilizado_2', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'informacion_juridica_captacion_145', 'mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '145. V3, t3', 'bd' => 'caudal_utilizado_3', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '145a. Promedio caudal utilizado', 'bd' => 'prom_caudal_utilizado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '146. Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la fuente receptora de vertimiento',
                'tabla' => 'fuente_receptora_vertimiento',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 2,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '147. Cuerpo receptor', 'bd' => 'id_cuerpo_receptor', 'valor' => null, 'seleccionado' => true, 'id_lista' => 20))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_cuerpo_receptor', 'valor_dependiente' => 472, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_cuerpo_receptor', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '148. Nombre de la fuente receptora', 'bd' => 'fuente_receptora', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '149. Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '150. Objetivo de calidad del cuerpo receptor (Uso/destinación)', 'bd' => 'id_objetivo_calidad_receptor', 'valor' => null, 'seleccionado' => true, 'id_lista' => 21))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '151. Departamento', 'bd' => 'id_departamento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 3))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_departamento', 'valor_dependiente' => 7, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'disabled_edit', 'titulo' => '152. Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '153. Municipio', 'bd' => 'id_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 4))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '154. Código DANE', 'bd' => 'id_dane_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 5))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '155. Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '156. Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '157. Nombre unidad hidrográfica nivel I', 'bd' => 'id_nombre_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true, 'id_lista' => 8))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '158. Código unidad hidrográfica nivel I', 'bd' => 'id_codigo_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true, 'id_lista' => 9))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '159. Nombre unidad hidrográfica nivel II', 'bd' => 'id_nombre_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true, 'id_lista' => 10))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '160. Código unidad hidrográfica nivel II', 'bd' => 'id_codigo_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true, 'id_lista' => 11)))
                )
            ),

            (object) array(
                'titulo' => 'Información del vertimiento',
                'tabla' => 'informacion_vertimiento',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 2,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '161. Actividad que genera el vertimiento', 'bd' => 'id_actividad_vertimiento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 22))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_actividad_vertimiento', 'valor_dependiente' => 491, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otra_actividad_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_multiple', 'titulo' => '162. Tipo de descarga', 'bd' => 'tipo_descarga', 'valor' => null, 'seleccionado' => true, 'elementos' => 23))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '163. Caudal vertido (l/s)', 'bd' => 'caudal_vertido', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '164. Tiempo descarga (hr/día)', 'bd' => 'tiempo_descarga', 'valor' => null, 'seleccionado' => true, 'id_lista' => 39))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '165. Frecuencia (días/mes)', 'bd' => 'frecuencia', 'valor' => null, 'seleccionado' => true, 'id_lista' => 40))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '166. Disposición del Vertimiento', 'bd' => 'id_disposicion_vertimiento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 24))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_disposicion_vertimiento', 'valor_dependiente' => 502, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otra_disposicion_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '167. Estado', 'bd' => 'id_estado', 'valor' => null, 'seleccionado' => true, 'id_lista' => 13))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_informacion_vertimiento',
                        'campos' => array(
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '168. Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '168. Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '168. Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '168. Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'informacion_vertimiento_1',
                        'filas' => array(
                            array(
                                (object) array('fila' => 'informacion_vertimiento_168', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '168a. Este- x(m)', 'bd' => 'este', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'informacion_vertimiento_168', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '168a. Norte-y(m)', 'bd' => 'norte', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'informacion_vertimiento_168', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 4, 'titulo' => '168a. Altura(m.s.n.m)', 'bd' => 'altura', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '169. Imagen del punto referenciaco (GPS)', 'bd' => 'imagen_punto_referenciado_gps', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '169. Imagen del punto referenciado (VERTIMIENTO - IMAGEN1)', 'bd' => 'imagen_punto_referenciado_vertimiento1', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '169. Imagen del punto referenciado (VERTIMIENTO - IMAGEN2)', 'bd' => 'imagen_punto_referenciado_vertimiento2', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '170. Observaciones del vertimiento', 'bd' => 'observaciones_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '171. ¿Presenta algún tipo de sistema de tratamiento? - Sistemas de Tratamiento', 'bd' => 'id_presenta_sistema_tratamiento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_presenta_sistema_tratamiento', 'valor_dependiente' => 446, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '172. Sistemas de Tratamiento', 'bd' => 'id_sistema_tratamiento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 31))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_sistema_tratamiento', 'valor_dependiente' => 543, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_sistema_tratamiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '173. Esquema de localización punto de vertimiento', 'bd' => 'esquema_localizacion_punto_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => 'Foto 1 punto de vertimiento', 'bd' => 'foto1_punto_vertimiento', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => 'Foto 2 punto de vertimiento', 'bd' => 'foto2_punto_vertimiento', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '174. Si el predio cuenta con pozo séptico o campo de infiltración, ¿este se encuentra a menos de 100 mts una fuente hídrica?', 'bd' => 'id_pozo_septico_campo_infiltracion_menos_100_mts', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                )
            ),

            (object) array(
                'titulo' => 'Información jurídica del vertimiento',
                'tabla' => 'informacion_juridica_vertimiento',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 2,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '175. Permiso de vertimiento', 'bd' => 'id_permiso_vertimiento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 19))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '176. Número de expediente', 'bd' => 'numero_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha', 'titulo' => '177. Fecha: (año/mes/dia) de expediente', 'bd' => 'fecha_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '178. Número de resolución', 'bd' => 'numero_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha', 'titulo' => '179. Fecha: (año/mes/dia) de resolución', 'bd' => 'fecha_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '180. Vigencia', 'bd' => 'vigencia_anios', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '181. Caudal de Vertido Autorizado (l/s)', 'bd' => 'caudal_vertido_autorizado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '182. Norma que debe cumplir el vertimiento', 'bd' => 'norma_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '183. Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la fuente con ocupación de cauce - Obras hidráulicas',
                'tabla' => 'informacion_fuente_ocupacion_cauce',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 3,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '184. Cuerpo de agua con ocupación de cauce - Obras hidráulicas', 'bd' => 'id_cuerpo_agua', 'valor' => null, 'seleccionado' => true, 'id_lista' => 25))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_cuerpo_agua', 'valor_dependiente' => 510, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_cuerpo_agua', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '185. Nombre de la fuente', 'bd' => 'nombre_fuente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '186. Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '187. Uso de la fuente en el área de influencia', 'bd' => 'fuente_area_influcencia', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '188. Pendiente del cauce %', 'bd' => 'pendiente_cauce', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '189. Patrón de drenaje del cauce', 'bd' => 'id_tipo_cauce', 'valor' => null, 'seleccionado' => true, 'id_lista' => 26))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_tipo_cauce', 'valor_dependiente' => 744, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_tipo_cauce', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '190. Características del lecho', 'bd' => 'id_tipo_lecho', 'valor' => null, 'seleccionado' => true, 'id_lista' => 27))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_tipo_lecho', 'valor_dependiente' => 517, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_tipo_lecho', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '191. Departamento', 'bd' => 'id_departamento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 3))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_departamento', 'valor_dependiente' => 7, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'disabled_edit', 'titulo' => '192. Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '193. Municipio', 'bd' => 'id_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 4))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '194. Código DANE', 'bd' => 'id_dane_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 5))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '195. Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '196. Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '197. Nombre unidad hidrográfica nivel I', 'bd' => 'id_nombre_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true, 'id_lista' => 8))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '198. Código unidad hidrográfica nivel I', 'bd' => 'id_codigo_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true, 'id_lista' => 9))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '199. Nombre unidad hidrográfica nivel II', 'bd' => 'id_nombre_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true, 'id_lista' => 10))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '200. Código unidad hidrográfica nivel II', 'bd' => 'id_codigo_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true, 'id_lista' => 11)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la ocupación de cauce asociado a obra hidráulica',
                'tabla' => 'informacion_ocupacion_cauce_obra_hidraulica',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 3,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto_largo', 'titulo' => '201. Descripción de la obra', 'bd' => 'descripcion_obra', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '202. Tipo de obra', 'bd' => 'id_tipo_obra', 'valor' => null, 'seleccionado' => true, 'id_lista' => 28))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_tipo_obra', 'valor_dependiente' => 528, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_tipo_obra', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '203. Estado', 'bd' => 'id_estado', 'valor' => null, 'seleccionado' => true, 'id_lista' => 13))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '204. Tipo de material', 'bd' => 'tipo_material', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '205. Altura (m)', 'bd' => 'alto', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '206. Ancho (m)', 'bd' => 'ancho', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '207. Longitud (m)', 'bd' => 'largo', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '208. Profundidad (m)', 'bd' => 'profundidad', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '209. Área (m2)', 'bd' => 'area', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '210. Diámetro (m)', 'bd' => 'diametro', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_informacion_ocupacion_cauce_obra_hidraulica',
                        'campos' => array(
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '211. Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '211. Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '211. Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '211. Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'informacion_ocupacion_cauce_obra_hidraulica_1',
                        'filas' => array(
                            array(
                                (object) array('fila' => 'informacion_ocupacion_cauce_obra_hidraulica_211', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '211a. Este- x(m)', 'bd' => 'este', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'informacion_ocupacion_cauce_obra_hidraulica_211', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '211a. Norte-y(m)', 'bd' => 'norte', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'informacion_ocupacion_cauce_obra_hidraulica_211', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 4, 'titulo' => '211a. Altura(m.s.n.m)', 'bd' => 'altura', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '212. Imagen del punto referenciado (GPS)', 'bd' => 'imagen_punto_referenciado_gps', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '212. Imagen del punto referenciado (OBRA HIDRAULICA - IMAGEN1)', 'bd' => 'imagen_punto_referenciado_obra_hidraulica1', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '212. Imagen del punto referenciado (OBRA HIDRAULICA - IMAGEN2)', 'bd' => 'imagen_punto_referenciado_obra_hidraulica2', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '213. Esquema de localización de la obra', 'bd' => 'esquema_localizacion_obra', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '214. Permiso de ocupación de cauce', 'bd' => 'id_permiso_ocupacion_cauce', 'valor' => null, 'seleccionado' => true, 'id_lista' => 29))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '215. Número de expediente', 'bd' => 'numero_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha', 'titulo' => '216. Fecha de expediente', 'bd' => 'fecha_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '217. Número de resolución', 'bd' => 'numero_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha', 'titulo' => '218. Fecha de resolución', 'bd' => 'fecha_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '219. Vigencia (años)', 'bd' => 'vigencia_anios', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de actividad minera cuerpos de agua',
                'tabla' => 'informacion_actividad_minera_cuerpos_agua',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 4,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '220. Cuerpo de agua utilizado para actividades mineras (material de arrastre o canteras)', 'bd' => 'id_cuerpo_agua', 'valor' => null, 'seleccionado' => true, 'id_lista' => 25))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_cuerpo_agua', 'valor_dependiente' => 510, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otro_cuerpo_agua', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '221. Nombre de la fuente', 'bd' => 'nombre_fuente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple_texto_editable', 'titulo' => '222. Descripción de la actividad', 'bd' => 'descripcion_actividad', 'valor' => null, 'seleccionado' => true, 'id_lista' => 44))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '223. Características del lecho', 'bd' => 'id_caracteristicas_lecho', 'valor' => null, 'seleccionado' => true, 'id_lista' => 27))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_caracteristicas_lecho', 'valor_dependiente' => 517, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '¿Cuál?', 'bd' => 'otra_caracteristica_lecho', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '224. Departamento', 'bd' => 'id_departamento', 'valor' => null, 'seleccionado' => true, 'id_lista' => 3))),
                    (object) array('tipo' => 'informacion', 'dependiente' => 'id_departamento', 'valor_dependiente' => 7, 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'disabled_edit', 'titulo' => '225. Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '226. Municipio', 'bd' => 'id_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 4))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '227. Código DANE municipio', 'bd' => 'id_dane_municipio', 'valor' => null, 'seleccionado' => true, 'id_lista' => 5))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '228. Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '229. Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '230. Nombre unidad hidrográfica nivel I', 'bd' => 'id_nombre_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true, 'id_lista' => 8))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '231. Código unidad hidrográfica nivel I', 'bd' => 'id_codigo_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true, 'id_lista' => 9))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '232. Nombre unidad hidrográfica nivel II', 'bd' => 'id_nombre_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true, 'id_lista' => 10))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '233. Código unidad hidrográfica nivel II', 'bd' => 'id_codigo_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true, 'id_lista' => 11)))
                )
            ),
                
            (object) array(
                'titulo' => 'Descripción de actividades de extracción',
                'tabla' => 'actividades_extraccion',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'id_modulo' => 4,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'seleccion_simple', 'titulo' => '234. Tipo de material de arrastre', 'bd' => 'id_tipo_material_arrastre', 'valor' => null, 'seleccionado' => true, 'id_lista' => 30))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '235. Volumen de extracción Autorizado (m3/año)', 'bd' => 'volumen_extraccion_atorizado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '236. Volumen de extracción real (m3/año)', 'bd' => 'volumen_extraccion_real', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '237. Título minero', 'bd' => 'id_titulo_minero', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => 'Numero de resolución de título minero', 'bd' => 'numero_resolucion_titulo_minero', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha', 'titulo' => '239. Fecha de resolución título minero', 'bd' => 'fecha_titulo_minero', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'radio', 'titulo' => '240. Licencia o permiso ambiental', 'bd' => 'id_licencia_permiso_ambiental', 'valor' => null, 'seleccionado' => true, 'id_lista' => 17))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '241. Número de resolución permiso ambiental', 'bd' => 'numero_resolucion_permiso_ambiental', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'fecha', 'titulo' => '242. Fecha de resolución permiso ambiental', 'bd' => 'fecha_permiso_ambiental', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_actividades_extraccion',
                        'campos' => array(
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '243. Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'decimal', 'puntos' => 15, 'minlength' => 0, 'maxlength' => 20, 'titulo' => '243. Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '243. Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '243. Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'tabla',
                        'clase_css' => 'col-md-4 col-sm-12',
                        'tabla' => 'actividades_extraccion_1',
                        'filas' => array(
                            array(
                                (object) array('fila' => 'actividades_extraccion_243', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '243a. Este- x(m)', 'bd' => 'este', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'actividades_extraccion_243', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 7, 'titulo' => '243a. Norte-y(m)', 'bd' => 'norte', 'valor' => null, 'seleccionado' => true),
                                (object) array('fila' => 'actividades_extraccion_243', 'mostrar' => true, 'requerido' => false, 'tipo' => 'numero', 'puntos' => 0, 'minlength' => 0, 'maxlength' => 4, 'titulo' => '243a. Altura(m.s.n.m)', 'bd' => 'altura', 'valor' => null, 'seleccionado' => true)
                            )
                        )
                    ),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '244. Imagen del punto referenciaco (GPS)', 'bd' => 'imagen_punto_referenciado_gps', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '244. Imagen del punto referenciado (PREDIO - ACTIVIDAD EXTRACCION)', 'bd' => 'imagen_punto_referenciado_predio_extraccion1', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'imagen', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'imagen', 'titulo' => '244. Imagen del punto referenciado (PREDIO - ACTIVIDAD EXTRACCION)', 'bd' => 'imagen_punto_referenciado_predio_extraccion2', 'valor' => null, 'seleccionado' => false))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'requerido' => false, 'tipo' => 'texto', 'titulo' => '245. Esquema de localización y/o punto de extracción', 'bd' => 'esquema_localizacion', 'valor' => null, 'seleccionado' => true)))
                )
            )       
        );
    }
}
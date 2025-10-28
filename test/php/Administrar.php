<?php

class Administrar extends DataBase
{
    private $oPostData = null;
    private $oSesion = null;
    private $aEstructuraEncuesta = array();

    public function __construct($oPost = null) 
    {
        parent::__construct();
        $this->oPostData = $oPost;
        $this->oSesion = new Sesion();
        $this->aEstructuraEncuesta = array();
    }

    public function iniciarSesion() : array
    {
        $this->sSql = "
            SELECT id, nombre, correo, contrasenia
            FROM dbo.usuario
            WHERE correo = :correo
        ";

        $this->aExecute = array(':correo' => $this->oPostData->correo);
        $this->execQuery();
        $aLogin = array('bLogin' => false);

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oUsuario = (object) $r;

            if ($this->oPostData->correo === $oUsuario->correo && sha1($this->oPostData->contrasenia) === $oUsuario->contrasenia) {
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
        return array('bSession' => $this->oSesion->validarSesion(), 'nombre' => $this->oSesion->nombreUsuario());
    }

    public function catalogoUsuarios() : array
    {
        $this->sSql = "
            SELECT id, nombre, correo, contrasenia, tipo_usuario
            FROM dbo.usuario
            WHERE 1 = 1
        ";

        $aCatalogo = array();

        $this->aExecute = array();
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oUsuario = (object) $r;
            $aCatalogo[] = $oUsuario;
        }

        return array('catalogo' => $aCatalogo);
    }

    public function guardarEditarUsuario() : array
    {
        $aData = array(
            "nombre" => trim($this->oPostData->nombre),
            "tipo_usuario" => $this->oPostData->tipo_usuario,
            "correo" => trim(mb_strtolower($this->oPostData->correo_electronico))
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
                $aData['ultimo_acceso'] = null;
                $aData['usuario_registro'] = $this->oSesion->idUsuario();
                $aData['fecha_registro'] = date('Y-m-d H:i:s');
    
                $bProceso = $this->insertSingle('usuario', $aData);
            }
        }

        return array('bProceso' => $bProceso, 'iCantidadExiste' => $iCantidadExiste);
    }

    public function catalogoEncuestas() : array
    {
        $aEncuestas = array();
        $this->aExecute = array();
        $this->sSql = "";
        $aIdsEncuestas = [];

        //Filtro nombre encuestador
        /*$this->sSql = "SELECT id_encuesta, SUBSTRING(master.dbo.fn_varbintohexstr(HASHBYTES('MD5', nombre_encuestador)), 3, 32) AS 'id' FROM dbo.encuesta WHERE nombre_encuestador IS NOT NULL ORDER BY nombre_encuestador";
        $this->aExecute = array();
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $sIdNombre = $r['id'];

            if (!in_array($sIdNombre, $aIds)) {
                $aNombreEncuestador[] = array('id' => $sIdNombre, 'nombre' => $r['nombre_encuestador']);
                $aIds[] = $sIdNombre;
            }
        }*/

        $sSqlEncuesta = "SELECT e.* FROM dbo.encuesta e WHERE 1 = 1";
        $aFiltroEncuesta = array();

        if (isset($this->oPostData->codigo_encuesta)) {
            $sSqlEncuesta .= " AND codigo_encuesta LIKE :codigoEncuesta";
            $aFiltroEncuesta[':codigoEncuesta'] = '%' . $this->oPostData->codigo_encuesta . '%';
        }

        $sSqlEncuesta .= " ORDER BY e.fecha_diligenciamiento DESC";

        $this->sSql = $sSqlEncuesta;
        $this->aExecute = $aFiltroEncuesta;

        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $bFiltro = true;
            $oEncuesta = (object) $r;

            if (isset($this->oPostData->nombre_encuestador)) {
                $sIdNombre = mb_strtolower($this->oSesion->sanearString($oEncuesta->nombre_encuestador));
                $bFiltro = $sIdNombre !== $this->oPostData->nombre_encuestador ? false : $bFiltro;
            }

            if (isset($this->oPostData->codigo_cuenca_hidrografica)) {
                $sIdNombre = mb_strtolower($this->oSesion->sanearString($oEncuesta->codigo_cuenca_hidrografica));
                $bFiltro = $sIdNombre !== $this->oPostData->codigo_cuenca_hidrografica ? false : $bFiltro;
            }

            if ($bFiltro) {
                $aEncuestas[] = $oEncuesta;
            }
        }

        return array('catalogo' => $aEncuestas);
    }

    public function informacionEncuesta() : array
    {
        $this->estructuraEncuesta();

        /*if ($this->oSesion->idUsuario() === 1) {
            $sql = [];

            foreach ($this->aEstructuraEncuesta as $i => $oTabla) {
                $sql[] = "\n";

                foreach ($oTabla->grupos as $oGrupo) {
                    foreach ($oGrupo->campos as $oCampoGrupo) {
                        $sql[] = "EXEC sp_addextendedproperty @name = N'MS_Description', @value = '" . $oCampoGrupo->titulo . "', @level0type = N'Schema', @level0name = dbo, @level1type = N'Table', @level1name = '" . $oTabla->tabla . "', @level2type = N'Column', @level2name = '" . $oCampoGrupo->bd . "'";
                    }
                }
            }

            print_r(implode(";\n", $sql));
        }*/

        $this->aExecute = array(':idEncuesta' => $this->oPostData->id);

        foreach ($this->aEstructuraEncuesta as $i => $oTabla) {
            $this->sSql = "SELECT * FROM dbo." . $oTabla->tabla . " WHERE " . $oTabla->id_validar . " = :idEncuesta";
            $this->execQuery();

            foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                foreach ($oTabla->grupos as $oGrupo) {
                    foreach ($oGrupo->campos as $oCampoGrupo) {
                        if (isset($r[$oCampoGrupo->bd])) {
                            $oCampoGrupo->valor = $r[$oCampoGrupo->bd];
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

                foreach ($oGrupo->campos as $oCampoGrupo) {
                    $oCampoGrupo->mostrar = isset($oCampoGrupo->valor);
                    $oGrupo->mostrar = $oCampoGrupo->mostrar ? true : $oGrupo->mostrar;

                    if ($oGrupo->tipo === 'mapa' && $oCampoGrupo->bd === 'latitud' && is_numeric($oCampoGrupo->valor)) {
                        $oGrupo->latitud = (float) $oCampoGrupo->valor;
                    } else if ($oGrupo->tipo === 'mapa' && $oCampoGrupo->bd === 'longitud' && is_numeric($oCampoGrupo->valor)) {
                        $oGrupo->longitud = (float) $oCampoGrupo->valor;
                    }
                }

                $oTabla->mostrar = $oGrupo->mostrar ? true : $oTabla->mostrar;
            }
        }

        return array('id' => $this->oPostData->id, 'estructura' => $this->aEstructuraEncuesta);
    }

    public function cargarEstructuraEncuesta() : array
    {
        $this->estructuraEncuesta();

        return array('estructura' => $this->aEstructuraEncuesta);
    }

    public function obtenerDatosEncuestasCSV() : array
    {
        $aTablasCampo = array();
        $aTitulos = array();
        $aFilasEncuestas = array();
        $aEstructura = array();

        foreach ($this->oPostData->campos as $oCampo) {
            $aTablasCampo[$oCampo->tabla] = isset($aTablasCampo[$oCampo->tabla]) ? $aTablasCampo[$oCampo->tabla] : [];
            $aTablasCampo[$oCampo->tabla][] = $oCampo->campo;
        }

        $this->estructuraEncuesta();

        $this->aExecute = array_values($this->oPostData->ids);

        foreach ($this->aEstructuraEncuesta as $i => $oTabla) {
            if (isset($aTablasCampo[$oTabla->tabla])) {
                $aCamposIncluir = $aTablasCampo[$oTabla->tabla];
                $this->sSql = "SELECT * FROM dbo." . $oTabla->tabla . " WHERE " . $oTabla->id_validar . " IN (" . implode(', ', array_fill(0, count($this->oPostData->ids), '?')) . ")";

                $this->execQuery();
                $iContador = 0;

                foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    foreach ($oTabla->grupos as $oGrupo) {
                        foreach ($oGrupo->campos as $oCampoGrupo) {
                            if (in_array($oCampoGrupo->bd, $aCamposIncluir)) {
                                if ($iContador === 0) {
                                    $aTitulos[] = $oCampoGrupo->titulo;
                                }

                                $aFilasEncuestas[$r[$oTabla->id_validar]] = isset($aFilasEncuestas[$r[$oTabla->id_validar]]) ? $aFilasEncuestas[$r[$oTabla->id_validar]] : [];
                                $aFilasEncuestas[$r[$oTabla->id_validar]][] = isset($r[$oCampoGrupo->bd]) ? $r[$oCampoGrupo->bd] : '';
                            }
                        }
                    }

                    $iContador++;
                }
            }
        }

        $aEstructura[] = $aTitulos;

        foreach ($aFilasEncuestas as $id => $aValores) {
            $aEstructura[] = $aValores;
        }

        return array('estructura' => $aEstructura);
    }

    public function filtrosEncuestas()
    {
        $aNombreEncuestador = array();
        $aCodigoCuenca = array();

        $aIdsNombreEncuestador = [];
        $aIdsCodigoCuenca = [];

        $this->sSql = "SELECT codigo_cuenca_hidrografica, nombre_encuestador FROM dbo.encuesta WHERE nombre_encuestador IS NOT NULL OR codigo_cuenca_hidrografica IS NOT NULL";
        $this->aExecute = array();
        $this->execQuery();

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $sIdNombreEncuestador = mb_strtolower($this->oSesion->sanearString($r['nombre_encuestador']));
            $sIdCodigoCuenca = mb_strtolower($this->oSesion->sanearString($r['codigo_cuenca_hidrografica'])); 

            if (!in_array($sIdNombreEncuestador, $aIdsNombreEncuestador)) {
                $aNombreEncuestador[] = array('id' => $sIdNombreEncuestador, 'nombre' => $r['nombre_encuestador']);
                $aIdsNombreEncuestador[] = $sIdNombreEncuestador;
            }

            if (!in_array($sIdCodigoCuenca, $aIdsCodigoCuenca)) {
                $aCodigoCuenca[] = array('id' => $sIdCodigoCuenca, 'nombre' => $r['codigo_cuenca_hidrografica']);
                $aIdsCodigoCuenca[] = $sIdCodigoCuenca;
            }
        }

        return array('nombre_encuestador' => $aNombreEncuestador, 'codigo_cuenca_hidrografica' => $aCodigoCuenca);
    }

    public function guardarCambiosEncuesta() 
    {
        $bProceso = true;

        foreach ($this->oPostData->datos->estructura as $oRegistro) {
            if ($oRegistro->mostrar) {
                $aData = [];
                
                if ($oRegistro->tabla === 'encuesta') {
                    $aData['id_usuario_edicion'] = $this->oSesion->idUsuario();
                    $aData['fecha_edicion_visor'] = date('Y-m-d H:i:s');
                }

                foreach ($oRegistro->grupos as $oGrupo) {
                    if ($oGrupo->mostrar) {
                        foreach ($oGrupo->campos as $oCampo) {
                            $aData[$oCampo->bd] = $oCampo->valor !== null ? (strlen(trim((string) $oCampo->valor)) > 0 ? $oCampo->valor : null) : null;
                        }
                    }
                }

                if (count($aData) > 0) {
                    $aWhere = $oRegistro->id_validar === 'id' ? array('id' => $this->oPostData->datos->id) : array('id_encuesta' => $this->oPostData->datos->id);
                    $bProceso = !$this->updateSingle($oRegistro->tabla, $aData, $aWhere) ? false : $bProceso;
                }
            }
        }

        return array('bProceso' => $bProceso);
    }

    private function estructuraEncuesta() 
    {
        $this->aEstructuraEncuesta = array(
            (object) array(
                'titulo' => 'Datos básicos de la encuesta',
                'tabla' => 'encuesta',
                'id_validar' => 'id',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código encuesta', 'bd' => 'codigo_encuesta', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Código asociado a la cuenca hidrográfica nivel I', 'bd' => 'codigo_cuenca_hidrografica', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fecha diligenciamiento', 'bd' => 'fecha_diligenciamiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones generales', 'bd' => 'observaciones_generales', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Nombre encuestador', 'bd' => 'nombre_encuestador', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Cédula encuestador', 'bd' => 'cedula_encuestador', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Ingeniero de campo', 'bd' => 'ingeniero_campo', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Nombre encuestado', 'bd' => 'nombre_encuestado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Coordinador de proyecto', 'bd' => 'coordinador_proyecto', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fecha de procesamiento', 'bd' => 'fecha_procesamiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones adicionales', 'bd' => 'observaciones_adicionales', 'valor' => null, 'seleccionado' => true)))
                )
            ),
    
            (object) array(
                'titulo' => 'Identificación del usuario',
                'tabla' => 'usuario_encuesta',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de usuario', 'bd' => 'tipo_usuario', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Nombre del usuario', 'bd' => 'nombre_usuario', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de identificación', 'bd' => 'tipo_identificacion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de documento', 'bd' => 'documento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Representante legal o administrador', 'bd' => 'representante_legal', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Dirección domicilio usuario', 'bd' => 'direccion_domicilio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'etiquetas', 'titulo' => 'Teléfono(s) domicilio', 'bd' => 'telefono_domicilio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Dirección correspondencia usuario', 'bd' => 'direccion_correspondencia', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'etiquetas', 'titulo' => 'Teléfono(s) correspondencia', 'bd' => 'telefono_correspondencia', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Identificación del predio',
                'tabla' => 'predio',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Nombre Predio', 'bd' => 'nombre', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Área (Has)', 'bd' => 'area', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Dirección del Predio', 'bd' => 'direccion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Departamento', 'bd' => 'departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Municipio', 'bd' => 'municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Codigo DANE municipio', 'bd' => 'dane_municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Cédula Catastral', 'bd' => 'cedula_catastral', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tenencia tipo', 'bd' => 'tenencia_tipo', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_predio',
                        'campos' => array(
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la fuente de captación',
                'tabla' => 'fuente_captacion',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fuente superficial', 'bd' => 'fuente_superficial', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Nombre de la fuente', 'bd' => 'nombre_fuente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Departamento', 'bd' => 'departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Municipio', 'bd' => 'municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Codigo DANE municipio', 'bd' => 'dane_municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Nombre unidad hidrográfica nivel I', 'bd' => 'nombre_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código unidad hidrográfica nivel I', 'bd' => 'codigo_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Nombre unidad hidrográfica nivel II', 'bd' => 'nombre_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código unidad hidrográfica nivel II', 'bd' => 'codigo_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la captación en la fuente',
                'tabla' => 'captacion_en_fuente',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de captación', 'bd' => 'tipo_captacion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Estado', 'bd' => 'estado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Diámetro (pulgada)', 'bd' => 'diametro', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones diámetro (pulgada)', 'bd' => 'diametro_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Ancho (m)', 'bd' => 'ancho', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones ancho (m)', 'bd' => 'ancho_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Alto (m)', 'bd' => 'alto', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones alto (m)', 'bd' => 'alto_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Profundidad (m)', 'bd' => 'profundidad', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones profundidad (m)', 'bd' => 'profundidad_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Tiempo captación (Hr/día)', 'bd' => 'tiempo_captacion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones tiempo captación (Hr/día)', 'bd' => 'tiempo_captacion_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Frecuencia (día/mes)', 'bd' => 'frecuencia', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones frecuencia (día/mes)', 'bd' => 'frecuencia_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Capacidad bomba (HP)', 'bd' => 'capacidad_bomba', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones capacidad bomba (HP)', 'bd' => 'capacidad_bomba_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Capacidad Instalada (l/s)', 'bd' => 'capacidad_instalada', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones capacidad Instalada (l/s)', 'bd' => 'capacidad_instalada_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector','titulo' => 'Características sistema de abastecimiento', 'bd' => 'caracteristicas_abastecimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones de la captación', 'bd' => 'observaciones_captacion', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_captacion_en_fuente',
                        'campos' => array(
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Esquema de localización y/o punto de captación', 'bd' => 'esquema_localizacion', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información del uso y/o aprovechamiento del recurso hídrico',
                'tabla' => 'uso_recurso_hidrico',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso doméstico', 'bd' => 'uso_domestico', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de personas permanentes', 'bd' => 'personas_permanentes', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de personas transitorias', 'bd' => 'personas_transitorias', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Días/mes', 'bd' => 'dias_mes', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Consumo (l/s)', 'bd' => 'consumo', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Menores de 5 años', 'bd' => 'menores_cinco_anios', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Mayores de 60 años', 'bd' => 'mayores_sesenta_anios', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Tipo de construcción', 'bd' => 'tipo_construccion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso pecuario', 'bd' => 'uso_pecuario', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso acuícola', 'bd' => 'uso_acuicola', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso agrícola y silvícola', 'bd' => 'uso_agricola_silvicola', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de riego predominante', 'bd' => 'riego_predominante', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso industrial', 'bd' => 'uso_industrial', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso minero', 'bd' => 'uso_minero', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso generación hidroeléctrica', 'bd' => 'uso_generacion_hidroelectrica', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso recreacional', 'bd' => 'uso_recreacional', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso servicios', 'bd' => 'uso_servicios', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Uso explotación petrolera', 'bd' => 'uso_explotacion_petrolera', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Otros usos', 'bd' => 'otros_usos', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información jurídica de la captación',
                'tabla' => 'informacion_juridica_captacion',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Doméstico', 'bd' => 'domestico', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'domestico_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Pecuario', 'bd' => 'pecuario', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'pecuario_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Acuícola', 'bd' => 'acuicola', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'acuicola_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Agrícola', 'bd' => 'agricola', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'agricola_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Industrial', 'bd' => 'industrial', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'industrial_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Minería', 'bd' => 'mineria', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'mineria_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Generación electrica', 'bd' => 'generacion_electrica', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'generacion_electrica_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Otros', 'bd' => 'otros', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'otros_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Total(l/s)', 'bd' => 'total', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'total_observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Permiso de concesión de uso del agua', 'bd' => 'permiso_concesion_uso_agua', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número del expediente que maneja la CAR correspondiente al número con el que se le otorgó el caudal, bajo Resolución', 'bd' => 'numero_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fecha: (año/mes/dia)', 'bd' => 'fecha_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de resolución', 'bd' => 'numero_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fecha: (año/mes/dia)', 'bd' => 'fecha_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Vigencia (años)', 'bd' => 'vigencia_anios', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Caudal Concesionado (l/s)', 'bd' => 'caudal_concesionado', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la fuente receptora de vertimiento',
                'tabla' => 'fuente_receptora_vertimiento',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Cuerpo receptor', 'bd' => 'cuerpo_receptor', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Nombre de la fuente receptora', 'bd' => 'fuente_receptora', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Objetivo de calidad del cuerpo receptor (Uso/destinación)', 'bd' => 'objetivo_calidad_receptor', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Departamento', 'bd' => 'departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código DANE', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Municipio', 'bd' => 'municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código DANE', 'bd' => 'dane_municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Nombre unidad hidrográfica nivel I', 'bd' => 'nombre_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código unidad hidrográfica nivel I', 'bd' => 'codigo_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Nombre unidad hidrográfica nivel II', 'bd' => 'nombre_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código unidad hidrográfica nivel II', 'bd' => 'codigo_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información del vertimiento',
                'tabla' => 'informacion_vertimiento',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Actividad que genera el vertimiento', 'bd' => 'actividad_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de descarga', 'bd' => 'tipo_descarga', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Caudal vertido (l/s)', 'bd' => 'caudal_vertido', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Tiempo descarga (hr/día)', 'bd' => 'tiempo_descarga', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Frecuencia (días/mes)', 'bd' => 'frecuencia', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Disposición del Vertimiento', 'bd' => 'disposicion_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Estado', 'bd' => 'estado', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_informacion_vertimiento',
                        'campos' => array(
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones del vertimiento', 'bd' => 'observaciones_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => '¿Presenta algún tipo de sistema de tratamiento? - Sistemas de Tratamiento', 'bd' => 'presenta_sistema_tratamiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Esquema de localización punto de vertimiento', 'bd' => 'esquema_localizacion_punto_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Si el predio cuenta con pozo séptico o campo de infiltración, ¿este se encuentra a menos de 100 mts una fuente hídrica?', 'bd' => 'pozo_septico_campo_infiltracion_menos_100_mts', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información jurídica del vertimiento',
                'tabla' => 'informacion_juridica_vertimiento',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Permiso de vertimiento', 'bd' => 'permiso_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de expediente', 'bd' => 'numero_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fecha: (año/mes/dia) de expediente', 'bd' => 'fecha_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de resolución', 'bd' => 'numero_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fecha: (año/mes/dia) de resolución', 'bd' => 'fecha_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Vigencia', 'bd' => 'vigencia_anios', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Caudal de Vertido Autorizado (l/s)', 'bd' => 'caudal_vertido_autorizado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Norma que debe cumplir el vertimiento', 'bd' => 'norma_vertimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la fuente con ocupación de cauce - Obras hidráulicas',
                'tabla' => 'informacion_fuente_ocupacion_cauce',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Cuerpo de agua con ocupación de cauce - Obras hidráulicas', 'bd' => 'cuerpo_agua', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Nombre de la fuente', 'bd' => 'nombre_fuente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Observaciones', 'bd' => 'observaciones', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Uso de la fuente en el área de influencia', 'bd' => 'fuente_area_influcencia', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Pendiente del cauce %', 'bd' => 'pendiente_cauce', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de cauce', 'bd' => 'tipo_cauce', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de lecho', 'bd' => 'tipo_lecho', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Departamento', 'bd' => 'departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Municipio', 'bd' => 'municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código DANE', 'bd' => 'dane_municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Nombre unidad hidrográfica nivel I', 'bd' => 'nombre_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código unidad hidrográfica nivel I', 'bd' => 'codigo_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Nombre unidad hidrográfica nivel II', 'bd' => 'nombre_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código unidad hidrográfica nivel II', 'bd' => 'codigo_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de la ocupación de cauce asociado a obra hidráulica',
                'tabla' => 'informacion_ocupacion_cauce_obra_hidraulica',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Descripción de la obra', 'bd' => 'descripcion_obra', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de obra', 'bd' => 'tipo_obra', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Estado', 'bd' => 'estado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Tipo de material', 'bd' => 'tipo_material', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Altura (m)', 'bd' => 'alto', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Ancho (m)', 'bd' => 'ancho', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Longitud (m)', 'bd' => 'largo', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Profundidad (m)', 'bd' => 'profundidad', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Área (m2)', 'bd' => 'area', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Diámetro (m)', 'bd' => 'diametro', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_informacion_ocupacion_cauce_obra_hidraulica',
                        'campos' => array(
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Esquema de localización de la obra', 'bd' => 'esquema_localizacion_obra', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Permiso de ocupación de cauce', 'bd' => 'permiso_ocupacion_cauce', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de expediente', 'bd' => 'numero_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Fecha de expediente', 'bd' => 'fecha_expediente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de resolución', 'bd' => 'numero_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Fecha de resolución', 'bd' => 'fecha_resolucion', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Vigencia (años)', 'bd' => 'vigencia_anios', 'valor' => null, 'seleccionado' => true)))
                )
            ),

            (object) array(
                'titulo' => 'Información de actividad minera cuerpos de agua',
                'tabla' => 'informacion_actividad_minera_cuerpos_agua',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Cuerpo de agua utilizado para actividades mineras (material de arrastre o canteras)', 'bd' => 'cuerpo_agua', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Nombre de la fuente', 'bd' => 'nombre_fuente', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Descripción de la actividad', 'bd' => 'descripcion_actividad', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Características del lecho', 'bd' => 'caracteristicas_lecho', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Departamento', 'bd' => 'departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código DANE departamento', 'bd' => 'dane_departamento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Municipio', 'bd' => 'municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código DANE municipio', 'bd' => 'dane_municipio', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Corregimiento', 'bd' => 'corregimiento', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Vereda', 'bd' => 'vereda', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Nombre unidad hidrográfica nivel I', 'bd' => 'nombre_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código unidad hidrográfica nivel I', 'bd' => 'codigo_und_geografica_nivel1', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Nombre unidad hidrográfica nivel II', 'bd' => 'nombre_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Código unidad hidrográfica nivel II', 'bd' => 'codigo_und_geografica_nivel2', 'valor' => null, 'seleccionado' => true)))
                )
            ),
                
            (object) array(
                'titulo' => 'Descripción de actividades de extracción',
                'tabla' => 'actividades_extraccion',
                'id_validar' => 'id_encuesta',
                'seleccionado' => true,
                'grupos' => array(
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Tipo de material de arrastre', 'bd' => 'tipo_material_arrastre', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Volumen de extracción Autorizado (m3/año)', 'bd' => 'volumen_extraccion_atorizado', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Volumen de extracción real (m3/año)', 'bd' => 'volumen_extraccion_real', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Título minero', 'bd' => 'titulo_minero', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Numero de resolución de título minero', 'bd' => 'numero_resolucion_titulo_minero', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fecha de resolución título minero', 'bd' => 'fecha_titulo_minero', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Licencia o permiso ambiental', 'bd' => 'licencia_permiso_ambiental', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Número de resolución permiso ambiental', 'bd' => 'numero_resolucion_permiso_ambiental', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'selector', 'titulo' => 'Fecha de resolución permiso ambiental', 'bd' => 'fecha_permiso_ambiental', 'valor' => null, 'seleccionado' => true))),
                    (object) array(
                        'tipo' => 'mapa',
                        'contenedor' => 'mapa_actividades_extraccion',
                        'campos' => array(
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Latitud (x.y °)', 'bd' => 'latitud', 'valor' => null, 'seleccionado' => true),
                            (object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Longitud (x.y °)', 'bd' => 'longitud', 'valor' => null, 'seleccionado' => true)
                        )
                    ),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Altitud (m)', 'bd' => 'altitud', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Precisión (m)', 'bd' => 'precision', 'valor' => null, 'seleccionado' => true))),
                    (object) array('tipo' => 'informacion', 'campos' => array((object) array('mostrar' => true, 'tipo' => 'texto', 'titulo' => 'Esquema de localización y/o punto de extracción', 'bd' => 'esquema_localizacion', 'valor' => null, 'seleccionado' => true)))
                )
            )       
        );
    }
}
<?php
    $aEstructuraEncuesta = array(
        array(
            'titulo' => 'Datos básicos de la encuesta',
            'tabla' => 'encuesta',
            'grupos' => array(
                array(
                    array('titulo' => 'Código Encuesta (Diligenciar código asignado en la ruta)', 'bd' => 'codigo_encuesta', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Código asociado a la cuenca hidrográfica nivel I', 'bd' => 'codigo_cuenca_hidrografica', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Fecha diligenciamiento', 'bd' => 'fecha_diligenciamiento', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Observaciones generales', 'bd' => 'observaciones_generales', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Nombre encuestador', 'bd' => 'nombre_encuestador', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Cédula encuestador', 'bd' => 'cedula_encuestador', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Ingeniero de campo', 'bd' => 'ingeniero_campo', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Nombre encuestado', 'bd' => 'nombre_encuestado', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Coordinador de proyecto', 'bd' => 'coordinador_proyecto', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Fecha de procesamiento', 'bd' => 'fecha_procesamiento', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Observaciones adicionales', 'bd' => 'observaciones_adicionales', 'valor' => null)
                )
            )
        ),

        array(
            'titulo' => 'Identificación del usuario',
            'tabla' => 'usuario_encuesta',
            'grupos' => array(
                array(
                    array('titulo' => 'Tipo de usuario', 'bd' => 'tipo_usuario', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Nombre del usuario','bd' => 'nombre_usuario', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Tipo de identificacion', 'bd' => 'tipo_identificacion', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Número de documento', 'bd' => 'documento', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Representante legal o administrador', 'bd' => 'representante_legal', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Dirección domicilio usuario','bd' => 'direccion_domicilio', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Teléfono(s)', 'bd' => 'telefono_domicilio', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Dirección correspondencia usuario', 'bd' => 'direccion_correspondencia', 'valor' => null)
                ),
                array(
                    array('titulo' => 'Teléfono(s)', 'bd' => 'telefono_correspondencia', 'valor' => null)
                )
            )
        )
    );
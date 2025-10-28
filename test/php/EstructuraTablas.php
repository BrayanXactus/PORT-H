<?php
    $aTablas = array(
        array(
            'titulo' => 'Datos básicos de la encuesta',
            'tabla' => 'encuesta',
            'grupos' => array(
                array(
                    array(
                        'titulo' => 'Código Encuesta (Diligenciar código asignado en la ruta)',
                        'bd' => 'codigo_encuesta',
                        'valor' => null
                    )
                ),
                array(
                    array(
                        'titulo' => 'Código asociado a la cuenca hidrográfica nivel I',
                        'bd' => 'codigo_cuenca_hidrografica',
                        'valor' => null
                    )
                ),
                array(
                    array(
                        'titulo' => 'Fecha diligenciamiento',
                        'bd' => 'fecha_diligenciamiento',
                        'valor' => null
                    )
                )
            )
        ),

        array(
            'titulo' => 'Identificación del usuario',
            'tabla' => 'usuario_encuesta',
            'grupos' => array(
                array(
                    'campos' => array(
                        'titulo' => 'Tipo de usuario',
                        'bd' => 'tipo_usuario',
                        'valor' => null
                    )
                ),
                array(
                    'campos' => array(
                        'titulo' => 'Nombre del usuario',
                        'bd' => 'nombre_usuario',
                        'valor' => null
                    )
                ),
                array(
                    array(
                        'titulo' => 'Tipo de identificacion',
                        'bd' => 'tipo_identificacion',
                        'valor' => null
                    )
                ),
                array(
                    array(
                        'titulo' => 'Número de documento',
                        'bd' => 'documento',
                        'valor' => null
                    )
                )
            )
        )
    );
<?php

class Sesion 
{
    public function __construct() 
    {
        
    }

    public function iniciarSesion(stdClass $oDataUsuario) 
    {
        @session_start();
        $_SESSION['LENGUAJE'] = 'es';
        $_SESSION['NOMBRE'] = $oDataUsuario->nombre;
        $_SESSION['CORREO'] = $oDataUsuario->correo;
        $_SESSION['IDUSUARIO'] = $oDataUsuario->id;
    }

    public function validarSesion() : bool 
    {
        @session_start();
        return isset($_SESSION['NOMBRE'], $_SESSION['CORREO'], $_SESSION['IDUSUARIO']);
    }

    public function nombreUsuario() 
    {
        @session_start();
        return isset($_SESSION['NOMBRE']) ? $_SESSION['NOMBRE'] : null;
    }

    public function idUsuario() 
    {
        @session_start();
        return isset($_SESSION['IDUSUARIO']) ? (int) $_SESSION['IDUSUARIO'] : null;
    }

    public function cerrarSesion() : bool 
    {
        @session_start();
        session_destroy();
        return true;
    }

    public function sanearString(string $string) : string
    {
        $string = trim($string);

        $string = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $string
        );

        $string = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $string
        );

        $string = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $string
        );

        $string = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $string
        );

        $string = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $string
        );

        $string = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C',),
            $string
        );
        
        return str_replace(
            array("\\", "¨", "º", "-", "~",
                 "#", "@", "|", "!", "\"",
                 "\'", "·", "$", "%", "&",
                 "(", ")", "?", "'", "¡",
                 "¿", "[", "^", "`", "]",
                 "+", "}", "{", "¨", "´",
                 ">", "< ", ";", ",", ":",
                 "/", ".", " "),
            '',
            $string
        );
    }
}
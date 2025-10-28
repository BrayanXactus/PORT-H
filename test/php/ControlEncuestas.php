<?php

include 'core/config.php';
include 'core/DataBase.php';
include 'core/Sesion.php';
include 'Administrar.php';

class ControlEncuestas 
{
    private $oAdministrar = null;
    public $aResponse;
    public $oSesion;

    public function __construct($oPostData = null) 
    {
        $this->oAdministrar = new Administrar($oPostData);
        $this->oSesion = new Sesion(); 
    }

    public function iniciarSesion()
    {
        $this->aResponse = $this->oAdministrar->iniciarSesion();
    }

    public function validarSesion()
    {
        $this->aResponse = $this->oAdministrar->validarSesion();
    }

    public function catalogoEncuestas()
    {
        $this->aResponse = $this->oAdministrar->catalogoEncuestas();
    }

    public function informacionEncuesta()
    {
        $this->aResponse = $this->oAdministrar->informacionEncuesta();
    }

    public function cargarEstructuraEncuesta()
    {
        $this->aResponse = $this->oAdministrar->cargarEstructuraEncuesta();
    }

    public function obtenerDatosEncuestasCSV()
    {
        $this->aResponse = $this->oAdministrar->obtenerDatosEncuestasCSV();
    }

    public function filtrosEncuestas()
    {
        $this->aResponse = $this->oAdministrar->filtrosEncuestas();
    }

    public function guardarCambiosEncuesta()
    {
        $this->aResponse = $this->oAdministrar->guardarCambiosEncuesta();
    }

    public function catalogoUsuarios()
    {
        $this->aResponse = $this->oAdministrar->catalogoUsuarios();
    }

    public function guardarEditarUsuario()
    {
        $this->aResponse = $this->oAdministrar->guardarEditarUsuario();
    }

    public function cerrarSesion()
    {
        $this->aResponse = array('sesion' => $this->oSesion->cerrarSesion());
    }
}

$oPostData = json_decode(file_get_contents("php://input"));
$oController = new ControlEncuestas($oPostData);
call_user_func(array($oController, $oPostData->method));

echo json_encode($oController->aResponse);
<?php

include 'core/config.php';
include 'library/vendor/autoload.php';
include 'core/Archivos.php';
include 'core/DataBase.php';
include 'core/Sesion.php';
include 'Administrar.php';
include 'PDF.php';

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

    public function guardarEncuestaServidor()
    {
        $this->aResponse = $this->oAdministrar->guardarEncuestaServidor();
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

    public function filtrosEncuestasDatosBasicos()
    {
        $this->aResponse =  $this->oAdministrar->filtrosEncuestasDatosBasicos();
    }

    public function filtrosEncuestasCaptaciones()
    {
        $this->aResponse =  $this->oAdministrar->filtrosEncuestasCaptaciones();
    }

    public function filtrosEncuestasVertimiento()
    {
        $this->aResponse =  $this->oAdministrar->filtrosEncuestasVertimiento();
    }

    public function filtrosEncuestasOcupacion()
    {
        $this->aResponse =  $this->oAdministrar->filtrosEncuestasOcupacion();
    }

    public function filtrosEncuestasMinera()
    {
        $this->aResponse =  $this->oAdministrar->filtrosEncuestasMinera();
    }

    public function catalogoEncuestasConsultas()
    {
        $this->aResponse =  $this->oAdministrar->catalogoEncuestasConsultas();
    }

    public function conteoEncuestasSesion()
    {
        $this->aResponse =  $this->oAdministrar->conteoEncuestasSesion();
    }

    public function generarPDF()
    {
        $this->aResponse =  $this->oAdministrar->generarPDF();
    }

    public function puntosMapa()
    {
        $this->aResponse =  $this->oAdministrar->puntosMapa();
    }

    public function sesionReporte1()
    {
        $this->aResponse =  $this->oAdministrar->sesionReporte1();
    }

    public function sesionReporte2()
    {
        $this->aResponse =  $this->oAdministrar->sesionReporte2();
    }

    public function subLista()
    {
        $this->aResponse =  $this->oAdministrar->subLista();
    }

    public function puntosMapaPredio()
    {
        $this->aResponse =  $this->oAdministrar->puntosMapaPredio();
    }

    public function reporteConteoModulo()
    {
        $this->aResponse =  $this->oAdministrar->reporteConteoModulo();
    }

    public function sesionReporte4()
    {
        $this->aResponse =  $this->oAdministrar->sesionReporte4();
    }

    public function grandes_usuarios()
    {
        $this->aResponse =  $this->oAdministrar->grandes_usuarios();
    }

    public function exportarMapa()
    {
        $this->aResponse = $this->oAdministrar->exportarMapa();
    }

    public function resumenEncuestas()
    {
        $this->aResponse = $this->oAdministrar->resumenEncuestas();
    }
}

$oPostData = json_decode(file_get_contents("php://input"));

if (is_null($oPostData)) {
    $oPostData = (object) filter_input_array(INPUT_POST);
}

$oController = new ControlEncuestas($oPostData);
call_user_func(array($oController, $oPostData->method));

echo json_encode($oController->aResponse);
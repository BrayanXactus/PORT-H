<?php

class PDF {
    public $oEncabezado = null;
    public $sEncabezado = null;
    public $oSesion = null;
    public $sRutaCSS = null;
    public $oPDF = null;
    
    public function __construct(Sesion $oSesion) {
        $this->oPDF = new \Mpdf\Mpdf(['tempDir' => PATH_TEMP, 'format' => 'A4', 'orientation' => 'P', 'margin_top' => '30']);
        
        $this->oSesion = $oSesion;
        $this->sEncabezado = '';
        $this->sRutaCSS = file_get_contents(BASE_URL . 'css/styles_pdf.css');
        
        $this->oEncabezado = (object) array(
            'sContenido1' => '', 
            'sContenido2' => '', 
            'sContenido3' => date('Y-m-d H:i:s')
        );
    }
    
    public function generarPDF(string $sContenidoPDF, string $sRutaArchivo) {
        try {
            
            $this->oPDF->SetHTMLHeader($this->sEncabezado);
            //$this->oPDF->WriteHTML($this->sRutaCSS, 1);
            $this->oPDF->WriteHTML(file_get_contents(BASE_URL . 'app-assets/css/bootstrap.css'), 1);
            $this->oPDF->WriteHTML(file_get_contents(BASE_URL . 'app-assets/css/colors.css'), 1);
            $this->oPDF->WriteHTML(file_get_contents(BASE_URL . 'app-assets/css/components.css'), 1);
            $this->oPDF->WriteHTML(file_get_contents(BASE_URL . 'css/styles.css'), 1);
            $this->oPDF->WriteHTML($sContenidoPDF, 2);
            
            if (file_exists($sRutaArchivo)) {
                chmod($sRutaArchivo, 0777);
            }
            $this->oPDF->Output($sRutaArchivo);
            chmod($sRutaArchivo, 0777);         
        } catch (Exception $ex) {
            print_r($ex);
        }
    }

    public function generarPDFArray(array $aContenido, string $sRutaArchivo) {
        try {
            
            $this->oPDF->SetHTMLHeader($this->sEncabezado);
            $this->oPDF->WriteHTML($this->sRutaCSS, 1);

            foreach ($aContenido as $sContenidoPDF) {
                $this->oPDF->WriteHTML($sContenidoPDF, 2);
            }

            if (file_exists($sRutaArchivo)) {
                chmod($sRutaArchivo, 0777);
            }
            $this->oPDF->Output($sRutaArchivo);
            chmod($sRutaArchivo, 0777);         
        } catch (Exception $ex) {
            print_r($ex);
        }
    }
    
    public function encabezadoPDF() {
        $aDataVars = array('$_sContenido1_' => $this->oEncabezado->sContenido1, '$_sContenido2_' => $this->oEncabezado->sContenido2, '$_sContenido3_' => $this->oEncabezado->sContenido3);
        
        $this->sEncabezado = str_replace (
            array_keys($aDataVars),
            array_values($aDataVars),
            file_get_contents(BASE_URL . 'template/encabezadoPDF.html')
        );
    }
    
    public function generarImagen($imagen, $x, $y, $w, $h = null, $type = null, $link = null) {
        try {
            $this->oPDF->Image('temp/'.$imagen, $x, $y, $w);            
        } catch (Exception $ex) {

        }
    }
    
    public function agregarHoja() {
        try {
            $this->oPDF->AddPage();
        } catch (Exception $ex) {

        }
    }

    public function configurarColumnas(int $nCols, string $vAlign, int $gap) {
        try {
            $this->oPDF->SetColumns($nCols, $vAlign, $gap);
        } catch (Exception $ex) {

        }
    }

    public function agregarColumna() {
        try {
            $this->oPDF->AddColumn();
        } catch (Exception $ex) {

        }
    }
}
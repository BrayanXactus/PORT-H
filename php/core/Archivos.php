<?php

use Aws\S3\S3Client;

class Archivos
{
    private $oS3 = null;

    public function __construct() {
        if (STORAGE === 'S3') {
            $this->oS3 = S3Client::factory([
                'region' => 'us-west-2',
                'version' => 'latest',
                'credentials' => array (
                    'key' => awsAccessKey,
                    'secret' => awsSecretKey
                ),
                'scheme' => 'http'
            ]);
            
            $this->oS3->registerStreamWrapper();
        }
    }

    public function moverArchivoS3(string $sRutaArchivo, string $sRutaDestino) : bool 
    {
        $bMover = false;
        
        try {
            if (STORAGE === 'S3') {
                $bMover = copy($sRutaArchivo, BASE_S3_ARCHIVOS . $sRutaDestino);

                $this->oS3->putObjectAcl([
                    'Bucket' => BUCKET_ARCHIVOS,
                    'Key' => $sRutaDestino,
                    'ACL' => 'public-read'
                ]);
            }
        } catch (S3Exception $e) {
            
        }
        
        return $bMover;
    }

    public function listarMoverArchivosEncuestasS3() 
    {
        if (STORAGE === 'S3') {
            $sDirectorio = PATHFILES;
            $handle = opendir($sDirectorio);
            $aEliminar = [];
    
            while($file = readdir($handle)){
                if (is_file($sDirectorio . $file)) {
                    $aPartesNombre = explode('.', $file);
                    $sExtension = strtolower(end($aPartesNombre));
    
                    if (in_array($sExtension, array('jpg', 'jpeg', 'png', 'gif'))) {
                        if (!file_exists(BASE_S3_ARCHIVOS . 'image/'. $file)) {
                            $this->moverArchivoS3($sDirectorio . $file, 'image/'. $file);
                        }

                        $aEliminar[] = $sDirectorio . $file;

                        
                    }
                }
            }

            foreach ($aEliminar as $sEliminar) {
                @unlink($sEliminar);
            }
        }
    }

    public function getUrlArchivo($sCarpeta, $sArchivo, $iMinutos = 25) : string {
        $presignedUrl = '';
        
        if ($sArchivo != "") {
            if (STORAGE === 'S3') {
                $sRutaCompleta = $sCarpeta . '/' . $sArchivo;

                if (file_exists(BASE_S3_ARCHIVOS . $sRutaCompleta)) {
                    try {
                        $cmd = $this->oS3->getCommand('GetObject', [
                            'Bucket' => BUCKET_ARCHIVOS,
                            'Key' => "{$sRutaCompleta}",
                        ]);
                        $request = $this->oS3->createPresignedRequest($cmd, '+' . $iMinutos . ' minutes');
                        $presignedUrl = (string) $request->getUri();
                        $presignedUrl = preg_replace("/^http:/i", "https:", $presignedUrl);
                    } catch (Aws\S3\Exception\S3Exception $ex) {}
                }
            } else if (STORAGE === 'LOCAL') {
                if (file_exists(PATH_TEMP . $sArchivo)) {
                    $presignedUrl = BASE_URL . 'temp/' . $sArchivo;
                } else if (file_exists(PATHFILES . $sArchivo)) {
                    if (copy(PATHFILES . $sArchivo, PATH_TEMP . $sArchivo)) {
                        $presignedUrl = BASE_URL . 'temp/' . $sArchivo;
                    }
                }
            }
        }

        return $presignedUrl;
    }
}
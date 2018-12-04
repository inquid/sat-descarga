<?php

namespace inquid\yii_sat;

/**
 * Created by PhpStorm.
 * User: gogl92
 * Date: 12/3/18
 * Time: 11:12 PM
 */
error_reporting(1);
ini_set('display_errors', 1);

class DownloadHandler extends \yii\base\Component
{
    public $downloadPath = 'files/';
    public $downloadMaxSimultaneous = 10;
    public $descargaCfdi = null;
    public $cerFile;
    public $keyFile;
    public $password;

    public function init()
    {
        parent::init();
        $this->descargaCfdi = new DescargaMasivaCfdi();
    }

    public function login()
    {
        $certificado = new UtilCertificado();
        $ok = $certificado->loadFiles(
            $this->cerFile,
            $this->keyFile,
            $this->password
        );
        if ($ok) {
            // iniciar sesion en el SAT
            $ok = $this->descargaCfdi->iniciarSesionFiel($certificado);
            if ($ok) {
                echo json_response(array(
                    'mensaje' => 'Se ha iniciado la sesión',
                    'sesion' => $this->descargaCfdi->obtenerSesion()
                ));
            } else {
                echo json_response(array(
                    'mensaje' => 'Ha ocurrido un error al iniciar sesión. Intente nuevamente',
                ));
            }
        } else {
            echo json_response(array(
                'mensaje' => 'Verifique que los archivos corresponden con la contraseña e intente nuevamente',
            ));
        }
    }


}
<?php

namespace inquid\yii_sat;

use Yii;
use inquid\yii_sat\DescargaMasivaCfdi;

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
    public $session = null;

    public function init()
    {
        parent::init();
        $this->descargaCfdi = new DescargaMasivaCfdi();
        $this->session = Yii::$app->session;
    }

    public function login()
    {
        if ($this->session->get('session_sat') != null) {
            return $this->session->get('session_sat');
        }
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
                $this->session->set('session_sat', $this->descargaCfdi->obtenerSesion());
                return [
                    'mensaje' => 'Se ha iniciado la sesión',
                    'sesion' => $this->descargaCfdi->obtenerSesion()
                ];
            } else {
                $this->session->set('session_sat', null);
                return null;
            }
        } else {
            $this->session->set('session_sat', null);
            return null;
        }
        $this->session->set('session_sat', null);
        return null;
    }

}
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
                $filtros = new BusquedaRecibidos();
                $filtros->establecerFecha(2018, 10);
                $xmlInfoArr = $this->descargaCfdi->buscar($filtros);
                print_r($xmlInfoArr);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
        return false;
    }

    public function buscarRecibidos($anio, $mes, $dia = null)
    {
        $filtros = new BusquedaRecibidos();
        $filtros->establecerFecha(2018, 10);

        $xmlInfoArr = $this->descargaCfdi->buscar($filtros);
        if ($xmlInfoArr) {
            $items = array();
            foreach ($xmlInfoArr as $xmlInfo) {
                $items[] = (array)$xmlInfo;
            }
            return $items;
        } else {
            return ['dsaksldñlas2'];
        }
        return ['dsaksldñlas'];
    }

    public function buscarEmitidos($anio_i, $mes_i, $dia_i, $anio_f, $mes_f, $dia_f)
    {
        $filtros = new BusquedaEmitidos();
        $filtros->establecerFechaInicial($anio_i, $mes_i, $dia_i);
        $filtros->establecerFechaFinal($anio_f, $mes_f, $dia_f);

        $xmlInfoArr = $this->descargaCfdi->buscar($filtros);
        if ($xmlInfoArr) {
            $items = array();
            foreach ($xmlInfoArr as $xmlInfo) {
                $items[] = (array)$xmlInfo;
            }
            return array(
                'items' => $items,
                'sesion' => $descargaCfdi->obtenerSesion()
            );
        } else {
            return array(
                'mensaje' => 'No se han encontrado CFDIs',
                'sesion' => $descargaCfdi->obtenerSesion()
            );
        }
    }

}
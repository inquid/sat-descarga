<?php

namespace inquid\SatDownload;

class BusquedaRecibidos
{
    public const ESTADO_TODOS = 1;
    public const ESTADO_VIGENTE = 2;
    public const ESTADO_CANCELADO_CA = 3; // Cancelado con aceptación
    public const ESTADO_CANCELADO_SA = 4; // Cancelado sin aceptación
    public const ESTADO_CANCELADO_PV = 5; // Plazo vencido

    protected $anio;
    protected $mes;
    protected $dia = '0';
    protected $hora_inicial = '0';
    protected $minuto_inicial = '0';
    protected $segundo_inicial = '0';
    protected $hora_final = '23';
    protected $minuto_final = '59';
    protected $segundo_final = '59';
    protected $rfc_emisor = '';
    protected $folio_fiscal = '';
    protected $tipo = '-1';
    protected $tipoBusqueda = 'fecha';
    protected $estado = self::ESTADO_TODOS;


    public function __construct()
    {
        $this->anio = date('Y');
        $this->mes = date('n');
    }

    /**
     * Permite indicar el estado de los CFDI a buscar
     * @param string $estado
     */
    public function establecerEstado($estado)
    {
        $this->estado = (int)$estado;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite indicar la fecha de búsqueda
     * @param int $anio año a 4 dígitos
     * @param int $mes mes del 1 a 12
     * @param int $dia día del 1 al 31. Si no se especifica,
     * no se tomará en cuenta el día al hacer la búsqueda
     */
    public function establecerFecha($anio, $mes, $dia = null)
    {
        $this->anio = (string)$anio;
        $this->mes = ltrim((string)$mes, '0');
        if ($dia == null) {
            $this->dia = '0';
        } else {
            $this->dia = str_pad($dia, 2, '0', STR_PAD_LEFT);
        }
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite indicar la hora inicial de búsqueda
     * @param int $hora hora en formato de 24 horas (0-23)
     * @param int $minuto minuto del 0 al 59
     * @param int $segundo segundo del 0 al 59
     */
    public function establecerHoraInicial($hora = '0', $minuto = '0', $segundo = '0')
    {
        $this->hora_inicial = (string)$hora;
        $this->minuto_inicial = (string)$minuto;
        $this->segundo_inicial = (string)$segundo;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite indicar la hora final de búsqueda
     * @param int $hora hora en formato de 24 horas (0-23)
     * @param int $minuto minuto del 0 al 59
     * @param int $segundo segundo del 0 al 59
     */
    public function establecerHoraFinal($hora = '23', $minuto = '59', $segundo = '59')
    {
        $this->hora_final = (string)$hora;
        $this->minuto_final = (string)$minuto;
        $this->segundo_final = (string)$segundo;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite establecer el RFC del emisor
     * @param string $rfc RFC del emisor
     */
    public function establecerRfcEmisor($rfc)
    {
        $this->rfc_emisor = $rfc;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite establecer el UUID
     * @param string $uuid el UUID
     */
    public function establecerFolioFiscal($uuid)
    {
        $this->folio_fiscal = $uuid;
        $this->setTipoBusqueda('folio');
    }

    public function obtenerFormulario()
    {
        switch ($this->estado) {
            case self::ESTADO_VIGENTE:
                $estadoStr = '1';
                $canceladoStr = '0';
                break;
            case self::ESTADO_CANCELADO_SA:
                $estadoStr = '0';
                $canceladoStr = '3';
                break;
            case self::ESTADO_CANCELADO_CA:
                $estadoStr = '0';
                $canceladoStr = '2';
                break;
            case self::ESTADO_CANCELADO_PV:
                $estadoStr = '0';
                $canceladoStr = '5';
                break;
            case self::ESTADO_TODOS:
            default:
                $estadoStr = '-1';
                $canceladoStr = '0';
                break;
        }

        return array(
            '__ASYNCPOST' => 'true',
            '__EVENTARGUMENT' => '',
            '__EVENTTARGET' => '',
            '__LASTFOCUS' => '',
            'ctl00$MainContent$BtnBusqueda' => 'Buscar CFDI',
            'ctl00$MainContent$CldFecha$DdlAnio' => $this->anio,
            'ctl00$MainContent$CldFecha$DdlDia' => $this->dia,
            'ctl00$MainContent$CldFecha$DdlHora' => $this->hora_inicial,
            'ctl00$MainContent$CldFecha$DdlHoraFin' => $this->hora_final,
            'ctl00$MainContent$CldFecha$DdlMes' => $this->mes,
            'ctl00$MainContent$CldFecha$DdlMinuto' => $this->minuto_inicial,
            'ctl00$MainContent$CldFecha$DdlMinutoFin' => $this->minuto_final,
            'ctl00$MainContent$CldFecha$DdlSegundo' => $this->segundo_inicial,
            'ctl00$MainContent$CldFecha$DdlSegundoFin' => $this->segundo_final,
            'ctl00$MainContent$DdlEstadoComprobante' => $estadoStr,
            'ctl00$MainContent$ddlVigente' => '0',
            'ctl00$MainContent$ddlCancelado' => $canceladoStr,
            'ctl00$MainContent$TxtRfcReceptor' => $this->rfc_emisor,
            'ctl00$MainContent$TxtUUID' => $this->folio_fiscal,
            'ctl00$MainContent$ddlComplementos' => $this->tipo,
            'ctl00$MainContent$hfInicialBool' => 'false',
            'ctl00$ScriptManager1' =>
                'ctl00$MainContent$UpnlBusqueda|ctl00$MainContent$BtnBusqueda',
            'ctl00$MainContent$FiltroCentral' =>
                ($this->tipoBusqueda == 'fecha') ? 'RdoFechas' : 'RdoFolioFiscal'
        );
    }

    public function obtenerFormularioAjax($post, $fuente)
    {
        $valores = explode('|', $fuente);
        $validos = array(
            '__EVENTTARGET',
            '__EVENTARGUMENT',
            '__LASTFOCUS',
            '__VIEWSTATE'
        );
        $items = array();
        foreach ($valores as $i => $iValue) {
            $item = $iValue;
            if (in_array($item, $validos)) {
                $items[$item] = $valores[$i + 1];
            }
        }

        return array_merge(
            array_merge($post, $this->obtenerFormulario()),
            $items
        );
    }

    protected function setTipoBusqueda($tipo)
    {
        $this->tipoBusqueda = $tipo;
        if ($tipo == 'fecha') {
            $this->folio_fiscal = '';
        }
    }
}

<?php
namespace inquid\SatDownload;

class BusquedaEmitidos {
    const ESTADO_TODOS        = 1;
    const ESTADO_VIGENTE      = 2;
    const ESTADO_CANCELADO_CA = 3; // Cancelado con aceptación
    const ESTADO_CANCELADO_SA = 4; // Cancelado sin aceptación
    const ESTADO_CANCELADO_PV = 5; // Plazo vencido

    private $fecha_inicial;
    private $hora_inicial    =  '0';
    private $minuto_inicial  =  '0';
    private $segundo_inicial =  '0';
    private $fecha_final;
    private $hora_final      = '23';
    private $minuto_final    = '59';
    private $segundo_final   = '59';
    private $rfc_receptor    = '';
    private $folio_fiscal    = '';
    private $tipo            = '-1';
    private $tipoBusqueda    = 'fecha';
    private $estado          = self::ESTADO_TODOS;


    public function __construct() {
        $this->hfInicial = date('Y');
        $this->hfFinal = date('n');
    }

    /**
     * Permite indicar el estado de los CFDI a buscar
     * @param string $estado
     */
    public function establecerEstado($estado){
        $this->estado = (int)$estado;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite indicar la fecha inicial de búsqueda
     * @param int $anio año a 4 dígitos
     * @param int $mes mes del 1 a 12
     * @param int $dia día del 1 al 31
     */
    public function establecerFechaInicial($anio, $mes, $dia){
        $this->hfInicial = (string)$anio;
        $this->fecha_inicial = 
            str_pad($dia, 2, '0', STR_PAD_LEFT) . '/' .
            str_pad($mes, 2, '0', STR_PAD_LEFT) . '/' .
            (string)$anio;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite indicar la fecha final de búsqueda
     * @param int $anio año a 4 dígitos
     * @param int $mes mes del 1 a 12
     * @param int $dia día del 1 al 31
     */
    public function establecerFechaFinal($anio, $mes, $dia){
        $this->hfFinal = (string)$anio;
        $this->fecha_final =
            str_pad($dia, 2, '0', STR_PAD_LEFT) . '/' .
            str_pad($mes, 2, '0', STR_PAD_LEFT) . '/' .
            (string)$anio;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite indicar la hora inicial de búsqueda
     * @param int $hora hora en formato de 24 horas (0-23)
     * @param int $minuto minuto del 0 al 59
     * @param int $segundo segundo del 0 al 59
     */
    public function establecerHoraInicial($hora='0', $minuto='0', $segundo='0'){
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
    public function establecerHoraFinal($hora='23', $minuto='59', $segundo='59'){
        $this->hora_final = (string)$hora;
        $this->minuto_final = (string)$minuto;
        $this->segundo_final = (string)$segundo;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite establecer el RFC del receptor
     * @param string $rfc RFC del emisor
     */
    public function establecerRfcReceptor($rfc){
        $this->rfc_receptor = $rfc;
        $this->setTipoBusqueda('fecha');
    }

    /**
     * Permite establecer el UUID
     * @param string $uuid el UUID
     */
    public function establecerFolioFiscal($uuid){
        $this->folio_fiscal = $uuid;
        $this->setTipoBusqueda('folio');
    }

    public function obtenerFormulario(){
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
            '__ASYNCPOST' =>'true',
            '__EVENTARGUMENT' => '',
            '__EVENTTARGET' => '',
            '__LASTFOCUS' => '',
            'ctl00$MainContent$BtnBusqueda' => 'Buscar CFDI',
            'ctl00$MainContent$hfInicial' => $this->hfInicial,
            'ctl00$MainContent$hfInicialBool' => 'false',
            'ctl00$MainContent$CldFechaInicial2$Calendario_text' => $this->fecha_inicial,
            'ctl00$MainContent$CldFechaInicial2$DdlHora' => $this->hora_inicial,
            'ctl00$MainContent$CldFechaInicial2$DdlMinuto' => $this->minuto_inicial,
            'ctl00$MainContent$CldFechaInicial2$DdlSegundo' => $this->segundo_inicial,
            'ctl00$MainContent$hfFinal' => $this->hfFinal,
            'ctl00$MainContent$hfFinalBool' => 'false',
            'ctl00$MainContent$CldFechaFinal2$Calendario_text' => $this->fecha_final,
            'ctl00$MainContent$CldFechaFinal2$DdlHora' => $this->hora_final,
            'ctl00$MainContent$CldFechaFinal2$DdlMinuto' => $this->minuto_final,
            'ctl00$MainContent$CldFechaFinal2$DdlSegundo' => $this->segundo_final,
            'ctl00$MainContent$DdlEstadoComprobante' => $estadoStr,
            'ctl00$MainContent$ddlVigente' => '0',
            'ctl00$MainContent$ddlCancelado' => $canceladoStr,
            'ctl00$MainContent$TxtRfcReceptor' => $this->rfc_receptor,
            'ctl00$MainContent$TxtUUID' => $this->folio_fiscal,
            'ctl00$MainContent$ddlComplementos' => $this->tipo,
            'ctl00$ScriptManager1' =>
                'ctl00$MainContent$UpnlBusqueda|ctl00$MainContent$BtnBusqueda',
            'ctl00$MainContent$FiltroCentral' =>
                ($this->tipoBusqueda == 'fecha') ? 'RdoFechas' : 'RdoFolioFiscal'
        );
    }

    public function obtenerFormularioAjax($post, $fuente){
        $valores = explode('|', $fuente);
        $validos = array(
            'EVENTTARGET',
            '__EVENTARGUMENT',
            '__LASTFOCUS',
            '__VIEWSTATE'
        );
        $valCount = count($valores);
        $items = array();
        for ($i=0; $i < $valCount; $i++) { 
            $item = $valores[$i];
            if(in_array($item, $validos)){
                $items[$item] = $valores[$i+1];
            }
        }

        return array_merge(
            array_merge(
                $post,
                $this->obtenerFormulario()
            ),
            $items
        );
    }

    private function setTipoBusqueda($tipo){
        $this->tipoBusqueda = $tipo;
        if($tipo == 'fecha'){
            $this->folio_fiscal = '';
        }
    }
}

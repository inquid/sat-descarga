<?php
namespace inquid\SatDownload;

use DOMDocument;
use DOMXPath;
/**
 * Libreria para descarga masiva de XMLs desde el SAT
 * Version: 9.4
 * Fecha Actualización: 5 NOVIEMBRE 2018
 */


class DescargaMasivaCfdi {
    const URL_CFDIAU = 'https://cfdiau.sat.gob.mx/nidp/app';
    const URL_PORTAL_CFDI = 'https://portalcfdi.facturaelectronica.sat.gob.mx/';
    const HEADER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';


    public function __construct() {
        // ocultar "Warnings" por errores de HTML en las paginas del SAT
        libxml_use_internal_errors(true);
        RespuestaCurl::reset();
    }

    /**
     * Realiza en inicio de sesión en el portal del SAT
     * mediante la FIEL
     * @param UtilCertificado $certificado objeto con informacion
     * del certificado FIEL
     * @return boolean resultado del inicio de sesion
     */
    public function iniciarSesionFiel($cert){
        $url = 'https://cfdiau.sat.gob.mx/nidp/app/login?id=SATx509Custom&sid=0&option=credential&sid=0';
        $headers = array(
            'User-Agent' => self::HEADER_USER_AGENT,
            'Referer' => self::URL_CFDIAU,
        );

        // 1
        $respuesta = RespuestaCurl::request(self::URL_PORTAL_CFDI);
        if($respuesta->getStatusCode() != 200){
            return false;
        }

        // 2
        $respuesta = RespuestaCurl::request($url, null, $headers);
        if($respuesta->getStatusCode() != 200){
            return false;
        }

        // 3
        $document = new DOMDocument();
        $document->loadHTML( $respuesta->getBody() );
        if(!$document) {
            return false;
        }
        $post = array();
        $form = $document->getElementById('certform');
        foreach (array('input','select') as $element) {
            foreach ($form->getElementsByTagName($element) as $val) {
                $name = $val->getAttribute('name');
                if(!empty($name)){
                    $post[$name] = utf8_decode($val->getAttribute('value'));
                }
            }
        }
        if(!$post) {
            return false;
        }
        $guid = $post['guid'];
        $serie = $cert->getNumeroCertificado();
        $rfc = $cert->getRFC();
        $validez = $cert->getRangoValidez();
        $co = $guid . '|' . $rfc . '|' . $serie;
        $laFirma = base64_encode($cert->firmarCadena($co, OPENSSL_ALGO_SHA1));
        $token = base64_encode(base64_encode($co) . '#' . $laFirma);
        $post['token'] = $token;
        $post['fert'] = gmdate('ymdHis', $validez['to']).'Z';
        $respuesta = RespuestaCurl::request($url, $post, $headers);
        if($respuesta->getStatusCode() != 200){
            return false;
        }

        // 4
        $post = $this->getFormData( $respuesta->getBody() );
        if(!$post) {
            return false;
        }
        $respuesta = RespuestaCurl::request(self::URL_PORTAL_CFDI, $post, $headers);
        if($respuesta->getStatusCode() != 200){
            return false;
        }

        // 5
        $post = $this->getFormData( $respuesta->getBody() );
        if(!$post) {
            return false;
        }
        $respuesta = RespuestaCurl::request(self::URL_PORTAL_CFDI, $post, $headers);
        if($respuesta->getStatusCode() != 200){
            return false;
        }

        // 6
        $respuesta = RespuestaCurl::request(self::URL_PORTAL_CFDI);
        if($respuesta->getStatusCode() != 200){
            return false;
        }elseif(strpos($respuesta->getBody(), $rfc) === false){
            return false;
        }else{
            return true;
        }
    }

    /**
     * Realiza en inicio de sesión en el portal del SAT
     * mediante la CIEC con Captcha
     * @param string $rfc RFC
     * @param string $contrasena Contraseña
     * @param string $captcha caracteres del captcha
     * @return boolean resultado del inicio de sesion
     */
    public function iniciarSesionCiecCaptcha($rfc, $contrasena, $captcha){
        $rfc = strtoupper($rfc);

        // 1
        $respuesta = RespuestaCurl::request(
            'https://cfdiau.sat.gob.mx/nidp/wsfed/ep?id=SATUPCFDiCon&sid=0&option=credential&sid=0',
            array(
                'option'=>'credential',
                'Ecom_User_ID'=>$rfc,
                'Ecom_Password'=>$contrasena,
                'jcaptcha'=>$captcha,
                'submit'=>'Enviar'
            )
        );
        if($respuesta->getStatusCode() != 200 || !$respuesta->getBody()){
            return false;
        }

        // 2
        $respuesta = RespuestaCurl::request('https://cfdiau.sat.gob.mx/nidp/wsfed/ep?sid=0');
        if($respuesta->getStatusCode() != 200 || !$respuesta->getBody()){
            return false;
        }
        $post = $this->getFormData( $respuesta->getBody() );
        if(!$post) {
            return false;
        }

        // 3
        $respuesta = RespuestaCurl::request(self::URL_PORTAL_CFDI, $post);
        if($respuesta->getStatusCode() != 200){
            return false;
        }
        $post = $this->getFormData( $respuesta->getBody() );
        if(!$post) {
            return false;
        }

        // 4
        $respuesta = RespuestaCurl::request(self::URL_PORTAL_CFDI, $post);
        if($respuesta->getStatusCode() != 200){
            return false;
        }

        // 5
        $respuesta = RespuestaCurl::request(self::URL_PORTAL_CFDI);
        if($respuesta->getStatusCode() != 200){
            return false;
        }elseif(strpos($respuesta->getBody(), $rfc) === false){
            return false;
        }else{
            return true;
        }
    }

    /**
     * Obtiene la imagen del captcha requerido para
     * el inicio de sesión con CIEC/Captcha
     * @return string contenido de la imagen del captcha en Base 64
     */
    public function obtenerCaptcha() {
        // 1
        $respuesta = RespuestaCurl::request('https://portalcfdi.facturaelectronica.sat.gob.mx');
        if($respuesta->getStatusCode() != 200 || !$respuesta->getBody()){
            return false;
        }

        // 2
        $respuesta = RespuestaCurl::request('https://cfdiau.sat.gob.mx/nidp/wsfed/ep?id=SATUPCFDiCon&sid=0&option=credential&sid=0');
        if($respuesta->getStatusCode() != 200){
            return false;
        }

        // 3
        $respuesta = RespuestaCurl::request('https://cfdiau.sat.gob.mx/nidp/jcaptcha.jpg');
        if($respuesta->getStatusCode() != 200){
            return false;
        }

        return base64_encode($respuesta->getBody());
    }

    /**
     * Permite obtener los CFDI emitidos/recibidos utilizando
     * las opciones que ofrece el portal del SAT
     * @param object $filtros configuración de los filtros a utilizar
     * @return array objetos XmlInfo de los XML encontrados
     */
    public function buscar($filtros) {
        if(get_class($filtros) == 'inquid\yii_sat\BusquedaEmitidos') {
            $url = 'https://portalcfdi.facturaelectronica.sat.gob.mx/ConsultaEmisor.aspx';
            $modulo = 'emitidos';
        }elseif(get_class($filtros) == 'inquid\yii_sat\BusquedaRecibidos') {
            $url = 'https://portalcfdi.facturaelectronica.sat.gob.mx/ConsultaReceptor.aspx';
            $modulo = 'recibidos';
        }else{
            return false;
        }

        $respuesta = RespuestaCurl::request($url);
        $html = $respuesta->getBody();
        $reqOk = $respuesta->getStatusCode() == 200;
        $post = $this->obtenerDatosFormHtml($html);
        if(!$post){
            return false;
        }

        $encabezados = array(
            'User-Agent' => self::HEADER_USER_AGENT,
            'Referer' => self::URL_PORTAL_CFDI,
            'X-MicrosoftAjax' => 'Delta=true',
            'X-Requested-With' => 'XMLHttpRequest',
        );
        $respuesta = RespuestaCurl::request($url, $post, $encabezados);
        $html = $respuesta->getBody();
        $post = $filtros->obtenerFormularioAjax($post, $html);
        $respuesta = RespuestaCurl::request($url, $post, $encabezados);
        $html = $respuesta->getBody();
        $objects = $this->getXmlObjects($html, $modulo);

        return empty($objects)
            ? null
            : $objects;
    }


    /**
     * Devuelve el XML del CFDI como string
     * @param string $url del XML
     * @return string datos del XML, o NULL
     */
    public function obtenerXml($url){
        if(!empty($url)) {
            $xml = $this->obtenerArchivoString($url);
            if(!empty($xml)) {
                return $xml;
            }
        }

        return null;
    }

    /**
     * Guarda el XML del CFDI en la ruta especificada
     * @param string $url del XML
     * @param string $dir ubicación del archivo
     * @param string $nombre nombre del archivo (sin extensión)
     */
    public function guardarXml($url, $dir, $nombre){
        if(empty($url)) {
            return false;
        }

        $resource = fopen($dir.DIRECTORY_SEPARATOR.$nombre.'.xml', 'w');

        $saved = false;
        $str = $this->obtenerArchivoString($url);
        if(!empty($str)) {
            $bytes = fwrite($resource, $str);
            $saved = ($bytes !== false);
            fclose($resource);
        }

        return $saved;
    }

    /**
     * Guarda el acuse de cancelación de un XML en la ruta especificada
     * @param string $url del acuse
     * @param string $dir ubicación del archivo
     * @param string $nombre nombre del archivo sin, incluir extensión
     */
    public function guardarAcuse($url, $dir, $nombre){
        if(empty($url)) {
            return false;
        }

        $resource = fopen($dir.DIRECTORY_SEPARATOR.$nombre.'.pdf', 'w');

        $saved = false;
        $str = $this->obtenerArchivoString($url);
        if(!empty($str)) {
            $bytes = fwrite($resource, $str);
            $saved = ($bytes !== false);
            fclose($resource);
        }

        return $saved;
    }

    /**
     * Obtiene los datos de la sesión actual
     * @return string datos de la sesion actual
     */
    public function obtenerSesion(){
        return base64_encode(
            json_encode(RespuestaCurl::getCookie())
        );
    }

    /**
     * Restaura una sesion previa
     * @param string $sesion datos de una sesion anterior
     */
    public function restaurarSesion($sesion){
        if(!empty($sesion)) {
            return RespuestaCurl::setCookie(
                json_decode(base64_decode($sesion), true)
            );
        }
        return false;
    }

    private function getXmlObjects($html, $modulo){
        $document = new DOMDocument();
        $document->loadHTML($html);
        if(!$document) return null;
        $xp = new DOMXPath($document);
        $trs = $xp->query('//table[@id="ctl00_MainContent_tblResult"]/tr');
        if(!$trs) return null;
        $xmls = array();
        foreach ($trs as $i => $trElement) {
            if($i == 0) continue;
            if($xml = XmlInfo::fromHtmlElement($xp, $trElement, $modulo)){
                $xmls[] = $xml;
            }
        }
        return $xmls;
    }

    private function getFormData($html){
        $document = new DOMDocument();
        $document->loadHTML($html);
        if(!$document) return null;
        $form = $document->getElementsByTagName('form')->item(0);
        if(!$form) return null;
        $post = array();
        foreach (array('input','select') as $element) {
            foreach ($form->getElementsByTagName($element) as $val) {
                $name = $val->getAttribute('name');
                if(!empty($name)){
                    $post[$name] = utf8_decode($val->getAttribute('value'));
                }
            }
        }
        return $post;
    }

    private function obtenerDatosFormHtml($html){
        $post = $this->getFormData($html);
        if(!empty($post)) {
            unset(
                $post['seleccionador'],
                $post['ctl00$MainContent$BtnDescargar'],
                $post['ctl00$MainContent$BtnCancelar'],
                $post['ctl00$MainContent$BtnImprimir'],
                $post['ctl00$MainContent$BtnMetadata'],
                $post['ctl00$MainContent$Captcha$btnCaptcha'],
                $post['ctl00$MainContent$Captcha$btnRefrescar'],
                $post['ctl00$MainContent$Captcha$Cancela']
            );
            return $post;
        }

        return null;
    }

    private function obtenerArchivoString($url){
        if(empty($url)) return false;

        $respuesta = RespuestaCurl::request($url, null, null);
        if($respuesta->getStatusCode() == 200) {
            return $respuesta->getBody();
        }else{
            return null;
        }
    }
}



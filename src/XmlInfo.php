<?php
namespace inquid\SatDownload;

# actualización 14 noviembre 2018

class XmlInfo {
    public $urlDescargaXml;
    public $urlDescargaAcuse;
    public $folioFiscal;
    public $emisorRfc;
    public $emisorNombre;
    public $receptorRfc;
    public $receptorNombre;
    public $fechaEmision;
    public $fechaCertificacion;
    public $pacCertifico;
    public $total;
    public $efecto;
    public $estado;
    public $estadoCancelacion;
    public $estadoProcesoCancelacion;
    public $fechaCancelacion;

    /**
     * @deprecated 3.0.0 Utilice la variable $urlDescargaAcuse
     */
    public $urlAcuseXml;


    public function esVigente(){
        return $this->estado === 'Vigente';
    }
    public function esCancelado(){
        return $this->estado === 'Cancelado';
    }

    public static function fromHtmlElement($xpath, $trElement, $modulo){
        if($trElement && $trElement->childNodes->length == 0) {
            return null;
        }

        $xml = new self;

        $index = 0;
        foreach ($trElement->childNodes as $node) {
            if($node->nodeName != 'td') {
                continue;
            }
            if($index == 0) {
                if($nodeSpan = $xpath->query('*//span[@id="BtnDescarga"]', $node)->item(0)) {
                    $xml->urlDescargaXml = DescargaMasivaCfdi::URL_PORTAL_CFDI . str_replace(
                        array('return AccionCfdi(\'','\',\'Recuperacion\');'),
                        '',
                        $nodeSpan->getAttribute('onclick')
                    );
                }
                if($nodeSpan = $xpath->query('*//span[@id="BtnRecuperaAcuse"]', $node)->item(0)) {
                    $xml->urlDescargaAcuse = DescargaMasivaCfdi::URL_PORTAL_CFDI . str_replace(
                        array('AccionCfdi(\'','\',\'Acuse\');'),
                        '',
                        $nodeSpan->getAttribute('onclick')
                    );
                }
            }else{
                $value = utf8_decode($node->nodeValue);
                switch ($index) {
                    case  1: $xml->folioFiscal = $value; break;
                    case  2: $xml->emisorRfc = $value; break;
                    case  3: $xml->emisorNombre = $value; break;
                    case  4: $xml->receptorRfc = $value; break;
                    case  5: $xml->receptorNombre = $value; break;
                    case  6: $xml->fechaEmision = $value; break;
                    case  7: $xml->fechaCertificacion = $value; break;
                    case  8: $xml->pacCertifico = $value; break;
                    case  9: $xml->total = $value; break;
                    case 10: $xml->efecto = $value; break;
                    case 11: $xml->estadoCancelacion = $value; break;
                    case 12: $xml->estado = $value; break;
                    case 13: $xml->estadoProcesoCancelacion = $value; break;
                    case 14: $xml->fechaCancelacion = $value; break;
                }
            }
            $index++;
        }

        return $xml;
    }
}

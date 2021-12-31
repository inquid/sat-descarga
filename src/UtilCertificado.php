<?php
namespace inquid\SatDownload;

/**
 * Clase para trabajar con certificados.
 */

class UtilCertificado {
    private $cerFileContent;
    private $keyFileContent;
    private $keyPemFileContent;
    private $keyPassword;
    private static $openSslFile;


    public function loadFiles($cerFile, $keyFile, $keyPassword){
        if($cerFile && $keyFile && $keyPassword){
            if(file_exists($cerFile) && file_exists($keyFile)){
                $cerFileContent = file_get_contents($cerFile);
                if(!empty($cerFileContent)){
                    $this->cerFileContent = $cerFileContent;
                    $this->keyFileContent = file_get_contents($keyFile);
                    $this->keyPemFileContent = $this->getKeyPemContent(
                        $keyFile,
                        $keyPassword
                    );
                    if($this->keyPemFileContent){
                        $this->keyPassword = $keyPassword;
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function loadData($cerFileContent, $keyPemFileContent){
        $this->cerFileContent = $cerFileContent;
        $this->keyPemFileContent = $keyPemFileContent;
        return true;
    }

    public static function establecerRutaOpenSSL($ruta=null) {
        if($ruta) {
            // utilizar la ruta especificada

            self::$openSslFile = $ruta;
            if(file_exists(self::$openSslFile)) return;

            throw new Exception('Ruta OpenSSL no válida', 2);
        }else{
            // intentar obtener la ruta automáticamente según el SO

            $os = strtoupper(php_uname('s'));
            if(strpos($os, 'WIN') === 0) { // windows
                self::$openSslFile = 'C:\OpenSSL-Win64\bin\openssl.exe';
                if(file_exists(self::$openSslFile)) return;

                self::$openSslFile = 'C:\OpenSSL-Win32\bin\openssl.exe';
                if(file_exists(self::$openSslFile)) return;
            }else{ // unix
                self::$openSslFile = '/usr/bin/openssl';
                if(file_exists(self::$openSslFile)) return;
            }

            throw new Exception('Ruta OpenSSL no configurada', 1);
        }
    }

    public function firmarCadena($cadena, $algo){
        $resultado = null;
        $pKeyId = openssl_pkey_get_private($this->keyPemFileContent);
        $signOk = @openssl_sign(
            $cadena,
            $resultado,
            $pKeyId,
            $algo
        );

        if($signOk){
            return base64_encode($resultado);
        }

        return null;
    }

    public function getRFC(){
        $d = openssl_x509_parse(
            $this->getCerPemContent(),
            true
        );

        if($d) {
            $rfcs = explode(
                '/',
                str_replace(' ', '', $d['subject']['x500UniqueIdentifier'])
            );
            return strtoupper($rfcs[0]);
        }

        return null;
    }

    public function getRangoValidez(){
        $d = openssl_x509_parse(
            $this->getCerPemContent(),
            true
        );

        if($d) {
            return array(
                'from' => $d['validFrom_time_t'],
                'to'   => $d['validTo_time_t']
            );
        }

        return null;
    }

    public function getNombre(){
        $d = openssl_x509_parse(
            $this->getCerPemContent(),
            true
        );        

        if($d && !empty($d['subject']['CN'])) {
            return $d['subject']['CN'];
        }

        return null;
    }

    public function getNumeroCertificado(){
        $d = openssl_x509_parse(
            $this->getCerPemContent(),
            true
        );

        if($d && !empty($d['serialNumberHex'])) {
            $hex = $d['serialNumberHex'];
            $num = '';
            for($i=0;$i<strlen($hex);$i+=2) {
                $num .= chr(hexdec(substr($hex, $i, 2)));
            }
            return $num;
        }

        if($d && !empty($d['serialNumber'])) {
            $number = $d['serialNumber'];
            $hexvalues = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
            $hexval = '';
            while($number != '0'){
                $hexval = $hexvalues[bcmod($number,'16')].$hexval;
                $number = bcdiv($number,'16',0);
            }
            $number = '';
            $len = strlen($hexval);
            for($i=0; $i<$len;$i+=2){
                $number .=  substr($hexval, $i+1, 1);
            }

            return $number;
        }

        return null;
    }

    public function getTipoCertificado(){
        $d = openssl_x509_parse(
            $this->getCerPemContent(),
            true
        );

        if($d) {
            $keyUsage = explode(',', str_replace(' ', '', $d['extensions']['keyUsage']));
            $count = count($keyUsage);
            if($count > 0 && in_array('DigitalSignature',$keyUsage) && in_array('NonRepudiation',$keyUsage)){
                if($count == 2){
                    return 'CSD';
                }elseif($count == 4){
                    return 'FIEL';
                }
            }
        }

        return null;
    }

    public function getCerPemContent(){
        return self::der2pem($this->cerFileContent);
    }

    public function getKeyPemFileContent() {
        return $this->keyPemFileContent;
    }

    public function getKeyPassword() {
        return $this->keyPassword;
    }

    public function getCerFileContent() {
        return $this->cerFileContent;
    }

    public function getKeyFileContent() {
        return $this->keyFileContent;
    }

    private function getKeyPemContent($keyFile, $keyPwd){
        if(!self::$openSslFile) {
            self::establecerRutaOpenSSL();
        }

        $cmd = self::$openSslFile.' pkcs8 -inform DER -in '.escapeshellarg($keyFile).' -passin pass:'.escapeshellarg($keyPwd);
        $res = shell_exec($cmd);
        if(!empty($res)){
            return $res;
        }

        return null;
    }

    private static function der2pem($der_data) {
        return '-----BEGIN CERTIFICATE-----'.PHP_EOL
            .chunk_split(base64_encode($der_data), 64, PHP_EOL)
            .'-----END CERTIFICATE-----'.PHP_EOL;
    }

    public function toBase64(){
        return str_replace(
            array('\n', '\r'),
            '',
            base64_encode($this->cerFileContent)
        );
    }
}

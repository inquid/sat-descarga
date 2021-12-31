<?php
namespace inquid\SatDownload;

class DescargaAsincrona {
    private $resultados;
    private $totalOk;
    private $totalErr;
    private $timeSec;
    private $mc;

    public function __construct($maxSimultaneos=10) {
        $this->mc = new MultiCurl($maxSimultaneos);

        $opts = RespuestaCurl::$defaultOptions;
        $opts[CURLOPT_COOKIE] = RespuestaCurl::getCookieString();
        $opts[CURLOPT_CUSTOMREQUEST] = 'GET';
        $this->mc->setOptions($opts);

        $this->mc->setCallback(function($url, $response, $user_data) {
            $ok = $this->guardarArchivo(
                $response,
                $user_data['dir'],
                $user_data['fn'],
                $user_data['ext']
            );
            $this->resultados[] = array(
                'uuid' => $user_data['uuid'],
                'guardado' => $ok
            );
            if($ok) {
                $this->totalOk++;
            }else{
                $this->totalErr++;
            }
        });
    }

    public function agregarXml($url, $dir, $uuid, $nombreArchivo=null) {
        $this->mc->addRequest($url, array(
            'ext'=>'xml',
            'dir'=>$dir,
            'uuid'=>$uuid,
            'fn'=>$nombreArchivo ? $nombreArchivo : $uuid
        ));
    }

    public function agregarAcuse($url, $dir, $uuid, $nombreArchivo=null) {
        $this->mc->addRequest($url, array(
            'ext'=>'pdf',
            'dir'=>$dir,
            'uuid'=>$uuid,
            'fn'=>$nombreArchivo ? $nombreArchivo : $uuid
        ));
    }

    public function procesar() {
        // restaurar valores
        $this->resultados = array();
        $this->totalOk = 0;
        $this->totalErr = 0;
        $this->timeSec = 0;

        $time = microtime(true);
        $this->mc->execute();
        $this->timeSec = microtime(true) - $time;
        $this->mc = null;
        return true;
    }

    public function totalDescargados() {
        return $this->totalOk;
    }

    public function totalErrores() {
        return $this->totalErr;
    }

    public function segundosTranscurridos() {
        return round($this->timeSec, 3);
    }

    public function resultado() {
        return $this->resultados;
    }

    /**
     * Updated
     * @param $str
     * @param $dir
     * @param $nombre
     * @param $ext
     * @return bool
     */
    private function guardarArchivo($str, $dir, $nombre, $ext) {
        $resource = fopen('/tmp/web/'.$dir.DIRECTORY_SEPARATOR.$nombre.'.'.$ext, 'wb');
        $saved = false;
        if(!empty($str)) {
            $bytes = fwrite($resource, $str);
            $saved = ($bytes !== false);
            fclose($resource);
        }
        return $saved;
    }
}

<?php
namespace inquid\SatDownload;

class RespuestaCurl {
    protected $respuesta;
    private static $cookie = array();
    public static $defaultOptions = array(
        CURLOPT_ENCODING       => "UTF-8",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_MAXREDIRS      => 10,
        CURLINFO_HEADER_OUT    => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1
    );


    public static function request($url, $post=null, $headers=null){
        $options = self::$defaultOptions;
        $options[CURLOPT_URL] = $url;

        if($cookie = self::getCookieString()){
            $options[CURLOPT_COOKIE] = $cookie;
        }

        if($post){
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = http_build_query($post);
            if(empty($headers)) $headers = array();
            $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        }else{
            $options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }

        if(!empty($headers)){
            $options[CURLOPT_HTTPHEADER] = array();
            foreach ($headers as $key => $value) {
                $options[CURLOPT_HTTPHEADER][] = $key.': '.$value;
            }
        }

        $ch = curl_init();
        curl_setopt_array( $ch, $options );

        $rawContent = curl_exec( $ch );
        $err        = curl_errno( $ch );
        $errmsg     = curl_error( $ch );
        $data       = curl_getinfo( $ch );
        $multi      = curl_multi_getcontent( $ch );
        curl_close( $ch );

        $headerContent = substr($rawContent, 0, $data['header_size']);
        $content = trim(str_replace($headerContent, '', $rawContent));

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerContent, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            $pos = strpos($item, '=');
            $cookies[ substr($item, 0, $pos) ] = substr($item, $pos+1);
        }
        self::$cookie = array_merge(self::$cookie, $cookies);

        // $data['errno']   = $err;
        // $data['errmsg']  = $errmsg;
        // $data['headers'] = $headerContent;
        $data['content'] = $content;
        $data['cookies'] = $cookies;

        $o = new self();
        $o->respuesta = $data;
        return $o;
    }

    public static function setCookie($cookie){
        self::$cookie = $cookie;
        return true;
    }

    public static function getCookie(){
        return self::$cookie;
    }

    public function getStatusCode(){
        return $this->respuesta['http_code'];
    }

    public function getBody(){
        return $this->respuesta['content'];
    }

    public static function getCookieString(){
        if(!empty(self::$cookie)){
            $str = '';
            foreach (self::$cookie as $key => $value) {
                $str .= $key.'='.$value.'; ';
            }
            $str = rtrim($str, '; ');
            return $str;
        }
        return '';
    }

    public static function reset() {
        self::$cookie = array();
    }
}

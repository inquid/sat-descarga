<?php

namespace inquid\SatDownload;

/**
 * MultiCurl class
 */
class MultiCurl
{
    protected $_curl_version;
    protected $_maxConcurrent = 0;    //max. number of simultaneous connections allowed
    protected $_options = array();   //shared cURL options
    protected $_headers = array();   //shared cURL request headers
    protected $_callback = null; //default callback
    protected $_timeout = 5000; //all requests must be completed by this time
    public $requests = array();   //request_queue


    function __construct($max_concurrent = 10)
    {
        $this->setMaxConcurrent($max_concurrent);
        $v = curl_version();
        $this->_curl_version = $v['version'];
    }

    public function setMaxConcurrent($max_requests)
    {
        if ($max_requests > 0) {
            $this->_maxConcurrent = $max_requests;
        }
    }

    public function setOptions(array $options)
    {
        $this->_options = $options;
    }

    public function setHeaders(array $headers)
    {
        if (is_array($headers) && count($headers)) {
            $this->_headers = $headers;
        }
    }

    public function setCallback(callable $callback)
    {
        $this->_callback = $callback;
    }

    public function setTimeout($timeout)
    { //in milliseconds
        if ($timeout > 0) {
            $this->_timeout = $timeout;
        }
    }

    //Add a request to the request queue
    public function addRequest($url, $user_data = null)
    { //Add to request queue
        $this->requests[] = array(
            'url' => $url,
            'user_data' => $user_data
        );
        return count($this->requests) - 1; //return request number/index
    }

    //Reset request queue
    public function reset()
    {
        $this->requests = array();
    }

    //Execute the request queue
    public function execute()
    {
        //the request map that maps the request queue to request curl handles
        $requests_map = array();
        $multi_handle = curl_multi_init();
        $num_outstanding = 0;
        //start processing the initial request queue
        $num_initial_requests = min($this->_maxConcurrent, count($this->requests));
        for ($i = 0; $i < $num_initial_requests; $i++) {
            $this->initRequest($i, $multi_handle, $requests_map);
            $num_outstanding++;
        }
        do {
            do {
                $mh_status = curl_multi_exec($multi_handle, $active);
            } while ($mh_status == CURLM_CALL_MULTI_PERFORM);
            if ($mh_status != CURLM_OK) {
                break;
            }
            //a request is just completed, find out which one
            while ($completed = curl_multi_info_read($multi_handle)) {
                $this->processRequest($completed, $multi_handle, $requests_map);
                $num_outstanding--;
                //try to add/start a new requests to the request queue
                while (
                    $num_outstanding < $this->_maxConcurrent && //under the limit
                    $i < count($this->requests) && isset($this->requests[$i]) // requests left
                ) {
                    $this->initRequest($i, $multi_handle, $requests_map);
                    $num_outstanding++;
                    $i++;
                }
            }
            usleep(15); //save CPU cycles, prevent continuous checking
        } while ($active || count($requests_map)); //End do-while
        $this->reset();
        curl_multi_close($multi_handle);
    }


    /**
     * Build individual cURL options for a request
     *
     * @param array $request
     * @return array
     */
    protected function buildOptions(array $request)
    {
        $url = $request['url'];
        $options = $this->_options;
        $headers = $this->_headers;
        //the below will overide the corresponding default or individual options
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_NOSIGNAL] = 1;
        if (version_compare($this->_curl_version, '7.16.2') >= 0) {
            $options[CURLOPT_CONNECTTIMEOUT_MS] = $this->_timeout;
            $options[CURLOPT_TIMEOUT_MS] = $this->_timeout;
            unset($options[CURLOPT_CONNECTTIMEOUT], $options[CURLOPT_TIMEOUT]);
        } else {
            $options[CURLOPT_CONNECTTIMEOUT] = round($this->_timeout / 1000);
            $options[CURLOPT_TIMEOUT] = round($this->_timeout / 1000);
            unset($options[CURLOPT_CONNECTTIMEOUT_MS], $options[CURLOPT_TIMEOUT_MS]);
        }
        if ($url) {
            $options[CURLOPT_URL] = $url;
        }
        if ($headers) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
        return $options;
    }

    /**
     * @param $request_num
     * @param $multi_handle
     * @param $requests_map
     */
    protected function initRequest($request_num, $multi_handle, &$requests_map)
    {
        $request =& $this->requests[$request_num];
        $ch = curl_init();
        $options = $this->buildOptions($request);
        $request['options_set'] = $options; //merged options
        $opts_set = curl_setopt_array($ch, $options);
        if (!$opts_set) {
            echo 'options not set';
            exit;
        }
        curl_multi_add_handle($multi_handle, $ch);
        //add curl handle of a new request to the request map
        $ch_hash = (string)$ch;
        $requests_map[$ch_hash] = $request_num;
    }

    /**
     * @param $completed
     * @param $multi_handle
     * @param array $requests_map
     */
    protected function processRequest($completed, $multi_handle, array &$requests_map)
    {
        $ch = $completed['handle'];
        $ch_hash = (string)$ch;
        $request =& $this->requests[$requests_map[$ch_hash]]; //map handler to request index to get request info
        $request_info = curl_getinfo($ch);
        $url = $request['url'];
        $user_data = $request['user_data'];

        if (curl_errno($ch) !== 0 || (int)$request_info['http_code'] !== 200) { //if server responded with http error
            $response = false;
        } else { //sucessful response
            $response = curl_multi_getcontent($ch);
        }
        //get request info
        $options = $request['options_set'];
        if ($response && !empty($options[CURLOPT_HEADER])) {
            $k = (int)$request_info['header_size'];
            $response = substr($response, $k);
        }
        //remove completed request and its curl handle
        unset($requests_map[$ch_hash]);
        curl_multi_remove_handle($multi_handle, $ch);
        //call the callback function and pass request info and user data to it
        if ($this->_callback) {
            call_user_func($this->_callback, $url, $response, $user_data);
        }
        $request = null; //free up memory now just incase response was large
    }
}

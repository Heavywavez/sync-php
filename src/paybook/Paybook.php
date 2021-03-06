<?php

namespace paybook;

use Exception;

class Paybook
{
    public static $api_key = null;
    public static $print_calls = false;
    public static $log = false;
    public static $env_url = false;
    const INDENT = '   ';
    const PAYBOOK_URL = 'https://sync.paybook.com/v1/';

    public static function init($api_key, $print_calls = false, $log = false, $env_url = false)
    {
        self::$api_key = $api_key;
        self::$print_calls = $print_calls;//For debugging
        self::$log = $log;
        self::$env_url = $env_url;//For debugging in other environment
    }//End of init

    public static function get_env()
    {
        if (self::$env_url == false) {
            return self::PAYBOOK_URL;
        } else {
            return self::$env_url;
        }//End of if 
    }//End of init

    public static function call($endpoint = null, $method = null, $data = null, $params = null, $headers = null, $url = false)
    {
        if ($url == null) {
            if (self::$env_url == false) {
                $url = self::PAYBOOK_URL.$endpoint;//By default uses production environment
            } else {
                $url = self::$env_url.$endpoint;// It uses the given environment
            }//End of if
        }//End of if
        $method = strtoupper($method);
        self::log(self::INDENT.'API Key:        '.strval(self::$api_key), $call = true);
        self::log(self::INDENT.'Endpoint:       '.strval($url), $call = true);
        self::log(self::INDENT.'HTTP Method:    '.strval($method), $call = true);
        // self::log(self::INDENT.'Data:           '.strval($data), $call = true);
        // self::log(self::INDENT.'Params:         '.strval($params), $call = true);
        self::log(self::INDENT.'Headers:        '.strval($headers), $call = true);
        $dataString = '';
        if ($data) {
            $dataString = json_encode($data);
        } elseif ($params) {
            $dataString = json_encode($params);
        }//End of if
        $headers = [//Default headers
            'Content-Type: application/json',
            'Content-Length: '.strlen($dataString),
        ];//End of $headers

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $dataString,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false, // Solves windows compatibility
            CURLOPT_SSL_VERIFYHOST => false, // Solves windows compatibility
        ));//End of curl_setopt_array
        $error = curl_error($curl);
        if ($error) {
            self::log(self::INDENT.'CURL error:        ', $call = true);
            print_r($error);
            throw new Error(500, null, 'CURL error', null);
        }//End of if
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);
        if ($http_code == 200) {
            if (strpos($content_type, 'json') !== false) {
                $paybookResponse = json_decode($response, true);

                return $paybookResponse['response'];
            } else {
                return $response;
            }//End of if
        } else {
            if (strpos($content_type, 'json') !== false) {
                $paybookResponse = json_decode($response, true);
                throw new Error($http_code, $paybookResponse['response'], $paybookResponse['message'], $paybookResponse['status']);
            } else {
                throw new Error($http_code);
            }//End of if
        }//End of if
    }//End of __call

    public static function log($message, $call = false)
    {
        if (($call == true && self::$print_calls) || self::$log == true) {
            echo $message.PHP_EOL;
        }//End of if
    }//End of log
}//End of Paybook class

// 	@staticmethod
// 	def __get_api_error__(conn):
// 		try:
// 			api_error = Error(conn.status_code,'','','')
// 		except Exception as e:
// 			api_error = Error(500,'Connection Error')
// 		return api_error

class Error extends Exception
{
    public function __construct($code, $response = '', $message = '', $status = '')
    {
        $this->code = $code;
        $this->response = $response;
        $this->message = $message;
        $this->status = $status;
    }//End of __construct

    public function get_message()
    {
        return $this->message;
    }//End of get_message

    public function get_code()
    {
        return $this->code;
    }//End of get_code

    public function get_array()
    {
        return [
            'code' => $this->code,
            'response' => $this->response,
            'message' => $this->message,
            'status' => $this->status,
        ];//End of return
    }//End of get_array
}//End of error

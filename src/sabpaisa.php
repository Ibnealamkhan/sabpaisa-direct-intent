<?php
// Suppress deprecation warnings for Guzzle compatibility
error_reporting(E_ALL & ~E_DEPRECATED);

require_once './vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;


include_once 'Auth.php';

class SabPaisaPayment {
    private $client;
    private $cookieJar;
    
    public function __construct() {
        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
            'cookies' => $this->cookieJar,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-GB,en-US;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1'
            ]
        ]);
    }
    
    public function initializePayment($encData, $clientCode) {
        try {
            $formData = [
                'encData' => $encData,
                'clientCode' => $clientCode,
                'submit' => ''
            ];
            
            $url = 'https://securepay.sabpaisa.in/SabPaisa/sabPaisaInit?v=1';
            
            $response = $this->client->post($url, [
                'form_params' => $formData,
                'allow_redirects' => true
            ]);
            
            $statusCode = $response->getStatusCode();
             $cookies = [];
            foreach ($this->cookieJar as $cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
            
            if ($statusCode === 200) {
               return [
                'success' => true,
                'status_code' => $statusCode,
                'cookies' => $cookies
            ];
            } else {
                return [
                'success' => false,
                'status_code' => $statusCode,
                'message' => 'Error posting data',
                'cookies' => $cookies
            ];
            }
            
        } catch (Exception $e) {
              return [
                'success' => false,
                 'message' => 'URL not Whitelisted OR Duplicate TXN id',
                'status_code' => 500
            ];
        }
    }
    
  
    
    public function processCompletePayment($encData, $clientCode,$txnid) {
        // Step 1: Initialize payment
        
        
        $result = $this->initializePayment($encData, $clientCode,$txnid);
        
        if($result['success']) {


$url = "https://api.onegateway.in/sabpaisa/composer/getIntent.php";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "Content-Type: application/x-www-form-urlencoded",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = "clientCode=$clientCode&txnid=$txnid&cooikes=".urlencode(json_encode($result['cookies']))."&encData=$encData";

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

//for debug only!
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$resp = curl_exec($curl);
curl_close($curl);

$data = json_decode($resp,true);

if($data['tmpTransStatus']==='SUCCESS'){
    
       return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Intent recieved',
                'txnid' => $txnid,
                'intent' => $data['upiQrValue']
            ];
            
    
} else {
    
             return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Intent not enabled please contact RM'
            ];
            
}

       

        } else {
            return $result;
        }
        
        
        
    }
}

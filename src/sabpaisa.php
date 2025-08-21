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
                'status_code' => $statusCode
            ];
            }
            
        } catch (Exception $e) {
              return [
                'success' => false,
                'status_code' => 500
            ];
        }
    }
    
  
    
    public function processCompletePayment($encData, $clientCode) {
        // Step 1: Initialize payment
        
        
        return $this->initializePayment($encData, $clientCode);
        
        
        
        
        
    }
}

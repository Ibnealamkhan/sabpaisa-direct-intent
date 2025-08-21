<?php

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Suppress deprecation warnings for Guzzle compatibility
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;

class SabPaisaPayment {
    private $client;
    private $cookieJar;
    private $logs = [];
    
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
    
    private function log($message, $type = 'info') {
        $this->logs[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message
        ];
    }
    
    public function initializePayment($encData, $clientCode) {
        try {
            $this->log("Initializing SabPaisa payment", "info");
            
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
            $responseBody = $response->getBody()->getContents();
            
            $this->log("Initialization successful - Status: {$statusCode}, Length: " . strlen($responseBody) . " bytes", "success");
            
            // Extract cookies
            $cookies = [];
            foreach ($this->cookieJar as $cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
            
            return [
                'success' => true,
                'status_code' => $statusCode,
                'response_length' => strlen($responseBody),
                'cookies' => $cookies,
                'html_response' => $responseBody
            ];
            
        } catch (Exception $e) {
            $this->log("Initialization failed: " . $e->getMessage(), "error");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function confirmUPIIntent($paymentData) {
        try {
            $this->log("Confirming UPI Intent", "info");
            
            $url = 'https://securepay.sabpaisa.in/SabPaisa/rest/intent/confirmintentupiV1';
            
            $headers = [
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en-GB,en-US;q=0.9,en;q=0.8',
                'Content-Type' => 'application/json',
                'Origin' => 'https://securepay.sabpaisa.in',
                'Referer' => 'https://securepay.sabpaisa.in/SabPaisa/sabPaisaInit?v=1',
                'Sec-CH-UA' => '"Not;A=Brand";v="99", "Google Chrome";v="139", "Chromium";v="139"',
                'Sec-CH-UA-Mobile' => '?1',
                'Sec-CH-UA-Platform' => '"Android"',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
                'User-Agent' => 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36'
            ];
            
            $response = $this->client->post($url, [
                'json' => $paymentData,
                'headers' => $headers
            ]);
            
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);
            
            $this->log("UPI Intent confirmation successful - Status: {$statusCode}", "success");
            
            return [
                'success' => true,
                'status_code' => $statusCode,
                'response' => $responseData
            ];
            
        } catch (Exception $e) {
            $this->log("UPI Intent confirmation failed: " . $e->getMessage(), "error");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function processCompletePayment($encData, $clientCode, $paymentData) {
        $finalResponse = [
            'success' => false,
            'timestamp' => date('Y-m-d H:i:s'),
            'steps' => [],
            'logs' => []
        ];
        
        // Step 1: Initialize payment
        $this->log("Starting complete payment process", "info");
        $initResult = $this->initializePayment($encData, $clientCode);
        $finalResponse['steps']['initialization'] = $initResult;
        
        if (!$initResult['success']) {
            $finalResponse['error'] = "Payment initialization failed";
            $finalResponse['logs'] = $this->logs;
            return $finalResponse;
        }
        
        // Step 2: Confirm UPI Intent
        $upiResult = $this->confirmUPIIntent($paymentData);
        $finalResponse['steps']['upi_confirmation'] = $upiResult;
        
        if ($upiResult['success']) {
            $finalResponse['success'] = true;
            $finalResponse['message'] = "Payment process completed successfully";
            
            // Extract important payment information
            $paymentInfo = [];
            
            if (isset($upiResult['response']['data']['deepLink'])) {
                $paymentInfo['upi_deep_link'] = $upiResult['response']['data']['deepLink'];
            }
            
            if (isset($upiResult['response']['data']['qrCode'])) {
                $paymentInfo['qr_code'] = $upiResult['response']['data']['qrCode'];
            }
            
            if (isset($upiResult['response']['txnId'])) {
                $paymentInfo['transaction_id'] = $upiResult['response']['txnId'];
            }
            
            if (isset($upiResult['response']['status'])) {
                $paymentInfo['payment_status'] = $upiResult['response']['status'];
            }
            
            if (isset($upiResult['response']['message'])) {
                $paymentInfo['payment_message'] = $upiResult['response']['message'];
            }
            
            $finalResponse['payment_info'] = $paymentInfo;
            $finalResponse['complete_response'] = $upiResult['response'];
            
            $this->log("Payment process completed successfully", "success");
        } else {
            $finalResponse['error'] = "UPI confirmation failed";
            $this->log("Payment process failed at UPI confirmation", "error");
        }
        
        $finalResponse['logs'] = $this->logs;
        return $finalResponse;
    }
}

// Handle the API request
try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception("Invalid JSON input");
        }
        
        $sabpaisa = new SabPaisaPayment();
        
        // Extract data from input
        $encData = $input['encData'] ?? '';
        $clientCode = $input['clientCode'] ?? '';
        $paymentData = $input['paymentData'] ?? [];
        
        if (empty($encData) || empty($clientCode) || empty($paymentData)) {
            throw new Exception("Missing required parameters: encData, clientCode, paymentData");
        }
        
        // Process payment
        $result = $sabpaisa->processCompletePayment($encData, $clientCode, $paymentData);
        
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Example usage for GET request
        $sabpaisa = new SabPaisaPayment();
        
        // Example data (you can modify these)
        $encData = '56JcMI7dDnrFYTvF+kklSip3aLeqm9/qFrKwjLTxUQY3TxD+DFxfJiKGXiUmaeZgkTKUjMJx9DGcg8R2zeyPnvKoQXsWRfDJliUsgbFm6VQrzGwNSFnaygbSHKJdzWOeV+lr92DOT8QGNvOQzLUgjp2QdGxo4+SHxW8WjzbBIZkKTN5noYALoCM92ye0dSvthOL0CGDJDh4RMN+TBZQ5sJfgIRR3aij8T7fg3CrnsPW7TYsg2ReHV/DupesVvykOeZBC4uYYf7lmU1N81RTayDzYNIuT+pBsCR9gmizYg9+Aq3CZViS7P8ZCTFpCpDTidHJdMa4sSFiOjOxaNGwSwTcVGCHSOm+SQQx7XFsX4iqlw16a7yca8nAk+Gbw0yARWSnzthBx06+fzLSS9I/9NPs7aYxF84l+bHjmDrunky+awdybvr/exJdTYkl26ljFQu14N9qXM+P3unH5xanflg==:eUlrNkdMajI3RTlvOHBaQg==';
        $clientCode = 'ALLP70';
        
        $paymentData = [
            'clientId' => 21832,
            'paidAmount' => '5.00',
            'clientTxnId' => '12787836866558e89',
            'clientName' => 'ALLPE',
            'clientCode' => 'ALLP70',
            'requestAmount' => 5,
            'payeeEmail' => 'ibnealamkhan811@gmail.com',
            'payeeMobile' => '7086303816',
            'amountType' => 'INR',
            'payMode' => [
                'paymodeId' => 15,
                'paymodeType' => 'online',
                'paymodeName' => 'UPI INTENT',
                'active' => true,
                'performanceFlag' => false
            ],
            'endPoint' => [
                'epId' => 1509,
                'bankName' => 'Airtel Payment Bank',
                'bankId' => '1509',
                'agrName' => 'SabPaisa',
                'epName' => 'AIRTEL',
                'epType' => 'UPI',
                'bankLogoUrl' => 'NA'
            ],
            'udf1' => null,
            'browserDetails' => 'en-GB|30|740|360|-330',
            'mandateFlag' => false,
            'mandateCharges' => 0
        ];
        
        // Process payment
        $result = $sabpaisa->processCompletePayment($encData, $clientCode, $paymentData);
        
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
    } else {
        throw new Exception("Method not allowed. Use POST or GET. use browser");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}

?>

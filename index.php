<?php
include_once 'config.php';
$name         = "Alam Khan";
$email        = "ibnealamkhan811@gmail.com";
$number       = 7086303816;
$amount       = 1;
$txnid        = rand(100000, 99999999999);
$callbackUrl  = "https://example.com";
$payerAddress='Guwahati,Assam';
$amountType='INR';
$mcc=4900;
$channelId='M';



$encData="clientCode=".$clientCode."&transUserName=".$username."&transUserPassword=".$password."&payerName=".$payerName.
"&payerMobile=".$number."&payerEmail=".$email."&payerAddress=".$payerAddress."&clientTxnId=".$txnid.
"&amount=".$amount."&amountType=".$amountType."&mcc=".$mcc."&channelId=".$channelId."&callbackUrl=".$callbackUrl."&udf1=&udf2=";
				
$AesCipher = new AesCipher(); 
$token = $AesCipher->encrypt($authKey, $authIV, $encData);

   echo  $sabpaisa->processCompletePayment($token, $clientCode, $paymentData);
        



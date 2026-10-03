<?php

use Twilio\Rest\Client;

function sendSMS($to, $message)
{
    $sid    = "YOUR_TWILIO_SID";
    $token  = "YOUR_TWILIO_AUTH_TOKEN";
    $from   = "YOUR_TWILIO_PHONE_NUMBER"; 

    try {
        $client = new Client($sid, $token);
        $client->messages->create(
            $to,
            [
                'from' => $from,
                'body' => $message
            ]
        );
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}


function sendWhatsAppMessage_using_Twilio($to, $message)
{

    $sid    = "YOUR_TWILIO_SID";
    $token  = "YOUR_TWILIO_AUTH_TOKEN";
    $from   = "whatsapp:+14155238886"; // Twilio WhatsApp sandbox number

    try {
        $client = new Client($sid, $token);
        $client->messages->create(
            "whatsapp:$to",
            [
                'from' => $from,
                'body' => $message
            ]
        );
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function sendWhatsAppMessage_using_cloud_api($to, $message)
{
    $token = "YOUR_ACCESS_TOKEN";
    $phone_id = "YOUR_PHONE_NUMBER_ID";

    $url = "https://graph.facebook.com/v16.0/$phone_id/messages";

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $to,
        "type" => "text",
        "text" => ["body" => $message]
    ];

    $headers = [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}


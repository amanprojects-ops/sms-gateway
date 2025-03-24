<?php
/**
 * Example API Request to Generate OTP
 * 
 * POST /api/send.php HTTP/1.1
 * Host: 127.0.0.1/www/sms-gateways/api/generate_otp.php
 * Content-Type: application/json
 * X-API-Key: 3c8a5f96798b0b9769da0cb6d86f22c8
 * 
 * Request Body:
 * {
 *     "phone": "+1234567890"
 * }
 * 
 * Response:
 * {
 *     "success": true,
 *     "message": "OTP sent successfully to +1234567890. Check your phone for the code."
 * }
 * 
 * cURL Request Example: */
    // curl -X POST http://127.0.0.1/www/sms-gateways/api/send.php \
    // -H "Content-Type: application/json" \
    // -H "X-API-Key: 3c8a5f96798b0b9769da0cb6d86f22c8" \
    // -d '{"phone": "+1234567890"}'
    
    // Call API Example:
    $api_url = "http://127.0.0.1/www/sms-gateways/api/send.php";
    $api_key = "3c8a5f96798b0b9769da0cb6d86f22c8";
    $phone = "7061029304";
    $message = mt_rand(0,999999);
    
    $data = json_encode(['mobile' => $phone,'message' => $message]);
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-Key: $api_key"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo $response; // Output the response from the API


?>
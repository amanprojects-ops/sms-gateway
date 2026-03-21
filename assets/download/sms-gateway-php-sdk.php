<?php
/**
 * SMS Gateway PHP SDK
 * 
 * A simple PHP SDK to interact with the SMS Gateway API
 * 
 * @version 1.0.0
 * @author SMS Gateway
 * @license MIT
 */

class SMSGateway {
    /**
     * API Key for authentication
     *
     * @var string
     */
    private $apiKey;
    
    /**
     * Base URL for API endpoints
     *
     * @var string
     */
    private $baseUrl;
    
    /**
     * Debug mode
     *
     * @var bool
     */
    private $debug = false;
    
    /**
     * Last API error if any
     *
     * @var string|null
     */
    private $lastError = null;
    
    /**
     * Last API response if any
     *
     * @var array|null
     */
    private $lastResponse = null;
    
    /**
     * Constructor
     *
     * @param string $apiKey Your API key
     * @param string $baseUrl Base URL for the API (optional)
     * @param bool $debug Enable debug mode (optional)
     */
    public function __construct($apiKey, $baseUrl = null, $debug = false) {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?: 'https://yourdomain.com/api';
        $this->debug = $debug;
    }
    
    /**
     * Send an SMS message
     *
     * @param string $phoneNumber Recipient's phone number in international format (e.g. +1234567890)
     * @param string $message Message content
     * @param string $reference Optional reference ID for tracking
     * @return bool|string Success status or message ID if successful
     */
    public function sendSMS($phoneNumber, $message, $reference = null) {
        $data = [
            'phone' => $phoneNumber,
            'message' => $message
        ];
        
        if ($reference) {
            $data['reference'] = $reference;
        }
        
        $response = $this->makeRequest('send.php', $data);
        
        if ($response && isset($response['success']) && $response['success']) {
            return $response['message_id'];
        }
        
        return false;
    }
    
    /**
     * Generate an OTP for a phone number
     *
     * @param string $phoneNumber Recipient's phone number in international format
     * @param string $template Optional template for the SMS (use {otp} as placeholder)
     * @return bool|string Success status or reference ID if successful
     */
    public function generateOTP($phoneNumber, $template = null) {
        $data = [
            'phone' => $phoneNumber
        ];
        
        if ($template) {
            $data['template'] = $template;
        }
        
        $response = $this->makeRequest('generate_otp.php', $data);
        
        if ($response && isset($response['success']) && $response['success']) {
            return $response['reference_id'];
        }
        
        return false;
    }
    
    /**
     * Verify an OTP code
     *
     * @param string $phoneNumber Phone number that received the OTP
     * @param string $otpCode The OTP code to verify
     * @param string $referenceId Optional reference ID returned when generating the OTP
     * @return bool Success status
     */
    public function verifyOTP($phoneNumber, $otpCode, $referenceId = null) {
        $data = [
            'phone' => $phoneNumber,
            'otp' => $otpCode
        ];
        
        if ($referenceId) {
            $data['reference_id'] = $referenceId;
        }
        
        $response = $this->makeRequest('verify_otp.php', $data);
        
        return $response && isset($response['success']) && $response['success'];
    }
    
    /**
     * Get the last error
     *
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Get the last response
     *
     * @return array|null
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }
    
    /**
     * Make an API request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|false Response data or false on failure
     */
    private function makeRequest($endpoint, $data) {
        // Reset previous errors
        $this->lastError = null;
        
        $url = rtrim($this->baseUrl, '/') . '/' . $endpoint;
        
        // Initialize cURL
        $ch = curl_init($url);
        
        // Set request options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
        ]);
        
        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($this->debug) {
            error_log('SMS Gateway API request to: ' . $url);
            error_log('Request data: ' . json_encode($data));
            error_log('Response code: ' . $httpCode);
            error_log('Response: ' . $response);
        }
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $this->lastError = 'cURL error: ' . curl_error($ch);
            if ($this->debug) {
                error_log($this->lastError);
            }
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Decode response
        $responseData = json_decode($response, true);
        $this->lastResponse = $responseData;
        
        // Check for API errors
        if (!$responseData || $httpCode >= 400) {
            $this->lastError = isset($responseData['error_message']) 
                ? $responseData['error_message'] 
                : 'HTTP error: ' . $httpCode;
            
            if ($this->debug) {
                error_log($this->lastError);
            }
            return false;
        }
        
        return $responseData;
    }
}

/**
 * Usage Examples
 * 
 * // Initialize the SDK
 * $sms = new SMSGateway('your_api_key', 'https://yourdomain.com/api', true);
 * 
 * // Send an SMS
 * $messageId = $sms->sendSMS('+1234567890', 'Hello from SMS Gateway!', 'order-123');
 * if ($messageId) {
 *     echo "SMS sent successfully with ID: " . $messageId;
 * } else {
 *     echo "Failed to send SMS: " . $sms->getLastError();
 * }
 * 
 * // Generate an OTP
 * $referenceId = $sms->generateOTP('+1234567890', 'Your verification code is {otp}');
 * if ($referenceId) {
 *     echo "OTP sent successfully with reference ID: " . $referenceId;
 * } else {
 *     echo "Failed to send OTP: " . $sms->getLastError();
 * }
 * 
 * // Verify an OTP
 * $isValid = $sms->verifyOTP('+1234567890', '123456', $referenceId);
 * if ($isValid) {
 *     echo "OTP verified successfully";
 * } else {
 *     echo "OTP verification failed: " . $sms->getLastError();
 * }
 */ 
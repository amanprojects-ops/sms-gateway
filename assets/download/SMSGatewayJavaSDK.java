/**
 * SMS Gateway Java SDK
 * 
 * A simple Java SDK to interact with the SMS Gateway API
 * 
 * @version 1.0.0
 * @author SMS Gateway
 * @license MIT
 */

package com.smsgateway.sdk;

import java.io.IOException;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.util.HashMap;
import java.util.Map;

import org.json.JSONObject;

/**
 * Main SDK class for the SMS Gateway
 */
public class SMSGateway {
    private String apiKey;
    private String baseUrl;
    private boolean debug;
    private String lastError;
    private JSONObject lastResponse;
    private HttpClient httpClient;

    /**
     * Constructor with default base URL
     * 
     * @param apiKey Your API key
     */
    public SMSGateway(String apiKey) {
        this(apiKey, "https://yourdomain.com/api", false);
    }

    /**
     * Constructor with custom base URL
     * 
     * @param apiKey Your API key
     * @param baseUrl Base URL for the API
     */
    public SMSGateway(String apiKey, String baseUrl) {
        this(apiKey, baseUrl, false);
    }

    /**
     * Constructor with all parameters
     * 
     * @param apiKey Your API key
     * @param baseUrl Base URL for the API
     * @param debug Enable debug mode
     */
    public SMSGateway(String apiKey, String baseUrl, boolean debug) {
        this.apiKey = apiKey;
        this.baseUrl = baseUrl;
        this.debug = debug;
        this.lastError = null;
        this.lastResponse = null;
        
        // Initialize HttpClient
        this.httpClient = HttpClient.newBuilder()
                .version(HttpClient.Version.HTTP_2)
                .connectTimeout(Duration.ofSeconds(10))
                .build();
    }

    /**
     * Send an SMS message
     * 
     * @param phoneNumber Recipient's phone number in international format (e.g. +1234567890)
     * @param message Message content
     * @return Message ID if successful, null otherwise
     * @throws IOException If an I/O error occurs
     * @throws InterruptedException If the operation is interrupted
     */
    public String sendSMS(String phoneNumber, String message) throws IOException, InterruptedException {
        return sendSMS(phoneNumber, message, null);
    }

    /**
     * Send an SMS message with reference ID
     * 
     * @param phoneNumber Recipient's phone number in international format (e.g. +1234567890)
     * @param message Message content
     * @param reference Optional reference ID for tracking
     * @return Message ID if successful, null otherwise
     * @throws IOException If an I/O error occurs
     * @throws InterruptedException If the operation is interrupted
     */
    public String sendSMS(String phoneNumber, String message, String reference) throws IOException, InterruptedException {
        Map<String, Object> data = new HashMap<>();
        data.put("phone", phoneNumber);
        data.put("message", message);
        
        if (reference != null) {
            data.put("reference", reference);
        }
        
        try {
            JSONObject response = makeRequest("send.php", data);
            
            if (response != null && response.optBoolean("success")) {
                return response.optString("message_id");
            }
            
            return null;
        } catch (Exception e) {
            if (debug) {
                System.err.println("Error sending SMS: " + e.getMessage());
            }
            this.lastError = "Error sending SMS: " + e.getMessage();
            return null;
        }
    }

    /**
     * Generate an OTP for a phone number
     * 
     * @param phoneNumber Recipient's phone number in international format
     * @return Reference ID if successful, null otherwise
     * @throws IOException If an I/O error occurs
     * @throws InterruptedException If the operation is interrupted
     */
    public String generateOTP(String phoneNumber) throws IOException, InterruptedException {
        return generateOTP(phoneNumber, null);
    }

    /**
     * Generate an OTP for a phone number with custom template
     * 
     * @param phoneNumber Recipient's phone number in international format
     * @param template Optional template for the SMS (use {otp} as placeholder)
     * @return Reference ID if successful, null otherwise
     * @throws IOException If an I/O error occurs
     * @throws InterruptedException If the operation is interrupted
     */
    public String generateOTP(String phoneNumber, String template) throws IOException, InterruptedException {
        Map<String, Object> data = new HashMap<>();
        data.put("phone", phoneNumber);
        
        if (template != null) {
            data.put("template", template);
        }
        
        try {
            JSONObject response = makeRequest("generate_otp.php", data);
            
            if (response != null && response.optBoolean("success")) {
                return response.optString("reference_id");
            }
            
            return null;
        } catch (Exception e) {
            if (debug) {
                System.err.println("Error generating OTP: " + e.getMessage());
            }
            this.lastError = "Error generating OTP: " + e.getMessage();
            return null;
        }
    }

    /**
     * Verify an OTP code
     * 
     * @param phoneNumber Phone number that received the OTP
     * @param otpCode The OTP code to verify
     * @return Success status
     * @throws IOException If an I/O error occurs
     * @throws InterruptedException If the operation is interrupted
     */
    public boolean verifyOTP(String phoneNumber, String otpCode) throws IOException, InterruptedException {
        return verifyOTP(phoneNumber, otpCode, null);
    }

    /**
     * Verify an OTP code with reference ID
     * 
     * @param phoneNumber Phone number that received the OTP
     * @param otpCode The OTP code to verify
     * @param referenceId Optional reference ID returned when generating the OTP
     * @return Success status
     * @throws IOException If an I/O error occurs
     * @throws InterruptedException If the operation is interrupted
     */
    public boolean verifyOTP(String phoneNumber, String otpCode, String referenceId) throws IOException, InterruptedException {
        Map<String, Object> data = new HashMap<>();
        data.put("phone", phoneNumber);
        data.put("otp", otpCode);
        
        if (referenceId != null) {
            data.put("reference_id", referenceId);
        }
        
        try {
            JSONObject response = makeRequest("verify_otp.php", data);
            return response != null && response.optBoolean("success");
        } catch (Exception e) {
            if (debug) {
                System.err.println("Error verifying OTP: " + e.getMessage());
            }
            this.lastError = "Error verifying OTP: " + e.getMessage();
            return false;
        }
    }

    /**
     * Get the last error
     * 
     * @return Last error message
     */
    public String getLastError() {
        return this.lastError;
    }

    /**
     * Get the last response
     * 
     * @return Last response object
     */
    public JSONObject getLastResponse() {
        return this.lastResponse;
    }

    /**
     * Make an API request
     * 
     * @param endpoint API endpoint
     * @param data Request data
     * @return Response data
     * @throws IOException If an I/O error occurs
     * @throws InterruptedException If the operation is interrupted
     */
    private JSONObject makeRequest(String endpoint, Map<String, Object> data) throws IOException, InterruptedException {
        // Reset previous errors
        this.lastError = null;
        
        String url = this.baseUrl.replaceAll("/$", "") + "/" + endpoint;
        
        JSONObject jsonData = new JSONObject(data);
        
        if (this.debug) {
            System.out.println("SMS Gateway API request to: " + url);
            System.out.println("Request data: " + jsonData.toString(2));
        }
        
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(url))
                .header("Content-Type", "application/json")
                .header("X-API-Key", this.apiKey)
                .POST(HttpRequest.BodyPublishers.ofString(jsonData.toString()))
                .build();
        
        try {
            HttpResponse<String> response = this.httpClient.send(request, HttpResponse.BodyHandlers.ofString());
            
            String responseBody = response.body();
            JSONObject jsonResponse = new JSONObject(responseBody);
            this.lastResponse = jsonResponse;
            
            if (this.debug) {
                System.out.println("Response status: " + response.statusCode());
                System.out.println("Response data: " + jsonResponse.toString(2));
            }
            
            // Check for API errors
            if (response.statusCode() >= 400) {
                this.lastError = jsonResponse.optString("error_message", "HTTP error: " + response.statusCode());
                
                if (this.debug) {
                    System.err.println("API Error: " + this.lastError);
                    System.err.println("Response data: " + jsonResponse.toString(2));
                }
                
                return null;
            }
            
            return jsonResponse;
        } catch (Exception e) {
            this.lastError = "Request error: " + e.getMessage();
            
            if (this.debug) {
                System.err.println("Request error: " + e.getMessage());
                e.printStackTrace();
            }
            
            throw e;
        }
    }
}

/*
 * Usage Examples:
 * 
 * // Initialize the SDK
 * SMSGateway sms = new SMSGateway("your_api_key", "https://yourdomain.com/api", true);
 * 
 * // Send an SMS
 * try {
 *     String messageId = sms.sendSMS("+1234567890", "Hello from SMS Gateway!", "order-123");
 *     if (messageId != null) {
 *         System.out.println("SMS sent successfully with ID: " + messageId);
 *     } else {
 *         System.out.println("Failed to send SMS: " + sms.getLastError());
 *     }
 * } catch (Exception e) {
 *     System.err.println("Error: " + e.getMessage());
 * }
 * 
 * // Generate an OTP
 * try {
 *     String referenceId = sms.generateOTP("+1234567890", "Your verification code is {otp}");
 *     if (referenceId != null) {
 *         System.out.println("OTP sent successfully with reference ID: " + referenceId);
 *     } else {
 *         System.out.println("Failed to send OTP: " + sms.getLastError());
 *     }
 * } catch (Exception e) {
 *     System.err.println("Error: " + e.getMessage());
 * }
 * 
 * // Verify an OTP
 * try {
 *     boolean isValid = sms.verifyOTP("+1234567890", "123456", "reference-id");
 *     if (isValid) {
 *         System.out.println("OTP verified successfully");
 *     } else {
 *         System.out.println("OTP verification failed: " + sms.getLastError());
 *     }
 * } catch (Exception e) {
 *     System.err.println("Error: " + e.getMessage());
 * }
 */ 
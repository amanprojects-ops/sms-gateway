/**
 * SMS Gateway C++ SDK
 * 
 * A simple C++ SDK to interact with the SMS Gateway API
 * 
 * @version 1.0.0
 * @author SMS Gateway
 * @license MIT
 */

#include <string>
#include <map>
#include <vector>
#include <curl/curl.h>
#include <nlohmann/json.hpp>
#include <iostream>
#include <memory>

namespace SMSGateway {

/**
 * Callback function for cURL to write response data
 */
static size_t WriteCallback(void* contents, size_t size, size_t nmemb, std::string* s) {
    size_t newLength = size * nmemb;
    try {
        s->append((char*)contents, newLength);
        return newLength;
    } catch(std::bad_alloc& e) {
        return 0;
    }
}

/**
 * Main SDK class for the SMS Gateway
 */
class Client {
private:
    std::string apiKey;
    std::string baseUrl;
    bool debug;
    std::string lastError;
    nlohmann::json lastResponse;
    
public:
    /**
     * Constructor with default base URL
     * 
     * @param apiKey Your API key
     */
    Client(const std::string& apiKey) : 
        apiKey(apiKey), 
        baseUrl("https://yourdomain.com/api"), 
        debug(false) {}
    
    /**
     * Constructor with custom base URL
     * 
     * @param apiKey Your API key
     * @param baseUrl Base URL for the API
     */
    Client(const std::string& apiKey, const std::string& baseUrl) : 
        apiKey(apiKey), 
        baseUrl(baseUrl), 
        debug(false) {}
    
    /**
     * Constructor with custom base URL and debug mode
     * 
     * @param apiKey Your API key
     * @param baseUrl Base URL for the API
     * @param debug Enable debug mode
     */
    Client(const std::string& apiKey, const std::string& baseUrl, bool debug) : 
        apiKey(apiKey), 
        baseUrl(baseUrl), 
        debug(debug) {}
    
    /**
     * Set debug mode
     * 
     * @param debug Enable or disable debug mode
     */
    void setDebug(bool debug) {
        this->debug = debug;
    }
    
    /**
     * Get the last error message
     * 
     * @return Last error message
     */
    std::string getLastError() const {
        return lastError;
    }
    
    /**
     * Get the last API response
     * 
     * @return Last API response as JSON
     */
    nlohmann::json getLastResponse() const {
        return lastResponse;
    }
    
    /**
     * Send an SMS message
     * 
     * @param to Recipient phone number in international format (e.g., +1234567890)
     * @param message Message content
     * @param from Sender ID (optional)
     * @return bool Success status
     */
    bool sendSMS(const std::string& to, const std::string& message, const std::string& from = "") {
        std::map<std::string, std::string> params;
        params["to"] = to;
        params["message"] = message;
        
        if (!from.empty()) {
            params["from"] = from;
        }
        
        nlohmann::json response = makeRequest("send", params);
        return !response.is_null() && response.contains("success") && response["success"].get<bool>();
    }
    
    /**
     * Generate and send an OTP
     * 
     * @param phone Recipient phone number in international format
     * @param template Message template with {otp} placeholder
     * @return string|null OTP reference ID or null on failure
     */
    std::string generateOTP(const std::string& phone, const std::string& templ = "{otp}") {
        std::map<std::string, std::string> params;
        params["phone"] = phone;
        params["template"] = templ;
        
        nlohmann::json response = makeRequest("generate_otp", params);
        
        if (!response.is_null() && response.contains("success") && response["success"].get<bool>()) {
            return response["data"]["reference_id"].get<std::string>();
        }
        
        return "";
    }
    
    /**
     * Verify an OTP
     * 
     * @param referenceId OTP reference ID
     * @param otp OTP code entered by the user
     * @return bool Verification result
     */
    bool verifyOTP(const std::string& referenceId, const std::string& otp) {
        std::map<std::string, std::string> params;
        params["reference_id"] = referenceId;
        params["otp"] = otp;
        
        nlohmann::json response = makeRequest("verify_otp", params);
        return !response.is_null() && response.contains("success") && response["success"].get<bool>();
    }
    
    /**
     * Get account balance
     * 
     * @return int|null SMS balance or null on failure
     */
    int getBalance() {
        nlohmann::json response = makeRequest("balance", {});
        
        if (!response.is_null() && response.contains("success") && response["success"].get<bool>()) {
            return response["data"]["balance"].get<int>();
        }
        
        return -1;
    }
    
private:
    /**
     * Make an API request
     * 
     * @param endpoint API endpoint
     * @param params Request parameters
     * @return json Response data
     */
    nlohmann::json makeRequest(const std::string& endpoint, const std::map<std::string, std::string>& params) {
        CURL* curl = curl_easy_init();
        std::string readBuffer;
        
        if (curl) {
            std::string url = baseUrl + "/" + endpoint;
            
            // Prepare POST data
            std::string postFields;
            for (const auto& param : params) {
                if (!postFields.empty()) {
                    postFields += "&";
                }
                char* escapedKey = curl_easy_escape(curl, param.first.c_str(), param.first.length());
                char* escapedValue = curl_easy_escape(curl, param.second.c_str(), param.second.length());
                postFields += std::string(escapedKey) + "=" + std::string(escapedValue);
                curl_free(escapedKey);
                curl_free(escapedValue);
            }
            
            // Set cURL options
            curl_easy_setopt(curl, CURLOPT_URL, url.c_str());
            curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postFields.c_str());
            curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
            curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);
            
            // Set headers
            struct curl_slist* headers = NULL;
            headers = curl_slist_append(headers, "Content-Type: application/x-www-form-urlencoded");
            headers = curl_slist_append(headers, ("X-API-Key: " + apiKey).c_str());
            curl_easy_setopt(curl, CURLOPT_HTTPHEADER, headers);
            
            // Debug output
            if (debug) {
                curl_easy_setopt(curl, CURLOPT_VERBOSE, 1L);
            }
            
            // Perform request
            CURLcode res = curl_easy_perform(curl);
            
            // Check for errors
            if (res != CURLE_OK) {
                lastError = curl_easy_strerror(res);
                curl_slist_free_all(headers);
                curl_easy_cleanup(curl);
                return nlohmann::json();
            }
            
            // Clean up
            curl_slist_free_all(headers);
            curl_easy_cleanup(curl);
            
            // Parse JSON response
            try {
                lastResponse = nlohmann::json::parse(readBuffer);
                
                if (lastResponse.contains("error")) {
                    lastError = lastResponse["error"]["message"].get<std::string>();
                    return nlohmann::json();
                }
                
                return lastResponse;
            } catch (const nlohmann::json::parse_error& e) {
                lastError = "JSON parse error: " + std::string(e.what());
                return nlohmann::json();
            }
        }
        
        lastError = "Failed to initialize cURL";
        return nlohmann::json();
    }
};

} // namespace SMSGateway

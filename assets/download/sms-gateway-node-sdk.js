/**
 * SMS Gateway Node.js SDK
 * 
 * A simple Node.js SDK to interact with the SMS Gateway API
 * 
 * @version 1.0.0
 * @author SMS Gateway
 * @license MIT
 */

const axios = require('axios');

class SMSGateway {
    /**
     * Constructor
     * 
     * @param {string} apiKey - Your API key
     * @param {string} baseUrl - Base URL for the API (optional)
     * @param {boolean} debug - Enable debug mode (optional)
     */
    constructor(apiKey, baseUrl = null, debug = false) {
        this.apiKey = apiKey;
        this.baseUrl = baseUrl || 'https://yourdomain.com/api';
        this.debug = debug;
        this.lastError = null;
        this.lastResponse = null;
    }

    /**
     * Send an SMS message
     * 
     * @param {string} phoneNumber - Recipient's phone number in international format (e.g. +1234567890)
     * @param {string} message - Message content
     * @param {string} reference - Optional reference ID for tracking
     * @returns {Promise<string|boolean>} - Message ID if successful, false otherwise
     */
    async sendSMS(phoneNumber, message, reference = null) {
        const data = {
            phone: phoneNumber,
            message: message
        };
        
        if (reference) {
            data.reference = reference;
        }
        
        try {
            const response = await this.makeRequest('send.php', data);
            
            if (response && response.success) {
                return response.message_id;
            }
            
            return false;
        } catch (error) {
            if (this.debug) {
                console.error('Error sending SMS:', error.message);
            }
            return false;
        }
    }

    /**
     * Generate an OTP for a phone number
     * 
     * @param {string} phoneNumber - Recipient's phone number in international format
     * @param {string} template - Optional template for the SMS (use {otp} as placeholder)
     * @returns {Promise<string|boolean>} - Reference ID if successful, false otherwise
     */
    async generateOTP(phoneNumber, template = null) {
        const data = {
            phone: phoneNumber
        };
        
        if (template) {
            data.template = template;
        }
        
        try {
            const response = await this.makeRequest('generate_otp.php', data);
            
            if (response && response.success) {
                return response.reference_id;
            }
            
            return false;
        } catch (error) {
            if (this.debug) {
                console.error('Error generating OTP:', error.message);
            }
            return false;
        }
    }

    /**
     * Verify an OTP code
     * 
     * @param {string} phoneNumber - Phone number that received the OTP
     * @param {string} otpCode - The OTP code to verify
     * @param {string} referenceId - Optional reference ID returned when generating the OTP
     * @returns {Promise<boolean>} - Success status
     */
    async verifyOTP(phoneNumber, otpCode, referenceId = null) {
        const data = {
            phone: phoneNumber,
            otp: otpCode
        };
        
        if (referenceId) {
            data.reference_id = referenceId;
        }
        
        try {
            const response = await this.makeRequest('verify_otp.php', data);
            return response && response.success;
        } catch (error) {
            if (this.debug) {
                console.error('Error verifying OTP:', error.message);
            }
            return false;
        }
    }

    /**
     * Get the last error
     * 
     * @returns {string|null} - Last error message
     */
    getLastError() {
        return this.lastError;
    }

    /**
     * Get the last response
     * 
     * @returns {object|null} - Last response object
     */
    getLastResponse() {
        return this.lastResponse;
    }

    /**
     * Make an API request
     * 
     * @param {string} endpoint - API endpoint
     * @param {object} data - Request data
     * @returns {Promise<object>} - Response data
     * @private
     */
    async makeRequest(endpoint, data) {
        // Reset previous errors
        this.lastError = null;
        
        const url = `${this.baseUrl}/${endpoint}`.replace(/([^:]\/)\/+/g, "$1");
        
        if (this.debug) {
            console.log(`SMS Gateway API request to: ${url}`);
            console.log('Request data:', data);
        }
        
        try {
            const response = await axios({
                method: 'post',
                url: url,
                data: data,
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.apiKey
                }
            });
            
            this.lastResponse = response.data;
            
            if (this.debug) {
                console.log('Response status:', response.status);
                console.log('Response data:', response.data);
            }
            
            return response.data;
        } catch (error) {
            if (error.response) {
                // The request was made and the server responded with a status code
                // that falls out of the range of 2xx
                this.lastError = error.response.data.error_message || `HTTP error: ${error.response.status}`;
                this.lastResponse = error.response.data;
                
                if (this.debug) {
                    console.error('API Error:', this.lastError);
                    console.error('Response data:', error.response.data);
                }
            } else if (error.request) {
                // The request was made but no response was received
                this.lastError = 'No response received from the server';
                
                if (this.debug) {
                    console.error('Request error:', error.request);
                }
            } else {
                // Something happened in setting up the request that triggered an Error
                this.lastError = `Request setup error: ${error.message}`;
                
                if (this.debug) {
                    console.error('Error:', error.message);
                }
            }
            
            throw new Error(this.lastError);
        }
    }
}

module.exports = SMSGateway;

/**
 * Usage Examples:
 * 
 * // Initialize the SDK
 * const SMSGateway = require('./sms-gateway-node-sdk');
 * const sms = new SMSGateway('your_api_key', 'https://yourdomain.com/api', true);
 * 
 * // Send an SMS
 * async function sendSMSExample() {
 *   try {
 *     const messageId = await sms.sendSMS('+1234567890', 'Hello from SMS Gateway!', 'order-123');
 *     if (messageId) {
 *       console.log(`SMS sent successfully with ID: ${messageId}`);
 *     } else {
 *       console.log(`Failed to send SMS: ${sms.getLastError()}`);
 *     }
 *   } catch (error) {
 *     console.error(`Error: ${error.message}`);
 *   }
 * }
 * 
 * // Generate an OTP
 * async function generateOTPExample() {
 *   try {
 *     const referenceId = await sms.generateOTP('+1234567890', 'Your verification code is {otp}');
 *     if (referenceId) {
 *       console.log(`OTP sent successfully with reference ID: ${referenceId}`);
 *     } else {
 *       console.log(`Failed to send OTP: ${sms.getLastError()}`);
 *     }
 *   } catch (error) {
 *     console.error(`Error: ${error.message}`);
 *   }
 * }
 * 
 * // Verify an OTP
 * async function verifyOTPExample() {
 *   try {
 *     const isValid = await sms.verifyOTP('+1234567890', '123456', 'reference-id');
 *     if (isValid) {
 *       console.log('OTP verified successfully');
 *     } else {
 *       console.log(`OTP verification failed: ${sms.getLastError()}`);
 *     }
 *   } catch (error) {
 *     console.error(`Error: ${error.message}`);
 *   }
 * }
 */ 
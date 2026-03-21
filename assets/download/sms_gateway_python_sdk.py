#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
SMS Gateway Python SDK

A simple Python SDK to interact with the SMS Gateway API

@version 1.0.0
@author SMS Gateway
@license MIT
"""

import json
import requests


class SMSGateway:
    """
    SMS Gateway Python SDK

    A client for interacting with the SMS Gateway API
    """

    def __init__(self, api_key, base_url=None, debug=False):
        """
        Initialize the SMS Gateway client

        Args:
            api_key (str): Your API key
            base_url (str, optional): Base URL for the API. Defaults to 'https://yourdomain.com/api'.
            debug (bool, optional): Enable debug mode. Defaults to False.
        """
        self.api_key = api_key
        self.base_url = base_url or 'https://yourdomain.com/api'
        self.debug = debug
        self.last_error = None
        self.last_response = None

    def send_sms(self, phone_number, message, reference=None):
        """
        Send an SMS message

        Args:
            phone_number (str): Recipient's phone number in international format (e.g. +1234567890)
            message (str): Message content
            reference (str, optional): Optional reference ID for tracking. Defaults to None.

        Returns:
            str or False: Message ID if successful, False otherwise
        """
        data = {
            'phone': phone_number,
            'message': message
        }

        if reference:
            data['reference'] = reference

        try:
            response = self._make_request('send.php', data)

            if response and response.get('success'):
                return response.get('message_id')

            return False
        except Exception as e:
            if self.debug:
                print(f"Error sending SMS: {str(e)}")
            return False

    def generate_otp(self, phone_number, template=None):
        """
        Generate an OTP for a phone number

        Args:
            phone_number (str): Recipient's phone number in international format
            template (str, optional): Optional template for the SMS (use {otp} as placeholder). Defaults to None.

        Returns:
            str or False: Reference ID if successful, False otherwise
        """
        data = {
            'phone': phone_number
        }

        if template:
            data['template'] = template

        try:
            response = self._make_request('generate_otp.php', data)

            if response and response.get('success'):
                return response.get('reference_id')

            return False
        except Exception as e:
            if self.debug:
                print(f"Error generating OTP: {str(e)}")
            return False

    def verify_otp(self, phone_number, otp_code, reference_id=None):
        """
        Verify an OTP code

        Args:
            phone_number (str): Phone number that received the OTP
            otp_code (str): The OTP code to verify
            reference_id (str, optional): Optional reference ID returned when generating the OTP. Defaults to None.

        Returns:
            bool: Success status
        """
        data = {
            'phone': phone_number,
            'otp': otp_code
        }

        if reference_id:
            data['reference_id'] = reference_id

        try:
            response = self._make_request('verify_otp.php', data)
            return response and response.get('success', False)
        except Exception as e:
            if self.debug:
                print(f"Error verifying OTP: {str(e)}")
            return False

    def get_last_error(self):
        """
        Get the last error

        Returns:
            str or None: Last error message
        """
        return self.last_error

    def get_last_response(self):
        """
        Get the last response

        Returns:
            dict or None: Last response data
        """
        return self.last_response

    def _make_request(self, endpoint, data):
        """
        Make an API request

        Args:
            endpoint (str): API endpoint
            data (dict): Request data

        Returns:
            dict: Response data

        Raises:
            Exception: If the request fails
        """
        # Reset previous errors
        self.last_error = None

        url = f"{self.base_url.rstrip('/')}/{endpoint}"

        headers = {
            'Content-Type': 'application/json',
            'X-API-Key': self.api_key
        }

        if self.debug:
            print(f"SMS Gateway API request to: {url}")
            print(f"Request data: {json.dumps(data)}")

        try:
            response = requests.post(url, json=data, headers=headers)
            response_data = response.json()
            self.last_response = response_data

            if self.debug:
                print(f"Response status: {response.status_code}")
                print(f"Response data: {json.dumps(response_data)}")

            # Check for API errors
            if not response.ok:
                self.last_error = response_data.get('error_message', f"HTTP error: {response.status_code}")
                if self.debug:
                    print(f"API Error: {self.last_error}")
                    print(f"Response data: {json.dumps(response_data)}")
                raise Exception(self.last_error)

            return response_data
        except requests.RequestException as e:
            self.last_error = f"Request error: {str(e)}"
            if self.debug:
                print(f"Request error: {str(e)}")
            raise Exception(self.last_error)
        except json.JSONDecodeError:
            self.last_error = "Invalid JSON response"
            if self.debug:
                print("Invalid JSON response")
                print(f"Response text: {response.text}")
            raise Exception(self.last_error)
        except Exception as e:
            self.last_error = f"Error: {str(e)}"
            if self.debug:
                print(f"Error: {str(e)}")
            raise


"""
Usage Examples:

# Initialize the SDK
sms = SMSGateway('your_api_key', 'https://yourdomain.com/api', True)

# Send an SMS
try:
    message_id = sms.send_sms('+1234567890', 'Hello from SMS Gateway!', 'order-123')
    if message_id:
        print(f"SMS sent successfully with ID: {message_id}")
    else:
        print(f"Failed to send SMS: {sms.get_last_error()}")
except Exception as e:
    print(f"Error: {str(e)}")

# Generate an OTP
try:
    reference_id = sms.generate_otp('+1234567890', 'Your verification code is {otp}')
    if reference_id:
        print(f"OTP sent successfully with reference ID: {reference_id}")
    else:
        print(f"Failed to send OTP: {sms.get_last_error()}")
except Exception as e:
    print(f"Error: {str(e)}")

# Verify an OTP
try:
    is_valid = sms.verify_otp('+1234567890', '123456', 'reference-id')
    if is_valid:
        print("OTP verified successfully")
    else:
        print(f"OTP verification failed: {sms.get_last_error()}")
except Exception as e:
    print(f"Error: {str(e)}")
"""

if __name__ == "__main__":
    print("SMS Gateway Python SDK")
    print("Import this module to use the SDK in your application.") 
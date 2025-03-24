# SMS Gateway API

A robust and scalable SMS Gateway API service that allows developers to send SMS messages and OTP codes programmatically.

## Features

- **SMS Sending**: Send SMS messages to any phone number worldwide
- **OTP Generation**: Generate and verify one-time passwords
- **Multiple Providers**: Fallback between different SMS providers for reliability
- **Comprehensive Dashboard**: Monitor usage, view logs, and manage your account
- **Detailed Analytics**: Track delivery rates and usage patterns
- **Flexible Pricing Plans**: Choose the plan that fits your needs

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (for PHP dependencies)

### Installation

1. Clone the repository
   ```bash
   git clone https://github.com/ToxicBoyQx/sms-gateway.git
   cd sms-gateway
   ```

2. Install dependencies
   ```bash
   composer install
   ```

3. Configure your database
   ```bash
   cp config/database.example.php config/database.php
   # Edit database.php with your database credentials
   ```

4. Import the database schema
   ```bash
   mysql -u username -p database_name < database/schema.sql
   ```

5. Start the server
   ```bash
   php -S localhost:8000
   ```

## API Documentation

### Authentication

All API requests require an API key which should be included in the header:






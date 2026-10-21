# Laravel bKash Payment Gateway Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sabitahmad/laravel-bkash.svg?style=flat-square)](https://packagist.org/packages/sabitahmad/laravel-bkash)
[![Total Downloads](https://img.shields.io/packagist/dt/sabitahmad/laravel-bkash.svg?style=flat-square)](https://packagist.org/packages/sabitahmad/laravel-bkash)

A comprehensive solution for integrating bKash payments into Laravel applications.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Response Handling](#paymentresponse-methods)
- [License](#license)

---

## Features
- **Complete bKash API v1.2.0 Integration**: Full support for all bKash APIs
- **Tokenized Checkout**: Agreement management for faster payments
- **Regular Checkout**: Traditional payment flow
- **Sandbox & Production Modes**: Easy environment switching
- **Advanced Token Management**: Auto-refresh with retry mechanisms
- **Comprehensive Payment Operations**: Create, Execute, Query, Refund, Search
- **Agreement Management**: Create, Execute, Query, Cancel agreements
- **Enhanced Exception Handling**: Detailed error reporting and handling
- **Transaction Logging**: Database logging with enhanced tracking
- **Network Resilience**: Automatic retries and timeout handling
- **Parameter Validation**: Strict validation with configurable options
- **Helper Utilities**: Common operations and utilities
- **Event-driven Architecture**: Laravel events integration
- **Customizable Configuration**: Flexible configuration options

## Requirements
- PHP 8.0+
- Laravel 9.x+
- Composer
- bKash Merchant Account

---

## Installation

You can install the package via composer:

```bash
composer require sabitahmad/laravel-bkash
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-bkash-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-bkash-config"
```

This is the contents of the published config file:

```php
return [
    'sandbox' => env('BKASH_SANDBOX', true),
    'log_transactions' => env('BKASH_LOG_TRANSACTIONS', true),
    'credentials' => [
        'app_key' => env('BKASH_APP_KEY'),
        'app_secret' => env('BKASH_APP_SECRET'),
        'username' => env('BKASH_USERNAME'),
        'password' => env('BKASH_PASSWORD'),
    ],
    'urls' => [
        'sandbox' => [
            'token' => 'https://checkout.sandbox.bka.sh/v1.2.0-beta/checkout/token/grant',
            // ... other endpoints
        ],
        'production' => [
            // Production endpoints
        ]
    ],
    'callback_url' => env('BKASH_CALLBACK_URL', '/bkash/callback'),
    'redirect_url' => env('BKASH_REDIRECT_URL', '/payment/redirect'),
];
```

Optionally, you can publish the views using

[//]: # (```bash)

[//]: # (php artisan vendor:publish --tag="laravel-bkash-views")

[//]: # (```)

## Configuration

Add the following environment variables to your `.env` file:

```dotenv
# Required Configuration
BKASH_SANDBOX=true
BKASH_APP_KEY=your_app_key
BKASH_APP_SECRET=your_app_secret
BKASH_USERNAME=your_username
BKASH_PASSWORD=your_password

# URL Configuration
BKASH_CALLBACK_URL=/bkash/callback
BKASH_REDIRECT_URL=/payment/redirect

# Optional Configuration
BKASH_LOG_TRANSACTIONS=true
BKASH_TIMEOUT=30
BKASH_RETRY_ATTEMPTS=3
BKASH_RETRY_DELAY=1000
BKASH_TOKEN_CACHE_TTL=3300
BKASH_CURRENCY=BDT

# Validation Settings
BKASH_STRICT_VALIDATION=true
BKASH_MAX_AMOUNT=999999.99
BKASH_MIN_AMOUNT=1.00
```
## Usage

### Agreement Management (Tokenized Checkout)

#### Create Agreement
```php
use SabitAhmad\Bkash\Facades\Bkash;
use SabitAhmad\Bkash\Exceptions\BkashException;

try {
    $agreement = Bkash::createAgreement('CUST-001');
    
    if ($agreement->isSuccess()) {
        // Redirect user to bKash for agreement
        return redirect()->away($agreement->getAgreementUrl());
    }
    
    throw new Exception('Agreement creation failed: ' . $agreement->getErrorMessage());
} catch (BkashException $e) {
    // Handle exception
}
```

#### Execute Agreement
```php
// After user completes agreement on bKash page
$agreement = Bkash::executeAgreement($paymentId);

if ($agreement->isSuccess()) {
    $agreementId = $agreement->getAgreementId();
    // Store agreementId for future payments
}
```

### Payment Operations

#### Create Payment (Regular)
```php
try {
    $payment = Bkash::createPayment(
        payerReference: 'CUST-001',
        amount: 100.50,
        invoiceNumber: 'INV-001'
    );
    
    if ($payment->isSuccess()) {
        return redirect()->away($payment->getPaymentUrl());
    }
    
    throw new Exception('Payment creation failed: ' . $payment->getErrorMessage());
} catch (BkashException $e) {
    // Handle exception
}
```

#### Create Payment (Tokenized with Agreement)
```php
try {
    $payment = Bkash::createPayment(
        payerReference: 'CUST-001',
        amount: 100.50,
        invoiceNumber: 'INV-001',
        callbackURL: '/payment/callback',
        agreementId: 'AGR123456' // Use existing agreement
    );
    
    if ($payment->isSuccess()) {
        return redirect()->away($payment->getPaymentUrl());
    }
} catch (BkashException $e) {
    // Handle exception
}
```

### `Execute Payment`
```php

$execution = Bkash::executePayment($paymentId);

if ($execution->getTransactionStatus() === 'Completed') {
    // Payment successful
    $trxId = $execution->getTrxId();
}

```
    
### `Handle Callback`

```php
Route::post('/bkash/callback', function (Request $request) {
    $payment = Bkash::executePayment($request->paymentID);
    
    if ($payment->isSuccess()) {
        event(new PaymentCompleted($payment));
        return redirect('/success');
    }
    
    return redirect('/failed');
});
```

### Additional Operations

#### Refund Payment
```php
$refund = Bkash::refundPayment('TRX123456', 100.50, 'Customer requested refund');

if ($refund->isSuccess()) {
    $refundId = $refund->getRefundId();
    echo "Refund successful. Refund ID: {$refundId}";
}
```

#### Query Payment Status
```php
$query = Bkash::queryPayment('PAY123456');

if ($query->isCompleted()) {
    echo "Payment completed successfully";
    echo "Transaction ID: " . $query->getTrxId();
}
```

#### Search Transaction
```php
$search = Bkash::searchTransaction('TRX123456');

if ($search->isSuccess()) {
    $transactions = $search->getTransactions();
    foreach ($transactions as $transaction) {
        echo "Amount: " . $transaction['amount'];
        echo "Status: " . $transaction['transactionStatus'];
    }
}
```

#### Refund Status Check
```php
$refundStatus = Bkash::refundStatus('PAY123456', 'REF789012');

if ($refundStatus->isSuccess()) {
    echo "Refund Status: " . $refundStatus->getTransactionStatus();
}
```

#### Agreement Operations
```php
// Query Agreement
$agreement = Bkash::queryAgreement('AGR123456');

// Cancel Agreement
$cancelResult = Bkash::cancelAgreement('AGR123456');
```

### Using the Helper Class
```php
use SabitAhmad\Bkash\Helpers\BkashHelper;

$helper = new BkashHelper(app(Bkash::class));

// Process complete payment flow
$payment = $helper->processPayment('CUST-001', 100.50, 'INV-001');

// Handle callback
$result = $helper->handleCallback($request->all());

// Get payment status with detailed info
$status = $helper->getPaymentStatus('PAY123456');

// Generate unique references
$payerRef = BkashHelper::generatePayerReference('CUSTOMER');
$invoiceNum = BkashHelper::generateInvoiceNumber('ORDER');
```


## Response Methods

### BaseResponse Methods (Available in All Response Classes)
| Method              | Returns    | Description                    |
|---------------------|------------|--------------------------------|
| `isSuccess()`       | `bool`     | Whether operation succeeded    |
| `hasError()`        | `bool`     | Whether operation has error    |
| `getErrorMessage()` | `?string`  | Error message if failed        |
| `getStatusCode()`   | `?string`  | bKash status code              |
| `getStatusMessage()`| `?string`  | bKash status message           |
| `getErrorCode()`    | `?string`  | bKash error code               |
| `getRawData()`      | `array`    | Complete API response          |
| `toArray()`         | `array`    | Response as array              |

### PaymentResponse Methods
| Method                   | Returns    | Description                    |
|--------------------------|------------|--------------------------------|
| `getPaymentId()`         | `?string`  | Payment ID                     |
| `getPaymentUrl()`        | `?string`  | Redirect URL for payment       |
| `getTrxId()`             | `?string`  | bKash transaction ID           |
| `getAmount()`            | `?float`   | Transaction amount             |
| `getCustomerMsisdn()`    | `?string`  | Customer phone number          |
| `getTransactionStatus()` | `?string`  | "Completed"/"Failed" etc       |
| `getInvoiceNumber()`     | `?string`  | Merchant invoice number        |
| `getPayerReference()`    | `?string`  | Payer reference                |
| `getCurrency()`          | `?string`  | Transaction currency           |
| `getIntent()`            | `?string`  | Payment intent                 |
| `getMode()`              | `?string`  | Payment mode                   |
| `getPaymentExecuteTime()`| `?Carbon`  | Execution timestamp            |
| `getCreateTime()`        | `?Carbon`  | Creation timestamp             |
| `getUpdateTime()`        | `?Carbon`  | Last update timestamp          |
| `isCompleted()`          | `bool`     | Whether payment completed      |
| `isCancelled()`          | `bool`     | Whether payment cancelled      |
| `isFailed()`             | `bool`     | Whether payment failed         |

### AgreementResponse Methods
| Method                    | Returns    | Description                    |
|---------------------------|------------|--------------------------------|
| `getAgreementId()`        | `?string`  | Agreement ID                   |
| `getAgreementUrl()`       | `?string`  | Redirect URL for agreement     |
| `getAgreementStatus()`    | `?string`  | Agreement status               |
| `getPayerReference()`     | `?string`  | Payer reference                |
| `getCustomerMsisdn()`     | `?string`  | Customer phone number          |
| `getAgreementExecuteTime()`| `?Carbon` | Agreement execution time       |

### RefundResponse Methods
| Method                   | Returns    | Description                    |
|--------------------------|------------|--------------------------------|
| `getRefundId()`          | `?string`  | Refund transaction ID          |
| `getOriginalPaymentId()` | `?string`  | Original payment ID            |
| `getRefundAmount()`      | `?float`   | Refunded amount                |
| `getTransactionStatus()` | `?string`  | Refund status                  |
| `getRefundTime()`        | `?Carbon`  | Refund timestamp               |
| `getCharge()`            | `?float`   | Refund charge                  |
| `getTrxId()`             | `?string`  | Transaction ID                 |
| `getPaymentId()`         | `?string`  | Payment ID                     |
| `getCurrency()`          | `?string`  | Currency                       |
| `getReason()`            | `?string`  | Refund reason                  |

### QueryResponse Methods
| Method                   | Returns    | Description                    |
|--------------------------|------------|--------------------------------|
| `getPaymentId()`         | `?string`  | Payment ID                     |
| `getTrxId()`             | `?string`  | Transaction ID                 |
| `getTransactionStatus()` | `?string`  | Current payment status         |
| `getAmount()`            | `?float`   | Transaction amount             |
| `getCustomerMsisdn()`    | `?string`  | Customer phone number          |
| `getMerchantInvoiceNumber()` | `?string` | Invoice number               |
| `getCurrency()`          | `?string`  | Transaction currency           |
| `getIntent()`            | `?string`  | Payment intent                 |
| `getInitiationTime()`    | `?Carbon`  | Transaction initiation time    |
| `getCompletedTime()`     | `?Carbon`  | Transaction completion time    |
| `isCompleted()`          | `bool`     | Whether transaction completed  |
| `isCancelled()`          | `bool`     | Whether transaction cancelled  |
| `isFailed()`             | `bool`     | Whether transaction failed     |

### SearchResponse Methods
| Method                       | Returns    | Description                    |
|------------------------------|------------|--------------------------------|
| `getTransactions()`          | `array`    | Array of transactions          |
| `getTransactionCount()`      | `int`      | Number of transactions         |
| `getFirstTransaction()`      | `?array`   | First transaction              |
| `getTransactionByTrxId()`    | `?array`   | Find transaction by trx ID     |
| `getTransactionByPaymentId()`| `?array`   | Find transaction by payment ID |

## Controller Example

See `examples/BkashController.php` for a complete implementation example showing:
- Agreement creation and handling
- Payment processing (both regular and tokenized)
- Callback handling
- Refund processing
- Transaction querying and searching
- Error handling

## Events

The package dispatches the following events:

- `SabitAhmad\Bkash\Events\PaymentCompleted` - When a payment is successfully completed
- `SabitAhmad\Bkash\Events\PaymentFailed` - When a payment fails
- `SabitAhmad\Bkash\Events\AgreementCreated` - When an agreement is successfully created

You can listen to these events in your `EventServiceProvider`:

```php
protected $listen = [
    \SabitAhmad\Bkash\Events\PaymentCompleted::class => [
        \App\Listeners\HandlePaymentCompleted::class,
    ],
    \SabitAhmad\Bkash\Events\PaymentFailed::class => [
        \App\Listeners\HandlePaymentFailed::class,
    ],
];
```

## Configuration Options

The package supports extensive configuration options:

- **Timeout Settings**: Configure API request timeouts and retry attempts
- **Validation**: Strict parameter validation with configurable limits
- **Caching**: Token caching with configurable TTL
- **Logging**: Enhanced transaction logging with detailed information
- **Error Handling**: Comprehensive error handling with retry mechanisms

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Recent Improvements

### Version 2.0.0 (Latest)
- ✅ **Complete Tokenized Checkout Support** - Agreement management APIs
- ✅ **Enhanced Response Classes** - More methods and better data access
- ✅ **Advanced Error Handling** - Retry mechanisms and detailed exceptions
- ✅ **Network Resilience** - Automatic retries and timeout handling
- ✅ **Search Transaction API** - Find transactions by various criteria
- ✅ **Refund Status API** - Track refund progress
- ✅ **Helper Utilities** - Common operations and utilities
- ✅ **Event System** - Laravel events for payment lifecycle
- ✅ **Enhanced Database Logging** - Better transaction tracking
- ✅ **Improved Configuration** - More flexible settings
- ✅ **Parameter Validation** - Strict validation with configurable options
- ✅ **Code Optimization** - Removed duplicates and improved performance
- ✅ **Updated API URLs** - Latest bKash endpoint configurations
- ✅ **Better Token Management** - Enhanced caching and refresh logic

## Credits

- [Sabit Ahmad](https://github.com/SabitAhmad)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

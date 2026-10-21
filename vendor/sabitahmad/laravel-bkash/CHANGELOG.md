# Changelog

All notable changes to `laravel-bkash` will be documented in this file.

## [2.0.0] - 2024-09-24

### Added
- **Complete Tokenized Checkout Support**: Added agreement management APIs (create, execute, query, cancel)
- **Enhanced Response Classes**: Added more methods and better data access across all response types
- **Search Transaction API**: Added ability to search transactions by transaction ID
- **Refund Status API**: Added ability to check refund status
- **Helper Class**: Added BkashHelper class with common utilities and operations
- **Event System**: Added Laravel events for payment lifecycle (PaymentCompleted, PaymentFailed, AgreementCreated)
- **Enhanced Exception Handling**: Added specific exception types and better error categorization
- **Network Resilience**: Added automatic retry mechanisms and timeout handling
- **Parameter Validation**: Added strict validation with configurable options
- **Advanced Configuration**: Added timeout, retry, and validation configuration options
- **Enhanced Database Logging**: Improved transaction logging with more fields and better tracking
- **Code Examples**: Added comprehensive controller examples

### Changed
- **Updated API URLs**: Updated to latest bKash endpoint configurations
- **Improved Token Management**: Enhanced caching and refresh logic with better error handling
- **Enhanced Database Migration**: Updated migration with new fields and indexes for better performance
- **Better Error Messages**: More descriptive error messages and proper error codes
- **Code Organization**: Restructured code for better maintainability and performance

### Fixed
- **Duplicate Code Removal**: Eliminated redundant code and improved efficiency
- **Token Refresh Issues**: Fixed token refresh failures and added fallback mechanisms
- **Response Parsing**: Improved response parsing and error handling
- **Memory Usage**: Optimized memory usage in response classes

### Security
- **Input Validation**: Enhanced parameter validation to prevent security issues
- **Error Information**: Reduced sensitive information exposure in error messages

## [1.0.0] - 2024-01-01

### Added
- Initial release with basic bKash integration
- Payment creation and execution
- Query and refund operations
- Basic exception handling
- Transaction logging
- Configuration managementhangelog

All notable changes to `Laravel-Bkash` will be documented in this file.

## Version 1.0.0 - 2025-04-02 - 2025-04-02

New Features

- Full bKash API v1.2.0 Integration: Seamless integration with bKash payment gateway.
- Sandbox & Production Modes: Support for both sandbox and production environments.
- Token Management: Automatic token refresh for uninterrupted transactions.
- Payment Operations: Create, execute, and query payments.
- Refund Operations: Process refunds for completed transactions.
- Exception Handling: Comprehensive error handling for all operations.
- Transaction Logging: Log transactions with a UI component for easy monitoring.
- Event-Driven Architecture: Trigger events on payment completion and other actions.
- Customizable Responses: Tailor responses to fit your application needs.
- Built-in Laravel HTTP Client: Utilize Laravel's HTTP client for API requests.

Improvements

- Enhanced Documentation: Detailed README with installation, configuration, and usage instructions.
- Code Style Fixes: Automated code style fixes using Laravel Pint.
- Testing: Comprehensive test suite using PestPHP.

Bug Fixes

- Compatibility Fixes: Ensured compatibility with Laravel versions 10, 11, and 12.
- Dependency Updates: Updated dependencies to the latest stable versions.

**Full Changelog**: https://github.com/sabitahmadumid/laravel-bkash/commits/v1.0.0

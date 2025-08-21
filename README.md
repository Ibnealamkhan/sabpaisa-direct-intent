# sabpaisa-direct-intent

## Prerequisites

- **SabPaisa Intent** must be enabled in your payment gateway account.
- You must have a valid SabPaisa merchant account and credentials.

## Configuration

- All configuration settings (client code, username, password, keys, etc.) should be set in the `config.php` file.

## Usage

1. Ensure `config.php` is properly configured with your SabPaisa credentials.
2. Include the package files in your project.
3. Use the provided classes and methods to initiate payments via SabPaisa Intent.

## Example

```php
include_once 'config.php';
include_once 'Sabpaisa.php';

$sabpaisa = new Sabpaisa();
// ...initialize payment as shown in index.php...
```

# PayNKolay for WooCommerce

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-6.0%2B-96588A)](https://woocommerce.com)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)](https://php.net)

WooCommerce payment gateway plugin for [PayNKolay](https://paynkolay.com.tr) virtual POS. Accept credit card payments on your WooCommerce store with 3D Secure support.

PayNKolay sanal POS ile WooCommerce mağazanızda kredi kartı ile 3D Secure destekli ödeme alın.

---

## Features

- 3D Secure payment support
- WooCommerce Blocks (new checkout) compatibility
- WooCommerce Subscriptions support
- Test mode for development and testing
- Hash verification for transaction security
- Turkish language support

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 5.8+ |
| WooCommerce | 6.0+ |
| PHP | 7.4+ |

## Installation

### Manual Installation

1. Download the [latest release](https://github.com/paynkolay/paynkolay-woocommerce/releases) `.zip` file
2. Go to **WordPress Admin > Plugins > Add New > Upload Plugin**
3. Upload the `.zip` file and click **Install Now**
4. Activate the plugin

### From Source

```bash
git clone https://github.com/paynkolay/paynkolay-woocommerce.git
cd paynkolay-woocommerce
npm install   # optional, only needed for JS development
```

Copy or symlink the plugin directory into `wp-content/plugins/`.

## Configuration

1. Go to **WooCommerce > Settings > Payments**
2. Enable **Pay N Kolay**
3. Click **Manage** and fill in the settings:

| Setting | Description |
|---|---|
| **Token (SX)** | Your merchant token from the PayNKolay dashboard |
| **Merchant Secret Key** | Your secret key from the PayNKolay dashboard |
| **Test Mode** | Enable to use the test environment for development |

## Test Mode

When test mode is enabled, transactions are processed through the PayNKolay test environment (`paynkolaytest.nkolayislem.com.tr`). No real charges are made.

You can obtain test credentials from your PayNKolay merchant dashboard.

## Development

### Prerequisites

- [Node.js](https://nodejs.org/) 18+
- [Composer](https://getcomposer.org/) (for PHP dev dependencies)
- [Docker](https://www.docker.com/) (optional, for `wp-env`)

### Local Development Environment

```bash
# Start a local WordPress environment with WooCommerce
npx wp-env start

# Watch for JS changes and rebuild
npm run start

# Build for production
npm run build

# Create a distributable zip
npm run plugin-zip
```

### Project Structure

```
paynkolay-woocommerce/
├── assets/js/frontend/     # Built JS (checked in for distribution)
├── includes/               # PHP classes
│   ├── blocks/             # WooCommerce Blocks integration
│   └── class-wc-gateway-paynkolay.php
├── languages/              # Translation files
├── src/js/frontend/        # JS source files
├── views/                  # Payment form templates
├── tests/                  # PHPUnit tests
└── woocommerce-gateway-paynkolay.php  # Main plugin file
```

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -m 'Add my feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Open a Pull Request

## License

This project is licensed under the [MIT License](LICENSE).

## Support

- [GitHub Issues](https://github.com/paynkolay/paynkolay-woocommerce/issues)
- [PayNKolay Website](https://paynkolay.com.tr)

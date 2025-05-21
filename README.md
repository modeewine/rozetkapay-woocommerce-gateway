# Buy with RozetkaPay Gateway for WooCommerce

<p align="center">
  <img src="assets/img/rozetkapay-logo.svg" alt="RozetkaPay Logo" width="200" />
</p>

WooCommerce Payment Gateway for Buy with RozetkaPay

---

## Description

**Buy with RozetkaPay Gateway for WooCommerce** is a payment gateway plugin that allows your WooCommerce store to accept payments via RozetkaPay. It supports both standard checkout and a fast "Pay one-click" button for seamless customer experience.

- **Version:** 1.0.0
- **Author:** RozetkaPay
- **License:** GPL2
- **Requires PHP:** 7.3+
- **Requires WordPress:** 6.2+
- **Requires WooCommerce:** 8.0+
- **Tested up to WooCommerce:** 9.8

---

## Features

- Accept payments via RozetkaPay in WooCommerce checkout
- "Pay one-click" button on product and cart pages
- Supports refunds directly from WooCommerce admin
- Handles RozetkaPay callbacks for payment and refund status
- Admin metabox for RozetkaPay order/payment info
- Payment logs (requests, callbacks, errors) with admin UI
- Multi-language support (translation ready)

---

## Installation

1. **Download** or clone this repository to your `wp-content/plugins` directory:
   ```sh
   git clone <repo-url> wp-content/plugins/buy-rozetkapay-woocommerce
   ```
2. **Activate** the plugin via the WordPress admin panel (`Plugins > Installed Plugins`).
3. **Ensure WooCommerce is active** and your site meets the minimum requirements (PHP 7.3+, WooCommerce 8.0+).

---

## Configuration

1. Go to `WooCommerce > Settings > Payments`.
2. Find **RozetkaPay** in the list and click **Manage**.
3. Configure the following options:
   - **Enable/Disable**: Toggle RozetkaPay as a payment method.
   - **API Login**: Enter your RozetkaPay API login.
   - **API Password**: Enter your RozetkaPay API password.
   - **Enable/Disable cart checkout payment gateway**: Show/hide RozetkaPay in the checkout.
   - **Enable/Disable "Pay one-click" button**: Show/hide the fast payment button on product/cart pages.
   - **"Pay one-click" button view mode**: Choose between black or white button style.

---

## Usage

- **Standard Checkout**: Customers can select RozetkaPay at checkout and complete payment via the RozetkaPay interface.
- **Pay One-Click**: If enabled, a "Pay one-click" button appears on product and cart pages for instant checkout.
- **Refunds**: Process refunds from the WooCommerce order admin; RozetkaPay will be notified automatically.

---

## Admin Features

- **Order Metabox**: View RozetkaPay payment info, receipt, and resend callbacks from the order edit screen.
- **Logs**: Access RozetkaPay logs (`WooCommerce > RozetkaPay Logs`) for requests, callbacks, and errors. Logs can be cleared from the admin UI.
- **Payment Info Page**: View detailed payment info for each order.

---

## Logs

- Logs are stored in the `logs/` directory inside the plugin folder.
- Types: `requests`, `callbacks`, `errors` (viewable and clearable from the admin panel).

---

## Translation

- Translation files are located in the `languages/` directory.
- The plugin is ready for localization. Use `.pot` files to create your own translations.

---

## Assets

- Custom styles and images are in the `assets/` directory.
- The plugin includes a branded RozetkaPay button and logo.

---

## Support

For support, please contact [RozetkaPay](https://rozetkapay.com/) or open an issue in this repository.

---

## License

This plugin is licensed under the GPL2. See the LICENSE file for details.

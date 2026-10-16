# Changelog

## 1.2.0 - 2026-10-16

Security release. All merchants should update.

### Security

- **Payment callback is now bound to the order, the amount and a single gateway transaction.**
  Before, the order was looked up only by `CLIENT_REFERENCE_CODE`, which the gateway does not sign.
  The amount was never compared and a gateway `REFERENCE_CODE` could be reused. That meant one small
  real payment could be replayed to mark other, more expensive orders as paid. The callback now:
  - looks up the order only through payment attempts the plugin stored on the order when it built the
    payment form (reference, expected amount, currency). Every reference issued for an order is kept,
    so the callback for an older payment-page view still finds the order;
  - requires the signed `AUTHORIZATION_AMOUNT` to be at least the amount sent and the current order
    total (compared in integer cents; installment commission may raise it);
  - requires the order currency to be TRY (the only currency the form charges). With `hashDataV2`
    the signed `CURRENCY_CODE` must also be TRY;
  - requires the order to still need payment and to use this payment method. A repeat of the callback
    that already paid the order just redirects to the order-received page;
  - treats `REFERENCE_CODE` as single-use. It is stored as the order transaction id
    (`payment_complete( $reference_code )`) and a callback is rejected if any order already holds it.
- **Hash verification hardened.** `hashDataV2` (SHA-512, `|`-separated, includes `CURRENCY_CODE`) is
  checked whenever the gateway sends it (`hashDataV2` or `hashDatav2`). A v2 mismatch is final, with no
  fallback to the weaker v1 `hashData`. Comparisons use `hash_equals()`. Callbacks are rejected when
  the merchant secret key is empty.
- **Success rule matches the official documentation.** `RESPONSE_CODE` must be `2` and `AUTH_CODE`
  must not be empty, `0` or `00`.
- Failed or invalid callbacks never change the order. The customer sees a fixed, generic message;
  gateway text (`RESPONSE_DATA`) is no longer passed through from the success callback. The failure
  notice that the `Reason` parameter shows is now HTML-escaped.

### Changed

- The payment method is now **disabled by default** on new installs, until the merchant enables it.
  Existing saved settings are not changed.
- Shop managers see an admin warning while the gateway is enabled in test mode.
- The payment page refuses to start a payment for orders not in TRY.
- `payment_complete()` sets the order status itself (processing, or completed for virtual/downloadable
  orders). The extra forced `processing` status update is gone.

### Merchant action required

- Make sure **Merchant Secret Key** is filled in under WooCommerce > Settings > Payments > Pay N Kolay.
  If it is empty, every payment callback is now rejected.
- Live stores: turn test mode off and enter your live Token (SX) and secret. In test mode, orders can
  be paid with public test cards.
- Orders whose payment page was opened before the update are still accepted, checked against the
  order total.

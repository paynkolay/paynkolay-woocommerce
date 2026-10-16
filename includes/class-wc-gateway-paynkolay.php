<?php
/**
 * WC_Gateway_Paynkolay class.
 *
 * @package WooCommerce PayNKolay Payments Gateway
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PayNKolay Gateway.
 *
 * @class   WC_Gateway_Paynkolay
 * @version 1.2.0
 */
class WC_Gateway_Paynkolay extends WC_Payment_Gateway {

	/**
	 * Payment gateway instructions.
	 *
	 * @var string
	 */
	protected $instructions;

	/**
	 * Whether the gateway is visible for non-admin users.
	 *
	 * @var string
	 */
	protected $hide_for_non_admin_users;

	/**
	 * Unique id for the gateway.
	 *
	 * @var string
	 */
	public $id = 'paynkolay';

	/**
	 * Test mode setting.
	 *
	 * @var string
	 */
	public $testmode;

	/**
	 * Token (SX).
	 *
	 * @var string
	 */
	public $token;

	/**
	 * Merchant Secret Key.
	 *
	 * @var string
	 */
	public $merchant_secret_key;

	/**
	 * Order meta key holding every payment attempt issued for the order, keyed by clientRefCode.
	 */
	const ATTEMPTS_META_KEY = '_paynkolay_payment_attempts';

	/**
	 * Maximum number of payment attempts remembered per order.
	 */
	const MAX_ATTEMPTS = 50;

	/**
	 * The only currency the payment form charges in (no currency field is sent, gateway default).
	 */
	const CURRENCY = 'TRY';

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		$this->icon               = apply_filters( 'woocommerce_paynkolay_gateway_icon', '' );
		$this->has_fields         = false;
		$this->supports           = array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions',
		);

		$this->method_title       = _x( 'Pay N Kolay', 'Paynkolay payment method', 'woocommerce-gateway-paynkolay' );
		$this->method_description = __( 'Ödemenizi PayNKolay ile güvenli bir şekilde yapabilirsiniz.', 'woocommerce-gateway-paynkolay' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->testmode            = $this->get_option( 'testmode' );
		$this->token               = $this->get_option( 'token' );
		$this->merchant_secret_key = $this->get_option( 'merchant_secret_key' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_scheduled_subscription_payment_paynkolay', array( $this, 'process_subscription_payment' ), 10, 2 );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_paynkolay', array( $this, 'webhook' ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Aktif/Pasif', 'woocommerce-gateway-paynkolay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Ödeme yöntemini aktifleştir.', 'woocommerce-gateway-paynkolay' ),
				'default' => 'no',
			),
			'title'       => array(
				'title'       => __( 'Başlık', 'woocommerce-gateway-paynkolay' ),
				'type'        => 'text',
				'description' => __( 'Ödeme sayfasındaki başlığı bu alandan değiştirebilirsiniz.', 'woocommerce-gateway-paynkolay' ),
				'default'     => __( 'Kredi Kartı ile Öde', 'woocommerce-gateway-paynkolay' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Açıklama', 'woocommerce-gateway-paynkolay' ),
				'type'        => 'textarea',
				'description' => __( 'Ödeme sayfasındaki açıklamayı bu alandan değiştirebilirsiniz.', 'woocommerce-gateway-paynkolay' ),
				'default'     => __( 'PayNKolay ile güvenli bir şekilde ödeme yapabilirsiniz.', 'woocommerce-gateway-paynkolay' ),
			),
			'testmode'    => array(
				'title'       => __( 'Test Modu', 'woocommerce-gateway-paynkolay' ),
				'label'       => __( 'Test modunu aktifleştir.', 'woocommerce-gateway-paynkolay' ),
				'type'        => 'checkbox',
				'description' => __( 'Test modunu aktifleştirerek test kartları ile ödeme yapabilirsiniz.', 'woocommerce-gateway-paynkolay' ),
				'default'     => 'yes',
			),
			'token'       => array(
				'title'       => __( 'Token (SX)', 'woocommerce-gateway-paynkolay' ),
				'type'        => 'text',
				'description' => __( 'PayNKolay yönetim panelinden alacağınız Token (SX Değeri) bilgisini bu alana girebilirsiniz. Test modu için varsayılan değer önceden doldurulmuştur.', 'woocommerce-gateway-paynkolay' ),
				'default'     => '118591467|bScbGDYCtPf7SS1N6PQ6/+58rFhW1WpsWINqvkJFaJlu6bMH2tgPKDQtjeA5vClpzJP24uA0vx7OX53cP3SgUspa4EvYix+1C3aXe++8glUvu9Oyyj3v300p5NP7ro/9K57Zcw==',
			),
			'merchant_secret_key' => array(
				'title'       => __( 'Merchant Secret Key', 'woocommerce-gateway-paynkolay' ),
				'type'        => 'text',
				'description' => __( 'PayNKolay yönetim panelinden alacağınız Merchant Secret Key bilgisini bu alana girebilirsiniz. Test modu için varsayılan değer önceden doldurulmuştur.', 'woocommerce-gateway-paynkolay' ),
				'default'     => '_YckdxUbv4vrnMUZ6VQsr',
			),
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Process subscription payment.
	 *
	 * @param float    $amount Subscription amount.
	 * @param WC_Order $order  Order object.
	 * @return void
	 * @throws Exception If payment fails.
	 */
	public function process_subscription_payment( $amount, $order ) {
		$payment_result = $this->get_option( 'result' );

		if ( 'success' === $payment_result ) {
			$order->payment_complete();
		} else {
			throw new Exception(
				__( 'Ödeme başarısız oldu. Lütfen ödeme ayarlarını kontrol ediniz.', 'woocommerce-gateway-paynkolay' )
			);
		}
	}

	/**
	 * Output the payment form on the receipt page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// The payment form does not send a currency, so the gateway always charges TRY.
		// Refuse to start a payment for an order in any other currency.
		if ( self::CURRENCY !== $order->get_currency() ) {
			echo '<p>' . esc_html__( 'Bu ödeme yöntemi yalnızca Türk Lirası (TRY) ile ödemeyi destekler.', 'woocommerce-gateway-paynkolay' ) . '</p>';
			return;
		}

		require_once plugin_dir_path( __DIR__ ) . 'views/iframe.php';
	}

	/**
	 * Remember one payment attempt (clientRefCode + expected amount + currency) on the order.
	 *
	 * The reference is regenerated on every payment page view, so every issued reference is kept
	 * (most recent last) and any of them can be resolved by the callback.
	 *
	 * @param WC_Order $order           Order object.
	 * @param string   $client_ref_code Reference sent to the gateway.
	 * @param string   $amount          Amount sent to the gateway, e.g. "870.00".
	 */
	public static function store_payment_attempt( $order, $client_ref_code, $amount ) {
		$attempts = $order->get_meta( self::ATTEMPTS_META_KEY, true );
		if ( ! is_array( $attempts ) ) {
			$attempts = array();
		}

		$attempts[ $client_ref_code ] = array(
			'amount_cents' => self::amount_to_cents( $amount ),
			'currency'     => self::CURRENCY,
			'created'      => time(),
		);

		// Keep the meta bounded; old attempts beyond the limit can no longer be resolved.
		if ( count( $attempts ) > self::MAX_ATTEMPTS ) {
			$attempts = array_slice( $attempts, -self::MAX_ATTEMPTS, null, true );
		}

		$order->update_meta_data( self::ATTEMPTS_META_KEY, $attempts );
		// Kept for backward compatibility with code reading the 1.1.x meta key.
		$order->update_meta_data( 'client_reference_code', $client_ref_code );
		$order->save();
	}

	/**
	 * Convert a decimal amount string ("870", "870.5", "870.00") to integer minor units.
	 *
	 * @param mixed $value Amount.
	 * @return int|null Amount in cents, or null when the value is not a plain non-negative decimal.
	 */
	public static function amount_to_cents( $value ) {
		$value = trim( (string) $value );
		if ( ! preg_match( '/^(\d{1,12})(?:\.(\d{1,2}))?$/', $value, $m ) ) {
			return null;
		}
		$fraction = isset( $m[2] ) ? str_pad( $m[2], 2, '0' ) : '00';
		return (int) $m[1] * 100 + (int) $fraction;
	}

	/**
	 * Response hash v1 (hashData): base64(sha1(fields concatenated without separator + secret)).
	 *
	 * @param array  $f      Callback fields.
	 * @param string $secret Merchant secret key.
	 * @return string
	 */
	public static function response_hash_v1( $f, $secret ) {
		$str = $f['MERCHANT_NO'] . $f['REFERENCE_CODE'] . $f['AUTH_CODE'] . $f['RESPONSE_CODE'] . $f['USE_3D'] . $f['RND'] . $f['INSTALLMENT'] . $f['AUTHORIZATION_AMOUNT'] . $secret;
		return base64_encode( sha1( $str, true ) );
	}

	/**
	 * Response hash v2 (hashDataV2): base64(sha512(fields joined with "|" + "|" + secret)).
	 *
	 * @param array  $f      Callback fields.
	 * @param string $secret Merchant secret key.
	 * @return string
	 */
	public static function response_hash_v2( $f, $secret ) {
		$str = implode(
			'|',
			array(
				$f['MERCHANT_NO'],
				$f['REFERENCE_CODE'],
				$f['AUTH_CODE'],
				$f['RESPONSE_CODE'],
				$f['USE_3D'],
				$f['RND'],
				$f['INSTALLMENT'],
				$f['AUTHORIZATION_AMOUNT'],
				$f['CURRENCY_CODE'],
				$secret,
			)
		);
		return base64_encode( hash( 'sha512', $str, true ) );
	}

	/**
	 * Verify the callback signature. hashDataV2 is preferred; when it is present a mismatch is
	 * final (no fallback to v1). v1 is only used when the gateway sent no v2 field.
	 *
	 * @param array  $f      Callback fields (must include hashData / hashDataV2 keys).
	 * @param string $secret Merchant secret key.
	 * @return string|false "v2" or "v1" for a valid signature, false otherwise.
	 */
	public static function verify_response_hash( $f, $secret ) {
		if ( '' === trim( (string) $secret ) ) {
			return false;
		}

		if ( '' !== $f['hashDataV2'] ) {
			return hash_equals( self::response_hash_v2( $f, $secret ), $f['hashDataV2'] ) ? 'v2' : false;
		}

		if ( '' !== $f['hashData'] ) {
			return hash_equals( self::response_hash_v1( $f, $secret ), $f['hashData'] ) ? 'v1' : false;
		}

		return false;
	}

	/**
	 * Official isPaymentSuccessful rule: RESPONSE_CODE is "2" and AUTH_CODE is not "", "0" or "00".
	 *
	 * @param array $f Callback fields.
	 * @return bool
	 */
	public static function is_payment_successful( $f ) {
		$auth_code = trim( $f['AUTH_CODE'] );
		return '2' === trim( $f['RESPONSE_CODE'] ) && ! in_array( $auth_code, array( '', '0', '00' ), true );
	}

	/**
	 * Whether a gateway CURRENCY_CODE value denotes TRY (alpha or numeric ISO 4217).
	 *
	 * @param string $code Currency code from the callback.
	 * @return bool
	 */
	public static function is_try_currency_code( $code ) {
		return in_array( strtoupper( trim( $code ) ), array( 'TRY', 'TL', '949' ), true );
	}

	/**
	 * Read one callback field as a raw string (no sanitizing, so the hash is computed over the
	 * exact bytes the gateway signed). Non-string values become "".
	 *
	 * @param string $key POST key.
	 * @return string
	 */
	private static function post_field( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Signed gateway callback, verified below.
		return ( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ) ? (string) wp_unslash( $_POST[ $key ] ) : '';
	}

	/**
	 * Redirect back to the payment page (or checkout) with a fixed failure message and stop.
	 *
	 * @param WC_Order|null $order   Order, when one was resolved.
	 * @param string        $message Fixed message, never request data.
	 */
	private function fail_redirect( $order, $message ) {
		$url = $order ? $order->get_checkout_payment_url() : wc_get_checkout_url();
		$url = add_query_arg(
			array(
				'success' => 'false',
				'Reason'  => base64_encode( $message ),
			),
			$url
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Handle the success callback from PayNKolay.
	 *
	 * The callback is a browser POST, so every field is customer-controllable. Only signed
	 * fields are trusted; CLIENT_REFERENCE_CODE is unsigned and used purely as a lookup key into
	 * the attempts stored server-side when the payment form was built.
	 */
	public function webhook() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			$this->fail_redirect( null, __( 'Geçersiz istek yöntemi.', 'woocommerce-gateway-paynkolay' ) );
		}

		$generic_error = __( 'Ödeme doğrulanamadı. Lütfen tekrar deneyin veya mağaza ile iletişime geçin.', 'woocommerce-gateway-paynkolay' );

		$fields = array();
		foreach ( array( 'MERCHANT_NO', 'REFERENCE_CODE', 'AUTH_CODE', 'RESPONSE_CODE', 'USE_3D', 'RND', 'INSTALLMENT', 'AUTHORIZATION_AMOUNT', 'CURRENCY_CODE', 'CLIENT_REFERENCE_CODE', 'hashData' ) as $key ) {
			$fields[ $key ] = self::post_field( $key );
		}
		// Accept both spellings of the v2 field.
		$fields['hashDataV2'] = self::post_field( 'hashDataV2' );
		if ( '' === $fields['hashDataV2'] ) {
			$fields['hashDataV2'] = self::post_field( 'hashDatav2' );
		}

		// 1. Signature. Rejected when the secret is empty or the hash does not match; no order is touched.
		$hash_version = self::verify_response_hash( $fields, $this->merchant_secret_key );
		if ( false === $hash_version ) {
			$this->fail_redirect( null, $generic_error );
		}

		// 2. Resolve the order from the reference this plugin issued: "WooCommerce|<order id>|Woo|<uniqid>".
		$client_ref_code = $fields['CLIENT_REFERENCE_CODE'];
		$ref_parts       = explode( '|', $client_ref_code );
		$order           = ( 4 === count( $ref_parts ) && 'WooCommerce' === $ref_parts[0] && ctype_digit( $ref_parts[1] ) )
			? wc_get_order( (int) $ref_parts[1] )
			: false;

		if ( ! $order || $this->id !== $order->get_payment_method() ) {
			$this->fail_redirect( null, $generic_error );
		}

		$attempts = $order->get_meta( self::ATTEMPTS_META_KEY, true );
		if ( is_array( $attempts ) && ! empty( $attempts ) ) {
			if ( ! isset( $attempts[ $client_ref_code ] ) ) {
				$this->fail_redirect( $order, $generic_error );
			}
			$attempt = $attempts[ $client_ref_code ];
		} elseif ( '' !== $client_ref_code && $order->get_meta( 'client_reference_code', true ) === $client_ref_code ) {
			// Payment page rendered by 1.1.x before the upgrade: no stored attempt, use the order total.
			$attempt = array(
				'amount_cents' => self::amount_to_cents( wc_format_decimal( $order->get_total(), 2 ) ),
				'currency'     => self::CURRENCY,
			);
		} else {
			$this->fail_redirect( $order, $generic_error );
		}

		$reference_code = trim( $fields['REFERENCE_CODE'] );

		// Idempotent replay of the callback that already paid this order.
		if ( ! $order->needs_payment() ) {
			if ( '' !== $reference_code && $order->get_transaction_id() === $reference_code && $order->is_paid() ) {
				wp_safe_redirect( $order->get_checkout_order_received_url() );
				exit;
			}
			$this->fail_redirect( null, $generic_error );
		}

		// 3. Gateway result.
		if ( ! self::is_payment_successful( $fields ) ) {
			$this->fail_redirect( $order, __( 'Ödeme işlemi başarısız oldu. Lütfen tekrar deneyin.', 'woocommerce-gateway-paynkolay' ) );
		}

		if ( '' === $reference_code ) {
			$this->fail_redirect( $order, $generic_error );
		}

		// 4. Amount: signed AUTHORIZATION_AMOUNT must cover the amount sent and the current order total.
		$auth_cents     = self::amount_to_cents( $fields['AUTHORIZATION_AMOUNT'] );
		$expected_cents = isset( $attempt['amount_cents'] ) ? $attempt['amount_cents'] : null;
		$order_cents    = self::amount_to_cents( wc_format_decimal( $order->get_total(), 2 ) );
		if ( null === $auth_cents || null === $expected_cents || null === $order_cents || $auth_cents < max( $expected_cents, $order_cents ) ) {
			$order->add_order_note( __( 'PayNKolay: ödeme tutarı sipariş tutarıyla uyuşmadığı için callback reddedildi.', 'woocommerce-gateway-paynkolay' ) );
			$this->fail_redirect( $order, $generic_error );
		}

		// 5. Currency: the form only ever charges TRY.
		$currency_ok = self::CURRENCY === $order->get_currency()
			&& ( ! isset( $attempt['currency'] ) || self::CURRENCY === $attempt['currency'] );
		if ( 'v2' === $hash_version && ! self::is_try_currency_code( $fields['CURRENCY_CODE'] ) ) {
			$currency_ok = false;
		}
		if ( ! $currency_ok ) {
			$this->fail_redirect( $order, $generic_error );
		}

		// 6. REFERENCE_CODE is single-use across all orders. The row lock (unique option_name,
		// INSERT IGNORE) stops two concurrent callbacks with the same reference from both passing.
		global $wpdb;
		$lock_name = 'paynkolay_ref_lock_' . md5( $reference_code );
		$locked    = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $lock_name, (string) time() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( 1 !== $locked ) {
			$this->fail_redirect( $order, $generic_error );
		}

		$used = wc_get_orders(
			array(
				'transaction_id' => $reference_code,
				'limit'          => 1,
				'return'         => 'ids',
			)
		);
		if ( ! empty( $used ) ) {
			delete_option( $lock_name );
			$this->fail_redirect( $order, $generic_error );
		}

		$order->payment_complete( $reference_code );
		// From here on the transaction id on the order is the single-use record.
		delete_option( $lock_name );
		$order->add_order_note(
			sprintf(
				/* translators: 1: gateway reference code, 2: authorized amount, 3: hash version, 4: test mode marker */
				__( 'PayNKolay ödemesi onaylandı. Referans: %1$s, tutar: %2$s, hash: %3$s%4$s', 'woocommerce-gateway-paynkolay' ),
				$reference_code,
				$fields['AUTHORIZATION_AMOUNT'],
				$hash_version,
				'yes' === $this->testmode ? ' (TEST MODU)' : ''
			)
		);

		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}

		wp_safe_redirect( $order->get_checkout_order_received_url() );
		exit;
	}
}

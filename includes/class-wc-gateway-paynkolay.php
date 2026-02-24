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
 * @version 1.1.0
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
				'default' => 'yes',
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

		require_once plugin_dir_path( __DIR__ ) . 'views/iframe.php';
	}

	/**
	 * Handle the webhook callback from PayNKolay.
	 */
	public function webhook() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_redirect( wc_get_checkout_url() . '?success=false&Reason=' . base64_encode( 'Geçersiz istek yöntemi.' ) );
			exit;
		}

		$merchant_secret_key = get_option( 'woocommerce_paynkolay_settings' )['merchant_secret_key'];

		$reference_code      = isset( $_POST['REFERENCE_CODE'] ) ? sanitize_text_field( wp_unslash( $_POST['REFERENCE_CODE'] ) ) : '';
		$response_code       = isset( $_POST['RESPONSE_CODE'] ) ? sanitize_text_field( wp_unslash( $_POST['RESPONSE_CODE'] ) ) : '';
		$response_data       = isset( $_POST['RESPONSE_DATA'] ) ? sanitize_text_field( wp_unslash( $_POST['RESPONSE_DATA'] ) ) : '';
		$use_3d              = isset( $_POST['USE_3D'] ) ? sanitize_text_field( wp_unslash( $_POST['USE_3D'] ) ) : '';
		$merchant_no         = isset( $_POST['MERCHANT_NO'] ) ? sanitize_text_field( wp_unslash( $_POST['MERCHANT_NO'] ) ) : '';
		$auth_code           = isset( $_POST['AUTH_CODE'] ) ? sanitize_text_field( wp_unslash( $_POST['AUTH_CODE'] ) ) : '';
		$client_ref_code     = isset( $_POST['CLIENT_REFERENCE_CODE'] ) ? sanitize_text_field( wp_unslash( $_POST['CLIENT_REFERENCE_CODE'] ) ) : '';
		$auth_amount         = isset( $_POST['AUTHORIZATION_AMOUNT'] ) ? sanitize_text_field( wp_unslash( $_POST['AUTHORIZATION_AMOUNT'] ) ) : '';
		$installment         = isset( $_POST['INSTALLMENT'] ) ? sanitize_text_field( wp_unslash( $_POST['INSTALLMENT'] ) ) : '';
		$rnd                 = isset( $_POST['RND'] ) ? sanitize_text_field( wp_unslash( $_POST['RND'] ) ) : '';
		$hash_data           = isset( $_POST['hashData'] ) ? sanitize_text_field( wp_unslash( $_POST['hashData'] ) ) : '';

		// Verify hash integrity.
		$hash_str     = $merchant_no . $reference_code . $auth_code . $response_code . $use_3d . $rnd . $installment . $auth_amount . $merchant_secret_key;
		$hash_created = base64_encode( pack( 'H*', sha1( $hash_str ) ) );

		global $woocommerce;

		$orders = wc_get_orders(
			array(
				'meta_key'   => 'client_reference_code',
				'meta_value' => $client_ref_code,
				'limit'      => 1,
			)
		);

		if ( empty( $orders ) ) {
			wp_redirect( wc_get_checkout_url() . '?success=false&Reason=' . base64_encode( $response_data ) );
			exit;
		}

		$order = $orders[0];

		if ( ! $order ) {
			wp_redirect( wc_get_checkout_url() . '?success=false&Reason=' . base64_encode( 'Sipariş bulunamadı.' ) );
			exit;
		}

		if ( $hash_data !== $hash_created ) {
			wp_redirect( $order->get_checkout_payment_url() . '&success=false&Reason=' . base64_encode( 'HASH uyuşmazlığı! Üye işyeri iletişime geçiniz.' ) );
			exit;
		}

		// Prevent duplicate transactions.
		if ( '0' === $response_code && false !== strpos( $response_data, 'Bu referans numarası için daha önce işlem yapılmıştır' ) ) {
			wp_die( 'Transaction already processed for this reference number.' );
		}

		if ( '2' !== $response_code ) {
			wp_redirect( $order->get_checkout_payment_url() . '&success=false&Reason=' . base64_encode( $response_data ) );
			exit;
		}

		if ( '2' === $response_code && $auth_code ) {
			$order->payment_complete();
			$order->update_status( 'processing' );
			$woocommerce->cart->empty_cart();
			wp_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

		wp_redirect( $order->get_checkout_payment_url() . '&success=false&Reason=' . base64_encode( 'Üye işyerinin NKolay ile iletişime geçmesi gerekmektedir. (AutoComplete açılmalıdır.)' ) );
		exit;
	}
}

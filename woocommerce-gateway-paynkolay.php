<?php
/**
 * Plugin Name: PayNKolay for WooCommerce
 * Plugin URI: https://paynkolay.com.tr
 * Description: PayNKolay sanal POS ile WooCommerce ödeme entegrasyonu.
 * Version: 1.0.8
 *
 * Author: PayNKolay
 * Author URI: https://paynkolay.com.tr
 *
 * Text Domain: woocommerce-gateway-paynkolay
 * Domain Path: /languages/
 *
 * Requires at least: 5.8
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.6
 *
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC PayNKolay Payment gateway plugin class.
 *
 * @class WC_Paynkolay_Payments
 */
class WC_Paynkolay_Payments {

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {

		// PayNKolay Payments gateway class.
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );

		// Make the PayNKolay Payments gateway available to WC.
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );

		// Registers WooCommerce Blocks integration.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_block_support' ) );
	}

	/**
	 * Add the PayNKolay Payment gateway to the list of available gateways.
	 *
	 * @param array $gateways Available payment gateways.
	 * @return array
	 */
	public static function add_gateway( $gateways ) {

		$options = get_option( 'woocommerce_paynkolay_settings', array() );

		$hide_for_non_admin_users = isset( $options['hide_for_non_admin_users'] )
			? $options['hide_for_non_admin_users']
			: 'no';

		if ( 'yes' === $hide_for_non_admin_users && ! current_user_can( 'manage_options' ) ) {
			return $gateways;
		}

		$gateways[] = 'WC_Gateway_Paynkolay';
		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {

		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-gateway-paynkolay.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin absolute path.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 */
	public static function woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/blocks/class-wc-paynkolay-payments-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Gateway_Paynkolay_Blocks_Support() );
				}
			);
		}
	}
}

WC_Paynkolay_Payments::init();


add_action( 'template_redirect', 'paynkolay_failed_redirect' );

/**
 * Handle failed payment redirects from PayNKolay.
 */
function paynkolay_failed_redirect() {
	$redirect_from = isset( $_GET['redirect_from'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_from'] ) ) : null;
	$success       = isset( $_GET['success'] ) ? sanitize_text_field( wp_unslash( $_GET['success'] ) ) : null;

	if ( 'paynkolay' === $redirect_from ) {
		$current_url = ( empty( $_SERVER['HTTPS'] ) ? 'http' : 'https' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		$current_url = remove_query_arg( 'redirect_from', $current_url );
		$current_url = add_query_arg( 'success', 'false', $current_url );
		wp_redirect( $current_url );
		exit;
	}

	if ( 'false' === $success ) {
		$reason = isset( $_GET['Reason'] ) ? sanitize_text_field( wp_unslash( $_GET['Reason'] ) ) : null;
		if ( $reason ) {
			wc_add_notice( base64_decode( $reason ), 'error' );
		} else {
			wc_add_notice( __( 'Ödeme işlemi başarısız oldu. Lütfen tekrar deneyin.', 'woocommerce-gateway-paynkolay' ), 'error' );
		}
	}
}

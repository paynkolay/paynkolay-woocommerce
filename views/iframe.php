<?php
/**
 * Payment form view.
 *
 * Generates the hidden form that auto-submits to the PayNKolay VPOS gateway.
 *
 * @package WooCommerce PayNKolay Payments Gateway
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format amount with two decimal places.
 *
 * @param string $total The total amount.
 * @return string
 */
function paynkolay_format_amount( $total ) {
	$parts = explode( '.', $total );
	if ( empty( $parts[1] ) ) {
		return $total . '.00';
	}
	if ( 1 === strlen( $parts[1] ) ) {
		return $total . '0';
	}
	return $total;
}

date_default_timezone_set( 'Europe/Istanbul' );

$order = wc_get_order( $order_id );

if ( 'yes' === $this->testmode ) {
	$base_url = 'https://paynkolaytest.nkolayislem.com.tr/Vpos';
} else {
	$base_url = 'https://paynkolay.nkolayislem.com.tr/Vpos';
}

$sx                  = $this->token;
$merchant_secret_key = $this->merchant_secret_key;
$amount              = paynkolay_format_amount( $order->get_total() );
$success_url         = add_query_arg( 'wc-api', 'paynkolay', $order->get_checkout_order_received_url() );
$fail_url            = add_query_arg( 'redirect_from', 'paynkolay', $order->get_checkout_payment_url() );
$client_ref_code     = 'WooCommerce|' . $order_id . '|Woo|' . uniqid();
$use_3d              = 'true';
$rnd                 = date( 'd.m.Y H:i:s' );
$detail              = 'false';
$transaction_type    = 'sales';

$order->update_meta_data( 'client_reference_code', $client_ref_code );
$order->save();

$hash_str  = $sx . $client_ref_code . $amount . $success_url . $fail_url . $rnd . $merchant_secret_key;
$hash_data = base64_encode( pack( 'H*', sha1( $hash_str ) ) );
?>

<form id="paynkolay-payment-form" action="<?php echo esc_url( $base_url ); ?>" method="post">
	<input type="hidden" name="sx" value="<?php echo esc_attr( $sx ); ?>">
	<input type="hidden" name="successUrl" value="<?php echo esc_url( $success_url ); ?>">
	<input type="hidden" name="failUrl" value="<?php echo esc_url( $fail_url ); ?>">
	<input type="hidden" name="amount" value="<?php echo esc_attr( $amount ); ?>">
	<input type="hidden" name="clientRefCode" value="<?php echo esc_attr( $client_ref_code ); ?>">
	<input type="hidden" name="productId" value="">
	<input type="hidden" name="use3D" value="<?php echo esc_attr( $use_3d ); ?>">
	<input type="hidden" name="rnd" value="<?php echo esc_attr( $rnd ); ?>">
	<input type="hidden" name="detail" value="<?php echo esc_attr( $detail ); ?>">
	<input type="hidden" name="transactionType" value="<?php echo esc_attr( $transaction_type ); ?>">
	<input type="hidden" name="hashData" value="<?php echo esc_attr( $hash_data ); ?>">
	<input type="hidden" name="ECOMM_PLATFORM" value="WooCommerce">
	<button type="submit"><?php esc_html_e( 'Ödeme işlemine devam et', 'woocommerce-gateway-paynkolay' ); ?></button>
</form>

<style>
	body { display: none !important; }
</style>

<script>
	document.getElementById( 'paynkolay-payment-form' ).submit();
</script>

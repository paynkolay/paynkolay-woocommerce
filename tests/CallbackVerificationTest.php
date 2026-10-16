<?php

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	// Minimal stand-in so the gateway class can be loaded without WordPress/WooCommerce.
	// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
	abstract class WC_Payment_Gateway {}
}

require_once dirname( __DIR__ ) . '/includes/class-wc-gateway-paynkolay.php';

/**
 * Offline tests for the callback verification helpers (hash, amount, success rule).
 */
final class CallbackVerificationTest extends TestCase {

	const SECRET = 'test_secret';

	/**
	 * A signed-looking successful callback.
	 *
	 * @return array
	 */
	private function fields() {
		$f = array(
			'MERCHANT_NO'          => '400000002',
			'REFERENCE_CODE'       => 'IKSIRPF3938836128',
			'AUTH_CODE'            => 'S32533320',
			'RESPONSE_CODE'        => '2',
			'USE_3D'               => 'true',
			'RND'                  => '1645700316156',
			'INSTALLMENT'          => '1',
			'AUTHORIZATION_AMOUNT' => '870.00',
			'CURRENCY_CODE'        => 'TRY',
			'hashData'             => '',
			'hashDataV2'           => '',
		);
		return $f;
	}

	public function test_amount_to_cents(): void {
		$this->assertSame( 87000, WC_Gateway_Paynkolay::amount_to_cents( '870.00' ) );
		$this->assertSame( 87050, WC_Gateway_Paynkolay::amount_to_cents( '870.5' ) );
		$this->assertSame( 87000, WC_Gateway_Paynkolay::amount_to_cents( '870' ) );
		$this->assertSame( 1, WC_Gateway_Paynkolay::amount_to_cents( '0.01' ) );
		$this->assertNull( WC_Gateway_Paynkolay::amount_to_cents( '' ) );
		$this->assertNull( WC_Gateway_Paynkolay::amount_to_cents( '-1.00' ) );
		$this->assertNull( WC_Gateway_Paynkolay::amount_to_cents( '1,00' ) );
		$this->assertNull( WC_Gateway_Paynkolay::amount_to_cents( '1.001' ) );
		$this->assertNull( WC_Gateway_Paynkolay::amount_to_cents( '1e3' ) );
	}

	public function test_v2_hash_is_preferred_and_strict(): void {
		$f               = $this->fields();
		$f['hashDataV2'] = WC_Gateway_Paynkolay::response_hash_v2( $f, self::SECRET );
		$this->assertSame( 'v2', WC_Gateway_Paynkolay::verify_response_hash( $f, self::SECRET ) );

		// A valid v1 hash must not rescue a bad v2 hash (no downgrade).
		$f['hashData']   = WC_Gateway_Paynkolay::response_hash_v1( $f, self::SECRET );
		$f['hashDataV2'] = 'bogus';
		$this->assertFalse( WC_Gateway_Paynkolay::verify_response_hash( $f, self::SECRET ) );
	}

	public function test_v2_hash_binds_amount_and_currency(): void {
		$f               = $this->fields();
		$f['hashDataV2'] = WC_Gateway_Paynkolay::response_hash_v2( $f, self::SECRET );

		$tampered                         = $f;
		$tampered['AUTHORIZATION_AMOUNT'] = '8700.00';
		$this->assertFalse( WC_Gateway_Paynkolay::verify_response_hash( $tampered, self::SECRET ) );

		$tampered                  = $f;
		$tampered['CURRENCY_CODE'] = 'USD';
		$this->assertFalse( WC_Gateway_Paynkolay::verify_response_hash( $tampered, self::SECRET ) );
	}

	public function test_v1_hash_used_only_without_v2(): void {
		$f             = $this->fields();
		$f['hashData'] = WC_Gateway_Paynkolay::response_hash_v1( $f, self::SECRET );
		$this->assertSame( 'v1', WC_Gateway_Paynkolay::verify_response_hash( $f, self::SECRET ) );

		$f['hashData'] = 'bogus';
		$this->assertFalse( WC_Gateway_Paynkolay::verify_response_hash( $f, self::SECRET ) );
	}

	public function test_empty_secret_or_missing_hash_is_rejected(): void {
		$f               = $this->fields();
		$f['hashDataV2'] = WC_Gateway_Paynkolay::response_hash_v2( $f, '' );
		$this->assertFalse( WC_Gateway_Paynkolay::verify_response_hash( $f, '' ) );
		$this->assertFalse( WC_Gateway_Paynkolay::verify_response_hash( $f, '   ' ) );

		$this->assertFalse( WC_Gateway_Paynkolay::verify_response_hash( $this->fields(), self::SECRET ) );
	}

	public function test_is_payment_successful(): void {
		$f = $this->fields();
		$this->assertTrue( WC_Gateway_Paynkolay::is_payment_successful( $f ) );

		foreach ( array( '', '0', '00', ' 00 ' ) as $bad ) {
			$f['AUTH_CODE'] = $bad;
			$this->assertFalse( WC_Gateway_Paynkolay::is_payment_successful( $f ), 'AUTH_CODE ' . var_export( $bad, true ) );
		}

		$f                  = $this->fields();
		$f['RESPONSE_CODE'] = '0';
		$this->assertFalse( WC_Gateway_Paynkolay::is_payment_successful( $f ) );
	}

	public function test_currency_codes(): void {
		$this->assertTrue( WC_Gateway_Paynkolay::is_try_currency_code( 'TRY' ) );
		$this->assertTrue( WC_Gateway_Paynkolay::is_try_currency_code( '949' ) );
		$this->assertFalse( WC_Gateway_Paynkolay::is_try_currency_code( '840' ) );
		$this->assertFalse( WC_Gateway_Paynkolay::is_try_currency_code( '' ) );
	}
}

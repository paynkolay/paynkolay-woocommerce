<?php

use PHPUnit\Framework\TestCase;

/**
 * Basic unit tests for PayNKolay WooCommerce gateway.
 */
final class Test extends TestCase {

	/**
	 * Test that the plugin main file exists.
	 */
	public function test_plugin_file_exists(): void {
		$this->assertFileExists(
			dirname( __DIR__ ) . '/woocommerce-gateway-paynkolay.php'
		);
	}
}

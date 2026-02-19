/**
 * PayNKolay WooCommerce Blocks integration.
 *
 * Registers PayNKolay as a payment method for the WooCommerce Blocks checkout.
 */
( function () {
	'use strict';

	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { getSetting } = wc.wcSettings;
	const { decodeEntities } = wp.htmlEntities;
	const { __ } = wp.i18n;
	const { createElement } = wp.element;

	const settings = getSetting( 'paynkolay_data', {} );
	const defaultLabel = __( 'Kredi Kartı ile Öde', 'woocommerce-gateway-paynkolay' );
	const label = decodeEntities( settings.title ) || defaultLabel;

	const Description = function () {
		return decodeEntities( settings.description || '' );
	};

	const Label = function ( props ) {
		return createElement( props.components.PaymentMethodLabel, { text: label } );
	};

	registerPaymentMethod( {
		name: 'paynkolay',
		label: createElement( Label, null ),
		content: createElement( Description, null ),
		edit: createElement( Description, null ),
		canMakePayment: function () { return true; },
		ariaLabel: label,
		supports: {
			features: settings.supports,
		},
	} );
} )();

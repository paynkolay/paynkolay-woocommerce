/**
 * PayNKolay WooCommerce Blocks integration.
 *
 * Registers PayNKolay as a payment method for the WooCommerce Blocks checkout.
 */

import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'paynkolay_data', {} );
const defaultLabel = __( 'PayNKolay', 'woocommerce-gateway-paynkolay' );
const label = decodeEntities( settings.title ) || defaultLabel;

const Description = () => decodeEntities( settings.description || '' );

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

registerPaymentMethod( {
	name: 'paynkolay',
	label: <Label />,
	content: <Description />,
	edit: <Description />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
} );

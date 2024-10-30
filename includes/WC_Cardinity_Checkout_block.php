<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;


/**
 * https://sevengits.com/payments-with-woocommerce-checkout-blocks/
 */
final class WC_Cardinity_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'cardinity';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( "woocommerce_{$this->name}_settings", array() );
        $this->gateway = new WC_Cardinity_Gateway();
    }

    public function is_active() {
        return ! empty( $this->settings[ 'enabled' ] ) && 'yes' === $this->settings[ 'enabled' ];
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'wc-cardinity-blocks-integration',
            plugin_dir_url(__FILE__) . 'block/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        return [ 'wc-cardinity-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description
        ];
    }

}

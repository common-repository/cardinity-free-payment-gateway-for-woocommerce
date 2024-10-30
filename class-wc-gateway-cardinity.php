<?php
/*
  Plugin Name: Cardinity Payment Gateway
  Plugin URI:
  Description: Cardinity payment gateway integration for WooCommerce.
  Version: 3.3.3
  Author: Cardinity
  Author URI: https://cardinity.com
  License: MIT
  WC tested up to: 9.3.3
 */

if (!defined('ABSPATH')) {
	exit;
} // exit if accessed directly


/**
 * Encode data to Base64URL
 * @param string $data
 * @return boolean|string
 */
function cardinity_wc_encode_base64Url($data)
{
	$b64 = base64_encode($data);

	if (!$b64) {
		return false;
	}

	// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
	$url = strtr($b64, '+/', '-_');

	// Remove padding character from the end of line and return the Base64URL result
	return rtrim($url, '=');
}

/**
 * Decode data from Base64URL
 * @param string $data
 * @param boolean $strict
 * @return boolean|string
 */
function cardinity_wc_decode_base64url($data, $strict = false)
{
	// Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
	$b64 = strtr($data, '-_', '+/');

	return base64_decode($b64, $strict);
}


function cardinity_wc_add_gateway_class($methods)
{
	$methods[] = 'WC_Cardinity_Gateway';

	return $methods;
}

function cardinity_wc_process_recurring($renewal_total, $renewal_order)
{
	$recurringPayment = new WC_Cardinity_Gateway();
	$recurringPayment->process_subscription_payment($renewal_total, $renewal_order);
}

function cardinity_wc_download_log() {

	if(!isset($_GET['screen'])){

	} else if($_GET['screen'] == 'transactions_log_download'){

		$lognumber = sanitize_key($_GET['lognumber']);

		$currentFilename = "transactions-".$lognumber.'.log';
		$transactionFile = WP_PLUGIN_DIR .'/cardinity-free-payment-gateway-for-woocommerce/transactions/'.$currentFilename;

		$downloadFileName = 'crd-transactions-'.$lognumber.'-'.time().'.log';

		if (file_exists($transactionFile)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($downloadFileName).'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($transactionFile));
			readfile($transactionFile);
			exit;
		}else{
			echo esc_url($transactionFile);
			echo esc_html("<h1>File not found</h1>");
			exit();
		}

	}
}


function cardinity_wc_init()
{

	if (!class_exists('Cardinity\Client')) {
		require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
	}


	if (!class_exists('WC_Payment_Gateway')) {
		return;
	} // if the WC payment gateway class is not available, do nothing

	if (class_exists('WC_Cardinity_Gateway')) {
		return;
	}

	include_once(plugin_dir_path(__FILE__) . 'includes/WC_Cardinity_Gateway.php');


	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param $methods
	 *
	 * @return array
	 */
	add_filter('woocommerce_payment_gateways', 'cardinity_wc_add_gateway_class');



	/**
	 * Execute renewal payment
	 *
	 * @param $renewal_order
	 * @param $renewal_order
	 *
	 */
	add_action('woocommerce_scheduled_subscription_payment_cardinity', 'cardinity_wc_process_recurring', 10, 2);



	/**
	 * Add hook for download
	 *
	 * @return void
	 */
	if ( ! (!current_user_can( 'manage_woocommerce') && ( ! wp_doing_ajax() )) ) {
		add_action( 'admin_init', 'cardinity_wc_download_log', 1 );
	}

}

add_action('plugins_loaded', 'cardinity_wc_init');

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );


add_action('woocommerce_credit_card_form_start', 'card_holder_build');
function card_holder_build($id) {

    $wcCardinityGateway = new WC_Cardinity_Gateway();
    $showHolder = $wcCardinityGateway->get_option('show_holder');
    if($showHolder == 'no'){
        return;
    }

    $fieldId = esc_attr( $id ) . '-card-holder';
    $fieldLabel = esc_html__( 'Card Holder', 'woocommerce' );

    $cardHolderFormField = "<p class='form-row form-row-wide'>
                <label for='$fieldId' >$fieldLabel</label>
				<input id='$fieldId'
				        type='text'
				        name='cardinity-card-holder'
				        class='input-text wc-credit-card-form-card-holder'
				        placeholder='$fieldLabel'/>
	</p>";

    echo wp_kses($cardHolderFormField, array(
        'input' => array(
            'type' => true,
            'id' => true,
            'class' => true,
            'name' => true,
            'value' => true,
            'placeholder' => true,
        ),
        'label' => array(
            'for' => true,
            'class' => true,
        ),
        'p' => array(
            'class' => true,
        )
    ));

}


/**
 * Custom function to declare compatibility with cart_checkout_blocks feature
 */
function declare_cart_checkout_blocks_compatibility() {
    // Check if the required class exists

    if( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'cardinity_wc_register_order_approval_payment_method_type' );

/**
 * Custom function to register a payment method type

 */
function cardinity_wc_register_order_approval_payment_method_type() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'includes/WC_Cardinity_Checkout_block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of WC_Phonepe_Blocks
            $payment_method_registry->register( new WC_Cardinity_Blocks );
        }
    );
}

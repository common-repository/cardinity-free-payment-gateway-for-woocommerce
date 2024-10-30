<?php
if (!defined('ABSPATH')) {
	exit;
} // exit if accessed directly

if (class_exists('WC_Payment_Gateway') && !class_exists('WC_Cardinity_Gateway')) {
	return;
}

define('CARD_HOLDER','cardinity-card-holder');
define('CARD_PAN','cardinity-card-number');
define('CARD_CVC','cardinity-card-cvc');
define('CARD_EXPIRY', 'cardinity-card-expiry');


class WC_Cardinity_Gateway extends WC_Payment_Gateway
{

    /** @var boolean Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log;

    private $notify_url;
    private $consumer_key;
    private $consumer_secret;
    private $project_key;
    private $project_secret;
    private $external;
    private $debug = false;

    public function __construct()
    {

        $this->id           = 'cardinity';
        $this->icon         = apply_filters('woocommerce_cardinity_icon', plugins_url('../images/cardinity.png', __FILE__));
        $this->method_title = 'Cardinity Payment Gateway';
        $this->notify_url   = WC()->api_request_url('WC_Gateway_Cardinity');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title           = $this->get_option('title');
        $this->description     = $this->get_option('description');
        $this->consumer_key    = $this->get_option('consumer_key');
        $this->consumer_secret = $this->get_option('consumer_secret');
        $this->project_key     = $this->get_option('project_key');
        $this->project_secret  = $this->get_option('project_secret');
        $this->external 	   = $this->get_option('external');
        $this->debug           = 'yes' === $this->get_option('debug', 'no');

        $this->has_fields   = ($this->external == 'no' ? true : false);
        $this->supports     = $this->external == 'no' ? array(
            'products',
            'subscriptions',
            'multiple_subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
        ) : array('products');

        self::$log_enabled = $this->debug;

        add_action('woocommerce_update_options_payment_gateways_' . $this->id,
                    array($this, 'process_admin_options')
        );
        add_action('woocommerce_receipt_cardinity', array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_gateway_cardinity', array($this, 'check_response'));
        add_action('woocommerce_api_wc_gateway_cardinity_notify', array($this, 'notify_response'));

    }

    /**
     * Logging method
     *
     * @param  string $message
     * @param  string $order_id
     */
    public static function log($message, $order_id = '')
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = new WC_Logger();
            }
            if (!empty($order_id)) {
                $message = 'Order: ' . $order_id . '. ' . $message;
            }
            self::$log->add('cardinity', $message);
        }
    }


    /**
     * Output admin panel options
     */
    public function admin_options()
    {
        if($this->is_using_checkout_block()){
            $this->update_option('external', 'yes');

            ?>

            <div class="notice notice-info">
                <p><strong>You are using block checkout</strong></p>
                <p>External hosted payment is enabled. To use internal checkout switch your checkout page to classic.</p>
            </div>
            <?php
        }

        if(isset($_GET['review'])){
            $this->update_option('hide_review', true);
        }

        if($this->get_option('hide_review', false) == false){
        ?>
        <div id="message" class="updated woocommerce-success">

            <p><strong>Thank you for using Cardinity!</strong></p>
            <p>Please let us know what you think about our module or services.</p>
            <p>
            <a href="https://www.trustpilot.com/evaluate/www.cardinity.com/">Write a review</a>&nbsp;&nbsp;&nbsp;
            <a  href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cardinity&review=dontshow' )?>" >Dont show again</a>
            </p>
        </div>
        <?php
        }

        $customerDefaultLocation = wc_get_customer_default_location();
        $country = $customerDefaultLocation['country'];

        if(!$country){
            echo '<div class="notice notice-info ">
            <p>You should enter a default customer location, its available under <a href="'.admin_url('admin.php?page=wc-settings&amp;tab=general').'">general settings page</a>. Leaving it empty might cause problem on checkout</p>
            </div>';
        }


        if( ! isset( $_GET['screen'] ) || $_GET['screen'] === '' ) {
            parent::admin_options();
        } else {

            if($_GET['screen'] == 'transactions_log'){

                $transactionLogFiles = scandir(WP_PLUGIN_DIR .'/cardinity-free-payment-gateway-for-woocommerce/transactions/');

                unset($transactionLogFiles[0]);
                unset($transactionLogFiles[1]);

                if($transactionLogFiles[2] == '.gitkeep') {
                    unset($transactionLogFiles[2]);
                }


                echo '<a class="button" href="javascript:window.history.back();">Back</a>';

                echo "<hr/>";

                echo "<strong>Format of log<br/>OrderId :: PaymentID :: 3d Secure Version :: Amount :: Status</strong><br/>";

                echo "<hr/>";
                echo "<div>";

                foreach($transactionLogFiles as $aFile){

                    $number = str_replace("transactions-","",$aFile);
                    $number = str_replace(".log","",$number);

                    echo 'Download - <a  href="'.admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cardinity&screen=transactions_log_download&lognumber='.esc_attr($number) ).'" class="link">'.
                    esc_attr($aFile).
                    '</a><br/>';
                }

                echo "</div>";
                exit();
            }
        }
    }




    /**
     * Initialise gateway settings form fields
     *
     * @access public
     * @return void
     */
    function init_form_fields()
    {

        $this->form_fields = include('settings-cardinity.php');

        if(current_user_can( 'manage_woocommerce')){
            $this->form_fields['screen_button'] = array(
                'id'    => 'screen_button',
                'type'  => 'screen_button',
                'title' => __( 'Transaction Logs', 'wcommerce' ),
            );
        }
    }

    /**
     * This method echo's HTML for the CreditCard form.
     */
    public function payment_fields()
    {
        echo "<p>".esc_html($this->get_option('description'))."</p>";

        if ($this->external != 'yes') {

            if (WC()->version < '2.7.0') {
                $this->credit_card_form();
            } else {
                $cc = new WC_Payment_Gateway_CC();
                $cc->id = $this->id;
                $cc->form();

                $threedv2config = "
                <input type='hidden' id='screen_width' name='cardinity_screen_width' value='1920' />
                <input type='hidden' id='screen_height' name='cardinity_screen_height' value='1080' />
                <input type='hidden' id='browser_language' name='cardinity_browser_language' value='en-US' />
                <input type='hidden' id='color_depth' name='cardinity_color_depth' value='24' />
                <input type='hidden' id='time_zone' name='cardinity_time_zone' value='-60' />
                ";

                echo wp_kses($threedv2config, array(
                    'input' => array(
                        'type' => true,
                        'id' => true,
                        'name' => true,
                        'value' => true
                    )
                ));

                $threedv2configscript = '
                <script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function() {
                        document.getElementById("screen_width").value = screen.availWidth;
                        document.getElementById("screen_height").value = screen.availHeight;
                        document.getElementById("browser_language").value = navigator.language;
                        document.getElementById("color_depth").value = screen.colorDepth;
                        document.getElementById("time_zone").value = new Date().getTimezoneOffset();
                    });
                </script>';

                echo wp_kses($threedv2configscript, array(
                    'script' => array(
                        'type' => true,
                    )
                ));
            }
        }
    }

    /**
     * Validate credit card data
     *
     * @return bool
     */
    public function validate_fields()
    {
        $post_data = sanitize_post($_POST);

        if ($this->external != 'yes' && isset($post_data[CARD_PAN]) ) {

            if (!$this->isCreditCardHolderName($post_data[CARD_HOLDER]) && $post_data[CARD_HOLDER] != "") {
                wc_add_notice(__('Credit Card Holder Name is not valid.', 'woocommerce'), 'error');
            }

            if (!$this->isCreditCardNumber($post_data[CARD_PAN])) {
                wc_add_notice(__('Credit Card Number is not valid.', 'woocommerce'), 'error');
            }

            if (!$this->isCorrectExpireDate($post_data[CARD_EXPIRY])) {
                wc_add_notice(__('Card Expiry Date is not valid.', 'woocommerce'), 'error');
            }

            if (!$post_data[CARD_CVC]) {
                wc_add_notice(__('Card CVC is not entered.', 'woocommerce'), 'error');
            }

            return false;
        }

        return true;
    }

    /**
     * Add notice in admin section if Cardinity gateway is used without SSL certificate
     */
    public function do_ssl_check()
    {
        if ($this->enabled == 'yes') {
            if (get_option('woocommerce_force_ssl_checkout') == 'no') {
                echo "<div class=\"error\"><p>" .
                    sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

    /**
     * Send payment request to Cardinity gateway
     *
     * @param int $order_id
     *
     * @return array
     */
    function process_payment($order_id)
    {
        if ($this->external == 'yes' || isset($_POST[CARD_PAN])==false ) {
            return $this->process_external_payment($order_id);
        } else {
            return $this->process_direct_payment($order_id);
        }
    }

    /**
     * Send payment through an external gateway
     *
     * @param int $order_id
     *
     * @return array
     */

    public function process_external_payment($order_id)
    {
        self::log("Start processing external transaction", $order_id);
        $order = new WC_Order($order_id);
        $order->update_status('pending-payment', __('Awaiting payment', 'woocommerce'));

        $project_key = $this->project_key;
        $project_secret = $this->project_secret;
        $attributes = [
            "amount" => number_format($order->get_total(), 2,'.',''),
            "currency" => $order->get_currency(),
            "country" => $order->get_billing_country(),
            "order_id" => $order_id,
            "description" => "WC-" . $order_id,
            "project_id" => $project_key,
            "return_url" => $this->get_return_url($order) . '&' . build_query(
                [
                    'wc-api'=>'WC_Gateway_Cardinity',
                    'wc-order' => cardinity_wc_encode_base64Url($order_id)
                ]
            ),
            "notification_url" => $this->get_return_url($order) . '&' . build_query(
                [
                    'wc-api'=>'WC_Gateway_Cardinity_Notify'
                ]
            ),
        ];

        $email = $order->get_billing_email();
        $mobile_number = str_replace("+", "0", $order->get_billing_phone());

        if($email){
            $attributes['email_address'] = $email;
        }
        if($mobile_number){
            $attributes['mobile_phone_number'] = $mobile_number;
        }

        ksort($attributes);

        $message = '';
        foreach ($attributes as $key => $value) {
            $message .= sanitize_text_field($key . $value);
        }

        $signature = hash_hmac('sha256', $message, $project_secret);
        $attributes["signature"] = $signature;
        $attributes['return_url'] = urlencode($attributes['return_url']);
        $attributes['notification_url'] = urlencode($attributes['notification_url']);

        $url = WP_PLUGIN_URL . "/cardinity-free-payment-gateway-for-woocommerce/redirect-to-external-checkout.php?";
        return array(
            'result'   => 'success',
            'redirect' =>  $url . build_query($attributes)
        );
    }

    /**
     * Send payment through a direct gateway
     *
     * @param int $order_id
     *
     * @return mixed
     */
    public function process_direct_payment($order_id)
    {
        $order = new WC_Order($order_id);

        $params = $this->getPaymentParams($order);
        $method = new Cardinity\Method\Payment\Create($params);

        $result = $this->sendCardinityRequest($method, $order_id);

        if ($result['status'] == 'approved') {
            $order->payment_complete($result['payment_id']);
            $order->add_order_note($result['message']);

            if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)) {
                $subscriptions = wcs_get_subscriptions_for_order($order_id);
                foreach ($subscriptions as $subscription) {
                    $subscription->update_meta_data( '_crd_recurring_id', $result['payment_id']);
                    $subscription->save();
                }
            }

            WC()->cart->empty_cart();

            $this->add_transaction_history(array(
                $order_id,
                $result['payment_id'],
                'none',
                $result['payment_obj']->getAmount()." ".$result['payment_obj']->getCurrency(),
                $result['status'],
            ));

            $order->save();

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } elseif ($result['status'] == 'pending') {
            $order->add_order_note($result['message']);

            self::log("Setting transaction id to pending order " . $result['payment_id'], $order->get_id());

            $order->set_transaction_id($result['payment_id']);
            $order->save();

            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        } elseif ($result['status'] == 'declined') {
            $order->add_order_note($result['message']);
        }

        foreach ($result['errors'] as $error) {
            wc_add_notice($error, 'error');
        }
        self::log('Direct payment errors: \r\n' . json_encode($result['errors']), "OrderID : $order_id");

        return null;
    }

    /**
     * Send recurring payment request to Cardinity gateway
     *
     * @param double $renewal_total
     * @param object $order
     *
     * @return mixed
     */
    public function process_subscription_payment($renewal_total, $order)
    {
        if (WC()->version < '2.7.0') {
            $order_id = $order->id;
        } else {
            $order_id = $order->get_id();
        };

        self::log('Starting Cardinity recurring processing', $order_id);

        $params = $this->getRecurringPaymentParams($order);
        $method = new Cardinity\Method\Payment\Create($params);

        $result = $this->sendCardinityRequest($method, $order_id);

        if ($result['status'] == 'approved') {
            $order->payment_complete($result['payment_id']);
            $order->add_order_note($result['message']);

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } elseif ($result['status'] == 'declined') {
            $order->add_order_note($result['message']);
            self::log('subscription payment declined', $order_id);
        }

        return null;
    }

    /**
     * Hook to trigger 3D Secure redirect
     *
     * @param int $order_id
     */
    function receipt_page($order_id)
    {
        $v2 = WC()->session->get('cardinity-3dsv2');

        if($v2 == 'true'){
            self::log('Performing POST redirect to 3D Secure v2 ACS', "OrderId : ".sanitize_key($order_id));
            echo $this->generate_threed_v2_form();
        }else{
            self::log('Performing POST redirect to 3D Secure ACS',"OrderId : ".sanitize_key($order_id));
            echo $this->generate_threed_form();
        }
    }

    /**
     * Generate 3D Secure redirection form
     *
     * @return string Redirect form HTML
     */
    public function generate_threed_form()
    {
        $threed_secure_args = array_merge(
            array(
                'PaReq'   => WC()->session->get('cardinity-pareq'),
                'MD'      => WC()->session->get('cardinity-MD'),
                'TermUrl' => $this->notify_url
            )
        );

        $threed_secure_args_array = array();

        foreach ($threed_secure_args as $key => $value) {
            $threed_secure_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        }

        wc_enqueue_js('jQuery("#threed_form_submit").click();');

        return '<form action="' . esc_url(WC()->session->get('cardinity-acs')) . '" method="post" id="threed_form">
                ' . implode('', $threed_secure_args_array) . '
                <input type="submit" class="button" id="threed_form_submit" value="' . __('Continue', 'woocommerce') . '" />
                </form>';
    }

    /**
     * Generate 3D Secure v2 redirection form
     *
     * @return string Redirect form HTML
     */
    public function generate_threed_v2_form()
    {
        $threed_secure_args = array_merge(
            array(
                'creq'      			=> WC()->session->get('cardinity-creq'),
                'threeDSSessionData'    => cardinity_wc_encode_base64Url(WC()->session->get('cardinity-threeDSSessionData'))
            )
        );

        $threed_secure_args_array = array();

        foreach ($threed_secure_args as $key => $value) {
            $threed_secure_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        }

        wc_enqueue_js('jQuery("#threed_form_submit").click();');

        return '<form action="' . esc_url(WC()->session->get('cardinity-acs_url')) . '" method="post" id="threed_form">
                ' . implode('', $threed_secure_args_array) . '
                <input type="submit" class="button" id="threed_form_submit" value="' . __('Continue', 'woocommerce') . '" />
                </form>';
    }

    /**
     * Process response
     */
    public function check_response()
    {
        if ($this->external == 'yes' || isset($_POST['project_id'])) {
            return $this->check_response_external();
        } else {
            return $this->check_response_direct();
        }
    }

    public function notify_response()
    {
        if (isset($_POST['order_id'])){
            $orderId = sanitize_text_field( $_POST['order_id'] );
            echo "Notification received by WP OrderID :: $orderId";
            $order = wc_get_order($orderId);
            $order->add_order_note("Cardinity External Notification received ".$orderId);
            $order->save();
            self::log("Notification received by WP Order :: $orderId");
            $this->check_response_external(false);
        }else{
            self::log("External notification hit with no orderID");
        }
        exit();
    }

    public function check_response_external($redirect = true)
    {
        $message = '';

        //necessary to make sure signature not altered
        ksort($_POST);
        foreach ($_POST as $key => $value) {
            if ($key == 'signature') continue;
            $message .= sanitize_text_field($key . $value);
        }

        //now we can sanitize
        $post_data = sanitize_post($_POST,'raw');
        self::log("processing external response with ".($redirect ? "redirect" : "notification"), $post_data['order_id']??"UNKNOWN");
        $order = wc_get_order($post_data['order_id']);

        // Check if response is valid
        $signature = hash_hmac('sha256', $message, $this->project_secret);

        if ($signature == $post_data['signature'] && $post_data['status'] == 'approved') {
            WC()->cart->empty_cart();

            if($order->has_status('pending')){
                $order->payment_complete($post_data['id']);
                $order->add_order_note("Cardinity External Payment complete ".$post_data['id']);

                $this->add_transaction_history(array(
                    $post_data['order_id'],
                    $post_data['id'],
                    'unknown (external)',
                    $post_data['amount']." ".$post_data['currency'],
                    $post_data['status'],
                ));
                self::log('Complete payment',$order->get_id());
            }else{
                self::log('OrderID : '.$order->get_id().' hosted callback status was already - '.$order->get_status());
            }

        } else if($order){
            //failed, but we have order
            $order->update_status('failed', __('Awaiting payment', 'woocommerce'));
            self::log('external checkout payment failed', "OrderID : ".$post_data['order_id']);
        }else{
            //we have no order, rebuild from url
            $order_id = cardinity_wc_decode_base64url(sanitize_text_field($_GET['wc-order']));
            $order = wc_get_order($order_id);

            if($order){
                self::log('external checkout payment failed', "OrderID : ".$post_data['order_id']);
            }else{
                self::log('external checkout response unable to track order '. print_r($post_data, true));
            }
        }
        $order->save();
        if($redirect){
            wp_redirect($this->get_return_url($order));
        }
        exit;
    }

    /**
     * Process 3D Secure callback
     */
    public function check_response_direct()
    {
        $post_data = sanitize_post($_POST,'raw');
        self::log('Cardinity callback.'.json_encode($_POST));

        if (!empty($post_data['threeDSSessionData']) && !empty($post_data['cres'])) {
            $threeDSSessionData = cardinity_wc_decode_base64url($post_data['threeDSSessionData']);
            $order = $this->get_cardinity_order(wp_unslash($threeDSSessionData));

            if (WC()->version < '2.7.0') {
                $order_id = $order->id;
            } else {
                $order_id = $order->get_id();
            }

            self::log('Starting Cardinity 3D v2 processing',"OrderId : $order_id");

            if ($order && $order->has_status('pending')) {
                $paymentId = $order->get_transaction_id();
                $cres     = wp_unslash($post_data['cres']);
                self::log("Getting transaction id to pending order $paymentId", $order->get_id());
                $method    = new Cardinity\Method\Payment\Finalize($paymentId, $cres, true);
                self::log('Starting Finalize request PaymentID : '.$paymentId, $order_id);

                $result = $this->sendCardinityRequest($method, $order_id);

                if ($result['status'] == 'approved') {
                    $order->payment_complete($result['payment_id']);
                    $order->add_order_note($result['message']);

                    if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)) {
                        $subscriptions = wcs_get_subscriptions_for_order($order_id);
                        foreach ($subscriptions as $subscription) {
                            $subscription->update_meta_data( '_crd_recurring_id', $result['payment_id']);
                            $subscription->save();
                        }
                    }
                    $order->save();

                    WC()->cart->empty_cart();

                    $this->add_transaction_history(array(
                        $order_id,
                        $result['payment_id'],
                        'v2',
                        $result['payment_obj']->getAmount()." ".$result['payment_obj']->getCurrency(),
                        $result['status'],
                    ));

                    wp_redirect($this->get_return_url($order));

                    exit;

                } elseif ($result['status'] == 'pending') {
                    //status still pending after 3dsv2, needs to fallback to 3dsv1
                    self::log('Still pending after 3dsv2, needs to fallback to 3dsv1',"OrderId : $order_id");

                    $payment = $result['payment_obj'];
                    $MD = urlencode($order->get_id() . '-CRD-' . $order->get_order_key());
                    //fetch fallback 3dsv1 data into session
                    WC()->session->set('cardinity-3dsv2', 'false');
                    WC()->session->set('cardinity-acs', $payment->getAuthorizationInformation()->getUrl());
                    WC()->session->set('cardinity-pareq', $payment->getAuthorizationInformation()->getData());
                    WC()->session->set('cardinity-MD', $MD);

                    $this->receipt_page($order_id);


                    exit();
                    //$order->add_order_note($result['message']);
                } elseif ($result['status'] == 'declined') {
                    $order->add_order_note($result['message']);
                }

                foreach ($result['errors'] as $error) {
                    wc_add_notice($error, 'error');
                }
            } else {
                self::log('Invalid order status.', "OrderId : $order_id");
                wp_die('3D Secure Failure.', '3D Secure', array('response' => 500));
            }

            if (WC()->version < '2.5.0') {
                wp_redirect(apply_filters('woocommerce_get_checkout_url', WC()->cart->get_checkout_url()));
            } else {
                wp_redirect(wc_get_checkout_url());
            }

            exit;
        }else if (!empty($post_data['MD']) && !empty($post_data['PaRes'])) {
            $order = $this->get_cardinity_order(wp_unslash($post_data['MD']));

            if (WC()->version < '2.7.0') {
                $order_id = $order->id;
            } else {
                $order_id = $order->get_id();
            }

            self::log('Starting Cardinity 3D processing', "OrderId : $order_id");

            if ($order && $order->has_status('pending')) {
                $paymentId = $order->get_transaction_id();
                $PaRes     = wp_unslash($post_data['PaRes']);
                $method    = new Cardinity\Method\Payment\Finalize($paymentId, $PaRes);

                $result = $this->sendCardinityRequest($method, $order_id);

                if ($result['status'] == 'approved') {
                    $order->payment_complete($result['payment_id']);
                    $order->add_order_note($result['message']);

                    if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)) {
                        $subscriptions = wcs_get_subscriptions_for_order($order_id);
                        foreach ($subscriptions as $subscription) {
                            $subscription->update_meta_data( '_crd_recurring_id', $result['payment_id']);
                            $subscription->save();
                        }
                    }

                    $order->save();
                    WC()->cart->empty_cart();

                    $this->add_transaction_history(array(
                        $order_id,
                        $result['payment_id'],
                        'v1',
                        $result['payment_obj']->getAmount()." ".$result['payment_obj']->getCurrency(),
                        $result['status'],
                    ));


                    wp_redirect($this->get_return_url($order));

                    exit;
                } elseif ($result['status'] == 'declined') {
                    $order->add_order_note($result['message']);
                    $order->save();
                }

                foreach ($result['errors'] as $error) {
                    wc_add_notice($error, 'error');
                }
            } else {
                self::log('Invalid order status.', "OrderId : $order_id");
                wp_die('3D Secure Failure.', '3D Secure', array('response' => 500));
            }

            if (WC()->version < '2.5.0') {
                wp_redirect(apply_filters('woocommerce_get_checkout_url', WC()->cart->get_checkout_url()));
            } else {
                wp_redirect(wc_get_checkout_url());
            }

            exit;
        } else {
            self::log('Malformed or invalid Cardinity callback.');
            wp_die('3D Secure Failure.', '3D Secure', array('response' => 500));
        }
    }

    /**
     * Get Cardinity client
     *
     * @return object
     */
    private function getCardinityClient()
    {
        $client = Cardinity\Client::create([
            'consumerKey'    => $this->consumer_key,
            'consumerSecret' => $this->consumer_secret,
        ]);

        return $client;
    }

    /**
     * Send Cardinity payment request
     *
     * @param  object $method
     * @param  int $order_id
     *
     * @return array
     */
    private function sendCardinityRequest($method, $order_id)
    {
        $errors     = array();
        $status     = null;
        $message    = null;
        $payment_id = null;

        $client = $this->getCardinityClient();


        $payment = 0;

        try {
            self::log('Calling Cardinity gateway endpoint.', $order_id);

            $payment = $client->call($method);

            //Payment non 3d secured
            if ($payment->getStatus() == 'approved') {
                $status     = 'approved';
                $payment_id = $payment->getId();
                $message    = sprintf(__('Cardinity payment completed at: %s. Payment ID: %s', 'woocommerce'), $payment->getCreated(), $payment_id);

                self::log($message, $order_id);
            //paymend needs 3ds processing
            } else if ($payment->getStatus() == 'pending') {
                $status     = 'pending';
                $payment_id = $payment->getId();
                $message    = sprintf(__('Cardinity payment pending, 3D auth required. Payment ID: %s', 'woocommerce'), $payment_id);

                // Retrieve and set 3D Secure details
                $order = new WC_Order($order_id);
                if (WC()->version < '2.7.0') {
                    $MD = urlencode($order->id . '-CRD-' . $order->order_key);
                } else {
                    $MD = urlencode($order->get_id() . '-CRD-' . $order->get_order_key());
                }

                if($payment->isThreedsV2() && !$payment->isThreedsV1()){
                    WC()->session->set('cardinity-3dsv2', 'true');
                    WC()->session->set('cardinity-acs_url', $payment->getThreeds2data()->getAcsUrl());
                    WC()->session->set('cardinity-creq', $payment->getThreeds2data()->getCReq());
                    WC()->session->set('cardinity-threeDSSessionData', $MD);
                }else{
                    WC()->session->set('cardinity-3dsv2', 'false');
                    WC()->session->set('cardinity-acs', $payment->getAuthorizationInformation()->getUrl());
                    WC()->session->set('cardinity-pareq', $payment->getAuthorizationInformation()->getData());
                    WC()->session->set('cardinity-MD', $MD);
                }

                self::log($message, "OrderID : $order_id");
            } else {
                array_push($errors, __('Payment failed: Internal Error.', 'woocommerce'));

                self::log('Unexpected payment response status.', "OrderID : $order_id");
            }
        } catch (Cardinity\Exception\Declined $exception) {
            $status     = 'declined';
            $payment    = $exception->getResult();
            $payment_id = $payment->getId();
            $message    = sprintf(__('Cardinity payment was declined: %s. Payment ID: %s', 'woocommerce'), $payment->getError(), $payment_id);
            array_push($errors, sprintf(__('Payment declined: %s', 'woocommerce'), $payment->getError()));

            self::log($message, "OrderID : $order_id");
        } catch (Cardinity\Exception\InvalidAttributeValue $exception) {

            self::log("Cardinity Validation Error Exception", "OrderID : $order_id");

            foreach ($exception->getViolations() as $violation) {
                array_push($errors, sprintf(__('Validation error: %s', 'woocommerce'), $violation->getMessage()));

                self::log($violation->getPropertyPath() . ' ' . $violation->getMessage(), "OrderID : $order_id");
            }
        } catch (Cardinity\Exception\ValidationFailed $exception) {
            array_push($errors, sprintf(__('Payment failed: %s.', 'woocommerce'), $exception->getErrorsAsString()));

            self::log($exception->getErrorsAsString(), "OrderID : $order_id");
        } catch (Cardinity\Exception\NotFound $exception) {
            array_push($errors, __('Payment failed: Internal Error.', 'woocommerce'));

            self::log($exception->getErrorsAsString(), "OrderID : $order_id");
        } catch (Exception $exception) {
            array_push($errors, __('Payment failed: Internal Error.', 'woocommerce'));

            self::log($exception->getMessage(), "OrderID : $order_id");
            self::log($exception->getPrevious()->getMessage(), "OrderID : $order_id");
        }

        return array(
            'status'     => $status,
            'errors'     => $errors,
            'message'    => $message,
            'payment_id' => $payment_id,
            'payment_obj' => $payment
        );
    }

    /**
     * Find WooCommerce order by callback MD value
     *
     * @param string $MD
     *
     * @return mixed - false | object $order
     */
    private function get_cardinity_order($MD)
    {
        $custom = explode('-CRD-', $MD);

        if(isset($custom[1])){
            $order_id  = $custom[0];
            $order_key = $custom[1];
        } else {
            self::log('Error: Order ID and key were not found in "custom".');

            return false;
        }

        if (!$order = wc_get_order($order_id)) {
            // We have an invalid $order_id, probably because invoice_prefix has changed
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order    = wc_get_order($order_id);
        }

        if (WC()->version < '2.7.0') {
            $key = $order->order_key;
        } else {
            $key = $order->get_order_key();
        }

        if (!$order || $key !== $order_key) {
            self::log('Error: Order keys do not match.');

            return false;
        }

        return $order;
    }

    /**
     * Get required Cardinity payment parameters
     *
     * @param object $order
     *
     * @return array
     */
    private function getPaymentParams($order)
    {
        if (WC()->version < '2.7.0') {
            $order_id    = $order->id;
            $amount      = (float) $order->order_total;
            $holder_name = '';
            if($this->get_option('show_holder') == 'yes'){
                $holder_name = sanitize_text_field($_POST[CARD_HOLDER]);
            }
            if(!isset($holder_name) || trim($holder_name) == ""){
                $holder_name = mb_substr($order->billing_first_name . ' ' . $order->billing_last_name, 0, 32);
            }
            if (!empty($order->billing_country)) {
                $country = $order->billing_country;
            } else {
                $country = $order->shipping_country;
            }
        } else {
            $order_id    = $order->get_id();
            $amount      = (float) $order->get_total();
            $holder_name = sanitize_text_field(stripslashes($_POST[CARD_HOLDER]));
            if(!isset($holder_name) || trim($holder_name) == ""){
                $holder_name = mb_substr($order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 0, 32);
            }
            if ($order->get_billing_country()) {
                //try use billing address country if set
                $country = $order->get_billing_country();
            } else {
                //try use shipping address if billing not available
                $country = $order->get_shipping_country();
            }
        }

        //billing or shipping neither has country set, try wc default location
        if(!$country){
            $customerDefaultLocation = wc_get_customer_default_location();
            $country = $customerDefaultLocation['country'];
        }

        //still nothing, use default
        if(!$country){
            $country = "LT";
        }


        $crd_order_id      = str_pad($order_id, 2, '0', STR_PAD_LEFT);
        $cvc               = sanitize_text_field($_POST[CARD_CVC]);
        $card_number       = str_replace(' ', '', sanitize_text_field($_POST[CARD_PAN]));
        $card_expire_array = explode('/', sanitize_text_field($_POST[CARD_EXPIRY]));
        $exp_month         = (int) $card_expire_array[0];
        $exp_year          = (int) $card_expire_array[1];
        if ($exp_year < 100) {
            $exp_year += 2000;
        }

        //default challenge window size
        $challenge_window_size = 'full-screen';
        $availChallengeWindowSizes = [
            [600,400],
            [500,600],
            [390,400],
            [250,400]
        ];

        $cardinity_screen_width = (int) sanitize_text_field($_POST['cardinity_screen_width']);
        $cardinity_screen_height = (int) sanitize_text_field($_POST['cardinity_screen_height']);

        $cardinity_color_depth = (int) sanitize_text_field($_POST['cardinity_color_depth']);
        $cardinity_time_zone = (int) sanitize_text_field($_POST['cardinity_time_zone']);

        $cardinity_browser_language = sanitize_text_field($_POST['cardinity_browser_language']);

        //display below 800x600
        if(!($cardinity_screen_width > 800 && $cardinity_screen_height > 600)){
            //find largest acceptable size
            foreach($availChallengeWindowSizes as $aSize){
                if($aSize[0] > $cardinity_screen_width|| $aSize[1] > $cardinity_screen_height){
                    //this challenge window size is not acceptable
                }else{
                    $challenge_window_size = "$aSize[0]x$aSize[1]";
                    break;
                }
            }
        }

        $email = $order->get_billing_email();
        $mobile_number = $order->get_billing_phone();

        $cardholder_info = [];
        if($email){
            $cardholder_info['email_address'] = $email;
        }
        if($mobile_number){
            $cardholder_info['mobile_phone_number'] = $mobile_number;
        }

        return [
            'amount'             => $amount,
            'currency'           => get_woocommerce_currency(),
            'order_id'           => $crd_order_id,
            'country'            => $country,
            'payment_method'     => Cardinity\Method\Payment\Create::CARD,
            'payment_instrument' => [
                'pan'       => $card_number,
                'exp_year'  => $exp_year,
                'exp_month' => $exp_month,
                'cvc'       => $cvc,
                'holder'    => $holder_name
            ],
            'threeds2_data' =>  [
                "notification_url" =>  $this->notify_url,
                "browser_info" => [
                    "accept_header" => "text/html",
                    "browser_language" => $cardinity_browser_language != '' ? $cardinity_browser_language : "en-US",
                    "screen_width" => $cardinity_screen_width != 0 ? $cardinity_screen_width : 1920 ,
                    "screen_height" => $cardinity_screen_height != 0 ? $cardinity_screen_height : 1040,
                    'challenge_window_size' => $challenge_window_size,
                    "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0",
                    "color_depth" => $cardinity_color_depth !=0 ?  $cardinity_color_depth : 24,
                    "time_zone" =>  $cardinity_time_zone != 0 ? $cardinity_time_zone : -60
                ],
                'cardholder_info' => $cardholder_info
            ]
        ];
    }

    /**
     * Get required Cardinity recurring payment parameters
     *
     * @param object $order
     *
     * @return mixed -array or null
     */
    private function getRecurringPaymentParams($order)
    {
        $country = '';

        if (WC()->version < '2.7.0') {
            $order_id = $order->id;
            $amount   = (float) $order->order_total;
            if (!empty($order->billing_country)) {
                $country = $order->billing_country;
            } else {
                $country = $order->shipping_country;
            }
        } else {
            $order_id = $order->get_id();
            $amount   = (float) $order->get_total();
            if ($order->get_billing_country()) {
                $country = $order->get_billing_country();
            } else {
                $country = $order->get_shipping_country();
            }
        }

        //billing or shipping neither has country set, try wc default location
        if(!$country){
            self::log("Missing billing/shipping country info use wc_default", $order_id);
            $customerDefaultLocation = wc_get_customer_default_location();
            if(isset($customerDefaultLocation['country'])){
                $country = $customerDefaultLocation['country'];
            }
        }

        //still nothing, use default
        if(!$country){
            self::log("Missing wc_default country",  $order_id);
            $country = "LT";
        }

        $crd_order_id = str_pad($order_id, 2, '0', STR_PAD_LEFT);

        $payment = [
            'amount'             => $amount,
            'currency'           => get_woocommerce_currency(),
            'order_id'           => $crd_order_id,
            'country'            => $country,
            'payment_method'     => Cardinity\Method\Payment\Create::RECURRING,
            'payment_instrument' => [
                'payment_id' => $order->get_meta('_crd_recurring_id')
            ]
        ];

        self::log("Recurring transactions params : ".print_r($payment, true), $crd_order_id);

        return $payment;
    }

    /**
     * Check if credit card number is valid
     *
     * @param string $toCheck
     *
     * @return bool
     */
    private function isCreditCardNumber($toCheck)
    {
        $number = preg_replace('/[^0-9]+/', '', $toCheck);

        if (!is_numeric($number)) {
            return false;
        }

        $strlen = strlen($number);
        $sum    = 0;

        if ($strlen < 13) {
            return false;
        }

        for ($i = 0; $i < $strlen; $i++) {
            $digit = (int) substr($number, $strlen - $i - 1, 1);
            if ($i % 2 == 1) {
                $sub_total = $digit * 2;
                if ($sub_total > 9) {
                    $sub_total = 1 + ($sub_total - 10);
                }
            } else {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }

        if ($sum > 0 and $sum % 10 == 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if credit card number is valid
     *
     * @param string $toCheck
     *
     * @return bool
     */
    private function isCreditCardHolderName($toCheck)
    {
        $exp = '/^[\pL\'\.\pZs\-]{2,32}$/u';
        $toCheck = stripslashes(trim($toCheck));
        if(preg_match($exp,$toCheck)){
            return true;
        }
        return false;
    }


    /**
     * Check if card expiry date is in the future
     *
     * @param string $toCheck
     *
     * @return bool
     */
    private function isCorrectExpireDate($toCheck)
    {
        if (!preg_match('/^([0-9]{2})\\s\\/\\s([0-9]{2,4})$/', $toCheck, $exp_date)) {
            return false;
        }

        $month = $exp_date[1];
        $year  = $exp_date[2];

        $now       = time();
        $result    = false;
        $thisYear  = (int) date('y', $now);
        $thisMonth = (int) date('m', $now);

        if (is_numeric($year) && is_numeric($month)) {
            if ($year > 100) {
                $year -= 2000;
            }

            if ($thisYear == (int) $year) {
                $result = (int) $month >= $thisMonth;
            } else if ($thisYear < (int) $year) {
                $result = true;
            }
        }

        return $result;
    }



    public function generate_screen_button_html( $key, $value ) {
        // /<tr><td>&nbsp;</td></tr>
        return '
        <tr valign="top">
            <th scope="row" class="titledesc">
                Transaction History
            </th>
            <td class="forminp">
                <a  href="'.admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cardinity&screen=transactions_log' ).'" class="button">'.
                  __( 'View', 'wcommerce' ).
                '</a>
            </td>
        </tr>

        ';

    }


    public function add_transaction_history($data){

        self::log("logging transaction", $data[0]);
        $currentFilename = "transactions-".date("Y-m").'.log';

        $transactionFile = WP_PLUGIN_DIR.'/cardinity-free-payment-gateway-for-woocommerce/transactions/'.$currentFilename;

        //self::log($transactionFile);

        $message = implode(" :: ",$data);

        if (!file_exists($transactionFile)) {
            $message = "OrderId :: PaymentID :: 3d Secure Version :: Amount :: Status\n".$message;
        }

        try {
            file_put_contents($transactionFile, $message."\n", FILE_APPEND);

            if (!file_exists($transactionFile)) {
                self::log("Unable to create file, please check permission on $transactionFile");
            }

        } catch (Exception $e) {
            self::log($e->getMessage());
        }

    }

    private function is_using_checkout_block() {
        if(method_exists("WC_Blocks_Utils",'has_block_in_page')){
            return WC_Blocks_Utils::has_block_in_page( wc_get_page_id('checkout'), 'woocommerce/checkout' );
        }
        return false;
    }
}

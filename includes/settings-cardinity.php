<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Settings for Cardinity Gateway
 */
return array(


    'enabled' => array(
        'title' => __('Enable/Disable', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable Cardinity gateway', 'woocommerce'),
        'default' => 'yes',
        'desc_tip' => true
    ),
    'title' => array(
        'title' => __('Title', 'woocommerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'default' => __('Credit / Debit Card', 'woocommerce'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description', 'woocommerce'),
        'type' => 'text',
        'desc_tip' => true,
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        'default' => __('Pay by credit / debit card.', 'woocommerce')
    ),
    'consumer_key' => array(
        'title' => __('Consumer key', 'woocommerce'),
        'type' => 'text',
        'description' => __('Your Cardinity consumer key.', 'woocommerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'consumer_secret' => array(
        'title' => __('Consumer secret', 'woocommerce'),
        'type' => 'text',
        'description' => __('Your Cardinity consumer secret', 'woocommerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'external' => array(
        'title' => __('External checkout', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable external checkout', 'woocommerce'),
        'default' => 'no',
        'desc_tip' => true,
        'description' => __('This controls whether or not your users leave your website during payment', 'woocommerce')
    ),
    'project_key' => array(
        'title' => __('Project key', 'woocommerce'),
        'type' => 'text',
        'description' => __('Your Cardinity project key.', 'woocommerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'project_secret' => array(
        'title' => __('Project secret', 'woocommerce'),
        'type' => 'text',
        'description' => __('Your Cardinity project secret', 'woocommerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'show_holder' => array(
        'title' => __('Show Card Holder', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Show Card Holder Input', 'woocommerce'),
        'default' => 'yes',
        'desc_tip' => true,
        'description' => __('Show Card Holder Name input field on checkout.', 'woocommerce')
    ),
    'debug' => array(
        'title' => __('Debug Log', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'woocommerce'),
        'default' => 'yes',
        'desc_tip' => true,
        'description' => sprintf(__('Log Cardinity events inside <code>%s</code>', 'woocommerce'),"Woocommerce > status > logs")
    )
);

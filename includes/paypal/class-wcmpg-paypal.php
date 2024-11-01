<?php
/**
 * Paypal Payment Gateway.
 *
 * @since	   1.0.0
 * @package	   WCMPG
 * @subpackage WCMPG/includes
 * @author	   Service technique IRCF <technique@ircf.fr>
 */


/*
 * WCMPG_Paypal
 */
class WCMPG_Paypal extends WCMPG_Base {

  /**
  * Constructor
  */
  function __construct() {
    parent::__construct();

    $this->id = 'wcmpg_paypal';
    $this->icon = '"width=50px"';
    $this->order_button_text  = __( 'Proceed to Paypal', 'wcmpg' );
    $this->has_fields = false;
    $this->method_title = 'WCMPG Paypal';
    $this->method_description = __( 'Paypal is the most popular payment mean.' , 'wcmpg' );

    // Load the form fields
    $this->init_form_fields();
    // Load the settings.
    $this->init_settings();

    // Get setting values
    foreach ($this->settings as $key => $values){
      $this->$key = $values;
    }

    // Hooks
    add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    add_action('woocommerce_receipt_' . $this->id, array($this, 'get_param_paypal'));	 
  } //end __contruct()

  /**
  * Admin tools
  */
  public function admin_options() {

    echo '<img src="'.plugins_url().'/wcmpg/assets/paypal.png" alt="Paypal" width="250">';
    echo '<h3> Paypal </h3>';
    echo '<p>' . __( 'PayPal standard sends customers to PayPal to enter their payment information.' , 'wcmpg'  ) . '</p>';
    echo '<table class="form-table">';
    $this->generate_settings_html();
    echo '</table>';
    echo '<hr/>';
  }

  /*
  * Initialize Gateway Settings Form Fields.
  */
  public function init_form_fields() {
    $this->form_fields = include __DIR__.'/../../admin/settings.php';
    $this->form_fields += array(
      'email'		      => array(
        'title'	      => __('Email', 'wcmpg'),
        'type'		  => 'text',
        'default'	  => 'test@wcmpg.fr',
        'description' => __('Email of the seller.', 'wcmpg')
      ),
      'test_mode'	      => array(
        'title'	      => __('PayPal Sandbox', 'wcmpg'),
        'type'		  => 'checkbox',
        'label'	      => __('Enable PayPal Sandbox', 'wcmpg'),
        'default'	  => 'no',
        'description' => __('The sandbox is PayPal\'s test environment and is only for use with sandbox accounts created within your', 'wcmpg' ). '<a href="http://developer.paypal.com" target="_blank">'. __(' PayPal developer account', 'wcmpg' ). '</a>. </br>'
      ),
      'sandbox_title'   => array(
        'title'		  => __( 'Options for Sandbox', 'wcmpg' ),
        'type'		  => 'title',
        'description' => '',
      ),
      'sandbox_api_username' => array(
        'title'	      => __('Sandbox API User Name', 'wcmpg'),
        'type'		  => 'text',
        'default'	  => '',
        'placeholder' => __( 'Optional', 'wcmpg' ),
      ),
      'sandbox_api_password' => array(
        'title'	      => __('Sandbox API Password', 'wcmpg'),
        'type'		  => 'password',
        'default'	  => '',
        'placeholder' => __( 'Optional', 'wcmpg' ),
      ),
      'sandbox_api_signature' => array(
        'title'	      => __('Sandbox API Signature', 'wcmpg'),
        'type'		  => 'text',
        'default'	  => '',
        'description' => '</br>',
        'placeholder' => __( 'Optional', 'wcmpg' ),
      ),
      'api_title'	      => array(
        'title'		  => __( 'Options for the API', 'wcmpg' ),
        'type'		  => 'title',
        'description' => '',
      ),
      'api_username'	  => array(
        'title'	      => __('Live API User Name', 'wcmpg'),
        'type'		  => 'text',
        'default'	  => '',
        'placeholder' => __( 'Optional', 'wcmpg' ),
      ),
      'api_password'	  => array(
        'title'	      => __('Live API Password', 'wcmpg'),
        'type'		  => 'password',
        'default'	  => '',
        'placeholder' => __( 'Optional', 'wcmpg' ),
      ),
      'api_signature'   => array(
        'title'	      => __('Live API Signature', 'wcmpg'),
        'type'		  => 'text',
        'default'	  => '',
        'placeholder' => __( 'Optional', 'wcmpg' ),
      ),
      'advanced'		  => array(
        'title'		  => '</br>'.__( 'Advanced options', 'wcmpg' ),
        'type'		  => 'title',
        'description' => '',
      ),
      'page_style'	  => array(
        'title'		  => __( 'Page Style', 'wcmpg' ),
        'type'		  => 'text',
        'description' => __( 'Optionally enter the name of the page style you wish to use. These are defined within your PayPal account.', 'wcmpg' ),
        'default'	  => '',
        'placeholder' => __( 'Optional', 'wcmpg' ),
      ),
      'payment_action'  => array(
        'title'		  => __( 'Payment Action', 'wcmpg'  ),
        'type'		  => 'select',
        'class'		  => 'wc-enhanced-select',
        'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'wcmpg'  ),
        'default'	  => 'sale',
        'options'	  => array(
          'sale'			=> __( 'Capture', 'wcmpg'  ),
          'authorization' => __( 'Authorize', 'wcmpg' ),
          'order'			=> __( 'Order', 'wcmpg' ),
        )
      ),
      'invoice_prefix'  => array(
        'title'		  => __( 'Invoice Prefix', 'wcmpg'  ),
        'type'		  => 'text',
        'description' => __( 'Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'wcmpg' ),
        'default'	  => '',
        'placeholder' => __( 'Optional', 'wcmpg' ),
      ),
    );
  } //end init_form_fields()

  /**
  * Process the payment and return the result
  */
  function process_payment($order_id) {
    $order = wc_get_order( $order_id );
  
    return array(
      'result'   => 'success',
      'redirect' => $this->get_request_url( $order )
    );
  } //end process_payment()
  
  /**
  * Get the PayPal request URL for an order.
  */
  public function get_request_url( $order ) {
    $params = $this->get_param_paypal( $order );
    error_log("WCMPG_Paypal DEBUG : request = " . print_r($params, true));
    $paypal_args = http_build_query( $params, '', '&' );
    if ( $this->test_mode == 'yes' ) {
      return 'https://www.sandbox.paypal.com/cgi-bin/webscr?test_ipn=1&' . $paypal_args;
    } else {
      return 'https://www.paypal.com/cgi-bin/webscr?' . $paypal_args;
    }
  } //end get_request_url

  /**
  * Get PayPal Args for passing to PP.
  */
  public function get_param_paypal( $order ) {
    return apply_filters( 'woocommerce_paypal_args', array_merge (
      array(
        'cmd'			=> '_cart',
        'business'		=> $this->email,
        'currency_code' => $this->functions->get_currency($this->currency),
        'charset'		=> 'utf-8',
        'rm'			=> is_ssl() ? 2 : 1,
        'upload'		=> 1,
        'return'		=> $this->success_url,
        'cancel_return' => $this->cancel_url,
        'page_style'	=> $this->page_style,
        'paymentaction' => $this->payment_action,
        'invoice'		=> $this->invoice_prefix . $order->get_id(),
        'custom'		=> json_encode( array( 'order_id' => $order->get_id(), 'order_key' => $order->get_order_key() ) ),
        'first_name'	=> $order->get_billing_first_name(),
        'last_name'		=> $order->get_billing_last_name(),
        'company'		=> $order->get_billing_company(),
        'address1'		=> $order->get_billing_address_1(),
        'address2'		=> $order->get_billing_address_2(),
        'city'			=> $order->get_billing_city(),
        'state'			=> $order->get_billing_state(),
        'zip'			=> $order->get_billing_postcode(),
        'country'		=> $order->get_billing_country(),
        'email'			=> $order->get_billing_email(),
        'night_phone_b' => $order->get_billing_phone(),
        'day_phone_b' 	=> $order->get_billing_phone()
      ),
      $this->get_items_args( $order )
    ), $order );
  } //end getParamPaypal

  public function get_items_args($order) {
    $item_array = array();
    $i = 1;
    foreach ( $order->get_items() as $item ) {
      $item_array['item_name_' . $i ] = $item['name'];
      $item_array['amount_' . $i ] = $item['line_total'] / $item['qty'];
      $item_array['quantity_' . $i ] = $item['qty'];
      $i++;
    }
    return $item_array;
  } //end get_items_args()
}

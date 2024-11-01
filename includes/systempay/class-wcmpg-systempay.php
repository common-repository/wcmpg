<?php
/**
 * Systempay Payment Gateway.
 *
 * @since      1.0.0
 * @package    WCMPG
 * @subpackage WCMPG/includes
 * @author     Service technique IRCF <technique@ircf.fr>


/*
 * WCMPG_Systempay
 */
class WCMPG_Systempay extends WCMPG_Base {

    private $_urls = array(
      'systempay'   => 'https://paiement.systempay.fr/vads-payment/',
      'sogenactif'  => 'https://sogecommerce.societegenerale.eu/vads-payment/',
      'scellius'    => 'https://scelliuspaiement.labanquepostale.fr/vads-payment/',
    );

    /**
    * Constructor
    */
    function __construct() {
        parent::__construct();

        $this->id = 'wcmpg_systempay';
        $this->icon = '"width=50px"';
        $this->order_button_text  = __( 'Proceed to Systempay', 'wcmpg' );
        $this->has_fields = false;
        $this->method_title = 'WCMPG Systempay';
        $this->method_description = __( 'SystemPay is used by Cyberplus Paiement by Banque Populaire and SP PLUS by Caisse d\'Epargne.' , 'wcmpg' );

        // Load the form fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();

        // Get setting values
        foreach ($this->settings as $key => $values){
            $this->$key = $values;
        }

        // Set a title to the payment gateway if free version
        if ($this->functions->enable_payment_gateways()==false) {
            $this->title = 'Systempay (Pro version)';
            $this->method_title = 'WCMPG Systempay (Pro)';
        }

        // Hooks
        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt'));
        add_action('woocommerce_api_' . $this->id, array($this, 'api')); 
    } //end __construct()

    /**
    * Admin tools
    */
     public function admin_options() {
        if ($this->functions->enable_payment_gateways()==true) {

            echo '<img src="'.plugins_url().'/wcmpg/assets/systempay.png" alt="Systempay" width="250">';
            echo '<h3> Systempay </h3>';
            echo
                '<p>' . __( 'Authorizes the payments by credit card with the systempay solution. It needs the signature of a distance selling contract from a bank compatible with Systempay.' , 'wcmpg'  ) . '</p>';
            
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
            echo '<hr />';
            echo '<h2>' . __('Please enter the following information into you bank back-office', 'wcmpg') . '</h2>';
            echo '<p>' . __('Automatic Return Link', 'wcmpg') . ' : ' . site_url('/?wc-api=' . get_class($this)) . '</strong></p>';
        }else{
            $current_url = explode("?", $_SERVER['REQUEST_URI']);
            $url= $current_url[0] .'?page=wc-settings&tab=checkout&section=wcmpg_licence';
            header('Location:'.$url);
        }
    } // end admin_options()

    /**
    * Initialize Gateway Settings Form Fields.
    */
    function init_form_fields() {
        $this->form_fields = include __DIR__.'/../../admin/settings.php';
        $this->form_fields += array(
            'bank' => array(
                'title'       => __( 'Bank', 'wcmpg'),
                'type'        => 'select',
                'description' => __( 'Your bank.', 'wcmpg'),
                'default'     => 'systempay',
                'class'       => 'wc-enhanced-select',
                'options'     => array(
                    'systempay'  => __( 'Systempay', 'wcmpg'),
                    'sogenactif' => __( 'Sogenactif by Société Générale', 'wcmpg'),
                    'scellius'   => __( 'Scellius by La Banque Postale', 'wcmpg'),
                )
            ),
            'site_id'         => array(
                'title'       => __('Site ID', 'wcmpg'),
                'type'        => 'text',
                'default'     => '12345678',
                'description' => __( 'The identifier provided by your payment gateway.', 'wcmpg'),
            ),
            'systempay_certificat' => array(
                'title'            => __('Certificat', 'wcmpg'),
                'type'             => 'text',
                'description'      => __('The certificat provided by Systempay.', 'wcmpg'),
                'default'          => '1122334455667788'
            ),
            'systempay_mode'  => array(
                'title'       => __( 'Mode', 'wcmpg'),
                'type'        => 'select',
                'description' => __( 'The context mode of this module.', 'wcmpg'),
                'class'       => 'wc-enhanced-select',
                'default'     => 'production',
                'options'     => array(
                    'PRODUCTION'   => __( 'Production', 'wcmpg' ),
                    'TEST'         => __( 'Testing', 'wcmpg' )
                ),
            ),
            'systempay_payment_cards'  => array(
                'title'       => __( 'Payment means in use', 'wcmpg'),
                'type'        => 'text',
                'description' => __( 'Ex : CB;CVCONNECT;PAYLIB;MASTERCARD;VISA', 'wcmpg'),
                'default'     => 'CB;CVCONNECT;PAYLIB;MASTERCARD;VISA',
            ),
            'systempay_algorithm'  => array(
                'title'       => __( 'Algorithm', 'wcmpg'),
                'type'        => 'select',
                'description' => __( 'Algorithm used for signature computation (configured in the Systempay back-office).', 'wcmpg'),
                'class'       => 'wc-enhanced-select',
                'default'     => 'SHA-1',
                'options'     => array(
                    'SHA-1'   => __( 'SHA-1', 'wcmpg' ),
                    'HMAC-SHA-256' => __( 'HMAC-SHA-256', 'wcmpg' )
                ),
            ),
        );
    } //end init_form_fields()

    /**
     * Process the payment and return the result
     */
    function process_payment($order_id) {
        $order = new WC_Order($order_id);

        return array(
                'result'    => 'success',
                'redirect'  => $order->get_checkout_payment_url(true)
        );            
    } //end process_payment()

    /**
     * Get the Systempay form.
     * @see https://paiement.systempay.fr/doc/fr-FR/form-payment/quick-start-guide/envoyer-un-formulaire-de-paiement-en-post.html
     */
    function receipt($order_id) {
        $order = new WC_Order($order_id);
        if (!isset($this->bank) || empty($this->bank)) $this->bank = 'systempay';
        if (!isset($this->_urls[$this->bank])){
          error_log("WCMPG_Systempay DEBUG : bank = {$this->bank}");
          throw new Exception(__('invalid bank', 'wcmpg'));
        }
        $url = $this->_urls[$this->bank];
        error_log("WCMPG_Systempay DEBUG : url = $url");
        $params = array(
          'vads_action_mode' => 'INTERACTIVE',
          'vads_amount' => $order->get_total()*100,
          'vads_ctx_mode' => $this->systempay_mode,
          'vads_currency' => $this->currency,
          'vads_page_action' => 'PAYMENT',
          'vads_payment_config' => 'SINGLE',
          'vads_site_id' =>  $this->site_id,
          'vads_trans_date' =>  date('YmdHis'),
          'vads_trans_id' =>  substr(str_pad($order->get_id(), 6, 0, STR_PAD_LEFT), -6),
          'vads_url_cancel' =>  $this->cancel_url,
          'vads_url_refused' =>  $this->refused_url,
          'vads_url_success' =>  $this->success_url,
          'vads_version' => 'V2',
          'vads_language' => $this->functions->get_language($this->language, $this->detect_language),
          'vads_payment_cards' => isset($this->systempay_payment_cards) ? $this->systempay_payment_cards : 'CB,CVCONNECT;PAYLIB;MASTERCARD;VISA',
          'vads_order_id' => $order->get_id(),
          'vads_order_info' => substr(str_replace("\n", " ", $order->get_customer_note()), 0, 255),
          'vads_order_info2' => $order->get_shipping_method(),
          'vads_cust_email' => $order->get_billing_email(),
          'vads_cust_id' => $order->get_customer_id(),
          // TODO 'vads_cust_title' => '',
          'vads_cust_status' => (empty($order->get_billing_company()) ? 'PRIVATE' : 'COMPANY'),
          'vads_cust_first_name' => $order->get_billing_first_name(),
          'vads_cust_last_name' => $order->get_billing_last_name(),
          'vads_cust_legal_name' => $order->get_billing_company(),
          'vads_cust_phone' => $order->get_billing_phone(),
          // TODO 'vads_cust_cell_phone' => '',
          // TODO 'vads_cust_address_number' => '',
          'vads_cust_address' => $order->get_billing_address_1(),
          'vads_cust_address2' => $order->get_billing_address_2(),
          // TODO 'vads_cust_district' => '',
          'vads_cust_zip' => $order->get_billing_postcode(),
          'vads_cust_city' => $order->get_billing_city(),
          'vads_cust_state' => $order->get_billing_state(),
          'vads_cust_country' => $order->get_billing_country(),
        );
        if (!empty($order->get_shipping_city())){
          $params+= array(
            'vads_ship_to_city' => $order->get_shipping_city(),
            'vads_ship_to_country' => $order->get_shipping_country(),
            // TODO 'vads_ship_to_district' => '',
            'vads_ship_to_first_name' => $order->get_shipping_first_name(),
            'vads_ship_to_last_name' => $order->get_shipping_last_name(),
            'vads_ship_to_legal_name' => $order->get_shipping_company(),
            // TODO 'vads_ship_to_phone_num' => '',
            'vads_ship_to_state' => $order->get_shipping_state(),
            'vads_ship_to_status' => (empty($order->get_shipping_company()) ? 'PRIVATE' : 'COMPANY'),
            // TODO 'vads_ship_to_street_number' => '',
            'vads_ship_to_street' => $order->get_shipping_address_1(),
            'vads_ship_to_street2' => $order->get_shipping_address_2(),
            'vads_ship_to_zip' => $order->get_shipping_postcode(),
          );
        }
        $items = $order->get_items();
        $params['vads_nb_products'] = count($items);
        $i = 0;
        foreach ($order->get_items() as $item){
          $params+= array(
            // TODO 'vads_product_ext_id'.$i => '',
            'vads_product_label'.$i => $item->get_name(),
            'vads_product_amount'.$i => intval($item->get_subtotal()*100),
            // TODO 'vads_product_type'.$i => '',
            'vads_product_ref'.$i => $item->get_product()->get_sku(),
            'vads_product_qty'.$i => $item->get_quantity(),
          );
          $i++;
        }
        $params = apply_filters('wcmpg_receipt_params', $params);
        $params['signature'] = $this->get_signature($params);
        error_log("WCMPG_Systempay DEBUG : request = " . print_r($params, true));
        
        $form_output = '<p>'.__('Thank you for your order. You will be redirected on SystemPay plateform.', 'wcmpg'). '</br>'. __('If nothing happens please click on the "SystemPay" button below.', 'wcmpg') . '</p>';
        $form_output.= '<form method="POST" action="'.$url.'" name="Systempay">';
        foreach($params as $key => $value){
          $form_output.= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'">';
        }
        $form_output.= '<input type="submit" value="SystemPay">';
        $form_output.= '</form>';
        $form_output .= '<script> document.forms["Systempay"].submit(); </script>';

        echo $form_output ;
    } // end receipt()

  /**
   * Payment API
   */
  public function api() {
    error_log("WCMPG_Systempay DEBUG : response = " . print_r($_POST, true));
    $order = new WC_Order(@$_POST['vads_trans_id']);

    $signature = $this->get_signature($_POST);
    error_log("WCMPG_Systempay DEBUG : response computed signature = $signature");
    if (@$_POST['signature'] != $signature){
      error_log("WCMPG_Systempay DEBUG : response signature KO");
      die("An error occurred while computing the signature.");
    }

    if ($_POST['vads_auth_result'] == '00'){
      $order->add_order_note(__('Payment accepted', 'wcmpg'));
      $order->payment_complete($_POST['vads_auth_number']);
    }else{
      $message = __('Payment refused', 'wcmpg');
      $message.= ' (' . $_POST['vads_auth_result'] . ' : ';
      if (isset($this->_errors[$_POST['vads_auth_result']])){
        $message.= __($this->_errors[$_POST['vads_auth_result']], 'wcmpg');
      }else{
        $message.= __('Unknown error', 'wcmpg');
      }
      $message.= ')';
      $order->add_order_note($message);
    }
    error_log("WCMPG_Systempay DEBUG : response signature OK");
    die("Order successfully updated.");
  }

  // see https://systempay.cyberpluspaiement.com/html/Doc/Payment_Form/Guide_d_implementation_du_formulaire_de_paiement_Systempay_v3.13.pdf
  // see https://paiement.systempay.fr/doc/fr-FR/form-payment/quick-start-guide/exemple-d-implementation-en-php.html
  private function get_signature($params){
    $data = '' ;
    ksort($params);
    foreach ($params as $key => $value){ 
      if (substr($key, 0, 5) == 'vads_') { 
        $data .= stripslashes($value) . '+';
      }
    }
    $data.= $this->systempay_certificat;
    error_log("WCMPG_Systempay DEBUG : data = " . $data);
    if (!isset($this->systempay_algorithm)) $this->systempay_algorithm = 'SHA-1';
    error_log("WCMPG_Systempay DEBUG : algorithm = " . $this->systempay_algorithm);
    if ($this->systempay_algorithm == 'SHA-1'){
      $data = sha1($data);
    }else if ($this->systempay_algorithm == 'HMAC-SHA-256'){
      $data = base64_encode(hash_hmac('sha256',$data, $this->systempay_certificat, true));
    }else{
      die("Invalid signature algorithm.");
    }
    error_log("WCMPG_Systempay DEBUG : signature = " . $data);
    return $data;
  }
} //end WCMPG_SystemPay

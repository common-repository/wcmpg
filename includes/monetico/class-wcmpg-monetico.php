<?php
/**
 * Monetico Payment Gateway.
 *
 * @since	   1.1
 * @package	   WCMPG
 * @subpackage WCMPG/includes
 * @author	   Service technique IRCF <technique@ircf.fr>


/*
 * WCMPG_Monetico
 */
class WCMPG_Monetico extends WCMPG_Base {

  /**
  * Constructor
  */
  function __construct() {
    parent::__construct();

    $this->id = 'wcmpg_monetico';
    $this->icon = '"width=50px"';
    $this->order_button_text  = __( 'Proceed to Monetico', 'wcmpg' );
    $this->has_fields = false;
    $this->method_title = 'WCMPG Monetico';
    $this->method_description = __( 'Monetico is the payment solution by Crédit Mutuel and CIC.' , 'wcmpg' );

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
      $this->title = 'Monetico (Pro version)';
      $this->method_title = 'WCMPG Monetico(Pro)';
    }

    // Hooks
    add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt'));
    add_action('woocommerce_api_' . $this->id, array($this, 'api'));

  } //end __contruct()

  /**
  * Admin tools
  */
  public function admin_options() {
    if ($this->functions->enable_payment_gateways()==true) {
      echo '<img src="'.plugins_url().'/wcmpg/assets/monetico.png" alt="Monetico" width="250">';
      echo '<h3> Monetico </h3>';
      echo $this->method_description;
      echo '<table class="form-table">';
      $this->generate_settings_html();
      echo'</table>';
      echo '<hr>';
      echo '<h2>' . __('Please send the following information to your bank support', 'wcmpg') . '</h2>';
      echo '<p>' . __('Automatic Return Link', 'wcmpg') . ' : ' . site_url('/?wc-api=' . get_class($this)) . '</strong></p>';
    }else{
      $current_url = explode("?", $_SERVER['REQUEST_URI']);
      $url= $current_url[0] .'?page=wc-settings&tab=checkout&section=wcmpg_licence';
      header('Location:'.$url);
    }
  }

  /*
  * Initialize Gateway Settings Form Fields.
  */
  function init_form_fields() {
    $this->form_fields = include __DIR__.'/../../admin/settings.php';
    $this->form_fields    += array(
      'societe'         => array(
        'title'       => __('Societe', 'wcmpg'),
        'type'        => 'text',
        'description' => __('Societe number.', 'wcmpg'),
        'default'     => 'example'
      ),
      'monetico_TPE'    => array(
        'title'       => __('TPE', 'wcmpg'),
        'type'        => 'textarea',
        'description' => __('The private TPE key generated at the Monetico back office.', 'wcmpg'),
        'default'     => '1234567'
      ),
      'monetico_key'    => array(
        'title'       => __('Secret key', 'wcmpg'),
        'type'        => 'textarea',
        'description' => __('The private secret key generated at the Monetico back office.', 'wcmpg'),
        'default'     => '0123456789ABCDEF0123456789ABCDEF01234567'
      ),
      'monetico_image_left'  => array(
        'title'	      => __( 'Left image', 'wcmpg' ),
        'type'		  => 'text',
        'description' => __( 'Left image on monetico paiement page.', 'wcmpg' ),
        'default'	  => 'left.png'
      ),
      'monetico_image_right'  => array(
        'title'	      => __( 'Right image', 'wcmpg' ),
        'type'		  => 'text',
        'description' => __( 'Right image monetico paiement page.', 'wcmpg' ),
        'default'	  => 'right.png'
      ),
      'monetico_image_center' => array(
        'title'	      => __( 'Center image', 'wcmpg' ),
        'type'		  => 'text',
        'description' => __( 'Center image monetico paiement page.', 'wcmpg' ),
        'default'	  => 'center.png'
      ),
      'monetico_mode'	  => array(
        'title'	      => __( 'Mode', 'wcmpg'),
        'type'		  => 'select',
        'description' => __( 'Choose Testing to test the payment gateway.', 'wcmpg'),
        'default'	  => 'production',
        'class'	      => 'wc-enhanced-select',
        'options'	  => array(
          'production'  => __( 'Production', 'wcmpg'),
          'testing'	  => __( 'Testing', 'wcmpg')
        )
      ),
      'monetico_algorithm'  => array(
          'title'       => __( 'Algorithm', 'wcmpg'),
          'type'        => 'select',
          'description' => __( 'Algorithm used for signature computation (configured in the Monetico back-office).', 'wcmpg'),
          'class'       => 'wc-enhanced-select',
          'default'     => 'sha1',
          'options'     => array(
              'sha1'   => __( 'sha1', 'wcmpg' ),
              'md5'    => __( 'md5', 'wcmpg' )
          ),
      ),
    );
  }//end init_form_fields()

  /**
  * Process the payment and return the result
  */
  function process_payment($order_id) {
    $order= new WC_Order($order_id);

    return array(
      'result'	=> 'success',
      'redirect'  => $order->get_checkout_payment_url(true)
    );
  } //end process_payment()

  /**
   * Get Monetico form.
   */
  function receipt($order_id) {
    $order  =  new WC_Order($order_id);
    $mode = $this->monetico_mode == 'production' ? '' : 'test/';
    $url = 'https://p.monetico-services.com/'.$mode.'paiement.cgi';
    if (!empty($this->monetico_key) && strlen($this->monetico_key) == 40) {
      $params = array(
        "version" => '3.0',
        "TPE" => $this->monetico_TPE,
        "date" => $this->get_date_monetico($order->get_date_completed()),
        "montant" => $order->get_total() . 'EUR',
        "reference" => $order->get_id(),
        "url_retour_ok" => $order->get_checkout_order_received_url(),
        "url_retour_err" => $this->refused_url,
        "lgue" => strtoupper($this->functions->get_language($this->language, $this->detect_language)),
        "societe" => $this->societe,
        "texte-libre" => $order->get_customer_note(),
        "mail" => $order->get_billing_email(),
        "contexte_commande" => $this->get_contexte_commande($order),
      );
      $params = apply_filters('wcmpg_receipt_params', $params);
      $params["MAC"] = $this->generate_mac($params);
      error_log("WCMPG_Monetico DEBUG : request = " . print_r($params, true));
      $form_output  = '<p>'.__('Thank you for your order. You will be redirected on Monetico plateform.' , 'wcmpg' ).' </br>'.__(' If nothing happens please click on the "Monetico" button below.', 'wcmpg') . '</p>';
      $form_output .= '<form method="POST" action="' . $url . '" name="Monetico">';
      foreach($params as $key => $value){
        $form_output .= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'">';
      }
      $form_output .= '<input type="submit" value="Monetico">';
      $form_output .= '</form>';
      //script
      $form_output .= '<script> document.forms["Monetico"].submit(); </script>';
    }
    else{ //if $this->monetico_key is null
      $form_output = __('Monetico secret key must be filled in.', 'wcmpg');
    }
    echo $form_output;
  } //end receipt()

  /**
   * Payment API
   */
  public function api() {
    error_log("WCMPG_Monetico DEBUG : response = " . print_r($_REQUEST, true));
    $order = new WC_Order(@$_REQUEST['reference']);
    
    $mac = $this->generate_mac($_REQUEST);
    error_log("WCMPG_Monetico DEBUG : response computed mac = $mac");
    if (@$_REQUEST['MAC'] != $mac){
      $order->add_order_note(__('Invalid mac signature, view logs for more info', 'wcmpg'));
      error_log("WCMPG_Monetico DEBUG : response mac KO");
      die("version=2\ncdr=1\n");
    }
    
    if (in_array(@$_REQUEST['code-retour'], array('payetest', 'paiement'))){
      $order->add_order_note(__('Payment accepted', 'wcmpg'));
      $order->payment_complete(@$_REQUEST['reference']);
    }else{
      $message = __('Payment refused', 'wcmpg');
      $message.= ' (' . @$_REQUEST['code-retour'] . ')';
      $order->add_order_note($message);
    }
    error_log("WCMPG_Monetico DEBUG : response mac OK");
    die("version=2\ncdr=0\n");
  }

  /**
   * Return date in Monetico format
   */
  private function get_date_monetico( $date ) {
    $UneDate = new DateTime(strval($date));
    $UneDate = $UneDate->format('d/m/Y:H:i:s');
    return $UneDate;
  }

  /**
  * Generate key to Monetico
  */
  private function generate_mac($data){
    $requiredKeys = array(
      // Request parameters
      '3dsdebrayable',
      'TPE',
      'ThreeDSecureChallenge',
      'contexte_commande',
      'date',
      'lgue',
      'mail',
      'montant',
      'reference',
      'societe',
      'texte-libre',
      'url_retour_err',
      'url_retour_ok',
      'version',
      'mode_affichage',
      // Response parameters
      'authentification',
      'bincb',
      'brand',
      'cbmasquee',
      'code-retour',
      'cvx',
      'ecard',
      'hpancb',
      'ipclient',
      'modepaiement',
      'motifrefus',
      'motifrefusautorisation',
      'numauto',
      'originecb',
      'originetr',
      'typecompte',
      'usage',
      'vld',
    );
    $string = '';
    asort($requiredKeys);
    foreach($requiredKeys as $key) {
      if(array_key_exists($key, $data) && $data[$key] !== null) {
        $string .= $key . '=' . stripslashes($data[$key]) . '*';
      }
    }
    if(substr($string, -1) === '*') {
      $string = substr($string, 0, -1);
    }
    if (empty($this->monetico_algorithm)) $this->monetico_algorithm = 'sha1';
    error_log("WCMPG_Monetico DEBUG : algorithm = ".$this->monetico_algorithm);
    error_log("WCMPG_Monetico DEBUG : mac = $string");
    $key = $this->_getUsableKey($this->monetico_key);
    if (!in_array($this->monetico_algorithm, array('sha1', 'md5'))){
      die("Invalid signature algorithm.");
    }
    return strtoupper(hash_hmac($this->monetico_algorithm, $string, $key));
  }

  /**
  * Return usable key
  */
  private function _getUsableKey($oEpt){

    $hexStrKey  = substr($oEpt, 0, 38);
    $hexFinal   = "" . substr($oEpt, 38, 2) . "00";
    
    $cca0=ord($hexFinal); 

    if ($cca0>70 && $cca0<97) 
      $hexStrKey .= chr($cca0-23) . substr($hexFinal, 1, 1);
    else { 
      if (substr($hexFinal, 1, 1)=="M") 
        $hexStrKey .= substr($hexFinal, 0, 1) . "0"; 
      else 
        $hexStrKey .= substr($hexFinal, 0, 2);
    }

    return pack("H*", $hexStrKey);
  }

  function get_contexte_commande($order){
    $result = array();
    $result['billing']['addressLine1'] = $order->get_billing_address_1();
    $result['billing']['city'] = $order->get_billing_city();
    $result['billing']['postalCode'] = $order->get_billing_postcode();
    $result['billing']['country'] = $order->get_billing_country();
    $result['shipping']['addressLine1'] = $order->get_shipping_address_1();
    $result['shipping']['city'] = $order->get_shipping_city();
    $result['shipping']['postalCode'] = $order->get_shipping_postcode();
    $result['shipping']['country'] = $order->get_shipping_country();
    if (empty($result['shipping']['country'])) unset($result['shipping']);
    return base64_encode(json_encode($result));
  }

} // end WCMPG_Monetico

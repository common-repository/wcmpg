<?php
/**
 * Axepta Payment Gateway.
 * @see https://docs.axepta.bnpparibas/display/DOCBNP/Exemple+de+classe+sur+Github
 * @since      1.0.0
 * @package    WCMPG
 * @subpackage WCMPG/includes
 * @author     Service technique IRCF <technique@ircf.fr>
 */

// @see https://github.com/aphania/axepta-access/blob/master/axepta.php
require_once 'class-axepta.php';

/*
 * WCMPG_Axepta
 */
class WCMPG_Axepta extends WCMPG_Base {

    // @see https://docs.axepta.bnpparibas/display/DOCBNP/A4+Response+codes
    private $_errors = array(
      // TODO
    );

    /**
    * Constructor
    */
    function __construct() {
        parent::__construct();

        $this->id = 'wcmpg_axepta';
        $this->icon = '"width=50px"';
        $this->order_button_text  = __( 'Proceed to Axepta', 'wcmpg' );
        $this->has_fields = false;
        $this->method_title = 'WCMPG Axepta';
        $this->method_description = __( 'Axepta by BNP-Paribas' , 'wcmpg' );

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
            $this->title = 'Axepta (Pro version)';
            $this->method_title = 'WCMPG Axepta (Pro)';
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
            echo '<img src="'.plugins_url().'/wcmpg/assets/axepta.png" alt="Axepta" width="250">';
            echo '<h3>Axepta</h3>';
            echo '<p>' . __( 'Axepta is a secure payment gateway powered by BNP Paribas.' , 'wcmpg'  ) . '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
            echo '<hr />';
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
            'merchant_id'         => array(
                'title'       => __('Merchant ID', 'wcmpg'),
                'type'        => 'text',
                'default'     => 'BNP_DEMO_AXEPTA',
                'description' => __( 'The merchant ID provided by your payment gateway.', 'wcmpg'),
            ),
            'hmac_key'         => array(
                'title'       => __('HMAC Key', 'wcmpg'),
                'type'        => 'text',
                'default'     => '4n!BmF3_?9oJ2Q*z(iD7q6[RSb5)a]A8',
                'description' => __( 'The HMAC key provided by your payment gateway.', 'wcmpg'),
            ),
            'secret_key' => array(
                'title'       => __('Secret key', 'wcmpg'),
                'type'        => 'text',
                'description' => __('The secret key provided by your payment gateway.', 'wcmpg'),
                'default'     => 'Tc5*2D_xs7B[6E?w'
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
     * Get the Axepta form.
     */
    function receipt($order_id) {
        $order = new WC_Order($order_id);
        $paymentRequest = new Axepta($this->hmac_key);
        $paymentRequest->setCryptKey($this->secret_key);
        $paymentRequest->setUrl(Axepta::PAYSSL);
        $paymentRequest->setMerchantID($this->merchant_id);
        $paymentRequest->setTransID($order->get_id() . '-' . time());
        $paymentRequest->setAmount(intval($order->get_total()*100));
        $paymentRequest->setCurrency($this->currency);
        $paymentRequest->setRefNr($order->get_id());
        $paymentRequest->setURLSuccess($order->get_checkout_order_received_url());
        $paymentRequest->setURLFailure($this->refused_url);
        $paymentRequest->setURLNotify(site_url('/?wc-api=' . get_class($this)));
        $paymentRequest->setURLBack($this->cancel_url);
        $paymentRequest->setReponse('encrypt');
        $paymentRequest->setLanguage($this->functions->get_language($this->language, $this->detect_language));
        if ($this->merchant_id == 'BNP_DEMO_AXEPTA'){
          $description = 'Test:0000';
        }else{
          $description = substr(str_replace("\n", " ", $order->get_customer_note()), 0, 255);
          if (empty($description)) $description = $order->get_id();
        }
        $paymentRequest->setOrderDesc($description);
        $paymentRequest->validate();
        $mac = $paymentRequest->getShaSign() ;
        $data = $paymentRequest->getBfishCrypt();
        $len = $paymentRequest->getLen();
        $params = array(
          'MerchantID' => $this->merchant_id,
          'Len' => $len,
          'Data' => $data,
          'URLBack' => $paymentRequest->getURLBack(),
          'MsgVer' => '2.0', // @see https://docs.axepta.bnpparibas/display/DOCBNP/Evoluer+vers+le+3DSV2
        );
        $params = apply_filters('wcmpg_receipt_params', $params);
        error_log("WCMPG_AXEPTA DEBUG : request = " . print_r($paymentRequest->toArray(), true));
        error_log("WCMPG_AXEPTA DEBUG : request params = " . print_r($params, true));
        $form_output = '<p>'.__('Thank you for your order. You will be redirected on Axepta plateform.' , 'wcmpg' ). '</br>'. __('If nothing happens please click on the "Axepta" button below.', 'wcmpg') . '</p>';
        $form_output.= '<form method="post" action="' . $paymentRequest->getUrl() . '" name="Axepta">';
        foreach($params as $key => $value){
          $form_output.= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
        }
        $form_output.= '<input type="submit" value="Axepta">';
        $form_output.= '</form>';
        $form_output.= '<script> document.forms["Axepta"].submit(); </script>';
        echo $form_output ;
    } // end receipt()

    /**
     * Payment API
     */
    public function api() {
        $paymentResponse = new Axepta($this->hmac_key);
        $paymentResponse->setCryptKey($this->secret_key);
        $paymentResponse->setResponse($_GET);
        error_log("WCMPG_AXEPTA DEBUG : response = " . print_r($paymentResponse->toArray(), true));
        $transId = $paymentResponse->getTransID();
        $orderId = substr($transId, 0, strpos($transId, '-'));
        if (empty($orderId)) return new WP_Error('missing orderId', 'missing orderId', array('status' => 403));
        $order = new WC_Order($orderId);

        if ($paymentResponse->isValid() && $paymentResponse->isSuccessful()) {
          $order->add_order_note(__('Payment accepted', 'wcmpg'));
          $order->payment_complete($paymentResponse->getPayID());
        }else{
          $message = __('Payment refused', 'wcmpg');
          $message.= ' (' . $paymentResponse->getCode() . ' : ';
          if (isset($this->_errors[$paymentResponse->getCode()])){
            $message.= __($this->_errors[$paymentResponse->getCode()], 'wcmpg');
          }else{
            $message.= __('Unknown error', 'wcmpg');
          }
          $message.= ')';
          $order->add_order_note($message);
        }
    }
} //end WCMPG_Axepta

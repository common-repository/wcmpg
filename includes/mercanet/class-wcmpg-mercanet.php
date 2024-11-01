<?php
/**
 * Mercanet Payment Gateway.
 *
 * @since      1.0.0
 * @package    WCMPG
 * @subpackage WCMPG/includes
 * @author     Service technique IRCF <technique@ircf.fr>


/*
 * WCMPG_Mercanet
 */
class WCMPG_Mercanet extends WCMPG_Base {

    // from https://documentation.mercanet.bnpparibas.net/index.php?title=Dico_des_donn%C3%A9es#responseCode
    private $_errors = array(
      '00' => 'Transaction acceptée',
      '02' => 'Demande d\'autorisation par téléphone à la banque à cause d\'un dépassement du plafond d\'autorisation sur la carte, si vous êtes autorisé à forcer les transactions',
      '03' => 'Contrat commerçant invalide',
      '05' => 'Autorisation refusée',
      '11' => 'Utilisé dans le cas d\'un contrôle différé. Le PAN est en opposition',
      '12' => 'Transaction invalide, vérifier les paramètres transférés dans la requête',
      '14' => 'Coordonnées du moyen de paiement invalides (ex: n° de carte ou cryptogramme visuel de la carte) ou vérification AVS échouée',
      '17' => 'Annulation de l\'acheteur',
      '30' => 'Erreur de format',
      '34' => 'Suspicion de fraude (seal erroné)',
      '54' => 'Date de validité du moyen de paiement dépassée',
      '75' => 'Nombre de tentatives de saisie des coordonnées du moyen de paiement sous Sips Paypage dépassé',
      '90' => 'Service temporairement indisponible',
      '94' => 'Transaction dupliquée : le transactionReference de la transaction est déjà utilisé',
      '97' => 'Délai expiré, transaction refusée',
      '99' => 'Problème temporaire du serveur de paiement.',
    );
    
    /**
    * Constructor
    */
    function __construct() {
        parent::__construct();

        $this->id = 'wcmpg_mercanet';
        $this->icon = '"width=50px"';
        $this->order_button_text  = __( 'Proceed to Mercanet', 'wcmpg' );
        $this->has_fields = false;
        $this->method_title = 'WCMPG Mercanet';
        $this->method_description = __( 'Mercanet is the payment solution by BNP-Paribas. This payment mode will be removed in a future version of WCMPG. Please consider using Sips Paysage Post instead.' , 'wcmpg' );

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
            $this->title = 'Mercanet (Pro version)';
            $this->method_title = 'WCMPG Mercanet (Pro)';
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
            echo '<img src="'.plugins_url().'/wcmpg/assets/mercanet.png" alt="Mercanet" width="250">';
            echo '<h3> Mercanet </h3>';
            echo '<p>' . __( 'Mercanet is a secure payment gateway powered by the french bank BNP Paribas.' , 'wcmpg'  ) . '</p>';
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
            'site_id'         => array(
                'title'       => __('Site ID', 'wcmpg'),
                'type'        => 'text',
                'default'     => '002001000000001',
                'description' => __( 'The identifier provided by your payment gateway.', 'wcmpg'),
            ),
            'mercanet_secret_key' => array(
                'title'       => __('Secret key', 'wcmpg'),
                'type'        => 'text',
                'description' => __('The secret key provided by Mercanet.', 'wcmpg'),
                'default'     => '002001000000001_KEY1'
            ),
            'mercanet_key_version' => array(
                'title'       => __('Key version', 'wcmpg'),
                'type'        => 'text',
                'description' => __('The key version provided by Mercanet.', 'wcmpg'),
                'default'     => '1'
            ),
            'mercanet_mode'   => array(
                'title'       => __( 'Mode', 'wcmpg'),
                'type'        => 'select',
                'description' => __( 'Choose Testing to test the payment gateway.', 'wcmpg'),
                'default'     => 'production',
                'class'       => 'wc-enhanced-select',
                'options'     => array(
                    'production'   => __( 'Production', 'wcmpg'),
                    'testing'      => __( 'Testing', 'wcmpg'),
                    'simulation'   => __( 'Simulation', 'wcmpg'),
                )
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
     * Get the Mercanet form.
     */
    function receipt($order_id) {
        $order = new WC_Order($order_id);

        if ($this->mercanet_mode == 'production'){
            $url = 'https://payment-webinit.mercanet.bnpparibas.net/paymentInit';
        } elseif ($this->mercanet_mode == 'testing') {
            $url = 'https://payment-webinit-mercanet.test.sips-atos.com/paymentInit';
        } else {
            $url = 'https://payment-webinit.simu.mercanet.bnpparibas.net/paymentInit';
        }

        $params = array(
          'amount' => $order->get_total()*100,
          'currencyCode' => $this->currency,
          'merchantId' => $this->site_id,
          'normalReturnUrl' => $order->get_checkout_order_received_url(),
          'automaticResponseUrl' => site_url('/?wc-api=' . get_class($this)),
          'transactionReference' => uniqid(),
          'keyVersion' => $this->mercanet_key_version,
          'orderId' => $order->get_id(),
          'customerLanguage' => $this->functions->get_language($this->language, $this->detect_language),
        );
        $params = apply_filters('wcmpg_receipt_params', $params);
        $Data = urldecode(http_build_query($params, '', '|'));
        $Seal = hash('sha256', $Data.$this->mercanet_secret_key);
        error_log("WCMPG_MERCANET DEBUG : request Data = $Data");
        error_log("WCMPG_MERCANET DEBUG : request Seal = $Seal");

        $form_output = '<p>'.__('Thank you for your order. You will be redirected on Mercanet plateform.' , 'wcmpg' ). '</br>'. __('If nothing happens please click on the "Mercanet" button below.', 'wcmpg') . '</p>';
        $form_output .= '<form method="post" action="' . $url . '" name="Mercanet">
                            <input type="hidden" name="Data" value="' . $Data . '">
                            <input type="hidden" name="InterfaceVersion" value="HP_2.9">
                            <input type="hidden" name="Seal" value="' . $Seal . '">
                            <input type="submit" value="Mercanet">
                        </form>';
        //script
        $form_output .= '<script> document.forms["Mercanet"].submit(); </script>';

        echo $form_output ;
    } // end receipt()

    /**
     * Payment API
     */
    public function api() {
        if (!isset($_REQUEST['Data'])) return new WP_Error('missing data', 'missing data', array('status' => 403));
        error_log("WCMPG_MERCANET DEBUG : response Data = {$_REQUEST['Data']}");
        $params = $this->get_params();
        error_log("WCMPG_MERCANET DEBUG : response params = ".print_r($params, true));
        if (empty($params['orderId'])) return new WP_Error('missing orderId', 'missing orderId', array('status' => 403));
        $order = new WC_Order($params['orderId']);
        
        $seal = hash('sha256', $_REQUEST['Data'] . $this->mercanet_secret_key);
        error_log("WCMPG_MERCANET DEBUG : response Seal = {$_REQUEST['Seal']}");
        error_log("WCMPG_MERCANET DEBUG : response expected Seal = $seal");
        if ($_REQUEST['Seal'] != $seal){
          return new WP_Error('invalid seal', __('Invalid payment signature', 'wcmpg'), array('status' => 403));
        }
        
        if ($params['responseCode'] == '00'){
          $order->add_order_note(__('Payment accepted', 'wcmpg'));
          $order->payment_complete($params['transactionReference']);
        }else{
          $message = __('Payment refused', 'wcmpg');
          $message.= ' (' . $params['responseCode'] . ' : ';
          if (isset($this->_errors[$params['responseCode']])){
            $message.= __($this->_errors[$params['responseCode']], 'wcmpg');
          }else{
            $message.= __('Unknown error', 'wcmpg');
          }
          $message.= ')';
          $order->add_order_note($message);
        }
    }
    
    /**
     * Get params
     */
    private function get_params(){
      $datas = explode('|', $_REQUEST['Data']);
      $result = array();
      foreach ($datas as $data) {
        preg_match('/(.*)=(.*)/', $data, $matches);
        $result[$matches[1]] = $matches[2];
      }
      return $result;
    }
    
} //end WCMPG_Mercanet

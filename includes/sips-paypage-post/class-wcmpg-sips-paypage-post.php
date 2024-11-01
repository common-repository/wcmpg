<?php
/**
 * Sips Paypage Post Payment Gateway.
 *
 * @since      1.0.0
 * @package    WCMPG
 * @subpackage WCMPG/includes
 * @author     Service technique IRCF <technique@ircf.fr>


/*
 * WCMPG_Sips_Paypage_Post
 */
class WCMPG_Sips_Paypage_Post extends WCMPG_Base {

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

    private $_urls = array(
      'mercanet' => array(
        'production' => 'https://payment-webinit.mercanet.bnpparibas.net/paymentInit',
        'testing'    => 'https://payment-webinit-mercanet.test.sips-services.com/paymentInit',
        'simulation' => 'https://payment-webinit.simu.mercanet.bnpparibas.net/paymentInit',
      ),
      'sherlocks' => array(
        'production' => 'https://sherlocks-payment-webinit.secure.lcl.fr/paymentInit',
        'testing'    => 'https://payment-webinit.test.sips-services.com/paymentInit',
        'simulation' => 'https://sherlocks-payment-webinit-simu.secure.lcl.fr/paymentInit',
      ),
      'scellius' => array(
        'production' => 'https://payment-webinit.sips-services.com/paymentInit',
        'testing'    => 'https://payment-webinit.test.sips-services.com/paymentInit',
        'simulation' => 'https://payment-webinit.simu.sips-services.com/paymentInit',
      ),
      'sogenactif' => array(
        'production' => 'https://payment-webinit.sips-services.com/paymentInit',
        'testing'    => 'https://payment-webinit.test.sips-services.com/paymentInit',
        'simulation' => 'https://payment-webinit.simu.sips-services.com/paymentInit',
      ),
    );
    
    /**
    * Constructor
    */
    function __construct() {
        parent::__construct();

        $this->id = 'wcmpg_sips_paypage_post';
        $this->icon = '"width=50px"';
        $this->order_button_text  = __( 'Proceed to Sips Paypage Post', 'wcmpg' );
        $this->has_fields = false;
        $this->method_title = 'WCMPG Sips Paypage Post';
        $this->method_description = __( 'Sips Paypage Post, also called Sips 2.0, is the new version of Sips 1.0, used by Sherlocks by LCL, Mercanet by BNP-Paribas, Scellius by La Banque Postale or Sogenactif by Société Générale.' , 'wcmpg' );

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
            $this->title = 'Sips Paypage Post (Pro version)';
            $this->method_title = 'WCMPG Sips Paypage Post (Pro)';
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
            echo '<img src="'.plugins_url().'/wcmpg/assets/sips_paypage_post.png" alt="Sips Paypage Post" width="250">';
            echo '<h3> Sips Paypage Post </h3>';
            echo '<p>' . __( 'Sips Paypage Post is a secure payment gateway powered by ATOS.' , 'wcmpg'  ) . '</p>';
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
            'secret_key' => array(
                'title'       => __('Secret key', 'wcmpg'),
                'type'        => 'text',
                'description' => __('The secret key provided by Sips Paypage Post.', 'wcmpg'),
                'default'     => '002001000000001_KEY1'
            ),
            'key_version' => array(
                'title'       => __('Key version', 'wcmpg'),
                'type'        => 'text',
                'description' => __('The key version provided by Sips Paypage Post.', 'wcmpg'),
                'default'     => '1'
            ),
            'bank' => array(
                'title'       => __( 'Bank', 'wcmpg'),
                'type'        => 'select',
                'description' => __( 'Your bank.', 'wcmpg'),
                'default'     => 'mercanet',
                'class'       => 'wc-enhanced-select',
                'options'     => array(
                    'mercanet'   => __( 'Mercanet by BNP-Paribas', 'wcmpg'),
                    'sherlocks'  => __( 'Sherlocks by LCL', 'wcmpg'),
                    'scellius'   => __( 'Scellius by La Banque Postale', 'wcmpg'),
                    'sogenactif' => __( 'Sogenactif by Société Générale', 'wcmpg'),
                )
            ),
            'mode'   => array(
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
            'interface_version'   => array(
                'title'       => __( 'Interface version', 'wcmpg'),
                'type'        => 'text',
                'description' => __( 'Enter the version number given in the Sips Paypage Post documentation.', 'wcmpg'),
                'default'     => 'HP_2.37',
            ),
            'send_transaction_reference' => array(
                'title'   => __( 'Send transaction reference ?', 'wcmpg' ),
                'type'    => 'checkbox',
                'label'   => __( 'Should WCMPG send a unique transaction reference to the bank server ? If you have an error try switching this parameter on/off', 'wcmpg' ),
                'default' => 'yes'
            ),
            'migration_mode' => array(
                'title'   => __( 'Enable migration mode', 'wcmpg' ),
                'type'    => 'checkbox',
                'label'   => __( 'You should enable this is your bank uses the v1.0 mode. If you have an error try switching this parameter on/off', 'wcmpg' ),
                'default' => 'no'
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
     * Get the Sips Paypage Post form.
     */
    function receipt($order_id) {
        $order = new WC_Order($order_id);
        if (!isset($this->_urls[$this->bank])){
          error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : bank = {$this->bank}");
          throw new Exception(__('invalid bank', 'wcmpg'));
        }
        if (!isset($this->_urls[$this->bank][$this->mode])){
          error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : mode = {$this->mode}");
          throw new Exception(__('invalid mode', 'wcmpg'));
        }
        $url = $this->_urls[$this->bank][$this->mode];
        error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : url = $url");
        $params = array(
          'amount' => $order->get_total()*100,
          'currencyCode' => $this->currency,
          'merchantId' => $this->site_id,
          'normalReturnUrl' => $order->get_checkout_order_received_url(),
          'automaticResponseUrl' => site_url('/?wc-api=' . get_class($this)),
          'keyVersion' => $this->key_version,
          'orderId' => $order->get_id(),
          'customerLanguage' => $this->functions->get_language($this->language, $this->detect_language),
          'orderChannel' => 'INTERNET',
        );

        if ($this->migration_mode == 'yes'){
          $params['s10TransactionReference.s10TransactionId'] = substr(microtime(), 2, 6);
        }else if ($this->send_transaction_reference == 'yes'){
          $params['transactionReference'] = uniqid();
        }
        $params = apply_filters('wcmpg_receipt_params', $params);
        $Data = urldecode(http_build_query($params, '', '|'));
        $Seal = hash('sha256', $Data.$this->secret_key);
        error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : request Data = $Data");
        error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : request Seal = $Seal");

        $form_output = '<p>'.__('Thank you for your order. You will be redirected on Sips Paypage Post plateform.' , 'wcmpg' ). '</br>'. __('If nothing happens please click on the "Sips Paypage Post" button below.', 'wcmpg') . '</p>';
        $form_output .= '<form method="post" action="' . $url . '" name="SipsPaypagePost">
                            <input type="hidden" name="Data" value="' . $Data . '">
                            <input type="hidden" name="InterfaceVersion" value="'.$this->interface_version.'">
                            <input type="hidden" name="Seal" value="' . $Seal . '">
                            <input type="submit" value="Sips Paypage Post">
                        </form>';
        //script
        $form_output .= '<script> document.forms["SipsPaypagePost"].submit(); </script>';

        echo $form_output ;
    } // end receipt()

    /**
     * Payment API
     */
    public function api() {
        if (!isset($_REQUEST['Data'])) return new WP_Error('missing data', 'missing data', array('status' => 403));
        error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : response Data = {$_REQUEST['Data']}");
        $params = $this->get_params();
        error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : response params = ".print_r($params, true));
        if (empty($params['orderId'])) return new WP_Error('missing orderId', 'missing orderId', array('status' => 403));
        $order = new WC_Order($params['orderId']);
        
        $seal = hash('sha256', $_REQUEST['Data'] . $this->secret_key);
        error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : response Seal = {$_REQUEST['Seal']}");
        error_log("WCMPG_SIPS_PAYPAGE_POST DEBUG : response expected Seal = $seal");
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
    
} //end WCMPG_Sips_Paypage_Post

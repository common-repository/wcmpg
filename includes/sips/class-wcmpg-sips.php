<?php
/**
 * Sips Payment Gateway.
 *
 * @since      1.0.0
 * @package    WCMPG
 * @subpackage WCMPG/includes
 * @author     Service technique IRCF <technique@ircf.fr>


/*
 * WCMPG_Sips
 */
class WCMPG_Sips extends WCMPG_Base {

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

        $this->id = 'wcmpg_sips';
        $this->icon = '"width=50px"';
        $this->order_button_text  = __( 'Proceed to Sips', 'wcmpg' );
        $this->has_fields = false;
        $this->method_title = 'WCMPG Sips';
        $this->method_description = __( 'France based ATOS Worldline SIPS is the leading secure payment solution in Europe. Atos works by sending the user to your bank to enter their payment information.' , 'wcmpg' );
        $this->flash = array('error' => null, 'success' => null);

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
            $this->title = 'Sips (Pro version)';
            $this->method_title = 'WCMPG Sips (Pro)';
        }

        if ($this->functions->is_empty_directory($this->get_sips_upload_dir())){
          $this->flash['error'] = $this->no_folder_detected();
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
    public function process_admin_options() {
        parent::process_admin_options();
        if(isset($_FILES["upload"]) && isset($_FILES["upload"]["name"]) && !empty($_FILES["upload"]["name"])) {
          // Upload SIPS kit
          $this->flash = $this->functions->upload_zip($this->get_sips_upload_dir());
          // Set execution permissions
          if (file_exists($this->sips_path_bin_request)){
            chmod($this->sips_path_bin_request, '755');
          }
          if (file_exists($this->sips_path_bin_response)){
            chmod($this->sips_path_bin_response, '755');
          }
          // update pathfile
          if (file_exists($this->sips_pathfile)){
            $pathfile_content = file_get_contents($this->sips_pathfile);
            $pathfile_content = preg_replace("/D_LOGO!.*logo/", "D_LOGO!".$this->get_sips_upload_uri()."logo", $pathfile_content);
            $pathfile_content = preg_replace("/F_DEFAULT!.*parmcom/", "F_DEFAULT!".$this->get_sips_upload_dir()."param/parmcom", $pathfile_content);
            $pathfile_content = preg_replace("/F_PARAM!.*parmcom/", "F_PARAM!".$this->get_sips_upload_dir()."param/parmcom", $pathfile_content);
            $pathfile_content = preg_replace("/F_CERTIFICATE!.*certif/", "F_CERTIFICATE!".$this->get_sips_upload_dir()."param/certif", $pathfile_content);
            file_put_contents($this->sips_pathfile, $pathfile_content);
          }
        }
    }
    
    public function admin_options() {
        if ($this->functions->enable_payment_gateways()==true) {
            if (!empty($this->flash['error'])){
              echo '<div class="notice notice-error">' . $this->flash['error'] . '</div>';
            }elseif (!empty($this->flash['success'])){
              echo '<div class="notice notice-success">' . $this->flash['success'] . '</div>';
            }
            echo '<img src="'.plugins_url().'/wcmpg/assets/sips.png" alt="ATOS SIPS" width="250">';
            echo '<h3> Atos Sips </h3>';
            echo $this->method_description;
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo'</table>';
            echo '<hr>';
            echo '<h2>' . __('Please send the following information to your bank support', 'wcmpg') . '</h2>';
            echo '<p>' . __('Automatic Return Link', 'wcmpg') . ' : ' . site_url('/?wc-api=' . get_class($this)) . '</strong></p>';
            echo $this->upload_admin();
            echo $this->test_api_admin();
            echo '<hr />';
        }else{
            $current_url = explode("?", $_SERVER['REQUEST_URI']);
            $url = $current_url[0] .'?page=wc-settings&tab=checkout&section=wcmpg_licence';
            header('Location:'.$url);
        }
    }

    function no_folder_detected(){
        $message = '<div class="notice notice-error">';
        $message.= '<p>' . __('The SIPS kit is not installed. You have to upload the kit given by your bank to enable this payment gateway.' , 'wcmpg' ) . '</p>';
        $message.= '<p> <a class="button" href="#woocommerce_wcmpg_sips_upload">' . __('Install SIPS kit', 'wcmpg') . '</a> </p> <br/>';
        $message.= '</div>';
        return $message;
    }

    function upload_admin(){
        $result = '<a name="woocommerce_wcmpg_sips_upload" id="woocommerce_wcmpg_sips_upload"></a>';
        if ($this->functions->is_empty_directory($this->get_sips_upload_dir())==false){
          $result.= '<hr><h3>' . __('Reinstall SIPS kit', 'wcmpg') . '</h3>';
          $result.= '<p>'. __('SIPS kit is already installed. You can reinstall it if needed but it\'s not recommended.', 'wcmpg') . '</p>';
        }else{
          $result.= '<hr><h3>' . __('Install SIPS kit', 'wcmpg') . '</h3>';
          $result.= '<p>'. __('SIPS kit is not installed.', 'wcmpg') .'</p>';
        }
        $result.= '<p>'. __('The SIPS kit can be downloaded from your bank back-office. Then upload it on your website using the button below. The kit version depends on your server, most of the time it will be "Linux 64 bits PHP version".', 'wcmpg') .'</p>';
        $result.= '<p> <label>' . __('Choose a compressed folder to upload (.zip / .tar) : ' , 'wcmpg' ) .'<input type="file" id="upload" name="upload" onchange="is_disabled()"/></label>';
        return $result;
    }

    function test_api_admin(){
        $result = '';
        if ($this->functions->is_empty_directory($this->get_sips_upload_dir())==false){
            $result .= '<hr><h3>' . __('Test your SIPS install', 'wcmpg') . '</h3>';
            $result .= '<p> <label>'.__('Click on the button to test your Atos Sips API (save your changes before) ' , 'wcmpg' ). '</label> ';
            $result .= '<button class="button-primary" type="button" onclick="wcmpg_sips_test()">Test</button> </p>';
            $result .= '<div id="wcmpg_sips_test_result"></div>';
            ?>
            <script>
                function wcmpg_sips_test(){
                  jQuery('#wcmpg_sips_test_result').load('<?=admin_url( 'admin-ajax.php?action=wcmpg_sips_test' )?>');
                }
            </script>
            <?php
            $result .= '<p>' . __('Once the test is successful you have to pass an order with a real bank card while your certificate is in pre-production and then ask your bank to pass it in production.' , 'wcmpg' ). '</p>';
            $result .= '<p><strong>' . __('IMPORTANT : While your certificate is in pre-production WooCommerce won\'t receive any payment notification.' , 'wcmpg' ). '</strong></p>';
        }
        return $result;
    }
    
    function test_ajax(){
      echo $this->functions->executable();
      wp_die();
    }

    function get_sips_upload_dir(){
      return  wcmpg_upload_dir() . 'sips/';
    }

    function get_sips_upload_url(){
      return  wcmpg_upload_url() . 'sips/';
    }

    function get_sips_upload_uri(){
       return parse_url($this->get_sips_upload_url(), PHP_URL_PATH);
    }

    /*
    * Initialize Gateway Settings Form Fields.
    */
    function init_form_fields() {
        $this->form_fields  = include __DIR__.'/../../admin/settings.php';
        unset($this->form_fields['success_url']);
        unset($this->form_fields['refused_url']);
        unset($this->form_fields['cancel_url']);
        $this->form_fields += array(
            'site_id'         => array(
                'title'       => __('Site ID', 'wcmpg'),
                'type'        => 'text',
                'default'     => '029800266211111',
                'description' => __( 'The identifier provided by your payment gateway.', 'wcmpg'),
            ),
            'sips_pathfile'   => array(
                'title'       => __( 'Pathfile file', 'wcmpg' ),
                'type'        => 'text',
                'description' => __( 'Path to the pathfile file given by your bank.', 'wcmpg' ),
                'default'     => $this->get_sips_upload_dir() . 'param/pathfile'
            ),
            'sips_path_bin_request'       => array(
                'title'       => __( 'Request bin file path', 'wcmpg' ),
                'type'        => 'text',
                'description' => __( 'Path to the request bin file given by your bank.', 'wcmpg' ),
                'default'     => $this->get_sips_upload_dir() . 'bin/static/request'
            ),
            'sips_path_bin_response'      => array(
                'title'       => __( 'Response bin file path', 'wcmpg' ),
                'type'        => 'text',
                'description' => __( 'Path to the response bin file given by your bank.', 'wcmpg' ),
                'default'     => $this->get_sips_upload_dir() . 'bin/static/response'
            ),
            'sips_image_left' => array(
                'title'       => __( 'Left image', 'wcmpg' ),
                'type'        => 'text',
                'description' => __( 'Left image on Sips paiement page.', 'wcmpg' ),
                'default'     => 'left.png'
            ),
            'sips_image_right'=> array(
                'title'       => __( 'Right image', 'wcmpg' ),
                'type'        => 'text',
                'description' => __( 'Right image Sips paiement page.', 'wcmpg' ),
                'default'     => 'right.png'
            ),
            'sips_image_center' => array(
                'title'       => __( 'Center image', 'wcmpg' ),
                'type'        => 'text',
                'description' => __( 'Center image Sips paiement page.', 'wcmpg' ),
                'default'     => 'center.png'
            ),
            /*'sips_mode'       => array(
                'title'       => __( 'Mode', 'wcmpg'),
                'type'        => 'select',
                'description' => __( 'Choose Testing to test the payment gateway.', 'wcmpg'),
                'default'     => 'production',
                'class'       => 'wc-enhanced-select',
                'options'     => array(
                    'production'   => __( 'production', 'wcmpg'),
                    'testing'      => __( 'testing', 'wcmpg')
                )
            ),*/
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
    * Appel le cgiRequest du système de paiement, 
    * récupère les paramètres en sortie et retourne soit un message d'erreur, 
    * soit le formulaire de CB
    */
    public function receipt( $order_id ) {

        $order = new WC_order( $order_id );
        $pathfile = $this->sips_pathfile;
        $path_bin_request = $this->sips_path_bin_request;
        $params = array(
          'pathfile' => $pathfile,
          'merchant_id' => $this->site_id,
          'merchant_country' => 'fr',
          'amount' => $order->get_total()*100,
          'currency_code' => $this->currency,
          'normal_return_url' => escapeshellcmd($order->get_checkout_order_received_url()),
          'cancel_return_url' => escapeshellcmd($order->get_cancel_order_url()),
          'automatic_response_url' => escapeshellcmd(site_url('/?wc-api=' . get_class($this))),
          'language' => $this->functions->get_language($this->language, $this->detect_language),
          'payment_means' => 'CB,2,VISA,2,MASTERCARD,2',
          'header_flag' => 'no',
          'order_id' => $order->get_id(),
          'logo_id' => $this->sips_image_left,
          'logo_id2' => $this->sips_image_right,
          'advert' => $this->sips_image_center,
        );
        // SIPS does not hangle language param correctly, so we have to override the pathfile
        // NB : Customer will have to create manually a pathfile/parmcom for each language.
        $language_pathfile = str_replace('pathfile', 'pathfile.' . $params['language'], $params['pathfile']);
        if (file_exists($language_pathfile)) $params['pathfile'] = $language_pathfile;
        $params = apply_filters('wcmpg_receipt_params', $params);
        $sips_param = urldecode(http_build_query($params, '', ' '));

        $cmd = "$path_bin_request $sips_param";
        error_log("WCMPG_SIPS DEBUG : request cmd = $cmd");
        $result = exec( $cmd );
        error_log("WCMPG_SIPS DEBUG : request result = $result");
        $tableau = explode( '!', $result );

        $code  = $tableau[1];
        $error = $tableau[2];

        if (( $code == '' ) && ( $error == '' )) {
            echo __( 'Error : request not found', 'wcmpg' );
        } elseif ($code != 0) {
            echo sprintf(__( 'Error : ', 'wcmpg' )), $error;
        } else {
            echo __( 'Thank you for your order, please click the button below to pay.', 'wcmpg' );
            echo $tableau[3];
        }

    } // end receipt()
    
    /**
     * Payment API
     */
    public function api() {
        if (!isset($_POST['DATA'])) return new WP_Error('missing data', 'missing data', array('status' => 403));
        error_log("WCMPG_SIPS DEBUG : response data = {$_POST['DATA']}");
        $message = escapeshellcmd($_POST['DATA']);
        $cmd = "{$this->sips_path_bin_response} pathfile={$this->sips_pathfile} message=$message";
        error_log("WCMPG_SIPS DEBUG : response cmd = $cmd");
        $result = exec( $cmd );
        error_log("WCMPG_SIPS DEBUG : response result = $result");
        $params = $this->get_params($result);
        $order_id = $params['order_id'];
        if (empty($order_id)) return new WP_Error('missing order_id', 'missing order_id', array('status' => 403));

        $order = new WC_Order($order_id);
        
        if ($params['response_code'] == '00'){
          $order->add_order_note(__('Payment accepted', 'wcmpg'));
          $order->payment_complete($params['transaction_id']);
        }else{
          $message = __('Payment refused', 'wcmpg');
          $message.= ' (' . $params['response_code'] . ' : ';
          if (isset($this->_errors[$params['response_code']])){
            $message.= __($this->_errors[$params['response_code']], 'wcmpg');
          }else{
            $message.= __('Unknown error', 'wcmpg');
          }
          $message.= ')';
          $order->add_order_note($message);
        }
        die();
    }
    
    /**
     * Get params
     */
    private function get_params($response){
      $datas = explode('!', $response);
      $result = array(
        'code' => $datas[1],
        'error' => $datas[2],
        'merchant_id' => $datas[3],
        'merchant_country' => $datas[4],
        'amount' => $datas[5],
        'transaction_id' => $datas[6],
        'payment_means' => $datas[7],
        'transmission_date' => $datas[8],
        'payment_time' => $datas[9],
        'payment_date' => $datas[10],
        'response_code' => $datas[11],
        'payment_certificate' => $datas[12],
        'authorisation_id' => $datas[13],
        'currency_code' => $datas[14],
        'card_number' => $datas[15],
        'cvv_flag' => $datas[16],
        'cvv_response_code' => $datas[17],
        'bank_response_code' => $datas[18],
        'complementary_code' => $datas[19],
        'complementary_info' => $datas[20],
        'return_context' => $datas[21],
        'caddie' => $datas[22],
        'receipt_complement' => $datas[23],
        'merchant_language' => $datas[24],
        'language' => $datas[25],
        'customer_id' => $datas[26],
        'order_id' => $datas[27],
        'customer_email' => $datas[28],
        'customer_ip_address' => $datas[29],
        'capture_day' => $datas[30],
        'capture_mode' => $datas[31],
        'data' => $datas[32],
      );
      return $result;
    }
    
} // end WCMPG_Sips

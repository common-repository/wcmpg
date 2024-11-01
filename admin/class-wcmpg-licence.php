<?php
/**
 * WCMPG_Licence
 *
 * @since      1.0.0
 * @package    WCMPG
 * @subpackage WCMPG/includes
 * @author     Service technique IRCF <technique@ircf.fr>
 */
class WCMPG_Licence extends WC_Payment_Gateway {

    private $error_message;
    private $success_message;
    private $functions;
    private $wcmpg_licence;

    /**
    * Constructor
    */
    function __construct() {
        $this->id = 'wcmpg_licence';
        $this->method_title = 'WCMPG Licence';
        $this->title = __( 'Register your licence', 'wcmpg');
        $this->error_message = null;
        $this->success_message = null;
        $this->method_description = __( 'Configure your WCMPG pro license. This is not a real payment mode. You should keep it disabled.' , 'wcmpg' );

        // Load the form fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        
        // Recovers the necessary functions
        include_once __DIR__.'/../includes/functions.php';
        $this->functions = new WCMPG_Functions();

        // Get setting values
        foreach ($this->settings as $key => $values){
            $this->$key = $values;
        }

        if ($this->wcmpg_licence != null && rand(0, 1000) == 0 ){
            $this->verify_licence();
        }

        // Hooks
        add_option('wcmpg_licence_index', 0);
        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    } //end __contruct()

    /**
    * Admin tools
    */
    public function admin_options() {
        echo '<img src="'.plugins_url().'/wcmpg/assets/wcmpg.png" alt="WCMPG" width="250">';
        echo '<h3>' . __( 'Register your licence', 'wcmpg') . '</h3>';
        if ($this->functions->enable_payment_gateways()==false) {
            echo '<p>' . __( 'You currently use the free version, <a href="https://ircf.fr/plugins-wordpress/" target="_blank">click here to purchase the pro version</a>.' , 'wcmpg' ) . '</p>';
        }
        else{
            echo '<p>' . __( 'You currently use the pro version, <a  href="https://ircf.fr/plugins-wordpress/" target="_blank">click here to get more information</a>.' , 'wcmpg' ) . '</p>';
        }
        echo '<p>' . __( 'You can contact our technical support by phone at <a href="tel:+33553467179">+33 5 53 46 71 79</a> or by e-mail at <a href="mailto:technique@ircf.fr">technique@ircf.fr</a>', 'wcmpg') . '</p>';

        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function process_admin_options(){
        parent::process_admin_options();
        $this->verify_licence();
    }

    /**
    * Initialize Gateway Settings Form Fields.
    */
    public function init_form_fields() {
        $this->form_fields = array(
            'wcmpg_licence'         => array(
                'title'       => __('Enter your license number', 'wcmpg'),
                'type'        => 'text',
                'default'     => '',
                'description' => __( 'The license number is provided when you buy the pro version.', 'wcmpg'),
            ),
        ); 
    } //end init_form_fields()

    /**
    * Check if the licence filled in is correct or not
    */
    function verify_licence(){

        $api_params = array(
            'slm_action'        => 'slm_activate',
            'secret_key'        => '623p9e73mas6825twqgy',
            'license_key'       => $this->wcmpg_licence,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference'    => urlencode('WCMPG'),
        );
        $query = esc_url_raw(add_query_arg($api_params, 'https://ircf.fr'));

        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response)){
            $this->error_message = __('Unexpected Error! The query returned with an error.' , 'wcmpg' );
        }

        $license_data = json_decode(wp_remote_retrieve_body($response));

        if($license_data->result == 'success'){
            $this->success_message = $license_data->message;
            if (get_option('wcmpg_licence_key') == false){
                add_option( 'wcmpg_licence_key', $this->wcmpg_licence );
                update_option( 'wcmpg_licence_key', $this->wcmpg_licence );
            }
        } else {
            $this->error_message = $license_data->message;
        }
    
        if(!strstr($license_data->message, "License key already in use")&& get_option('wcmpg_licence_key')!= false ){
            if (isset($this->error_message)) echo '<div class="notice notice-warning"> <p><strong>'.$this->error_message.'</strong></p></div>';
            if (isset($this->success_message)) echo '<div class="notice notice-warning"> <p><strong>'.$this->success_message.'</strong></p></div>';
        }
        
    }
}

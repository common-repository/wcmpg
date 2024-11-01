<?php
/**
 * Paybox Payment Gateway.
 *
 * @since      1.0.0
 * @package    WCMPG
 * @subpackage WCMPG/includes
 * @author     Service technique IRCF <technique@ircf.fr>


/*
 * WCMPG_Paybox
 */
class WCMPG_Paybox extends WCMPG_Base {

    private $binKey;

    // from https://wordpress.org/plugins/paybox-woocommerce-gateway/
    private $_params = array(
      'M' => 'amount',
      'R' => 'reference',
      'T' => 'transaction',
      //'A' => 'authorization',
      //'B' => 'subscription',
      //'C' => 'cardType',
      //'D' => 'validity',
      'E' => 'error',
      //'F' => '3ds',
      //'G' => '3dsWarranty',
      //'H' => 'imprint',
      //'I' => 'ip',
      //'J' => 'lastNumbers',
      //'N' => 'firstNumbers',
      //'O' => '3dsInlistment',
      //'o' => 'celetemType',
      //'P' => 'paymentType',
      'Q' => 'time',
      //'S' => 'call',
      //'U' => 'subscriptionData',
      //'W' => 'date',
      //'Y' => 'country',
      //'Z' => 'paymentIndex',
      'K' => 'sign',
    );

    // from https://wordpress.org/plugins/paybox-woocommerce-gateway/
    private $_errors = array(
      '00000' => 'Successful operation',
      '00001' => 'Payment system not available',
      '00003' => 'Paybor error',
      '00004' => 'Card number or invalid cryptogram',
      '00006' => 'Access denied or invalid identification',
      '00008' => 'Invalid validity date',
      '00009' => 'Subscription creation failed',
      '00010' => 'Unknown currency',
      '00011' => 'Invalid amount',
      '00015' => 'Payment already done',
      '00016' => 'Existing subscriber',
      '00021' => 'Unauthorized card',
      '00029' => 'Invalid card',
      '00030' => 'Timeout',
      '00033' => 'Unauthorized IP country',
      '00040' => 'No 3D Secure',
    );

    // ISO3166 country codes
    private $_countries = array(
        'AD' => '020',
        'AE' => '784',
        'AF' => '004',
        'AG' => '028',
        'AI' => '660',
        'AL' => '008',
        'AM' => '051',
        'AO' => '024',
        'AQ' => '010',
        'AR' => '032',
        'AS' => '016',
        'AT' => '040',
        'AU' => '036',
        'AW' => '533',
        'AX' => '248',
        'AZ' => '031',
        'BA' => '070',
        'BB' => '052',
        'BD' => '050',
        'BE' => '056',
        'BF' => '854',
        'BG' => '100',
        'BH' => '048',
        'BI' => '108',
        'BJ' => '204',
        'BL' => '652',
        'BM' => '060',
        'BN' => '096',
        'BO' => '068',
        'BQ' => '535',
        'BR' => '076',
        'BS' => '044',
        'BT' => '064',
        'BV' => '074',
        'BW' => '072',
        'BY' => '112',
        'BZ' => '084',
        'CA' => '124',
        'CC' => '166',
        'CD' => '180',
        'CF' => '140',
        'CG' => '178',
        'CH' => '756',
        'CI' => '384',
        'CK' => '184',
        'CL' => '152',
        'CM' => '120',
        'CN' => '156',
        'CO' => '170',
        'CR' => '188',
        'CU' => '192',
        'CV' => '132',
        'CW' => '531',
        'CX' => '162',
        'CY' => '196',
        'CZ' => '203',
        'DE' => '276',
        'DJ' => '262',
        'DK' => '208',
        'DM' => '212',
        'DO' => '214',
        'DZ' => '012',
        'EC' => '218',
        'EE' => '233',
        'EG' => '818',
        'EH' => '732',
        'ER' => '232',
        'ES' => '724',
        'ET' => '231',
        'FI' => '246',
        'FJ' => '242',
        'FK' => '238',
        'FM' => '583',
        'FO' => '234',
        'FR' => '250',
        'GA' => '266',
        'GB' => '826',
        'GD' => '308',
        'GE' => '268',
        'GF' => '254',
        'GG' => '831',
        'GH' => '288',
        'GI' => '292',
        'GL' => '304',
        'GM' => '270',
        'GN' => '324',
        'GP' => '312',
        'GQ' => '226',
        'GR' => '300',
        'GS' => '239',
        'GT' => '320',
        'GU' => '316',
        'GW' => '624',
        'GY' => '328',
        'HK' => '344',
        'HM' => '334',
        'HN' => '340',
        'HR' => '191',
        'HT' => '332',
        'HU' => '348',
        'ID' => '360',
        'IE' => '372',
        'IL' => '376',
        'IM' => '833',
        'IN' => '356',
        'IO' => '086',
        'IQ' => '368',
        'IR' => '364',
        'IS' => '352',
        'IT' => '380',
        'JE' => '832',
        'JM' => '388',
        'JO' => '400',
        'JP' => '392',
        'KE' => '404',
        'KG' => '417',
        'KH' => '116',
        'KI' => '296',
        'KM' => '174',
        'KN' => '659',
        'KP' => '408',
        'KR' => '410',
        'KW' => '414',
        'KY' => '136',
        'KZ' => '398',
        'LA' => '418',
        'LB' => '422',
        'LC' => '662',
        'LI' => '438',
        'LK' => '144',
        'LR' => '430',
        'LS' => '426',
        'LT' => '440',
        'LU' => '442',
        'LV' => '428',
        'LY' => '434',
        'MA' => '504',
        'MC' => '492',
        'MD' => '498',
        'ME' => '499',
        'MF' => '663',
        'MG' => '450',
        'MH' => '584',
        'MK' => '807',
        'ML' => '466',
        'MM' => '104',
        'MN' => '496',
        'MO' => '446',
        'MP' => '580',
        'MQ' => '474',
        'MR' => '478',
        'MS' => '500',
        'MT' => '470',
        'MU' => '480',
        'MV' => '462',
        'MW' => '454',
        'MX' => '484',
        'MY' => '458',
        'MZ' => '508',
        'NA' => '516',
        'NC' => '540',
        'NE' => '562',
        'NF' => '574',
        'NG' => '566',
        'NI' => '558',
        'NL' => '528',
        'NO' => '578',
        'NP' => '524',
        'NR' => '520',
        'NU' => '570',
        'NZ' => '554',
        'OM' => '512',
        'PA' => '591',
        'PE' => '604',
        'PF' => '258',
        'PG' => '598',
        'PH' => '608',
        'PK' => '586',
        'PL' => '616',
        'PM' => '666',
        'PN' => '612',
        'PR' => '630',
        'PS' => '275',
        'PT' => '620',
        'PW' => '585',
        'PY' => '600',
        'QA' => '634',
        'RE' => '638',
        'RO' => '642',
        'RS' => '688',
        'RU' => '643',
        'RW' => '646',
        'SA' => '682',
        'SB' => '090',
        'SC' => '690',
        'SD' => '729',
        'SE' => '752',
        'SG' => '702',
        'SH' => '654',
        'SI' => '705',
        'SJ' => '744',
        'SK' => '703',
        'SL' => '694',
        'SM' => '674',
        'SN' => '686',
        'SO' => '706',
        'SR' => '740',
        'SS' => '728',
        'ST' => '678',
        'SV' => '222',
        'SX' => '534',
        'SY' => '760',
        'SZ' => '748',
        'TC' => '796',
        'TD' => '148',
        'TF' => '260',
        'TG' => '768',
        'TH' => '764',
        'TJ' => '762',
        'TK' => '772',
        'TL' => '626',
        'TM' => '795',
        'TN' => '788',
        'TO' => '776',
        'TR' => '792',
        'TT' => '780',
        'TV' => '798',
        'TW' => '158',
        'TZ' => '834',
        'UA' => '804',
        'UG' => '800',
        'UM' => '581',
        'US' => '840',
        'UY' => '858',
        'UZ' => '860',
        'VA' => '336',
        'VC' => '670',
        'VE' => '862',
        'VG' => '092',
        'VI' => '850',
        'VN' => '704',
        'VU' => '548',
        'WF' => '876',
        'WS' => '882',
        'YE' => '887',
        'YT' => '175',
        'ZA' => '710',
        'ZM' => '894',
        'ZW' => '716',
    );

    /**
    * Constructor
    */
    function __construct() {
        parent::__construct();

        $this->id = 'wcmpg_paybox';
        $this->icon = '"width=50px"';
        $this->order_button_text  = __( 'Proceed to Paybox', 'wcmpg' );
        $this->has_fields = false;
        $this->method_title = 'WCMPG Paybox';
        $this->method_description = __( 'Paybox System allows more than thirty payment means.' , 'wcmpg' );

        // Load the form fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        
        // Get setting values
        foreach ($this->settings as $key => $values){
            $this->$key = $values;
        }

        // Recovers the necessary functions
        include_once __DIR__.'/../functions.php';
        $this->functions = new WCMPG_Functions();

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
        
        echo '<img src="'.plugins_url().'/wcmpg/assets/paybox.png" alt="Paybox" width="250">';
        echo '<h3> Paybox / E-Transaction</h3>';
        echo
            '<p>' . __( 'Authorizes the payments by credit card with the Paybox solution. It needs the signature of a distance selling contract from a bank compatible with Paybox.' , 'wcmpg'  ) . '</p>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
        echo '<hr />';
    }

    /**
    * Initialize Gateway Settings Form Fields.
    */
    public function init_form_fields() {
        $this->form_fields = include __DIR__.'/../../admin/settings.php';
        $this->form_fields += array(
            'site_id'         => array(
                'title'       => __('Site ID', 'wcmpg'),
                'type'        => 'text',
                'default'     => '1999888',
                'description' => __( 'The identifier provided by your payment gateway.', 'wcmpg'),
            ),
            'paybox_identifiant' => array(
                'title'          => __('Paybox ID', 'wcmpg'),
                'type'           => 'text',
                'description'    => __('The Paybox ID provided by PayBox.', 'wcmpg'),
                'default'        => '107904482'
            ),
            'paybox_rang'     => array(
                'title'       => __('Paybox Rank', 'wcmpg'),
                'type'        => 'text',
                'description' => __('The Paybox Rank provided by PayBox.', 'wcmpg'),
                'default'     => '32'
            ),
            'paybox_server'   => array(
                'title'       => __( 'Paybox Server', 'wcmpg'),
                'type'        => 'select',
                'description' => __( 'Choose a testing or a production server.', 'wcmpg'),
                'default'     => 'tpeweb.paybox.com',
                'class'       => 'wc-enhanced-select',
                'options'     => array(
                    'tpeweb.paybox.com'               => __( 'Paybox Production', 'wcmpg'),
                    'preprod-tpeweb.paybox.com'       => __( 'Paybox Testing', 'wcmpg'),
                    'tpeweb.e-transactions.fr'        => __( 'E-Transactions Production', 'wcmpg'),
                    'preprod-tpeweb.e-transactions.fr'=> __( 'E-Transaction Testing', 'wcmpg'),
                )
            ),
            'paybox_key'      => array(
                'title'       => __('Secret key', 'wcmpg'),
                'type'        => 'textarea',
                'description' => __('The private secret key generated at the Paybox back office.', 'wcmpg'),
                'default'     => '0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF'
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
     * Get the Paybox form.
     */
    function receipt($order_id) {
        $order = new WC_Order($order_id);
        $url = "https://{$this->paybox_server}/cgi/MYchoix_pagepaiement.cgi";
        
        if (!empty($this->paybox_key)) {
            $form_output = '<p>'.__('Thank you for your order. You will be redirected on Paybox plateform.' , 'wcmpg' ). '</br>'. __('If nothing happens please click on the "Paybox" button below.', 'wcmpg') . '</p>';
            $form_output .= '<form method="POST" action="' . $url . '" name="Paybox">';
            $params = array(
                "PBX_SITE" => $this->site_id,
                "PBX_RANG" => $this->paybox_rang,
                "PBX_IDENTIFIANT" => $this->paybox_identifiant,
                "PBX_TOTAL" => $order->get_total()*100,
                "PBX_DEVISE" => $this->currency,
                "PBX_CMD" => $order->get_id(),
                "PBX_PORTEUR" => $order->get_billing_email(),
                "PBX_RETOUR" => $this->get_params_str(),
                "PBX_HASH" => "SHA512",
                "PBX_TIME" => date('c'),
                "PBX_EFFECTUE" => $order->get_checkout_order_received_url(),
                "PBX_REFUSE" => $this->refused_url,
                "PBX_ANNULE" => $this->cancel_url,
                "PBX_REPONDRE_A" => site_url('/?wc-api=' . get_class($this)),
                "PBX_LANGUE" => $this->functions->get_language($this->language, $this->detect_language, true),
                "PBX_SHOPPINGCART" => $this->get_shoppingcart($order),
                "PBX_BILLING" => $this->get_billing($order),
            );
            $params = apply_filters('wcmpg_receipt_params', $params);
            $params['PBX_HMAC'] = $this->get_hmac($params);
            error_log("WCMPG_Paybox DEBUG : request = " . print_r($params, true));
            foreach($params as $key => $value){
              $form_output .= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'">';
            }
            $form_output .= '<input type="submit" value="Paybox">';
            $form_output .= '</form>';
            $form_output .= '<script> document.forms["Paybox"].submit(); </script>';
        } else {
            $form_output = __('Paybox secret key must be filled in.', 'wcmpg');
        }
        echo $form_output ;
    } //end receipt();

    /**
     * Payment API
     */
    public function api() {
        error_log("WCMPG_Paybox DEBUG : response = " . print_r($_REQUEST, true));
        $params = $this->get_params();
        $order = new WC_Order($params['reference']);
        
        // TODO if (!openssl_verify($params, $params['sign'], $pubkey)){
        //  throw new Exception(__('Invalid payment signature', 'wcmpg'));
        //}
        
        if ($params['error'] == '00000'){
          $order->add_order_note(__('Payment accepted', 'wcmpg'));
          $order->payment_complete($params['transaction']);
        }else{
          $message = __('Payment refused', 'wcmpg');
          $message.= ' (' . $params['error'] . ' : ';
          if (isset($this->_errors[$params['error']])){
            $message.= __($this->_errors[$params['error']], 'wcmpg');
          }else{
            $message.= __('Unknown error', 'wcmpg');
          }
          $message.= ')';
          $order->add_order_note($message);
        }
    }

    /**
     * Get params string
     */
    private function get_params_str(){
      $result = array();
      foreach ($this->_params as $key => $value){
        $result[] = $value . ':' . $key;
      }
      return implode(';', $result);
    }

    /**
     * Get params
     */
    private function get_params(){
      $result = array();
      foreach ($this->_params as $key => $value){
        $result[$value] = $_REQUEST[$value];
      }
      return $result;
    }

    /**
     * Get HMAC
     */
    private function get_hmac($params){
      $msg = '';
      foreach($params as $key => $value) {
        $msg .= $key . '=' . $value . '&';
      }
      if(substr($msg, -1) === '&') {
        $msg = substr($msg, 0, -1);
      }
      $binKey = pack("H*", $this->paybox_key);
      $hmac = strtoupper(hash_hmac('sha512', $msg, $binKey));
      return $hmac;
    }

    private function get_text_value($value, $maxLength = null)
    {
      $value = remove_accents($value);
      $value = preg_replace("/-|'/", ' ', $value);
      $value = trim(preg_replace("/\r|\n/", '', $value));
      if (!empty($maxLength) && is_numeric($maxLength) && $maxLength > 0) {
        if (function_exists('mb_strlen')) {
          if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
          }
        } elseif (strlen($value) > $maxLength) {
          $value = substr($value, 0, $maxLength);
        }
      }
      return trim($value);
    }

    private function get_valid_xml($xml)
    {
      if (class_exists('DOMDocument')) {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $xml = $doc->saveXML();
      } elseif (function_exists('simplexml_load_string')) {
        $xml = simplexml_load_string($xml)->asXml();
      }
      $xml = trim(preg_replace('/(\s*)(' . preg_quote('<?xml version="1.0" encoding="utf-8"?>') . ')(\s*)/', '$2', $xml));
      $xml = trim(preg_replace("/\r|\n/", '', $xml));
      return $xml;
    }

    private function get_billing(WC_Order $order)
    {
      $firstName = $this->get_text_value($order->get_billing_first_name(), 22);
      $lastName = $this->get_text_value($order->get_billing_last_name(), 22);
      $addressLine1 = $this->get_text_value($order->get_billing_address_1(), 50);
      $addressLine2 = $this->get_text_value($order->get_billing_address_2(), 50);
      $zipCode = $this->get_text_value($order->get_billing_postcode(), 10);
      $city = $this->get_text_value($order->get_billing_city(), 50);
      $countryCode = isset($this->_countries[$order->get_billing_country()]) ? $this->_countries[$order->get_billing_country()] : '';
      $xml = sprintf(
        '<?xml version="1.0" encoding="utf-8"?><Billing><Address><FirstName>%s</FirstName><LastName>%s</LastName><Address1>%s</Address1><Address2>%s</Address2><ZipCode>%s</ZipCode><City>%s</City><CountryCode>%s</CountryCode></Address></Billing>',
        $firstName,
        $lastName,
        $addressLine1,
        $addressLine2,
        $zipCode,
        $city,
        $countryCode
      );
      return $this->get_valid_xml($xml);
    }

    private function get_shoppingcart(WC_Order $order)
    {
      $totalQuantity = 0;
      foreach ($order->get_items() as $item) {
        $totalQuantity += (int)$item->get_quantity();
      }
      $totalQuantity = max(1, min($totalQuantity, 99));
      return sprintf('<?xml version="1.0" encoding="utf-8"?><shoppingcart><total><totalQuantity>%d</totalQuantity></total></shoppingcart>', $totalQuantity);
    }

} // end WCMPG_Paybox

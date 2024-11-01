<?php 

/**
 * Settings for Payment Gateways.
 */
return array(
	'enabled'     => array(
		'title'   => __( 'Enable/Disable', 'wcmpg' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable the payment gateway.', 'wcmpg' ),
		'default' => 'no'
	),
	'title'           => array(
        'title'       => __('Title', 'wcmpg'),
        'type'        => 'text',
        'description' => __('Title of the payment gateway displayed on checkout page.', 'wcmpg'),
        'default'     => __('Payment Gateway', 'wcmpg')
    ),
    'icon'            => array(
        'title'       => __('Icon', 'wcmpg'),
        'type'        => 'text',
        'description' => __('Icon of the payment gateway displayed on checkout page.', 'wcmpg'),
        'default'     => plugins_url().'/wcmpg/assets/icons/'.str_replace('wcmpg_', '', $this->id).'.png'
    ),
    'description'     => array(
        'title'       => __('Customer Message','wcmpg'),
        'type'        => 'textarea',
        'description' => __('Quick description of the payment method displayed on checkout page.', 'wcmpg'),
        'default'     => __('Secure payment', 'wcmpg')
    ),
    'detect_language'     => array(
      'title'   => __( 'Detect language', 'wcmpg' ),
      'type'    => 'checkbox',
      'label'   => __( 'Detect language from wpml or polylang. WARNING : For SIPS you will have to create a pathfile/parmcom for each language (i.e : pathfile.en, parmcom.sherlocks.en, pathfile.fr, parmcom.sherlocks.fr, etc.)', 'wcmpg' ),
      'default' => 'no'
    ),
    'language'        => array(
        'title'       => __( 'Language', 'wcmpg'),
        'type'        => 'select',
        'description' => __( 'Language used by the payment gateway (if not detected from wpml or polylang)', 'wcmpg'),
        'class'       => 'wc-enhanced-select',
        'default'     => 'fr',
        'options'=> array(
            'fr' => __( 'French', 'wcmpg'),
            'en' => __( 'English', 'wcmpg' ),
            'es' => __( 'Spanish', 'wcmpg' ),
            'it' => __( 'Italian', 'wcmpg'),
            'de' => __( 'German', 'wcmpg' ),
            'nl' => __( 'Dutch', 'wcmpg' ),
            'sv' => __( 'Swedish', 'wcmpg' ),
            'pt' => __( 'Portuguese', 'wcmpg' ),
            'jp' => __( 'Japanese', 'wcmpg' ),
            'zh' => __( 'Chinese', 'wcmpg' )
        ),
    ),
    'currency'   => array(
        'title'  => __('Currency', 'wcmpg'),
        'type'   => 'select',
        'description' => __('Currency used by the shop.' , 'wcmpg' ).' </br>' .__('We recommend to also change the currency in the Woocommerce\'s General settings.' , 'wcmpg'),
        'class'       => 'wc-enhanced-select',
        'default'     => '978',
        'options'     => array(
                    '978' => __( 'Euro (&euro;)', 'wcmpg'),
                    '840' => __( 'USA dollar ($)', 'wcmpg'),
                    '826' => __( 'Pound sterling (&pound;)', 'wcmpg'),
                    '036' => __( 'Australian dollar (&#8371;)', 'wcmpg'),
                    '756' => __( 'Swiss franc (CHF)', 'wcmpg'),
                    '752' => __( 'Swedish Krona (kr)', 'wcmpg'),
                    '578' => __( 'Norwegian krone (kr)', 'wcmpg'),
                    '392' => __( 'Japanese yen (&yen;)', 'wcmpg'),
                    '208' => __( 'Danish krone (DKK)', 'wcmpg'),
                    '124' => __( 'Canadian dollar (&#36;)', 'wcmpg'),
                    '203' => __( 'Czech koruna (K&#269;)', 'wcmpg'),
                    '348' => __( 'Hungarian forint (Ft)', 'wcmpg'),
                    '985' => __( 'Polish zloty (z&#322;)', 'wcmpg'),
                    '643' => __( 'Russian rouble (&#8381;)', 'wcmpg'),
                    '986' => __( 'Brazilian real (R&#36;)', 'wcmpg'),
                    '344' => __( 'Hong Kong dollar (&#36;)', 'wcmpg'),
                    '376' => __( 'Israeli shekel (&#8362;)', 'wcmpg'),
                    '484' => __( 'Mexican peso (&#36;)', 'wcmpg'),
                    '458' => __( 'Malaysian ringgit (RM)', 'wcmpg'),
                    '554' => __( 'New Zealand dollar (&#36;)', 'wcmpg'),
                    '608' => __( 'Philippine peso (&#8369;)', 'wcmpg'),
                    '702' => __( 'Singapore dollar (&#36;)', 'wcmpg'),
                    '764' => __( 'Thai baht (&#3647;)', 'wcmpg')
        ),
    ),
    // TODO read only urls (read them from wc endpoints)
    'success_url'     => array(
        'title'       => __('Successful Return Link', 'wcmpg'),
        'type'        => 'text',
        'description' => __('Link when the transaction succeeds.', 'wcmpg'),
        'default'     => wc_get_endpoint_url('order-received', '', get_permalink(wc_get_page_id('checkout')))
    ),
    'refused_url'     => array(
        'title'       => __('Failed Return Link', 'wcmpg'),
        'type'        => 'text',
        'description' => __('Link when the transaction is refused by gateway.', 'wcmpg'),
        'default'     => get_permalink( wc_get_page_id( 'checkout' ) )
    ),
    'cancel_url'      => array(
        'title'       => __('Cancel Return Link','wcmpg'),
        'type'        => 'text',
        'description' => __('Link when the client cancels the transaction.', 'wcmpg'),
        'default'     => get_permalink( wc_get_page_id( 'checkout' ) )
    ),
);

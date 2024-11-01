<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Generic payment block for WCMPG
 *
 * @since 1.67
 */
final class WCMPG_Block extends AbstractPaymentMethodType {

  /**
   * The gateway instance.
   *
   * @var WC_Payment_Gateway
   */
  private $gateway;

  /**
   * Payment method name/id/slug.
   *
   * @var string
   */
  protected $name;

  /**
   * Constructor
   *
   * @param $name of payment gateway
   */
  public function __construct( $name ) {
    $this->name = $name;
  }

  /**
   * Initializes the payment method type.
   */
  public function initialize() {
    $this->settings = get_option( 'woocommerce_' . $this->name . '_settings', [] );
    $gateways       = WC()->payment_gateways->payment_gateways();
    $this->gateway  = $gateways[ $this->name ];
  }

  /**
   * Returns if this payment method should be active. If false, the scripts will not be enqueued.
   *
   * @return boolean
   */
  public function is_active() {
    return $this->gateway->is_available();
  }

  /**
   * Returns an array of scripts/handles to be registered for this payment method.
   *
   * @return array
   */
  public function get_payment_method_script_handles() {
    wp_register_script(
      'wcmpg-block',
      plugin_dir_url( __DIR__ ) . 'build/index.js',
      array(
        'wc-blocks-registry',
        'wc-settings',
        'wp-element',
        'wp-html-entities',
      ),
      null,
      true
    );
    wp_localize_script('wcmpg-block', 'wcmpg_params', array('name' => $this->name));
    return [ 'wcmpg-block' ];
  }

  /**
   * Returns an array of key=>value pairs of data made available to the payment methods script.
   *
   * @return array
   */
  public function get_payment_method_data() {
    return [
      'title'       => $this->get_setting( 'title' ),
      'description' => $this->get_setting( 'description' ),
      'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
    ];
  }
}

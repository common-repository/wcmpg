<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ircf.fr/produit/woocommerce-multiple-payment-gateways-monosite/
 * @since             1.0.0
 * @package           WCMPG
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Multiple Payment Gateways
 * Plugin URI:        https://ircf.fr/produit/woocommerce-multiple-payment-gateways-monosite/
 * Description:       Multiple Payment Gateways for WooCommerce.
 * Version:           1.68
 * Author:            IRCF
 * Author URI:        https://ircf.fr/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wcmpg
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

/** 
* Make sure WooCommerce is active
*/
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

function WCMPG_activate_gateway()
{
  include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
  if( !is_plugin_active('woocommerce/woocommerce.php') )
  {
    _e('WooCommerce must be installed and activated in order to use this plugin !', 'payment_gateway');
    exit;
  }
  if( !class_exists('WC_Payment_Gateway') )
  {
    _e('An error as occured with WooCommerce: can not find gateway methods...', 'payment_gateway');
    exit;
  }
}

//do_action( 'woocommerce_init', $array );
add_action('plugins_loaded', 'WCMPG_woocommerce_gateway_init', 0);
register_activation_hook(__FILE__, 'WCMPG_activate_gateway');

function WCMPG_woocommerce_gateway_init()
{
  load_plugin_textdomain( 'wcmpg', FALSE, 'wcmpg/languages' ); // Required for Wordpress < 4.6

  if( class_exists('WC_Payment_Gateway') )
  {
    include_once( plugin_dir_path( __FILE__ ).'admin/class-wcmpg-licence.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/class-wcmpg-base.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/paybox/class-wcmpg-paybox.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/paypal/class-wcmpg-paypal.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/mercanet/class-wcmpg-mercanet.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/sips/class-wcmpg-sips.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/systempay/class-wcmpg-systempay.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/monetico/class-wcmpg-monetico.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/sips-paypage-post/class-wcmpg-sips-paypage-post.php' );
    include_once( plugin_dir_path( __FILE__ ).'includes/axepta/class-wcmpg-axepta.php' );
  } else {
    exit;
  }

  add_filter('woocommerce_payment_gateways', 'wcmpg_woocommerce_payment_gateways');
  add_action('woocommerce_blocks_loaded', 'wcmpg_woocommerce_blocks_loaded');
}

function wcmpg_woocommerce_payment_gateways($methods = array()){
  $methods[]  = 'WCMPG_Licence'; // TODO remove
  $methods[] .= 'WCMPG_Paybox';
  $methods[] .= 'WCMPG_Paypal';
  $methods[] .= 'WCMPG_Mercanet';
  $methods[] .= 'WCMPG_Sips';
  $methods[] .= 'WCMPG_Systempay';
  $methods[] .= 'WCMPG_Monetico';
  $methods[] .= 'WCMPG_Sips_Paypage_Post';
  $methods[] .= 'WCMPG_Axepta';
  return $methods;
}

/*
* Show notice if the plugin version is free 
*/
function WCMPG_Show_Notice(){

  if(get_option('WCMPH_hide_notice')==false){//create option
    add_option( 'WCMPH_hide_notice', false);
  }

  $url='http://' .$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);

  $notice =  get_option('wcmpg_licence_key') ==false && get_option('WCMPH_hide_notice')==false && is_user_logged_in() && strstr($url, 'wp-admin');

  if ( $notice || strstr($_SERVER['REQUEST_URI'], 'wcmpg') && $notice){

    $license = $url.'/admin.php?page=wc-settings&tab=checkout&section=wcmpg_licence';
    $url .= '/admin.php?page=wc-settings&wcmpg_hide=true';

    echo '<div class="notice notice-warning"><p>';
    echo __('You currently use the free licence of WCMPG.' , 'wcmpg');

    if (!strstr($_SERVER['REQUEST_URI'], 'wcmpg')){
      echo '<a href="'.$url.'" style="float:right; border:none;">'.__('Don\'t show this anymore', 'wcmpg').'</a>';
    }

    echo '<br/>';
    echo  __('You can register your license ', 'wcmpg');
    echo '<a href="'.$license.'">'.__('here', 'wcmpg').'</a> .';
    echo '<br/>';
    echo  __('If you didn\'t buy the plugin, you can get the pro version ', 'wcmpg') ;
    echo '<a href="https://ircf.fr/plugin-wordpress" target="_blank">'.__('here', 'wcmpg').'</a> .';
    echo '</p></div>';
  }

  if(isset($_GET['wcmpg_hide']) && $_GET['wcmpg_hide']){
    update_option( 'WCMPH_hide_notice', true);
    $current_url = explode("?", $_SERVER['REQUEST_URI']);
    $url= $current_url[0] .'?page=wc-settings&tab=checkout&section=licence';
    header('Location:'.$url);
  }
}
add_action('all_admin_notices', 'WCMPG_Show_Notice');

// TODO move elsewhere
function wcmpg_sips_test(){
  $wcmpg_sips = new WCMPG_Sips();
  $wcmpg_sips->test_ajax();
}
add_action('wp_ajax_wcmpg_sips_test', 'wcmpg_sips_test');

function wcmpg_upload_dir(){
  $upload_dir = wp_upload_dir();
  return  $upload_dir['basedir'] . '/wcmpg_uploads/';
}

function wcmpg_upload_url(){
  $upload_dir = wp_upload_dir();
  return  $upload_dir['baseurl'] . '/wcmpg_uploads/';
}

/**
 * Paypage and Mercanet do not implement cancel_url
 * so we have to redirect the order_received endpoint
 * depending on payment status
 */
add_action( 'template_redirect', 'wcmpg_order_received_redirect' );
function wcmpg_order_received_redirect(){
  if ( !is_wc_endpoint_url( 'order-received' ) || empty( $_GET['key'] ) ) {
    return;
  }
  $order_id = wc_get_order_id_by_order_key( $_GET['key'] );
  $order = wc_get_order( $order_id );
  $gateway = wc_get_payment_gateway_by_order( $order );
  if (!in_array($gateway->id, array('wcmpg_sips_paypage_post', 'wcmpg_mercanet'))){
    return;
  }
  if (in_array($order->get_status(), array('failed', 'cancelled', 'pending'))){
    wp_redirect($order->get_cancel_order_url());
    exit;
  }
}

/**
 * Register WooCommerce payment gateways as blocks
 */
function wcmpg_woocommerce_blocks_loaded() {
  if ( !class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) )
    return;
  require_once 'includes/class-wcmpg-block.php';
  add_action('woocommerce_blocks_payment_method_type_registration', function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
    $methods = wcmpg_woocommerce_payment_gateways();
    foreach($methods as $method){
      if ($method == 'WCMPG_Licence') continue; // TODO remove
      $payment_method_registry->register( new WCMPG_Block(strtolower($method)) );
    }
  });
}

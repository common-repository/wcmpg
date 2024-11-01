<?php
/**
 * WCMPG Base class
 */
#[AllowDynamicProperties]
class WCMPG_Base extends WC_Payment_Gateway {

    protected $functions;

    function __construct(){
      require_once __DIR__ . '/functions.php';
      $this->functions = new WCMPG_Functions();
    }
}

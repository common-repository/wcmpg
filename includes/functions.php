<?php

add_action( 'after_setup_theme', 'wcmpg_woocommerce_support' );
function wcmpg_woocommerce_support() {
    add_theme_support( 'woocommerce' );
}

class WCMPG_Functions {

    /**
    * Enable the payment gateways Atos Sips, Mercanet and Systempay if the licence is checked
    */
    function enable_payment_gateways(){
        $enable = false;
        if (get_option('wcmpg_licence_key') != null){ // if the option 'wcmpg_licence_key' exists (=licence valid)
            $enable=true;
        }
        return $enable;
    }

    /**
     * Get current language
     * @param $language           default language
     * @param $detect_language    detect language from WMPL or Polylang
     * @param $iso3               convert to iso3
     */
    function get_language($default, $detect_language = false, $iso3 = false){
      $result = $default;
      if ($detect_language){
        if (defined('ICL_LANGUAGE_CODE')){
          $result = ICL_LANGUAGE_CODE;
        }elseif(function_exists('pll_current_language')){
          $result = pll_current_language();
        }
      }
      if ($iso3){
        $result = $this->get_language_iso3($result);
      }
      return $result;
    }

    /**
     * Get language ISO3 from ISO2
     */
    function get_language_iso3($iso2){
      $result = '';
      $iso3 = array(
        'fr' => 'FRA',
        'en' => 'GBR',
        'es' => 'ESP',
        'it' => 'ITA',
        'de' => 'DEU',
        'nl' => 'NLD',
        'sv' => 'SWE',
        'pt' => 'PRT',
      );
      if (isset($iso3[$iso2])){
        $result = $iso3[$iso2];
      }
      return $result;
    } //end get_language_iso3()

//--------------------------------------------------------------------------------------------------------------------

	// WCMPG_Paypal + WCMPG_Paypal_Form

	/**
    * Get the right currency for Paypal
    */
    public function get_currency($currency){
    	$the_currency = '';
    	if ($currency == '978') { //euro
        	$the_currency = 'EUR';
        } elseif ($currency == '840'){ //USA dollar
        	$the_currency = 'USD';
		} elseif ($currency == '826'){ //pound sterling
        	$the_currency = 'GBP';
		} elseif ($currency == '036'){ //australian dollar
        	$the_currency = 'AUD';
        } elseif ($currency == '756'){ //swiss franc
        	$the_currency = 'CHF';
        } elseif ($currency == '752'){ //swedish krona
        	$the_currency = 'SEK';
        } elseif ($currency == '578'){ //norwegian krone
        	$the_currency = 'NOK';
		} elseif ($currency == '392'){ //japanese yen
        	$the_currency = 'JPY';
		} elseif ($currency == '208'){ //danish krone
        	$the_currency = 'DKK';
       	} elseif ($currency == '124'){ //canadian dollar
        	$the_currency = 'CAD';
        } elseif ($currency == '203'){ //czech koruna
        	$the_currency = 'CZK';
       	} elseif ($currency == '348'){ //hungarian forint
        	$the_currency = 'HUF';
		} elseif ($currency == '985'){ //polish zloty
        	$the_currency = 'PLN';
        } elseif ($currency == '643'){ //russian rouble
        	$the_currency = 'RUB';
		} elseif ($currency == '986'){ //brasilian real
        	$the_currency = 'BRL';
		} elseif ($currency == '344'){ //hong kong dollar
        	$the_currency = 'HKD';
        } elseif ($currency == '376'){ //israeli shekel
        	$the_currency = 'ILS';
        } elseif ($currency == '484'){ //mexican peso
        	$the_currency = 'MXN';
        } elseif ($currency == '458'){ //malaysian ringgit
        	$the_currency = 'MYR';
		} elseif ($currency == '554'){ //new zealand dollar
        	$the_currency = 'NZD';
		} elseif ($currency == '608'){ //philippine peso
        	$the_currency = 'PHP';
       	} elseif ($currency == '702'){ //singapore dollar
        	$the_currency = 'SGD';
        } elseif ($currency == '764'){ //thai baht
        	$the_currency = 'THB';
        }
        return $the_currency;
    } //end get_currency()

//--------------------------------------------------------------------------------------------------------------------

    // WCMPG_Sips & WCMPG_Mercanet

    /**
    * Check if the upload is a .zip folder and extract it
    */
    function upload_zip($folder){
      $result = array('error' => null, 'success' => null);
      if (strpos($folder, wcmpg_upload_dir()) !== 0){
        throw new Exception('cannot upload outside of wcmpg upload dir');
      }
      if(isset($_FILES["upload"]) && isset($_FILES["upload"]["name"]) && !empty($_FILES["upload"]["name"])) {
        $filename = $_FILES["upload"]["name"];
        $source = $_FILES["upload"]["tmp_name"];
        $type = $_FILES["upload"]["type"];
        $name = explode(".", $filename);
        $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed', 'application/x-tar');
        try{
          if (!in_array($type, $accepted_types)) { //if the type of file is not one of the accepted types
              throw new Exception(__('Unknown file mime type : zip or tar expected', 'wcmpg'));
          }
          if (!file_exists(wcmpg_upload_dir())){
            mkdir(wcmpg_upload_dir());
          }
          if (file_exists($folder)){ //if the folder already exists
              $this->delete_folder($folder);
          }
          mkdir($folder); //create the folder
          $target_path = $folder . $filename;
          if(!move_uploaded_file($source, $target_path)) {
            throw new Exception(__('File could not be uploaded, please check file permissions', 'wcmpg'));
          }
          switch (pathinfo($filename, PATHINFO_EXTENSION)){
            case 'zip':
              $zip = new ZipArchive;
              if ($zip->open($target_path) === TRUE) {
                $zip->extractTo($folder);
                $zip->close();
              }
              break;
            case 'tar':
              $phar = new PharData($target_path);
              $phar->extractTo($folder);
              break;
            default:
              throw new Exception(__('Unknown file extension : zip or tar expected', 'wcmpg'));
              break;
          }
          unlink($target_path);
          $result['success'] = __("Your folder was uploaded and unpacked. ", 'wcmpg' ) . '</br>' ;
        } catch (Exception $e) {
          $result['error'] = __("There was a problem with the upload. Please try again.", 'wcmpg' ) . '</br>' ;
          $result['error'].= $e->getMessage() . '</br>' ;
        }
      }
    }

    /**
    * Delete folder
    */
    function delete_folder($directory){
        if (strpos($directory, wcmpg_upload_dir()) !== 0){
          throw new Exception('cannot delete outside of wcmpg upload dir');
        }
        $handle = opendir($directory);
        while(false !== ($entry = readdir($handle))){
            if($entry != '.' && $entry != '..'){
                if(is_dir($directory.'/'.$entry)){ //if the targeted file is a directory
                    $this->delete_folder($directory.'/'.$entry);
                }
                elseif(is_file($directory.'/'.$entry)){ //if the targeted file is not a directory
                    unlink($directory.'/'.$entry);
                }
            }
        }
        closedir($handle);
        rmdir($directory);
    }

   /**
   * Checks if the directory is empty
   */
    function is_empty_directory($directory) {
        $isEmpty = true;
        if (file_exists($directory)){
            $files = scandir($directory);
            $count = count($files);
            if ($count <= 2) {
                $isEmpty = true;
            } else {
                $isEmpty = false;
            }
        }
        return $isEmpty;
    } // end is_empty_dir()

    /**
    * Test the API and display the result
    */
    function executable(){
        $sips = new WCMPG_Sips();

        $pathfile = $sips->sips_pathfile;
        $path_bin_request = $sips->sips_path_bin_request;
        $path_bin_response = $sips->sips_path_bin_response;

        $param_test  = 'pathfile=' . $pathfile;
        $param_test .= ' merchant_id=' . $sips->site_id;
        $param_test .= ' merchant_country=' . 'fr';
        $param_test .= ' amount=' . 100;
        $param_test .= ' currency_code=' . $sips->currency;
        $param_test .= ' normal_return_url='; // . $sips->success_url;
        $param_test .= ' cancel_return_url='; // . $sips->cancel_url;
        $param_test .= ' automatic_response_url=' . escapeshellcmd(site_url('/?wc-api=wcmpg_sips'));
        $param_test .= ' language=' . $sips->language;
        $param_test .= ' payment_means=' . 'CB,2,VISA,2,MASTERCARD,2';
        $param_test .= ' header_flag=' . 'no';
        $param_test .= ' order_id=' . 1;
        $param_test .= ' logo_id=' . $sips->sips_image_left;
        $param_test .= ' logo_id2=' . $sips->sips_image_right;
        $param_test .= ' advert=' . $sips->sips_image_center;

        try{
          if (!file_exists($path_bin_request)){
            throw new Exception(
              __('Request program not found, please check if the following file exists', 'wcmpg') . '<br>' .
              $path_bin_request
            );
          }

          if (!is_executable($path_bin_request)){
            throw new Exception(
              __('Request program is not executable, please check permissions on the following file', 'wcmpg') . '<br>' .
              $path_bin_request
            );
          }

          $result = exec("$path_bin_request $param_test");

          if (empty($result)){
            throw new Exception(
              __('Request program returned an empty string, please try to execute manually :', 'wcmpg') . '<br>' .
              $path_bin_request . ' ' . $param_test
            );
          }

          $tableau= explode ("!", "$result");
          $code   = $tableau[1];
          $error  = $tableau[2];

          if ($code != 0) {
            throw new Exception(
              __('Request program returned an error', 'wcmpg') . '<br>' .
              __('Error code : ', 'wcmpg') . $code . '<br>' .
              __('Error message :', 'wcmpg' ) . strip_tags($error, '<B><TD>')
            );
          }

          if (!file_exists($path_bin_response)){
            throw new Exception(
              __('Response program not found, please check if the following file exists', 'wcmpg') . '<br>' .
              $path_bin_response
            );
          }

          if (!is_executable($path_bin_response)){
            throw new Exception(
              __('Response program is not executable, please check permissions on the following file', 'wcmpg') . '<br>' .
              $path_bin_response
            );
          }

          $message = '<b>' . __("It works!" , 'wcmpg' ) . '</b>';
          //$message.= "$path_bin_request $param_test";
        }catch(Exception $e){
          $message = '<b>' . $e->getMessage() . '</b>';
        }
        return $message;
    }

//--------------------------------------------------------------------------------------------------------------------

} // end class


add_filter( 'post_date_column_time' , 'woo_custom_post_date_column_time' );
/**
 * woo_custom_post_date_column_time
 *
 * @access  public
 * @since     1.0 
 * @return  void
*/
function woo_custom_post_date_column_time( $post ) {
    
    $h_time = get_post_time( __( 'd/m/Y', 'woocommerce' ), $post );
    
    return $h_time;
    
}

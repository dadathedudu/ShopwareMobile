<?php
/**
 * [plugin]
 * @link http://www.shopware.de
 * @package Plugins
 * @subpackage [scope]
 * @copyright Copyright (c) [year], shopware AG
 * @version [version] [date] [revision]
 * @author [author]
 */
class Shopware_Controllers_Backend_MobileTemplate extends Enlight_Controller_Action
{
	/** {obj} Shopware configuration object */
	protected $config;
	
	/** {arr} Plugin configuration */
	protected $props;
	
	/** {obj} Shopware database object */
	protected $db;
	
	/** {str} Upload path for the logo, icon and startup screen */
	protected $uploadPath;
	
	/** {int} Max. upload size of a file */
	protected $maxFileSize;
	
	/** {arr} Allowed file extension */
	protected $fileExtensions;
	
	/** {str} HTTP base path */
	protected $basePath;
	
	/** {str} HTTP plugin views path */
	protected $pluginPath;
	
	/** {arr} Color templates */
	protected $colorTemplates;

	/** {str} Prefix for generated thumbnails */
	protected $thumbNailPrefix;
	
	/**
	 * Used as a constructor method for this class, which
	 * sets the views, provides global variables and reads
	 * the config from the DB
	 *
	 * @access public
	 * @return void
	 */
	public function init()
	{
		$this->config = Shopware()->Config();
		$this->uploadPath = Shopware()->DocPath() . '/images/swag_mobiletemplate';
		$this->db = Shopware()->Db();
		
		// Get max file upload size from the php.ini 
		$iniMaxSize = ini_get('post_max_size');
		$unit = strtoupper(substr($iniMaxSize, -1));
		$multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));
		$maxFileSizeValue = substr($iniMaxSize, 0, -1);
		
		// Upload size in bytes
		$this->maxFileSize = $maxFileSizeValue * $multiplier;
		
		// Set allowed file extensions
		$this->fileExtensions = array("jpg", "jpeg", "tif", "tiff", "gif", 'png');
		
		// Get all settings
		$props = $this->db->query('SELECT * FROM `s_plugin_mobile_settings`');
		$props = $props->fetchAll();
		
		$properties = array();
		foreach($props as $prop) {
			$properties[$prop['name']] = $prop['value'];
		}
		
		$this->props = $properties;
		
		// Set plugin base path
		$docPath = Enlight::Instance()->DocPath();
		$request = Enlight::Instance()->Front()->Request();
		$this->basePath = $request->getScheme().'://'. $request->getHttpHost() . $request->getBasePath() . '/';
		
		$path = explode($docPath, dirname(__FILE__));
		$path = $path[1];
		$this->pluginPath = $this->basePath . $path . '/Views/backend/mobile_template/img/colortemplates/';
		
		// Set colorTemplates array
		$this->colorTemplates = array(
			'data' => array(
				array('value' => 'android', 'displayText' => 'Android-Style'),
				array('value' => 'blue', 'displayText' => 'Blau'),
				array('value' => 'default', 'displayText' => 'Standard'),
				array('value' => 'green', 'displayText' => utf8_encode('Grün')),
				array('value' => 'ios', 'displayText' => 'iOS-Style'),
				array('value' => 'orange', 'displayText' => 'Orange'),
				array('value' => 'pink', 'displayText' => 'Pink'),
				array('value' => 'red', 'displayText' => 'Rot'),
				array('value' => 'turquoise', 'displayText' => utf8_encode('Türkis')),
				array('value' => 'neon', 'displayText' => 'Neon'),
				array('value' => 'vintage',  'displayText' => 'Vintage'),
				array('value' => 'light-blue', 'displayText' => 'Hellblau')
			)
		);

		// Set thumbnail prefix
		$this->thumbNailPrefix = 'thumb-';

		$this->View()->addTemplateDir(dirname(__FILE__) . "/Views/");
	}
 	
 	/**
	  * Provides all configuration settings to
	  * the view.
	  *
	  * @return void
	  */
	public function indexAction()
	{
		// Assign plugin props to view
		foreach($this->props as $k => $v) {
			$this->View()->assign($k, $v);
		}
		
		$this->View()->assign('pluginBase', $this->pluginPath);
		
		// Screenshots
		if(!empty($this->props['screenshots'])) {
			$screenshots = explode('|', $this->props['screenshots']);
			if(is_array($screenshots)) {
				foreach($screenshots as $k => $v) {
					$screenshots[$k] = $this->basePath . 'images/swag_mobiletemplate/'. $v;
				}
			}
			$this->View()->assign('screenshots', $screenshots);
		}
		
		// Supported devices
		$data = array(
			array('boxLabel' => 'iPhone', 'name' => 'iphone'),
			array('boxLabel' => 'iPod', 'name' => 'ipod'),
			array('boxLabel' => 'iPad (experimental)', 'name' => 'ipad'),
			array('boxLabel' => 'Android', 'name' => 'android'),
			array('boxLabel' => 'BlackBerry (experimental)', 'name' => 'blackberry')
		);
		$properties = strtolower($this->props['supportedDevices']);
		$properties = explode('|', $properties);
		
		// Set checked attribute
		foreach($data as $k => $v) {
			if(in_array($v['name'], $properties)) {
				$data[$k]['checked'] = true;
			}
		}
		$this->View()->assign('supportedDevicesJSON', Zend_Json::encode($data));
		
		// Get paymentmeans -
		$paymentmeans = $this->db->query("SELECT `id`, `name`, `description`, `additionaldescription` FROM `s_core_paymentmeans`");
		$paymentmeans = $paymentmeans->fetchAll();
		
		// Supported paymentmeans
		$supportedPaymentmeans = explode('|', $this->props['supportedPayments']);
		$availablePayments = array(3, 4, 5, 20);
		
		$payments = array();
		foreach($paymentmeans as $k => $v) {
			if(in_array($v['id'], $availablePayments)) {
				if(in_array($v['id'], $supportedPaymentmeans)) {
					$payments[] = array(
						'boxLabel' => utf8_encode($v['description']),
						'checked' => true,
						'name' => utf8_encode($v['name'])
					);
				} else {
					$payments[] = array(
						'boxLabel' => utf8_encode($v['description']),
						'name' => utf8_encode($v['name'])
					);
				}
			} else {
				$payments[] = array(
					'boxLabel' => utf8_encode($v['description'] . ' (noch nicht unterstützt)'),
					'disabled' => true,
					'name' => utf8_encode($v['name'])
				);
			}
		}

		
		$this->View()->assign('supportedPaymentmeansJSON', Zend_Json::encode($payments));
	}
 	
	/**
	 * Necessary method which have some auto magic
	 * and provides an iFrame which contains the
	 * content of the backend module
	 *
	 * Note: This is a empty functions
	 *
	 * @return void
	 */
	public function skeletonAction() { }

	/**
	 * Saves the form elements of the
	 * generell settings tab to the DB
	 *
	 * @return void
	 */
	public function processGenerellFormAction()
	{
		$request = $this->Request();
		
		// Supported devices
		$supportedDevices = array();
		$iphone = $request->getParam('iphone');
		if(!empty($iphone)) {
			$supportedDevices[] = 'iPhone';
		}
		$ipod = $request->getParam('ipod');
		if(!empty($ipod)) {
			$supportedDevices[] = 'iPod';
		}
		$ipad = $request->getParam('ipad');
		if(!empty($ipad)) {
			$supportedDevices[] = 'iPad';
		}
		$android = $request->getParam('android');
		if(!empty($android)) {
			$supportedDevices[] = 'Android';
		}
		$blackBerry = $request->getParam('blackberry');
		if(!empty($blackBerry)) {
			$supportedDevices[] = 'BlackBerry';
		}
		$supportedDevices = implode('|', $supportedDevices);
		$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$supportedDevices' WHERE `name` LIKE 'supportedDevices';");
		
		// Supported paymentmeans
		$supportedPaymentmeans =  array();
		$cash = $request->getParam('cash');
		if(!empty($cash)) {
			$supportedPaymentmeans[] = 3;
		}
		$invoice = $request->getParam('invoice');
		if(!empty($invoice)) {
			$supportedPaymentmeans[] = 4;
		}
		$prepayment = $request->getParam('prepayment');
		if(!empty($prepayment)) {
			$supportedPaymentmeans[] = 5;
		}

		$paypalexpress = $request->getParam('paypalexpress');
		if(!empty($paypalexpress)) {
			$supportedPaymentmeans[] = 20;
		}

		$supportedPaymentmeans = implode('|', $supportedPaymentmeans);
		$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$supportedPaymentmeans' WHERE `name` LIKE 'supportedPayments';");
		
		//Shopsite-ID AGB
		$agbInfoID = $request->getParam('agbInfoID');
		if(isset($agbInfoID)) {
			$agbInfoID = (int) $agbInfoID;
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$agbInfoID' WHERE `name` LIKE 'agbInfoID';");
		}
		
		//Shopsite-ID Right of Revocation
		$cancelRightID = $request->getParam('cancelRightID');
		if(isset($cancelRightID)) {
			$cancelRightID = (int) $cancelRightID;
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$cancelRightID' WHERE `name` LIKE 'cancelRightID';");
		}
		
		//Infosite group name
		$infoGroupName = $request->getParam('infoGroupName');
		if(isset($infoGroupName)) {
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$infoGroupName' WHERE `name` LIKE 'infoGroupName';");
		}
		
		// Show normal version link
		$showNormalVersionLink = $request->getParam('showNormalVersionLink');
		if(isset($showNormalVersionLink)) {
			if($showNormalVersionLink == 'on') {
				$showNormalVersionLink = 1;
			}
		} else {
			$showNormalVersionLink = 0;
		}
		$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$showNormalVersionLink' WHERE `name` LIKE 'showNormalVersionLink';");

		// Use Shopware Mobile as Subshop
		$useAsSubshop = $request->getParam('useAsSubshop');
		if(isset($useAsSubshop)) {
			if($useAsSubshop == 'on') {
				$useAsSubshop = 1;
			}
		} else {
			$useAsSubshop = 0;
		}
		$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$useAsSubshop' WHERE `name` LIKE 'useAsSubshop';");


		//Subshop-ID
		$subshopID = $request->getParam('hiddenSubshopID');
		if(isset($subshopID)) {
			$subshopID = intval($subshopID);
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$subshopID' WHERE `name` LIKE 'subshopID';");
		}

		// Voucher on confirm page
		$useVoucher = $request->getParam('useVoucher');
		if(isset($useVoucher)) {
			if($useVoucher == 'on') {
				$useVoucher = 1;
			} else {
				$useVoucher = 0;
			}
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$useVoucher' WHERE `name` LIKE 'useVoucher';");
		}

		// Newsletter signup on confirm page
		$useNewsletter = $request->getParam('useNewsletter');
		if(isset($useNewsletter)) {
			if($useNewsletter == 'on') {
				$useNewsletter = 1;
			} else {
				$useNewsletter = 0;
			}
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$useNewsletter' WHERE `name` LIKE 'useNewsletter';");
		}

		// Commentfield on confirm page
		$useComment = $request->getParam('useComment');
		if(isset($useComment)) {
			if($useComment == 'on') {
				$useComment = 1;
			} else {
				$useComment = 0;
			}
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$useComment' WHERE `name` LIKE 'useComment';");
		}
		
		$message = 'Das Formular wurde erfolgreich gespeichert.';
		echo Zend_Json::encode(array('success' => true, 'message' => $message));
		die();
	}
	
	/**
	 * Saves the form elements of the design related
	 * settings tab and processes the file uploads
	 *
	 * @return void
	 */
	public function processDesignFormAction()
	{
		$logoUpload    = $_FILES['logoUpload'];
		$startupUpload = $_FILES['startupUpload'];
		$iconUpload    = $_FILES['iconUpload'];
		$request       = $this->Request();
		
		// Check if the user chooses a new logo
		if(is_array($logoUpload) && !empty($logoUpload) && $logoUpload['size'] > 0) {
			$logo = $this->processUpload($logoUpload, 'logo', 'logo');
			$logoImage = $logo['image'];
			$logoHeight = $logo['height'];
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$logoImage' WHERE `name` LIKE 'logoUpload';");
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$logoHeight' WHERE `name` LIKE 'logoHeight';");
		}
		
		// Check if the user chooses a new icon
		if(is_array($iconUpload) && !empty($iconUpload) && $iconUpload['size'] > 0) {
			$icon = $this->processUpload($iconUpload, 'icon', 'icon');
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$icon' WHERE `name` LIKE 'iconUpload';");
		}
		
		// Check if the user chooses a new startup screen
		if(is_array($startupUpload) && !empty($startupUpload) && $startupUpload['size'] > 0) {
			$startup = $this->processUpload($startupUpload, 'startup', 'startup');
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$startup' WHERE `name` LIKE 'startupUpload';");
		}
		
		// Sencha.IO
		$useSenchaIO = $request->getParam('useSenchaIO');
		if(isset($useSenchaIO)) {
			if($useSenchaIO == 'on') {
				$useSenchaIO = 1;
			} else {
				$useSenchaIO = 0;
			}
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$useSenchaIO' WHERE `name` LIKE 'useSenchaIO';");
		}

		// Checkbox green
		$checkboxesGreen = $request->getParam('checkboxesGreen');
		if(isset($checkboxesGreen)) {
			if($checkboxesGreen == 'on') {
				$checkboxesGreen = 1;
			} else {
				$checkboxesGreen = 0;
			}
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$useSenchaIO' WHERE `name` LIKE 'useSenchaIO';");
		}

		// Show normal version link
		$showBanner = $request->getParam('showBanner');
		if(isset($showBanner)) {
			if($showBanner == 'on') {
				$showBanner = 1;
			}
		} else {
			$showBanner = 0;
		}
		$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$showBanner' WHERE `name` LIKE 'showBanner';");
		
		// Colortemplate
		$colorTemplate = $request->getParam('hiddenColorTemplate');
		if(isset($colorTemplate)) {
			
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$colorTemplate' WHERE `name` LIKE 'colorTemplate';");
		}
		
		// Additional CSS
		$additionalCSS = $request->getParam('additionalCSS');
		if(isset($additionalCSS)) {
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$additionalCSS' WHERE `name` LIKE 'additionalCSS';");
		}
		
		// Statusbar style
		$statusbarStyle = $request->getParam('hiddenStatusbarStyle');
		if(isset($statusbarStyle)) {
		
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$statusbarStyle' WHERE `name` LIKE 'statusbarStyle';");
		}
		
		// Gloss on icon
		$glossOnIcon = $request->getParam('glossOnIcon');
		if(isset($glossOnIcon)) {
			if($glossOnIcon == 'on') {
				$glossOnIcon = 1;
			} else {
				$glossOnIcon = 0;
			}
			$this->db->query("UPDATE `s_plugin_mobile_settings` SET `value` = '$glossOnIcon' WHERE `name` LIKE 'glossOnIcon';");
		}
		
		$message = 'Das Formular wurde erfolgreich gespeichert.';
		echo Zend_Json::encode(array('success' => true, 'message' => $message));
		die();
	}

	/**
	 * Helper function for ExtJS which provides
	 * all available color templates
	 * in a suitable format (JSON).
	 *
	 * The data which are used here are all from
	 * the database.
	 * 
	 * @return void
	 */
	public function getColorTemplateStoreAction()
	{		
		$extension = '.jpg';
		
		// Basic data array
		$data = $this->colorTemplates;
				
		$selected = $this->props['colorTemplate'];
		foreach($data['data'] as $k => $v) {
			// Set id
			$data['data'][$k]['id'] = $k;
			
			// Set selected attribute
			if($v['value'] == $selected) {
				$data['data'][$k]['selected'] = true;
			}
			
			// Set preview image
			$data['data'][$k]['previewImage'] = $this->pluginPath . $v['value'] . $extension; 
		}
		
		// Set totalCount
		$data['totalCount'] = count($data['data']);
		
		// Set success attribute
		$data['success'] = true;
		
		echo Zend_Json::encode($data);
		die();
	}

	/**
	 * Helper function for ExtJS which provides
	 * all available sub shops in a suitable
	 * format (JSON).
	 *
	 * @return void
	 */
	public function getSubshopStoreAction()
	{
		$sql = "SELECT id, id AS valueField, name AS displayText FROM `s_core_multilanguage` GROUP BY id";
		$result = $this->db->fetchAll($sql);

		$selected = $this->props['subshopID'];
		foreach($result as $k => $v) {
			if($v['id'] == $selected) {
				$result[$k]['selected'] = true;
			}
		}

		$data['data'] = $result;
		$data['totalCount'] = count($result);
		$data['success'] = true;

		echo Zend_Json::encode($data);
		die();
	}

	/**
	 * Helper function for ExtJS which provides
	 * all available statusbar styles in a suitable
	 * format.
	 *
	 * Note that the styles are static placed in the
	 * source code.
	 *
	 * @return void
	 */
	public function getStatusbarStyleStoreAction()
	{
		$data = array(
			'data' => array(
				array('value' => 'default', 'displayText' => 'Standard'),
				array('value' => 'black', 'displayText' => 'Schwarz'),
				array('value' => 'black-translucent', 'displayText' => 'Schwarz-transparent')
			)
		);
		
		$selected = $this->props['statusbarStyle'];
		foreach($data['data'] as $k => $v) {
		
			// Set id
			$data['data'][$k]['id'] = $k;
			
			// Set selected attribute
			if($v['value'] == $selected) {
				$data['data'][$k]['selected'] = true;
			}
		}
		
		// Set totalCount
		$data['totalCount'] = count($data['data']);
		
		// Set success attribute
		$data['success'] = true;
		
		echo Zend_Json::encode($data);
		die();
	}
	
	/**
	 * Handles the whole native application tab of
	 * the backend module.
	 *
	 * @return void
	 */
	public function processNativeApplicationFormAction()
	{
		$request = $this->Request();

		$email = 'te@shopware.de';
		$msg = $request->getParam('msg');
		$contactPerson = $request->getParam('contactPerson');

		$template = Shopware()->Config()->get('sTemplates')->sMobileNativeApplicationRequest;

		$template['content'] = str_replace('{sMail}', $email, $template['content']);
		$template['content'] = str_replace('{sShopURL}', 'http://'.Shopware()->Config()->BasePath, $template['content']);
		$template['content'] = str_replace('{sShop}', Shopware()->Config()->ShopName, $template['content']);
		$template['content'] = str_replace('{sMessage}', $msg, $template['content']);
		$template['content'] = str_replace('{sContactPerson}', $contactPerson, $template['content']);

		$mail           = clone Shopware()->Mail();
		$mail->From     = $template['frommail'];
		$mail->FromName = $template['fromname'];
		$mail->Subject  = $template['subject'];

		if ($template['ishtml']){
			$mail->IsHTML(1);
			$mail->Body     = $template['contentHTML'];
			$mail->AltBody     = $template['content'];
		} else {
			$mail->IsHTML(0);
			$mail->Body     = $template['content'];
		}

		$mail->ClearAddresses();

		$mail->AddAddress($email, '');

		if (!$mail->Send()){
			throw new Enlight_Exception("Could not send mail");
		} else {
			echo Zend_Json::encode(array('success' => true, 'message' => 'Ihre Anfrage wurde erfolgreich versendet. Sie erhalten in K&uuml;rze eine R&uuml;ckmeldung.'));
		}

		die();
	}

	
	/**
	 * Helper function which provides an easy
	 * to use way to use cURL to request or
	 * get data.
	 * 
	 * The method supports requests with different
	 * user agents, could login in password
	 * protected area and has the ability to
	 * send additional post parameters.
	 *
	 * @param $url
	 * @param string $post - post variables
	 * @param string $userAgent - user agent
	 * @param string $login - login informations
	 * @param int $timeout - timeout in milliseconds
	 * @return mixed
	 */
	private function getData($url, $post = '', $userAgent = '', $login = '', $timeout = 5)
	{
		// Initialize cURL 
		$ch = curl_init();
		
		// Set POST variables
		if(!empty($post) && is_array($post)) {
			//curl_setopt($ch, CURLOPT_POST, $postCount);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		
		// Set User agent
		if(!empty($userAgent)) {
			curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		}
		
		// Set login informations
		if(!empty($login)) {
			curl_setopt($ch, CURLOPT_USERPWD, $login['username'] . ':' . $login['password']); 
		}
		
		// Set url
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, $timeout);
		
		// Execute request
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
    
    /**
     * Processes the whole upload process for images in the backend module.
	 * The supported image types are defined in the {@link init()} method.
	 *
	 * Note that this method needs an upload folder for the images. This
	 * folder is defined in the {@link init()} method.
	 *
     * @access private
     * @param arr $upload - $_FILES array
     * @param str $filename - Der zuverwendene Dateiname
     * @param str $imageType - Typ des Bildes (Logo, Icon, Startup-Screen)
     * @return str $path - Pfad zum Bild
     */
    private function processUpload($upload, $filename, $imageType)
    {
    	// Check upload path
    	if(!is_dir($this->uploadPath)) {
			if(!mkdir($this->uploadPath, 0777)) {
				$message = 'Das Uploadverzeichnis "' . $this->uploadPath . '" ben&ouml;tigt die Rechte 0777.';
				echo Zend_Json::encode(array('success' => false, 'message' => $message));
				die();
			}
		}
		
		// Validate file size
		$file_size = @filesize($upload["tmp_name"]);
		if (!$file_size || $file_size > $this->maxFileSize) {
			$message = 'Die von Ihnen gew&auml;hlte Datei &uuml;bersteigt das Uploadlimit.';
			echo Zend_Json::encode(array('success' => false, 'message' => $message));
			die();
		}
		if ($file_size <= 0) {
			$message = 'Die von Ihnen gew&auml;hlte Datei konnte nicht hochgeladen werden.';
			echo Zend_Json::encode(array('success' => false, 'message' => $message));
			die();
		}
		
		// Validate file extension
		$path_info = pathinfo($upload['name']);
		$file_extension = $path_info["extension"];
		$is_valid_extension = false;
		foreach ($this->fileExtensions as $extension) {
			if (strcasecmp($file_extension, $extension) == 0) {
				$is_valid_extension = true;
				$file_extension = $extension;
				break;
			}
		}
		if (!$is_valid_extension) {
			$message = 'Die Datei besitzt einen Dateitypen der nicht unterst&uuml;tzt wird';
			echo Zend_Json::encode(array('success' => false, 'message' => $message));
			die();
		}
		
		// Check image size
		list($width, $height, $type, $attr) = getimagesize($upload['tmp_name']);
		if($width <= 0) {
			$message = 'Das Bild hat eine Breite von weniger als 0 Pixel.';
			echo Zend_Json::encode(array('success' => false, 'message' => $message));
			die();
		}
		
		// Image type related size checking
		switch($imageType) {
			case 'screenshot':
				if($width > 640 || $height > 960) {
					$message = 'Das Icon muss eine Gr&ouml;&szlig;e von 512 Pixel x 512 Pixel aufweisen. Bitte w&auml;hlen Sie ein anderes Bild als Icon.';
					echo Zend_Json::encode(array('success' => false, 'message' => $message));
					die();
				}
				break;
			case 'icon':
				if($width != 512 || $height != 512) {
					$message = 'Das Icon muss eine Gr&ouml;&szlig;e von 512 Pixel x 512 Pixel aufweisen. Bitte w&auml;hlen Sie ein anderes Bild als Icon.';
					echo Zend_Json::encode(array('success' => false, 'message' => $message));
					die();
				}
				break;
			case 'startup':
				if($width > 640 || $height > 960) {
					$message = 'Der Startup-Screen muss eine maximale Gr&ouml;&szlig;e von 640 Pixel x 960 Pixel aufweisen. Bitte w&auml;hlen Sie ein anderes Bild als Startup-Screen.';
					echo Zend_Json::encode(array('success' => false, 'message' => $message));
					die();
				}
				break;
			case 'logo':
			default:
				if($width > 320) {
					$message = 'Das Logo darf maximal eine Gr&ouml;&szlig;e von maximal 320 Pixeln aufweisen. Bitte w&auml;hlen Sie ein anderes Bild als Logo.';
					echo Zend_Json::encode(array('success' => false, 'message' => $message));
					die();
				}
				break;
		}
		
		// Set generic file name
		$upload['name'] = $filename . '.' . $file_extension;
		
		$path = $this->uploadPath . '/' . $upload['name'];
		
		// Process the file
		if (!@move_uploaded_file($upload["tmp_name"], $path)) {
			$message = 'Die Datei konnte nicht gespeichert werden.';
			echo Zend_Json::encode(array('success' => false, 'message' => $message));
			die();
		}

		if($imageType == 'logo') {
			return array('image' => $this->basePath . 'images/swag_mobiletemplate/' . $upload['name'], 'height' => $height);
		} elseif($imageType == 'icon') {
			$iconPath = $this->basePath . 'images/swag_mobiletemplate/' . $upload['name'];

			// Generate icon for appstore
			$this->createThumbnail($iconPath, 512, 512, 'appstore-icon', $file_extension);

			// Generate icon for iphone (retina display)
			$this->createThumbnail($iconPath, 114, 114, 'app-icon-iphone4', $file_extension);

			// Generate icon for ipad
			$this->createThumbnail($iconPath, 72, 72, 'app-icon-ipad', $file_extension);

			// ... for the rest
			$this->createThumbnail($iconPath, 57, 57, 'app-icon-iphone', $file_extension);

			return $this->basePath . 'images/swag_mobiletemplate/' . $upload['name'];
		} elseif($imageType == 'screenshot') {
			return $upload['name'];
		} else {
			return $this->basePath . 'images/swag_mobiletemplate/' . $upload['name'];
		}
    }

	/**
	 * Helper method which provides a easy to use way
	 * to resize images or create thumbnails on the
	 * fly
	 *
	 * @access private
     * @param str $originalImage
     * @param str $toWidth
     * @param str $toHeight
	 * @param str $name
	 * @param str $extension
     */
    private function createThumbnail($originalImage, $toWidth, $toHeight, $name, $extension)
    {
        // Get the original geometry and calculate scales
        list($width, $height) = getimagesize($originalImage);
        if($width < $toWidth){
            $toWidth = $width;
        }
        if($height < $toHeight){
            $toHeight = $height;
        }
        $xscale = $width / $toWidth;
        $yscale = $height / $toHeight;

        // Recalculate new size with default ratio
        if ($yscale > $xscale) {
            $new_width = round($width * (1 / $yscale));
            $new_height = round($height * (1 / $yscale));
        }
        else {
            $new_width = round($width * (1 / $xscale));
            $new_height = round($height * (1 / $xscale));
        }

        // Resize the original image
        $imageResized = imagecreatetruecolor($new_width, $new_height);
        if($extension !== 'png') {
            $imageTmp = imagecreatefromjpeg($originalImage);
        }
        else{
            $imageTmp = imagecreatefrompng($originalImage);
        }
        imagecopyresampled($imageResized, $imageTmp, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        $path = dirname($originalImage);
        $thumbnailFileName = $this->thumbNailPrefix.$name;

        if ($extension !== 'png') {
            imagejpeg($imageResized, $this->uploadPath . "/" . $thumbnailFileName.'.'.$extension);
        }
        else{
            imagepng($imageResized, $this->uploadPath . "/" . $thumbnailFileName.'.'.$extension);
        }
    }
}
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
class Shopware_Plugins_Frontend_SwagMobileTemplate_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

	/** 16px x 16px phone icon for the backend module (base64 encoded) */
	private static $icnBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABwElEQVQ4jY3SO6/TMBjG8b/jpAlJSi+0alVoYUEHmJC4iIlP0q1zJyYY+Q6Hjc6I4fA1GBAdGJCABVF0quoAp216cWI7DCAEDVLr0Xqen+zXFuys4+Nn+Ww2wxhDHIW4nodSCqVSms0Gw+FQ/J13d4Hx+C2vTk6o1qrcf/AQ13MZv3nNZDKh3+/vxotAkiTcvnOXXrfL5StdttstRzdv0Wp3UErtBxzp8eTpYxq1+E8h8APOFktePH+5HxACOldjKlEJbeSvkCvxqhdBFPpFwBiDsUu2uSQnR+CQ6pS1NhitD7iCgMn8A5dMiHAFYHE2Zc5PHfRBgOPwZfaO70uDyRVuZFGfGsSre2ht9gMgCIMQ31ekWY5aLXDKKxLxEWsPAIwxlCK4EHl4WuKlEr/uslydYa3dD2RZhso/Q64wQuP4gkRK1iWPTBef4b/A11Mol0NcE5JaSywVer0hVQecYLPZskk6eCIiME02WlNx55RWS7Jsuh+Yz88ZPXqPsWCFpFqtsfjxDaUUN46uFwBnd6NerxP4Pr7n0mm1uNbrEQYlKuWIOI4KQGEqo9Eon06nvz+NAHIApJS0220Gg8E/nZ864b4HQnsY4wAAAABJRU5ErkJggg==';

	/**
	 * Standard install method which creates the event listeners, the plugin configuration form,
	 * and a new menu entry in the backend.
	 *
	 * Note that this method fills in the default configuration
	 *
	 * @access public
	 * @return bool
	 */
	public function install()
	{
		$this->deletePreviousEntries();
		$this->createEvents();
		$this->createBackendMenuEntry();
		
		// Create database table
		Shopware()->Db()->query("CREATE TABLE `s_plugin_mobile_settings` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`name` VARCHAR( 255 ) NOT NULL ,
			`value` VARCHAR( 255 ) NOT NULL
			) ENGINE = MYISAM ;"
		);
		
		// Fill database table fields
		Shopware()->Db()->query("INSERT INTO `s_plugin_mobile_settings` (`name` ,`value`)
			VALUES 
			('supportedDevices', 'iPhone|iPod|Android'),
			('supportedPayments', '3|4|5|20'),
			('agbInfoID', '4'),
			('cancelRightID', '8'),
			('infoGroupName', 'gMobile'),
			('showNormalVersionLink', '1'),
			('useAsSubshop', '0'),
			('subshopID', '1'),
			('useSenchaIO', '0'),
			('useVoucher', '0'),
			('useNewsletter', '0'),
			('useComment', '0'),
			('colorTemplate', 'default'),
			('checkboxesGreen', '0'),
			('logoUpload', ''),
			('logoHeight', ''),
			('additionalCSS', ''),
			('iconUpload', ''),
			('startupUpload', ''),
			('screenshots', ''),
			('statusbarStyle', 'default'),
			('glossOnIcon', '1'),
			('apptitle', ''),
			('appversion', ''),
			('publishdate', ''),
			('keywords', ''),
			('contact_email', ''),
			('support_url', ''),
			('app_url', ''),
			('description', ''),
			('changelog', '');"
		);

		/** Add new mail template */
		$sql = "INSERT INTO `s_core_config_mails` (`id`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `htmlable`, `attachment`) VALUES (NULL, 'sMobileNativeApplicationRequest', 'info@example.com', 'info@example.com', 'Antrag - Native Applikation auf Basis von Shopware Mobile', 'Hallo Shopware-Team,\r\n\r\nich m�chte gerne eine native Applikation f�r meinen Shop bei Ihnen beantragen.\r\n\r\nDie Rahmeninformationen lauten wie folgt:\r\n\r\nAnsprechpartner: {sContactPerson}\r\n\r\nShop-Name: {sShop}\r\n\r\nShop-URL: {sShopURL}\r\n\r\nBeschreibung: {sMessage}\r\n\r\n', '', 0, 0, '');";
		Shopware()->Db()->query($sql);

		$form = $this->Form();
		$form->setElement('controllerbutton', 'Backendmodul aufrufen', array('label'=>'Shopware Mobile Backend Modul &ouml;ffnen','value'=>'','attributes'=>array('controller'=>'MobileTemplate','action'=>'skeleton')));
		$form->save();

		Shopware()->Cache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array(
			'Shopware_Config'
		));

		return true;
	}

	/**
	 * Standard uninstall method which just drops the configuration
	 * tables from DB
	 *
	 * @access public
	 * @return bool
	 */
	public function uninstall()
	{
		// Delete settings table
		Shopware()->Db()->query('DROP TABLE IF EXISTS  `s_plugin_mobile_settings`;');
		
		// Delete menu entry
		Shopware()->Db()->query(" DELETE IGNORE FROM `s_core_menu` WHERE `name` = 'Shopware Mobile'");

		/* Delete the mail template */
		Shopware()->Db()->query("DELETE IGNORE FROM `s_core_config_mails` WHERE `name` = 'sMobileNativeApplicationRequest'");
		
		// Delete previous entries
		$this->deletePreviousEntries();
		
		return true;
	}

	/**
	 * Helper method which subscribes the necessary events
	 *
	 * @access public
	 * @return void
	 */
	public function createEvents()
	{
		/* Subscribe events */
		$event = $this->createEvent(
	 		'Enlight_Controller_Dispatcher_ControllerPath_Frontend_MobileTemplate',
	 		'onGetControllerPath'
	 	);
	 	$this->subscribeEvent($event);

		$event = $this->createEvent(
 			'Enlight_Controller_Dispatcher_ControllerPath_Backend_MobileTemplate',
 			'onGetControllerPathBackend'
	 	);
	 	$this->subscribeEvent($event);

		$event = $this->createEvent(
			'Enlight_Controller_Action_PostDispatch',
			'onPostDispatch'
		);
		$this->subscribeEvent($event);

		$event = $this->createEvent(
			'Enlight_Controller_Action_PostDispatch_Frontend_Register',
			'onPostDispatchRegister'
		);
		$this->subscribeEvent($event);

		$event = $this->createEvent(
			'Shopware_Modules_Admin_SaveRegister_Start',
			'onSaveRegisterStart'
		);
		$this->subscribeEvent($event);

		$event = $this->createEvent(
			'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
			'mobileFinishAction'
		);
		$this->subscribeEvent($event);
	}

	/**
	 * Helper method whichs deletes old subscribers from the DB
	 *
	 * @access public
	 * @return void
	 */
	public function deletePreviousEntries()
	{
		$sql = "DELETE FROM s_core_subscribes WHERE listener LIKE 'Shopware_Plugins_Frontend_SwagMobileTemplate_Bootstrap%';";
		Shopware()->Db()->query($sql);
	}

	/**
	 * Helper method which creates the backend menu entry
	 * for the mobile template
	 *
	 * @access public
	 * @return void
	 */
	public function createBackendMenuEntry()
	{
		$parent = $this->Menu()->findOneBy('label', 'Marketing');
        $item = $this->createMenuItem(array(
		        'label' => 'Shopware Mobile',
	            'onclick' => 'openAction(\'MobileTemplate\');',
	            'class' => 'ico2 iphone',
	            'active' => 1,
	            'parent' => $parent,
	            'style' => 'background-position: 5px 5px;'
		));
		$this->Menu()->addItem($item);
		$this->Menu()->save();
	}


	/**
	 * Returns the meta informations of this plugin
	 *
	 * @access public
	 * @return mixed
	 */
	public function getInfo()
    {
    	return include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Meta.php');
    }
    
	/**
	 * Passes the plugin configuration to the view, handles the subshop
	 * support, checks if the the used device is supported and sets
	 * the backend module icon
	 *
	 * @static
	 * @access public
	 * @param Enlight_Event_EventArgs $args
	 * @return
	 */
    public static function onPostDispatch(Enlight_Event_EventArgs $args)
    {	
    	$request = $args->getSubject()->Request();
		$response = $args->getSubject()->Response();
		$view = $args->getSubject()->View();

		if(!$request->isDispatched() || $response->isException() || $request->getModuleName() !== 'frontend'){
			return;
		}


		$config = Shopware()->Db()->fetchAll('SELECT * FROM `s_plugin_mobile_settings`');
		$properties = array();
		foreach($config as $prop) {
			$properties[$prop['name']] = $prop['value'];
		}
		$config = $properties;

		$version = self::checkForMobileDevice($config['supportedDevices']);

	    // Set session value
	    if($config['useAsSubshop'] == 1) {
		    if(Shopware()->System()->sLanguage == $config['subshopID']) {
			    Shopware()->Session()->Mobile = 1;
		    } else {
			    Shopware()->Session()->Mobile = 0;
		    }
	    } else {
			if($request->sMobile == '1' && $request->sAction == 'useNormal') {
				Shopware()->Session()->Mobile = 0;
			} else if($request->sMobile == '1') {
				Shopware()->Session()->Mobile = 1;
			}
	    }


	    // Add icon for Backend module
		if($request->getModuleName() != 'frontend') {
			$view->extendsBlock('backend_index_css', '<style type="text/css">a.iphone { background-image: url("'. self::$icnBase64 .'"); background-repeat: no-repeat; }</style>', 'append');
			return;
		}
	    
	    // Merge template directories
	    $mobileSession = Shopware()->Session()->Mobile;
		if($version === 'mobile' && $mobileSession === 1) {
			$dirs = Shopware()->Template()->getTemplateDir();
			$newDirs = array_merge(array(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR), $dirs);
			Shopware()->Template()->setTemplateDir($newDirs);

			$basepath = Shopware()->Config()->BasePath;

			// Assign plugin configuration
			$view->assign('shopwareMobile', array(
				'additionalCSS'  => $config['additionalCSS'],
				'isUserLoggedIn' => Shopware()->Modules()->sAdmin()->sCheckUser(),
				'useNormalSite'  => $config['showNormalVersionLink'],
				'template'       => 'frontend'. DIRECTORY_SEPARATOR . '_resources'.DIRECTORY_SEPARATOR.'styles' . DIRECTORY_SEPARATOR . trim($config['colorTemplate']) . '.css',
				'useVoucher'     => $config['useVoucher'],
				'useNewsletter'  => $config['useNewsletter'],
				'useComment'     => $config['useComment'],
				'logoPath'       => $config['logoUpload'],
				'logoHeight'     => $config['logoHeight'],
				'iconPath'       => $config['iconUpload'],
				'glossOnIcon'    => $config['glossOnIcon'],
				'startUpPath'    => $config['startupUpload'],
				'statusBarStyle' => $config['statusbarStyle'],
				'payments'       => $config['supportedPayments'],
				'agbID'          => $config['agbInfoID'],
				'cancellationID' => $config['cancelRightID'],
				'checkboxGreen'  => $config['checkboxesGreen'],
				'basePath'       => $basepath
			));

		} else {
			if(!empty($mobileSession) && $mobileSession == 0) { $active = 1; } else { $active = 0; }

			$view->addTemplateDir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR);
			$view->assign('shopwareMobile', array(
				'active'     => $active,
				'useSubShop' => $config['useAsSubshop'],
				'subShopId'  => $config['subshopID'],
				'userAgents' => $config['supportedDevices'],
				'basePath'   => $request->getBasePath()
			));

			$view->extendsTemplate('frontend' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'swag_mobiletemplate' . DIRECTORY_SEPARATOR . 'index.tpl');
		}
    }

	/**
	 * Returns the path of the frontend controller which handles the
	 * whole mobile template
	 *
	 * @static
	 * @access public
	 * @return string
	 */
    public static function onGetControllerPath()
    {
    	return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MobileTemplate.php';
    }
    
    /**
	 * Returns the path of the backend controller which handles the whole
	 * backend module
	 *
	 * @static
	 * @access public
	 * @return string
	 */
    public static function onGetControllerPathBackend()
    {
    	return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MobileTemplateAdmin.php';
    }

	/**
	 * Refactors the error message string in a suitable format (utf8 encoded)
	 *
	 * @static
	 * @access public
	 * @param Enlight_Event_EventArgs $args
	 * @return void
	 */
	public static function onPostDispatchRegister(Enlight_Event_EventArgs $args)
	{
		$request = $args->getSubject()->Request();
		$response = $args->getSubject()->Response();
		$view = $args->getSubject()->View();
		$mobileSession = Shopware()->Session()->Mobile;

		if(!$mobileSession) {
			return;
		}

		$error_flags = array();
		if(!empty($view->register->personal->error_messages)) {
			$errors = '';
			foreach($view->register->personal->error_messages as $k => $v) {
				$errors = $errors . '<br/>' . utf8_encode($v);
			}
			$error_flags[] = $view->register->personal->error_flags;
		}

		$billingErrorFlags = array();
		if(!empty($view->register->billing->error_messages)) {
			foreach($view->register->billing->error_messages as $k => $v) {
				$errors = $errors . '<br/>' . utf8_encode($v);
			}
			$error_flags[] = $view->register->billing->error_flags;
		}

		$view->registerErrors = $errors;
		$view->error_flags = $error_flags;
	}

	/**
	 * Corrects the character encoding for the registration
	 * informations due to the fact that sencha touch needs
	 * the strings utf8 encoded.
	 *
	 * @static
	 * @access public
	 * @param Enlight_Event_EventArgs $args
	 * @return void
	 */
	public static function onSaveRegisterStart(Enlight_Event_EventArgs $args)
	{
		$subject = $args->getSubject();
		$session = Shopware()->Session();

		if(Shopware()->Session()->Mobile == 1 && !empty($session['sRegister']['billing'])) {
			foreach($session['sRegister']['billing'] as $key => $value) {
				$value = utf8_decode($value);
				$value = htmlentities($value);
				$session['sRegister']['billing'][$key] = $value;
			}
		}
	}

	/**
	 * Helper method which checks if the payment was processed
	 * by an external payment provider (like PayPal or Heidelpay),
	 * sets the mandatory informations in the view and loads the
	 * whole base template structure from the index/index.tpl
	 *
	 * Note that this method will be only come in place if the
	 * request wasn't a XML HTTP request
	 *
	 * @static
	 * @param Enlight_Event_EventArgs $args
	 */
	public static function mobileFinishAction(Enlight_Event_EventArgs $args)
	{
		$subject = $args->getSubject();
		$request = $subject->Request();
		$view = $args->getSubject()->View();
		$mobileSession = Shopware()->Session()->Mobile;

		$uniqueID = $request->getParam('sUniqueID');
		$paymentType = $request->getParam('paymentType');


		/** Payment successfully */
		if($request->getActionName() === 'finish' && !$request->isXmlHttpRequest() && !empty($uniqueID)) {
				
			/** PayPal Express Mobile payment */
			$result = Shopware()->Db()->query('SELECT * FROM s_order WHERE transactionID = ?', array($request->getParam('sUniqueID')));
			$result = $result->fetch();

			$view->assign('lastOrder', array(
				'ordernumber' => $result['ordernumber'],
				'invoice_amount' => $result['invoice_amount'],
				'date' => date('H:i:s d.m.Y', strtotime($result['ordertime'])),
				'payment_method' => 'PayPal'
			));
		}

		/** Payment canceled */
		if($request->getActionName() === 'confirm' && !$request->isXmlHttpRequest()) {
			if(!empty($paymentType) && $paymentType === 'Sale') {
				$view->assign('canceledOrder', true);
			}
		}

		/** Load the basic structure */
		if(!$request->isXmlHttpRequest() && $mobileSession === 1) {
			$view->loadTemplate(dirname(__FILE__) . '/Views/frontend/index/index.tpl');
		}
	}

	/**
	 * Checks against the user agent if the user's
	 * currently used device is supported by the
	 * mobile template
	 *
	 * @access private
	 * @param $devices
	 * @return string
	 */
    private function checkForMobileDevice($devices) {
    	$agent = $_SERVER['HTTP_USER_AGENT'];
    	$device = 'desktop';

    	if(preg_match('/(' . $devices . ')/i', $agent)) {
    		$device = 'mobile';
    	}

		return $device;
    }
}
<?php

namespace Detain\MyAdminVpsCpanel;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminVpsCpanel
 */
class Plugin {

	public static $name = 'CPanel VPS Addon';
	public static $description = 'Allows selling of CPanel Licenses as VPS Addon.  cPanel is an online (Linux-based) web hosting control panel that provides a graphical interface and automation tools designed to simplify the process of hosting a web site. cPanel utilizes a 3 tier structure that provides capabilities for administrators, resellers, and end-user website owners to control the various aspects of website and server administration through a standard web browser.  More info at https://cpanel.com/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'addon';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getAddon(GenericEvent $event) {
		$serviceOrder = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->setModule(self::$module)
			->set_text('CPanel')
			->set_cost(VPS_CPANEL_COST)
			->set_require_ip(TRUE)
			->set_enable([__CLASS__, 'doEnable'])
			//->set_verify([__CLASS__, 'doEnable'])
			->set_disable([__CLASS__, 'doDisable'])
			->register();
		$serviceOrder->addAddon($addon);
	}

	/**
	 * @param \Service_Order $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 */
	public static function doEnable(\Service_Order $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$serviceTypes = run_event('get_service_types', FALSE, self::$module);
		$settings = get_module_settings(self::$module);
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__);
		function_requirements('get_cpanel_license_data_by_ip');
		$serviceExtra = get_cpanel_license_data_by_ip($serviceInfo[$settings['PREFIX'].'_ip']);
		// check if activated,if not then activate cpanel license
		if (($serviceExtra['valid'] != 1) && ($serviceInfo[$settings['PREFIX'].'_ip'] != '')) {
			function_requirements('activate_cpanel');
			// 188 = openvz , 1814 = kvm
			if (in_array($serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_category'], [get_service_define('OPENVZ'), get_service_define('SSD_OPENVZ'), get_service_define('VIRTUOZZO'), get_service_define('OPENVZ')]))
				activate_cpanel($serviceInfo[$settings['PREFIX'].'_ip'], 188);
			else
				activate_cpanel($serviceInfo[$settings['PREFIX'].'_ip'], 1814);
			$GLOBALS['tf']->history->add($settings['TABLE'], 'add_cpanel', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
		}
	}

	/**
	 * @param \Service_Order $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 */
	public static function doDisable(\Service_Order $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
		function_requirements('get_cpanel_license_data_by_ip');
		$serviceExtra = get_cpanel_license_data_by_ip($serviceInfo[$settings['PREFIX'].'_ip']);
		// check if activated,if so then deactivate cpanel license
		if (($serviceExtra['valid'] != 1) && ($serviceInfo[$settings['PREFIX'].'_ip'] != '')) {
			function_requirements('deactivate_cpanel');
			deactivate_cpanel($serviceInfo[$settings['PREFIX'].'_ip']);
			$GLOBALS['tf']->history->add($settings['TABLE'], 'del_cpanel', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
			add_output(self::$name.' Canceled');
			$email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'.$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>Repeat Invoice: '.$repeatInvoiceId.'<br>Description: '.self::$name.'<br>';
			$subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled '.self::$name;
			$headers = '';
			$headers .= 'MIME-Version: 1.0'.EMAIL_NEWLINE;
			$headers .= 'Content-type: text/html; charset=UTF-8'.EMAIL_NEWLINE;
			$headers .= 'From: '.$settings['TITLE'].' <'.$settings['EMAIL_FROM'].'>'.EMAIL_NEWLINE;
			admin_mail($subject, $email, $headers, FALSE, 'admin_email_vps_cpanel_canceled.tpl');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Addon Costs', 'vps_cpanel_cost', 'VPS CPanel License:', 'This is the cost for purchasing a cpanel license on top of a VPS.', $settings->get_setting('VPS_CPANEL_COST'));
	}

}

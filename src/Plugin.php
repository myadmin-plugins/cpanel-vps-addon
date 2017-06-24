<?php

namespace Detain\MyAdminVpsCpanel;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Cpanel Licensing VPS Addon';
	public static $description = 'Allows selling of Cpanel Server and VPS License Types.  More info at https://www.netenberg.com/cpanel.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a cpanel license. Allow 10 minutes for activation.';
	public static $module = 'vps';
	public static $type = 'addon';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'vps.load_addons' => [__CLASS__, 'getAddon'],
			'vps.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function getAddon(GenericEvent $event) {
		$serviceOrder = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->setModule('vps')
			->set_text('CPanel')
			->set_cost(VPS_CPANEL_COST)
			->set_require_ip(TRUE)
			->set_enable([__CLASS__, 'doEnable'])
			->set_disable([__CLASS__, 'doDisable'])
			->register();
		$serviceOrder->add_addon($addon);
	}

	public static function doEnable(\Service_Order $serviceOrder, $repeatInvoiceId, $regex_match = false) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings($serviceOrder->getModule());
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		function_requirements('get_cpanel_license_data_by_ip');
		$serviceExtra = get_cpanel_license_data_by_ip($serviceInfo[$settings['PREFIX'].'_ip']);
		// check if activated,if not then activate cpanel license
		if (($serviceExtra['valid'] != 1) && ($serviceInfo[$settings['PREFIX'].'_ip'] != '')) {
			function_requirements('activate_cpanel');
			// 188 = openvz , 1814 = kvm
			if (in_array($serviceInfo[$settings['PREFIX'].'_type'], array(SERVICE_TYPES_KVM_LINUX, SERVICE_TYPES_CLOUD_KVM_LINUX), TRUE))
				activate_cpanel($serviceInfo[$settings['PREFIX'].'_ip'], 1814);
			else
				activate_cpanel($serviceInfo[$settings['PREFIX'].'_ip'], 188);
			$GLOBALS['tf']->history->add($settings['TABLE'], 'add_cpanel', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
		}
	}

	public static function doDisable(\Service_Order $serviceOrder) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings($serviceOrder->getModule());
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		function_requirements('get_cpanel_license_data_by_ip');
		$serviceExtra = get_cpanel_license_data_by_ip($serviceInfo[$settings['PREFIX'].'_ip']);
		// check if activated,if so then deactivate cpanel license
		if (($serviceExtra['valid'] != 1) && ($serviceInfo[$settings['PREFIX'].'_ip'] != '')) {
			function_requirements('deactivate_cpanel');
			deactivate_cpanel($serviceInfo[$settings['PREFIX'].'_ip']);
			$GLOBALS['tf']->history->add($settings['TABLE'], 'del_cpanel', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
		}
	}

	public static function getSettings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Addon Costs', 'vps_cpanel_cost', 'VPS CPanel License:', 'This is the cost for purchasing a cpanel license on top of a VPS.', $settings->get_setting('VPS_CPANEL_COST'));
	}

}

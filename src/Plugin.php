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

	public static function Hooks() {
		return [
			'vps.load_addons' => ['Detain\MyAdminVpsCpanel\Plugin', 'Load'],
			'vps.settings' => ['Detain\MyAdminVpsCpanel\Plugin', 'Settings'],
		];
	}

	public static function Load(GenericEvent $event) {
		$service_order = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->set_module('vps')
			->set_text('CPanel')
			->set_cost(VPS_CPANEL_COST)
			->set_require_ip(true)
			->set_enable(['Detain\MyAdminVpsCpanel\Plugins', 'Enable'])
			->set_disable(['Detain\MyAdminVpsCpanel\Plugins', 'Disable'])
			->register();
		$service_order->add_addon($addon);
	}

	public static function Enable($service_order) {
		$service_info = $service_order->get_service_info();
		$settings = get_module_settings($service_order->get_module());
		require_once 'include/licenses/license.functions.inc.php';
		function_requirements('get_cpanel_license_data_by_ip');
		$service_extra = get_cpanel_license_data_by_ip($service_info[$settings['PREFIX'].'_ip']);
		// check if activated,if not then activate cpanel license
		if (($service_extra['valid'] != 1) && ($service_info[$settings['PREFIX'].'_ip'] != '')) {
			function_requirements('activate_cpanel');
			// 188 = openvz , 1814 = kvm
			if (in_array($service_info[$settings['PREFIX'].'_type'], array(SERVICE_TYPES_KVM_LINUX, SERVICE_TYPES_CLOUD_KVM_LINUX), true))
				activate_cpanel($service_info[$settings['PREFIX'].'_ip'], 1814);
			else
				activate_cpanel($service_info[$settings['PREFIX'].'_ip'], 188);
			$GLOBALS['tf']->history->add($settings['TABLE'], 'add_cpanel', $service_info[$settings['PREFIX'].'_id'], $service_info[$settings['PREFIX'].'_ip'], $service_info[$settings['PREFIX'].'_custid']);
		}
	}

	public static function Disable($service_order) {
		$service_info = $service_order->get_service_info();
		$settings = get_module_settings($service_order->get_module());
		require_once 'include/licenses/license.functions.inc.php';
		function_requirements('get_cpanel_license_data_by_ip');
		$service_extra = get_cpanel_license_data_by_ip($service_info[$settings['PREFIX'].'_ip']);
		// check if activated,if so then deactivate cpanel license
		if (($service_extra['valid'] != 1) && ($service_info[$settings['PREFIX'].'_ip'] != '')) {
			function_requirements('deactivate_cpanel');
			deactivate_cpanel($service_info[$settings['PREFIX'].'_ip']);
			$GLOBALS['tf']->history->add($settings['TABLE'], 'del_cpanel', $service_info[$settings['PREFIX'].'_id'], $service_info[$settings['PREFIX'].'_ip'], $service_info[$settings['PREFIX'].'_custid']);
		}
	}

	public static function Settings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Addon Costs', 'vps_cpanel_cost', 'VPS CPanel License:', 'This is the cost for purchasing a cpanel license on top of a VPS.', $settings->get_setting('VPS_CPANEL_COST'));
	}

}

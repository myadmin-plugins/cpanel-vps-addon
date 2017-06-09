<?php

namespace Detain\MyAdminVpsCpanel;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Load(GenericEvent $event) {
		$service_order = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->set_module('vps')->set_text('CPanel')->set_cost(VPS_CPANEL_COST)
			->set_require_ip(true)->set_enable(function() use ($service_order) {
				$service_info = $service_order->get_service_info();
				$settings = get_module_settings($service_order->get_module());
				require_once 'include/licenses/license.functions.inc.php';
				function_requirements('get_license_data');
				function_requirements('activate_cpanel');
				$service_extra = get_license_data($service_info[$settings['PREFIX'].'_ip']);
				// check if activated,if not then activate cpanel license
				if (($service_extra['valid'] != 1) && ($service_info[$settings['PREFIX'].'_ip'] != '')) {
					// 188 = openvz , 1814 = kvm
					if (in_array($service_info[$settings['PREFIX'].'_type'], array(SERVICE_TYPES_KVM_LINUX, SERVICE_TYPES_CLOUD_KVM_LINUX), true))
						activate_cpanel($service_info[$settings['PREFIX'].'_ip'], 1814);
					else
						activate_cpanel($service_info[$settings['PREFIX'].'_ip'], 188);
					$GLOBALS['tf']->history->add($settings['TABLE'], 'add_cpanel', $service_info[$settings['PREFIX'].'_id'], $service_info[$settings['PREFIX'].'_ip'], $service_info[$settings['PREFIX'].'_custid']);
				}
			})->set_disable(function($service) {
			})->register();
		$service_order->add_addon($addon);
	}

	public static function Settings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Addon Costs', 'vps_cpanel_cost', 'VPS CPanel License:', 'This is the cost for purchasing a cpanel license on top of a VPS.', $settings->get_setting('VPS_CPANEL_COST'));
	}

}

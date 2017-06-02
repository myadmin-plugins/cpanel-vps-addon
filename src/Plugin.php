<?php

namespace Detain\MyAdminVpsCpanel;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Load(GenericEvent $event) {
		$service = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->set_module('vps')->set_text('CPanel')->set_cost(VPS_CPANEL_COST)
			->set_require_ip(true)->set_enable(function() {
				require_once 'include/licenses/license.functions.inc.php';
				function_requirements('get_license_data');
				function_requirements('activate_cpanel');
				$service_extra = get_license_data($this->service_info[$this->settings['PREFIX'].'_ip']);
				// check if activated,if not then activate cpanel license
				if (($service_extra['valid'] != 1) && ($ip != '')) {
					// 188 = openvz , 1814 = kvm
					if (in_array($service_type, array(SERVICE_TYPES_KVM_LINUX, SERVICE_TYPES_CLOUD_KVM_LINUX), true))
						activate_cpanel($ip, 1814);
					else
						activate_cpanel($ip, 188);
					$GLOBALS['tf']->history->add($settings['TABLE'], 'add_'.$this->get_short(), $this->service_info[$this->settings['PREFIX'].'_id'], $this->service_info[$this->settings['PREFIX'].'_ip'], $this->service_info[$this->settings['PREFIX'].'_custid']);
				}
			})->set_disable(function() {
			})->register();
	}

}

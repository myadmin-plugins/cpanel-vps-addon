<?php
/**
 * VPS Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category VPS
 */

/**
 * Adds CPanel to a VPS
 * @return void
 */
function vps_add_cpanel() {
	function_requirements('class.AddServiceAddon');
	$addon = new AddServiceAddon();
	$addon->load(__FUNCTION__, 'CPanel', 'vps', VPS_CPANEL_COST);
	$addon->process();
}

<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_cpanel define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Cpanel Licensing VPS Addon',
	'description' => 'Allows selling of Cpanel Server and VPS License Types.  More info at https://www.netenberg.com/cpanel.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a cpanel license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-cpanel-vps-addon',
	'repo' => 'https://github.com/detain/myadmin-cpanel-vps-addon',
	'version' => '1.0.0',
	'type' => 'addon',
	'hooks' => [
		'vps.load_addons' => ['Detain\MyAdminVpsCpanel\Plugin', 'Load'],
		'vps.settings' => ['Detain\MyAdminVpsCpanel\Plugin', 'Settings'],
		/* 'function.requirements' => ['Detain\MyAdminVpsCpanel\Plugin', 'Requirements'],
		'licenses.activate' => ['Detain\MyAdminVpsCpanel\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminVpsCpanel\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminVpsCpanel\Plugin', 'Menu'] */
	],
];

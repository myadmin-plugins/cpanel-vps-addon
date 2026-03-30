---
name: myadmin-plugin-structure
description: Scaffolds a new MyAdmin plugin class following the src/Plugin.php structure with static props, getHooks(), getRequirements(), getAddon(), getSettings(), doEnable(), doDisable(). Use when user says 'new plugin', 'add plugin class', 'create addon plugin', 'scaffold plugin'. Do NOT use for modifying existing hooks or editing non-Plugin.php files.
---
# myadmin-plugin-structure

## Critical

- All five static properties (`$name`, `$description`, `$help`, `$module`, `$type`) MUST be `public static` — the plugin loader reads them directly.
- `getHooks()`, `getRequirements()`, `getAddon()`, `getSettings()` MUST be `public static`.
- Hook keys for `load_addons` and `settings` MUST use `self::$module` concatenation — never hardcode the module string.
- Use tabs for indentation throughout (see `.scrutinizer.yml`).
- Never use PDO — use MyAdmin DB helpers (`get_module_db()`, `make_insert_query()`).
- `doEnable`/`doDisable` signature is always `(\ ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)`.

## Instructions

1. **Create `src/Plugin.php`** under the new package root. Use this exact skeleton:

```php
<?php

namespace Detain\MyAdmin{AddonName};

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdmin{AddonName}
 */
class Plugin
{
	public static $name = '{Human Readable Addon Name}';
	public static $description = '{Full description of the addon.}';
	public static $help = '';
	public static $module = 'vps';   // the MyAdmin module this addon attaches to
	public static $type = 'addon';

	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			'function.requirements'        => [__CLASS__, 'getRequirements'],
			self::$module.'.load_addons'   => [__CLASS__, 'getAddon'],
			self::$module.'.settings'      => [__CLASS__, 'getSettings'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		/** @var \MyAdmin\Plugins\Loader $loader */
		$loader = $event->getSubject();
		$loader->add_page_requirement('{module}_add_{addon}', '/../vendor/detain/myadmin-{addon}-{module}-addon/src/{module}_add_{addon}.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getAddon(GenericEvent $event)
	{
		$serviceOrder = $event->getSubject();
		function_requirements('class.AddonHandler');
		$addon = new \AddonHandler();
		$addon->setModule(self::$module)
			->set_text('{AddonLabel}')
			->set_text_match('{AddonLabel} (.*) Accounts')
			->set_cost({MODULE}_{ADDON}_COST)
			->set_require_ip(true)
			->setEnable([__CLASS__, 'doEnable'])
			->setDisable([__CLASS__, 'doDisable'])
			->register();
		$serviceOrder->addAddon($addon);
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                 $repeatInvoiceId
	 * @param bool            $regexMatch
	 */
	public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
	{
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		function_requirements('{check_license_by_ip_func}');
		$serviceExtra = {check_license_by_ip_func}($serviceInfo[$settings['PREFIX'].'_ip']);
		if (($serviceExtra === false || $serviceExtra['valid'] != 1) && $serviceInfo[$settings['PREFIX'].'_ip'] != '') {
			function_requirements('{activate_func}');
			{activate_func}($serviceInfo[$settings['PREFIX'].'_ip']);
			$GLOBALS['tf']->history->add($settings['TABLE'], 'add_{addon}', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
		}
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                 $repeatInvoiceId
	 * @param bool            $regexMatch
	 */
	public static function doDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
	{
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		function_requirements('{check_license_by_ip_func}');
		$serviceExtra = {check_license_by_ip_func}($serviceInfo[$settings['PREFIX'].'_ip']);
		if ($serviceExtra !== false && $serviceExtra['valid'] == 1 && $serviceInfo[$settings['PREFIX'].'_ip'] != '') {
			function_requirements('{deactivate_func}');
			{deactivate_func}($serviceInfo[$settings['PREFIX'].'_ip']);
			$GLOBALS['tf']->history->add($settings['TABLE'], 'del_{addon}', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
			add_output(self::$name.' Canceled');
			$email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'.$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>Repeat Invoice: '.$repeatInvoiceId.'<br>Description: '.self::$name.'<br>';
			$subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled '.self::$name;
			(new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/{module}_{addon}_canceled.tpl');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/** @var \MyAdmin\Settings $settings **/
		$settings = $event->getSubject();
		$settings->setTarget('module');
		$settings->add_text_setting(self::$module, _('Addon Costs'), '{module}_{addon}_cost', _('{Module} {Addon} License'), _('Cost for the {addon} license on top of a {module}.'), $settings->get_setting('{MODULE}_{ADDON}_COST'));
		$settings->setTarget('global');
	}
}
```

   Verify: class has exactly 5 static properties and 7 public methods before proceeding.

2. **Create `src/{module}_add_{addon}.php`** — procedural file, no namespace, with docblock:

```php
<?php
/**
 * {Module} Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category {Module}
 */

/**
 * Adds {Addon} to a {Module}
 * @return void
 */
function {module}_add_{addon}()
{
	function_requirements('class.AddServiceAddon');
	$addon = new AddServiceAddon();
	$addon->load(__FUNCTION__, '{AddonLabel}', '{module}', {MODULE}_{ADDON}_COST);
	$addon->process();
}
```

3. **Update `composer.json`** — set `"type": "myadmin-plugin"` and add PSR-4 autoload entry: `"Detain\\MyAdmin{AddonName}\\": "src/"`.

4. **Run tests** to verify structure: `vendor/bin/phpunit tests/ -v`

## Examples

**User says:** "Create a new VPS addon plugin for Softaculous licenses"

**Actions taken:**
- `src/Plugin.php`: `$name = 'Softaculous VPS Addon'`, `$module = 'vps'`, `$type = 'addon'`
- `getHooks()` returns keys `function.requirements`, `vps.load_addons`, `vps.settings`
- `getAddon()`: `set_text('Softaculous')`, `set_text_match('Softaculous (.*) Accounts')`, `set_cost(VPS_SOFTACULOUS_COST)`
- `getSettings()`: `add_text_setting('vps', _('Addon Costs'), 'vps_softaculous_cost', ...)`
- `src/vps_add_softaculous.php`: `$addon->load(__FUNCTION__, 'Softaculous', 'vps', VPS_SOFTACULOUS_COST)`
- History action tags: `'add_softaculous'` / `'del_softaculous'`
- Cancel template: `'admin/vps_softaculous_canceled.tpl'`

**Result:** Plugin registers 3 hooks, addon cost constant follows `{MODULE}_{ADDON}_COST` pattern.

## Common Issues

- **"Class 'AddonHandler' not found"** — missing `function_requirements('class.AddonHandler')` before `new \AddonHandler()` in `getAddon()`.
- **Hook never fires for `load_addons`/`settings`** — hook key was hardcoded as `'vps.load_addons'` instead of `self::$module.'.load_addons'`. Fix: always use `self::$module` concatenation.
- **Test `testStaticPropertyCount` fails with count 6** — an extra static property was added. Plugin must have exactly 5: `$name`, `$description`, `$help`, `$module`, `$type`.
- **`doEnable`/`doDisable` not called** — method not registered: confirm `->setEnable([__CLASS__, 'doEnable'])` and `->setDisable([__CLASS__, 'doDisable'])` are chained before `->register()` in `getAddon()`.
- **Settings not persisting** — missing `$settings->setTarget('global')` after `add_text_setting()` in `getSettings()`. Both `setTarget('module')` before and `setTarget('global')` after are required.

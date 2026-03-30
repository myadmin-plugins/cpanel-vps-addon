---
name: cpanel-addon-handler
description: Implements doEnable/doDisable methods for cPanel VPS addon using AddonHandler, get_cpanel_license_data_by_ip, activate_cpanel/deactivate_cpanel, myadmin_log, and history tracking. Use when user says 'add enable logic', 'implement disable', 'handle license activation', 'wire up cpanel addon'. Do NOT use for non-cPanel addons or for getAddon/getSettings registration.
---
# cpanel-addon-handler

## Critical

- Always call `function_requirements('get_cpanel_license_data_by_ip')` before using `get_cpanel_license_data_by_ip()` — do not assume it is autoloaded.
- Always `require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php'` at the top of both `doEnable` and `doDisable`.
- Never activate if `$serviceInfo[$settings['PREFIX'].'_ip']` is empty — guard every IP-dependent call.
- Never call `activate_cpanel` if the license is already valid (`$serviceExtra['valid'] == 1`).
- Never call `deactivate_cpanel` if the license is not active (`$serviceExtra === false || $serviceExtra['valid'] != 1`).
- Use `self::$module` (`'vps'`) everywhere — never hardcode the string `'vps'`.

## Instructions

1. **Declare the method signature** — both methods share the same signature:
   ```php
   public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
   public static function doDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
   ```
   Verify `\ServiceHandler` is the type hint (not `\AddonHandler`).

2. **Extract service info and settings** (identical in both methods):
   ```php
   $serviceInfo = $serviceOrder->getServiceInfo();
   $settings = get_module_settings(self::$module);
   require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
   ```
   Verify `$settings['PREFIX']`, `$settings['TABLE']`, `$settings['TBLNAME']` are all available after this call.

3. **Log the action** immediately after step 2:
   ```php
   // doEnable:
   myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
   // doDisable:
   myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
   ```

4. **Check current license state** before taking action:
   ```php
   function_requirements('get_cpanel_license_data_by_ip');
   $serviceExtra = get_cpanel_license_data_by_ip($serviceInfo[$settings['PREFIX'].'_ip']);
   ```
   Verify `$serviceExtra` is either `false` or an array with a `valid` key.

5. **doEnable — activate only if not already active**:
   ```php
   if (($serviceExtra === false || $serviceExtra['valid'] != 1) && $serviceInfo[$settings['PREFIX'].'_ip'] != '') {
       function_requirements('activate_cpanel');
       activate_cpanel($serviceInfo[$settings['PREFIX'].'_ip'], 31369);
       $GLOBALS['tf']->history->add($settings['TABLE'], 'add_cpanel',
           $serviceInfo[$settings['PREFIX'].'_id'],
           $serviceInfo[$settings['PREFIX'].'_ip'],
           $serviceInfo[$settings['PREFIX'].'_custid']);
   }
   ```
   The second argument to `activate_cpanel` is always `31369` (cPanel license type ID).

6. **doDisable — deactivate only if currently active**:
   ```php
   if ($serviceExtra !== false && $serviceExtra['valid'] == 1 && $serviceInfo[$settings['PREFIX'].'_ip'] != '') {
       function_requirements('deactivate_cpanel');
       deactivate_cpanel($serviceInfo[$settings['PREFIX'].'_ip']);
       $GLOBALS['tf']->history->add($settings['TABLE'], 'del_cpanel',
           $serviceInfo[$settings['PREFIX'].'_id'],
           $serviceInfo[$settings['PREFIX'].'_ip'],
           $serviceInfo[$settings['PREFIX'].'_custid']);
       add_output(self::$name.' Canceled');
       $email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'
           .$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>'
           .'Repeat Invoice: '.$repeatInvoiceId.'<br>Description: '.self::$name.'<br>';
       $subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled '.self::$name;
       (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/vps_cpanel_canceled.tpl');
   }
   ```
   Note: `doEnable` does NOT send an email; only `doDisable` does.

7. **Register both callbacks** in `getAddon` via `AddonHandler`:
   ```php
   $addon->setEnable([__CLASS__, 'doEnable'])
         ->setDisable([__CLASS__, 'doDisable']);
   ```

## Examples

**User says:** "Add enable and disable logic for the cPanel addon."

**Actions taken:**
- Added `require_once` for `license.functions.inc.php` in both methods.
- Called `myadmin_log` with `'info'` level and service ID.
- Called `get_cpanel_license_data_by_ip` and guarded activation/deactivation on `$serviceExtra['valid']` and non-empty IP.
- Used `activate_cpanel($ip, 31369)` in `doEnable`, `deactivate_cpanel($ip)` in `doDisable`.
- Added `$GLOBALS['tf']->history->add(...)` with `'add_cpanel'`/`'del_cpanel'` action string.
- Sent admin cancellation email only in `doDisable` using `admin/vps_cpanel_canceled.tpl`.

**Result:** Matches `src/Plugin.php:76-116` exactly.

## Common Issues

- **`Call to undefined function get_cpanel_license_data_by_ip()`**: Missing `function_requirements('get_cpanel_license_data_by_ip')` before the call. Add it on the line immediately before.
- **`Call to undefined function activate_cpanel()`**: Missing `function_requirements('activate_cpanel')` inside the guard block. Each function must be loaded individually.
- **License activated every run even when already active**: The `doEnable` guard is wrong. Check `$serviceExtra === false || $serviceExtra['valid'] != 1` — not just `=== false`.
- **`require_once` path wrong / file not found**: Path is relative to `__DIR__` (`src/`), so it must be `__DIR__.'/../../../../include/licenses/license.functions.inc.php'`. Count the `../` levels from `vendor/detain/myadmin-cpanel-vps-addon/src/` to the repo root.
- **History not recording**: Confirm argument order is `($table, $action, $id, $ip, $custid)` — swapping `$id` and `$ip` silently stores wrong data.
- **No cancellation email sent on disable**: Email block must be inside the `$serviceExtra['valid'] == 1` guard in `doDisable`, not outside it.
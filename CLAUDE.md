# cPanel VPS Addon — MyAdmin Plugin

MyAdmin plugin that provisions/deprovisions cPanel licenses as VPS addons via the `Detain\MyAdminVpsCpanel` namespace.

## Commands

```bash
composer install                              # install deps
vendor/bin/phpunit tests/ -v                 # run tests
vendor/bin/phpunit --coverage-html build/coverage              # coverage report
```

## Architecture

- **Entry**: `src/Plugin.php` — registers hooks via `getHooks()` using `Symfony\Component\EventDispatcher\GenericEvent`
- **Addon function**: `src/vps_add_cpanel.php` — procedural, no namespace, uses `AddServiceAddon`
- **Tests**: `tests/PluginTest.php` · `tests/VpsAddCpanelFileTest.php` — PHPUnit 9.6, `Detain\MyAdminVpsCpanel\Tests` namespace
- **Autoload**: PSR-4 `Detain\MyAdminVpsCpanel\` → `src/` · dev tests → `tests/`
- **CI/CD**: `.github/` contains workflows for automated testing and deployment pipelines
- **IDE config**: `.idea/` contains inspectionProfiles, deployment.xml, and encodings.xml for JetBrains IDE settings

## Plugin Hook Pattern

```php
public static function getHooks(): array {
    return [
        'function.requirements'        => [__CLASS__, 'getRequirements'],
        self::$module.'.load_addons'   => [__CLASS__, 'getAddon'],
        self::$module.'.settings'      => [__CLASS__, 'getSettings'],
    ];
}
```

## AddonHandler Registration (in `getAddon`)

```php
$addon = new \AddonHandler();
$addon->setModule(self::$module)
    ->set_text('CPanel')
    ->set_text_match('CPanel (.*) Accounts')
    ->set_cost(VPS_CPANEL_COST)
    ->set_require_ip(true)
    ->setEnable([__CLASS__, 'doEnable'])
    ->setDisable([__CLASS__, 'doDisable'])
    ->register();
$serviceOrder->addAddon($addon);
```

## Enable/Disable Pattern

- Get service info: `$serviceInfo = $serviceOrder->getServiceInfo()`
- Get settings: `$settings = get_module_settings(self::$module)`
- Log: `myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id'])`
- Load functions: `function_requirements('get_cpanel_license_data_by_ip')`
- Check by IP: `get_cpanel_license_data_by_ip($serviceInfo[$settings['PREFIX'].'_ip'])`
- Activate: `activate_cpanel($ip, 31369)` · Deactivate: `deactivate_cpanel($ip)`
- History: `$GLOBALS['tf']->history->add($settings['TABLE'], 'add_cpanel', $id, $ip, $custid)`
- Cancel email: `(new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/vps_cpanel_canceled.tpl')`

## Conventions

- Tabs for indentation (see `.scrutinizer.yml`)
- `camelCase` for parameters/properties
- Docblocks required: `@author`, `@package MyAdmin`, `@category VPS`, `@return void` on functions in `src/vps_add_cpanel.php`
- `VPS_CPANEL_COST` constant (not a variable) used for addon pricing
- `self::$module` is always `'vps'`
- No PDO — MyAdmin DB helpers only
- Commit messages: lowercase, descriptive

## Key Settings

- `VPS_CPANEL_COST` — set via `$settings->add_text_setting()` in `getSettings()`
- `$settings['PREFIX']` — used to key into `$serviceInfo` (e.g. `_ip`, `_id`, `_custid`, `_hostname`)
- `$settings['TABLE']` / `$settings['TBLNAME']` — history/email context

## Plugin contract harness

This package is on the shared contract harness from `detain/myadmin-plugin-installer`.
`tests/ContractTest.php` is **generated** — run `composer myadmin:scaffold-tests` (add
`--force --write` to re-emit it), never hand-edit it.

The harness **executes** the plugin: it defines the bare constants the class body references
and then calls `getHooks()`, `getSettings()`, `getMenu()`, `apiRegister()` and — for
`type=service` packages — the activate/deactivate/change-ip/queue handlers, for real.

**So do not write reflection-only tests for the plugin class.** Asserting a handler exists,
is public, is static and takes one parameter passes whether or not the handler works; three
production bugs in this fleet were sitting behind assertions of exactly that shape. Older
guidance in this repo that says those methods must not be called predates the harness.

The harness is **additive**: it runs alongside this package's existing tests, and nothing is
deleted to make room for it. Run the whole suite, never `--filter ContractTest` alone — the
contract class primes constants and calls `register_module()`, neither of which can be undone.

See the `plugin-contract-tests` skill for the full workflow, and `docs/testing-harness.md` in
the installer.

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

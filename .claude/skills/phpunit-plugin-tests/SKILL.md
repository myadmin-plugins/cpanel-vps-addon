---
name: phpunit-plugin-tests
description: Generates PHPUnit 9.6 tests under `tests/` in `Detain\MyAdminVpsCpanel\Tests` namespace following `tests/VpsAddCpanelFileTest.php` and `tests/PluginTest.php` patterns — file existence, PHP tag, docblock assertions, function/class presence via source analysis and ReflectionClass. Use when user says 'add tests', 'write test for', 'test this file', 'write phpunit', or 'test the plugin'. Do NOT use for integration tests requiring a live MyAdmin runtime, database, or licensing API calls.
---
# PHPUnit Plugin Tests

## Critical

- **Never instantiate framework classes** (`AddServiceAddon`, `AddonHandler`, `function_requirements`, DB helpers) — these require a live MyAdmin runtime. Use source-level string analysis or `ReflectionClass` instead.
- **Namespace must be** `Detain\MyAdminVpsCpanel\Tests` — no exceptions.
- **PHPUnit version is 9.6** — use `assertMatchesRegularExpression()`, `assertDoesNotMatchRegularExpression()`, `assertFileExists()`. Do NOT use deprecated `assertRegExp()`.
- **Tabs for indentation** (`.scrutinizer.yml` enforces this) — never spaces.
- Every test method needs a docblock explaining *what* it verifies and *why* it matters.

## Instructions

1. **Identify the target file.** Determine whether you are testing a class file (e.g. `src/Plugin.php`) or a procedural file (e.g. `src/vps_add_cpanel.php`). This determines which test pattern to use.
   - Verify the target file exists under `src/` before proceeding.

2. **Create the test class file** at `tests/PluginTest.php` or `tests/VpsAddCpanelFileTest.php` as appropriate. Open with:
   ```php
   <?php

   namespace Detain\MyAdminVpsCpanel\Tests;

   use PHPUnit\Framework\TestCase;
   ```
   For class files, also add:
   ```php
   use Detain\MyAdminVpsCpanel\Plugin;
   use ReflectionClass;
   use ReflectionMethod;
   ```

3. **For procedural files** (no namespace, global functions): cache source in `setUp()` and assert via string analysis.
   ```php
   private string $source;

   protected function setUp(): void
   {
   	$this->source = file_get_contents(__DIR__ . '/../src/vps_add_cpanel.php');
   }
   ```
   Required test groups (in section comment blocks `// ---`):
   - **File existence & structure:** `assertFileExists`, `assertStringStartsWith('<?php', ...)`, `@author`, `@package MyAdmin`, `@category VPS`
   - **No namespace:** `assertDoesNotMatchRegularExpression('/^\s*namespace\s+/m', $this->source)`
   - **Function definition:** `assertMatchesRegularExpression('/function\s+vps_add_cpanel\s*\(/', $this->source)`
   - **Docblock `@return void`:** `assertStringContainsString('@return void', $this->source)`
   - **Function body:** assert `function_requirements('class.AddServiceAddon')`, `new AddServiceAddon()`, `VPS_CPANEL_COST`, `$addon->process()`

4. **For class files** (namespaced, PSR-4): use `ReflectionClass` in `setUp()`.
   ```php
   private ReflectionClass $reflection;

   protected function setUp(): void
   {
   	$this->reflection = new ReflectionClass(Plugin::class);
   }
   ```
   Required test groups:
   - **Class structure:** `class_exists`, FQCN, `isAbstract()` false, `isFinal()` false, `isInstantiable()` true, constructor zero required params
   - **Static properties:** value assertions on `$name`, `$description`, `$module` (`'vps'`), `$type` (`'addon'`), `$help`; confirm each is `isPublic()` and `isStatic()`
   - **`getHooks()`:** returns array, count 3, keys `function.requirements` / `vps.load_addons` / `vps.settings`, each value is `[Plugin::class, 'methodName']`
   - **Method signatures:** `getRequirements`, `getAddon`, `getSettings` each take 1 required param named `$event` typed `Symfony\Component\EventDispatcher\GenericEvent`; `doEnable`/`doDisable` take 2 required + 1 optional (`$regexMatch`, default `false`)
   - **Source-level analysis** (for DB-heavy methods): `file_get_contents(__DIR__ . '/../src/Plugin.php')` then assert `activate_cpanel(`, `deactivate_cpanel(`, `myadmin_log(`, `adminMail(`, `'add_cpanel'`, `'del_cpanel'`, `set_require_ip(true)`, `VPS_CPANEL_COST`

5. **Run tests to verify:**
   ```bash
   vendor/bin/phpunit tests/ -v
   ```
   All new tests must pass. Fix any failures before marking done.

## Examples

**User says:** "Add tests for `src/vps_add_cpanel.php`"

**Actions taken:**
1. Read `src/vps_add_cpanel.php` to identify function name (`vps_add_cpanel`), docblock tags, and body calls.
2. Create `tests/VpsAddCpanelFileTest.php` with namespace `Detain\MyAdminVpsCpanel\Tests`, cache source in `setUp()`.
3. Add test sections: file existence, PHP tag, `@author`/`@package MyAdmin`/`@category VPS`, no namespace assertion, function regex, `@return void`, `function_requirements('class.AddServiceAddon')`, `new AddServiceAddon()`, `VPS_CPANEL_COST`, `$addon->process()`.
4. Run `vendor/bin/phpunit tests/VpsAddCpanelFileTest.php -v` → all green.

**Result:** `tests/VpsAddCpanelFileTest.php` with 12 focused tests, zero runtime dependencies.

## Common Issues

- **`Error: Class 'AddServiceAddon' not found`** — you tried to instantiate or `include` the procedural file. Never require/include `src/` files in tests. Read source with `file_get_contents()` and assert on the string.
- **`assertRegExp is deprecated`** — replace with `assertMatchesRegularExpression()` (PHPUnit 9 renamed it).
- **Test file not discovered** — confirm the file is named `*Test.php` and the class name matches. Check `phpunit.xml.dist` `<testsuites>` path includes `tests/`.
- **Wrong indentation causes CS failure** — use tabs, not spaces. Run `make php-cs-fixer` if the CI check fails.
- **`ReflectionException: Class does not exist`** — the class isn't autoloaded. Run `composer install` first; confirm `composer.json` PSR-4 maps `Detain\MyAdminVpsCpanel\` → `src/`.

<?php

namespace Detain\MyAdminVpsCpanel\Tests;

use Detain\MyAdminVpsCpanel\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for the Plugin class.
 *
 * Because Plugin relies heavily on global functions and external services
 * (database, licensing APIs, etc.), these tests focus on structural
 * verification via ReflectionClass, static property values, hook
 * wiring, and source-level analysis of the DB-heavy methods.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    // ------------------------------------------------------------------
    //  Class structure
    // ------------------------------------------------------------------

    /**
     * Tests that the Plugin class exists and lives in the expected namespace.
     * This guards against accidental renames or moves.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Tests that the fully-qualified class name matches the PSR-4
     * autoload mapping declared in composer.json.
     */
    public function testFullyQualifiedClassName(): void
    {
        $this->assertSame(
            'Detain\MyAdminVpsCpanel\Plugin',
            $this->reflection->getName()
        );
    }

    /**
     * Tests that Plugin is a concrete (non-abstract) class so it can
     * be instantiated by the plugin loader at runtime.
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Tests that Plugin is not declared final, allowing potential
     * extension by downstream packages.
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Tests that Plugin can be instantiated without arguments.
     * The constructor is intentionally empty.
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
    }

    /**
     * Tests that the constructor accepts zero required parameters
     * so the plugin loader can create instances with `new Plugin()`.
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertSame(0, $constructor->getNumberOfRequiredParameters());
    }

    /**
     * Tests that instantiation actually produces a Plugin object.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    // ------------------------------------------------------------------
    //  Static properties
    // ------------------------------------------------------------------

    /**
     * Tests that the $name property holds the expected human-readable
     * addon name used in the admin UI.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('CPanel VPS Addon', Plugin::$name);
    }

    /**
     * Tests that the $description property is a non-empty string
     * containing key identifying terms.
     */
    public function testDescriptionProperty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
        $this->assertStringContainsString('cPanel', Plugin::$description);
        $this->assertStringContainsString('VPS', Plugin::$description);
    }

    /**
     * Tests that the $help property is defined (empty string by default).
     */
    public function testHelpProperty(): void
    {
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Tests that $module is set to 'vps', tying this addon to the
     * VPS service module.
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('vps', Plugin::$module);
    }

    /**
     * Tests that $type is set to 'addon', marking this as an addon
     * plugin rather than a standalone module.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('addon', Plugin::$type);
    }

    /**
     * Tests that every expected static property is declared public so
     * the framework can read them directly.
     */
    public function testStaticPropertiesArePublic(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            $rp = $this->reflection->getProperty($prop);
            $this->assertTrue($rp->isPublic(), "Property \${$prop} should be public");
            $this->assertTrue($rp->isStatic(), "Property \${$prop} should be static");
        }
    }

    /**
     * Tests that the class has exactly the five expected static
     * properties and no more, preventing accidental additions.
     */
    public function testStaticPropertyCount(): void
    {
        $props = $this->reflection->getProperties(\ReflectionProperty::IS_STATIC);
        $this->assertCount(5, $props);
    }

    // ------------------------------------------------------------------
    //  getHooks()
    // ------------------------------------------------------------------

    /**
     * Tests that getHooks() is a public static method, as required by
     * the plugin loader which calls Plugin::getHooks().
     */
    public function testGetHooksIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Tests that getHooks() requires no parameters so it can be called
     * without arguments.
     */
    public function testGetHooksAcceptsNoParameters(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertSame(0, $method->getNumberOfRequiredParameters());
    }

    /**
     * Tests that getHooks() returns an array (the contract expected
     * by the event dispatcher registration).
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Tests that exactly three hooks are registered: requirements,
     * load_addons, and settings.
     */
    public function testGetHooksReturnsThreeHooks(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(3, $hooks);
    }

    /**
     * Tests the exact hook event names that are registered, ensuring
     * the plugin integrates with the correct framework events.
     */
    public function testGetHooksEventNames(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertArrayHasKey('vps.load_addons', $hooks);
        $this->assertArrayHasKey('vps.settings', $hooks);
    }

    /**
     * Tests that each hook value is a callable-style array pointing
     * to a static method on the Plugin class.
     */
    public function testGetHooksCallableFormat(): void
    {
        $hooks = Plugin::getHooks();

        foreach ($hooks as $event => $handler) {
            $this->assertIsArray($handler, "Handler for '{$event}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$event}' should have two elements");
            $this->assertSame(Plugin::class, $handler[0], "Handler for '{$event}' should reference Plugin class");
            $this->assertIsString($handler[1], "Handler method name for '{$event}' should be a string");
        }
    }

    /**
     * Tests that the hook callbacks map to the correct static methods.
     */
    public function testGetHooksMethodMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getRequirements', $hooks['function.requirements'][1]);
        $this->assertSame('getAddon', $hooks['vps.load_addons'][1]);
        $this->assertSame('getSettings', $hooks['vps.settings'][1]);
    }

    /**
     * Tests that every method referenced in getHooks() actually exists
     * on the Plugin class.
     */
    public function testGetHooksMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $this->assertTrue(
                $this->reflection->hasMethod($handler[1]),
                "Method {$handler[1]} referenced in hook '{$event}' does not exist"
            );
        }
    }

    /**
     * Tests that the load_addons hook key is dynamically built from
     * the $module property, verifying the concatenation logic.
     */
    public function testLoadAddonsHookUsesModuleProperty(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKey = Plugin::$module . '.load_addons';
        $this->assertArrayHasKey($expectedKey, $hooks);
    }

    /**
     * Tests that the settings hook key is dynamically built from
     * the $module property.
     */
    public function testSettingsHookUsesModuleProperty(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKey = Plugin::$module . '.settings';
        $this->assertArrayHasKey($expectedKey, $hooks);
    }

    // ------------------------------------------------------------------
    //  Event handler method signatures
    // ------------------------------------------------------------------

    /**
     * Tests that getRequirements() accepts exactly one parameter
     * (a GenericEvent instance).
     */
    public function testGetRequirementsSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertSame('event', $param->getName());
        $type = $param->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\Component\EventDispatcher\GenericEvent', $type->getName());
    }

    /**
     * Tests that getAddon() accepts exactly one parameter
     * (a GenericEvent instance).
     */
    public function testGetAddonSignature(): void
    {
        $method = $this->reflection->getMethod('getAddon');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertSame('event', $param->getName());
        $type = $param->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\Component\EventDispatcher\GenericEvent', $type->getName());
    }

    /**
     * Tests that getSettings() accepts exactly one parameter
     * (a GenericEvent instance).
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertSame('event', $param->getName());
        $type = $param->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\Component\EventDispatcher\GenericEvent', $type->getName());
    }

    /**
     * Tests that doEnable() accepts three parameters with the third
     * being optional (default false).
     */
    public function testDoEnableSignature(): void
    {
        $method = $this->reflection->getMethod('doEnable');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());
        $this->assertCount(3, $method->getParameters());

        $params = $method->getParameters();
        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
        $this->assertSame('regexMatch', $params[2]->getName());
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * Tests that doDisable() accepts three parameters with the third
     * being optional (default false).
     */
    public function testDoDisableSignature(): void
    {
        $method = $this->reflection->getMethod('doDisable');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());
        $this->assertCount(3, $method->getParameters());

        $params = $method->getParameters();
        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
        $this->assertSame('regexMatch', $params[2]->getName());
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    // ------------------------------------------------------------------
    //  Method inventory
    // ------------------------------------------------------------------

    /**
     * Tests that all expected public methods exist on the class.
     * This acts as a contract test for the plugin interface.
     */
    public function testExpectedPublicMethodsExist(): void
    {
        $expected = [
            '__construct',
            'getHooks',
            'getRequirements',
            'getAddon',
            'getSettings',
            'doEnable',
            'doDisable',
        ];

        foreach ($expected as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Expected public method {$method} not found"
            );
        }
    }

    /**
     * Tests that the class declares exactly the expected number of
     * methods, no more and no less.
     */
    public function testMethodCount(): void
    {
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        // Filter to only methods declared in Plugin (not inherited)
        $ownMethods = array_filter($methods, function (ReflectionMethod $m) {
            return $m->getDeclaringClass()->getName() === Plugin::class;
        });
        $this->assertCount(7, $ownMethods);
    }

    // ------------------------------------------------------------------
    //  Source-level static analysis of DB-heavy methods
    // ------------------------------------------------------------------

    /**
     * Tests that the Plugin.php source file contains the expected
     * require_once for the license functions include, which is needed
     * by both doEnable and doDisable.
     */
    public function testSourceIncludesLicenseFunctions(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString(
            "require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php'",
            $source
        );
    }

    /**
     * Tests that doEnable source calls activate_cpanel, the licensing
     * API function responsible for provisioning.
     */
    public function testDoEnableSourceCallsActivateCpanel(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('activate_cpanel(', $source);
    }

    /**
     * Tests that doDisable source calls deactivate_cpanel, the
     * licensing API function responsible for deprovisioning.
     */
    public function testDoDisableSourceCallsDeactivateCpanel(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('deactivate_cpanel(', $source);
    }

    /**
     * Tests that doEnable source calls get_cpanel_license_data_by_ip
     * to check the current license status before activation.
     */
    public function testDoEnableChecksLicenseStatus(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('get_cpanel_license_data_by_ip', $source);
    }

    /**
     * Tests that doDisable sends an admin email notification when
     * canceling an active license.
     */
    public function testDoDisableSendsAdminEmail(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('adminMail(', $source);
        $this->assertStringContainsString('admin/vps_cpanel_canceled.tpl', $source);
    }

    /**
     * Tests that doEnable logs the activation event via myadmin_log.
     */
    public function testDoEnableLogsActivation(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        // Verify the logging call exists with the 'Activation' keyword
        $this->assertMatchesRegularExpression(
            "/myadmin_log\(.+Activation/",
            $source
        );
    }

    /**
     * Tests that doDisable logs the deactivation event via myadmin_log.
     */
    public function testDoDisableLogsDeactivation(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertMatchesRegularExpression(
            "/myadmin_log\(.+Deactivation/",
            $source
        );
    }

    /**
     * Tests that doDisable adds history entries for the deactivation
     * using the 'del_cpanel' action tag.
     */
    public function testDoDisableAddsHistory(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString("'del_cpanel'", $source);
    }

    /**
     * Tests that doEnable adds history entries for the activation
     * using the 'add_cpanel' action tag.
     */
    public function testDoEnableAddsHistory(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString("'add_cpanel'", $source);
    }

    /**
     * Tests that doEnable passes the correct package ID (31369) when
     * calling activate_cpanel for this VPS addon.
     */
    public function testDoEnableUsesCorrectPackageId(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('31369', $source);
    }

    /**
     * Tests that the getRequirements method registers the
     * vps_add_cpanel page requirement with the correct path.
     */
    public function testGetRequirementsSourceRegistersPage(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString("'vps_add_cpanel'", $source);
        $this->assertStringContainsString('vps_add_cpanel.php', $source);
    }

    /**
     * Tests that getAddon source configures the addon to require an
     * IP address (set_require_ip(true)).
     */
    public function testGetAddonRequiresIp(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('set_require_ip(true)', $source);
    }

    /**
     * Tests that getAddon source uses VPS_CPANEL_COST constant for
     * the addon pricing.
     */
    public function testGetAddonUsesCostConstant(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('VPS_CPANEL_COST', $source);
    }

    /**
     * Tests that getAddon source sets the text match regex pattern
     * used to identify existing CPanel addons on an account.
     */
    public function testGetAddonSetsTextMatch(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString("'CPanel (.*) Accounts'", $source);
    }

    // ------------------------------------------------------------------
    //  Source file structure
    // ------------------------------------------------------------------

    /**
     * Tests that Plugin.php uses the correct namespace declaration.
     */
    public function testSourceNamespace(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('namespace Detain\MyAdminVpsCpanel;', $source);
    }

    /**
     * Tests that Plugin.php imports GenericEvent from Symfony.
     */
    public function testSourceImportsGenericEvent(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString(
            'use Symfony\Component\EventDispatcher\GenericEvent;',
            $source
        );
    }

    /**
     * Tests that the Plugin.php file opens with a proper PHP tag.
     */
    public function testSourceStartsWithPhpTag(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringStartsWith('<?php', $source);
    }
}

<?php

namespace Detain\MyAdminVpsCpanel\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the vps_add_cpanel.php procedural file.
 *
 * Since this file defines a global function that depends on external
 * framework classes (AddServiceAddon, function_requirements), these
 * tests use source-level static analysis to verify the file structure
 * and the function's integration points without executing it.
 */
class VpsAddCpanelFileTest extends TestCase
{
    /**
     * @var string Cached source contents of vps_add_cpanel.php
     */
    private string $source;

    protected function setUp(): void
    {
        $this->source = file_get_contents(
            __DIR__ . '/../src/vps_add_cpanel.php'
        );
    }

    // ------------------------------------------------------------------
    //  File existence and structure
    // ------------------------------------------------------------------

    /**
     * Tests that the vps_add_cpanel.php source file exists at the
     * expected path within the package.
     */
    public function testFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../src/vps_add_cpanel.php');
    }

    /**
     * Tests that the file starts with a proper PHP opening tag.
     */
    public function testFileStartsWithPhpTag(): void
    {
        $this->assertStringStartsWith('<?php', $this->source);
    }

    /**
     * Tests that the file has a docblock header with author metadata.
     */
    public function testFileHasAuthorDocblock(): void
    {
        $this->assertStringContainsString('@author', $this->source);
    }

    /**
     * Tests that the file has a docblock header with package metadata.
     */
    public function testFileHasPackageDocblock(): void
    {
        $this->assertStringContainsString('@package MyAdmin', $this->source);
    }

    /**
     * Tests that the file has a docblock header with category metadata.
     */
    public function testFileHasCategoryDocblock(): void
    {
        $this->assertStringContainsString('@category VPS', $this->source);
    }

    // ------------------------------------------------------------------
    //  Function definition
    // ------------------------------------------------------------------

    /**
     * Tests that the file defines the vps_add_cpanel function which
     * is loaded as a page requirement by Plugin::getRequirements().
     */
    public function testDefinesVpsAddCpanelFunction(): void
    {
        $this->assertMatchesRegularExpression(
            '/function\s+vps_add_cpanel\s*\(/',
            $this->source
        );
    }

    /**
     * Tests that the function has a docblock with @return void.
     */
    public function testFunctionDocblockHasReturnVoid(): void
    {
        $this->assertStringContainsString('@return void', $this->source);
    }

    // ------------------------------------------------------------------
    //  Function body analysis
    // ------------------------------------------------------------------

    /**
     * Tests that the function loads the AddServiceAddon class via the
     * framework's function_requirements mechanism.
     */
    public function testFunctionLoadsAddonHandler(): void
    {
        $this->assertStringContainsString(
            "function_requirements('class.AddServiceAddon')",
            $this->source
        );
    }

    /**
     * Tests that the function creates a new AddServiceAddon instance.
     */
    public function testFunctionCreatesAddonInstance(): void
    {
        $this->assertStringContainsString('new AddServiceAddon()', $this->source);
    }

    /**
     * Tests that the function calls load() with the expected
     * parameters: function name, addon label, module, and cost.
     */
    public function testFunctionCallsLoadWithCorrectArgs(): void
    {
        $this->assertStringContainsString(
            "__FUNCTION__, 'CPanel', 'vps', VPS_CPANEL_COST",
            $this->source
        );
    }

    /**
     * Tests that the function calls process() on the addon to finalize
     * the addon addition workflow.
     */
    public function testFunctionCallsProcess(): void
    {
        $this->assertStringContainsString('$addon->process()', $this->source);
    }

    /**
     * Tests that the function references the VPS_CPANEL_COST constant
     * for pricing consistency with the Plugin class.
     */
    public function testFunctionUsesVpsCpanelCost(): void
    {
        $this->assertStringContainsString('VPS_CPANEL_COST', $this->source);
    }

    /**
     * Tests that the file does NOT declare a namespace, since it
     * defines a global function used by the framework's
     * function_requirements system.
     */
    public function testFileHasNoNamespace(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*namespace\s+/m',
            $this->source
        );
    }
}

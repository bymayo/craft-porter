<?php
/**
 * Porter plugin for Craft CMS 3.x
 *
 * A toolkit with lots of helpers for users and accounts
 *
 * @link      https://bymayo.co.uk
 * @copyright Copyright (c) 2020 Jason Mayo
 */

namespace bymayo\portertests\unit;

use Codeception\Test\Unit;
use UnitTester;
use Craft;
use bymayo\porter\Porter;

/**
 * ExampleUnitTest
 *
 *
 * @author    Jason Mayo
 * @package   Porter
 * @since     1.0.0
 */
class ExampleUnitTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testPluginInstance()
    {
        $this->assertInstanceOf(
            Porter::class,
            Porter::$plugin
        );
    }

    /**
     *
     */
    public function testCraftEdition()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }
}

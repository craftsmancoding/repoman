<?php
/**
 * This must be installed somewhere inside of your MODx web root.
 *
 * To run these tests, pass the test directory as the 1st argument to phpunit:
 *
 *   phpunit path/to/tests
 *
 * or if you're having any trouble running phpunit, download its .phar file, and
 * then run the tests like this:
 *
 *  php phpunit.phar path/to/tests
 *
 */
namespace Repoman;

use Repoman\Config;


class configTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Could not find a valid MODX config.core.php file.
     */
    public function testBadDir()
    {
        $modx = Bridge::getMODX('/tmp');
    }

    public function testGetModx() {
        $modx = Bridge::getMODX();

        $this->assertTrue(is_a($modx, 'modX'));
    }

}
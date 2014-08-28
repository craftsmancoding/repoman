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

use Repoman\Filesystem;
use Repoman\Utils;

class utilsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Could not find a valid MODX config.core.php file.
     */
    public function testBadDir()
    {
        $modx = Utils::getMODX('/tmp');
    }

    public function testGetModx()
    {
        $modx = Utils::getMODX();

        $this->assertTrue(is_a($modx, 'modX'));
    }

    public function testValidPhpSyntax()
    {
        $result = Utils::validPhpSyntax(__DIR__ . '/php_files/valid.php');
        $this->assertTrue($result);
    }

    public function testValidPhpSyntax1()
    {
        $result = Utils::validPhpSyntax(__DIR__ . '/php_files/not_php.txt');
        $this->assertFalse($result);
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage File not found
     */
    public function testValidPhpSyntax2()
    {
        Utils::validPhpSyntax(__DIR__ . '/php_files/does_not_exist.php');
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Directory found. File expected:
     */
    public function testValidPhpSyntax3()
    {
        Utils::validPhpSyntax(__DIR__ . '/php_files/');
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Errors parsing
     */
    public function testValidPhpSyntax4()
    {
        Utils::validPhpSyntax(__DIR__ . '/php_files/invalid.php');
    }
}
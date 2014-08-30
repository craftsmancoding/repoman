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
use Repoman\Filesystem;

class configTest extends \PHPUnit_Framework_TestCase
{

    public static $config;

    /**
     * Load up MODX for our tests.
     *
     */
    public static function setUpBeforeClass()
    {
        self::$config = new Config(new Filesystem());

    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Path is not a directory
     */
    public function testBadDir()
    {
        self::$config->setPkgRootDir('does/not/exist');
    }

    public function testGlobal() {
        self::$config->setPkgRootDir(dirname(__FILE__).'/pkg1/');
        $config = self::$config->getGlobal();
        $this->assertTrue(is_array($config));
        $this->assertEquals('elements/chunks/',$config['chunks_path']);
    }

    public function testParseJson() {
        $dir = dirname(__FILE__).'/pkg1/';
        self::$config->setPkgRootDir($dir);
        $config = self::$config->parseJson();
        $this->assertTrue(is_array($config));
        $this->assertEquals('123.456.789',$config['extra']['version']);

    }

    public function testPkg() {
        self::$config->setPkgRootDir(dirname(__FILE__).'/pkg1/');
        $config = self::$config->getPkg();
        $this->assertTrue(is_array($config));
        //error_log(print_r($config,true));
        $this->assertEquals('123.456.789',$config['version']);
    }
    public function testOverrides() {
        //$C = new Config(dirname(__FILE__).'/pkg1/', array('version'=>'1.2.3'));
        self::$config->setPkgRootDir(dirname(__FILE__).'/pkg1/');
        $config = self::$config->getAll(array('version'=>'1.2.3'));
        $this->assertTrue(is_array($config));
        $this->assertEquals('1.2.3',$config['version']);
    }


    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage does not match the expected JSON schema.
     */
    public function testBadConfig() {
        self::$config->setPkgRootDir(dirname(__FILE__).'/pkg2/');
        $config = self::$config->getAll();
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Invalid namespace
     */
    public function testInvalidNamespace() {
        self::$config->setPkgRootDir(dirname(__FILE__).'/pkg1/');
        $config = self::$config->getAll(array('namespace'=>'@#$^@%#$&'));
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Invalid version
     */
    public function testInvalidVersion() {
        self::$config->setPkgRootDir(dirname(__FILE__).'/pkg1/');
        $config = self::$config->getAll(array('version'=>'donkey(duck)#punch'));
    }
}
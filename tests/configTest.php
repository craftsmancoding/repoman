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
     * @expectedExceptionMessage Path is not a directory
     */
    public function testBadDir()
    {
        $C = new Config('does/not/exist');
    }

    public function testGlobal() {
        $C = new Config(dirname(__FILE__).'/pkg1/');
        $config = $C->getGlobal();
        $this->assertTrue(is_array($config));
        //error_log(print_r($config,true));
        $this->assertEquals('elements/chunks/',$config['chunks_path']);
    }

    public function testParseJson() {
        $dir = dirname(__FILE__).'/pkg1/';
        $C = new Config($dir);
        $config = $C->parseJson($dir.'composer.json');
        $this->assertTrue(is_array($config));
        //error_log(print_r($config,true));
        $this->assertEquals('123.456.789',$config['extra']['version']);

    }

    public function testPkg() {
        $C = new Config(dirname(__FILE__).'/pkg1/');
        $config = $C->getPkg();
        $this->assertTrue(is_array($config));
        //error_log(print_r($config,true));
        $this->assertEquals('123.456.789',$config['version']);
    }
    public function testOverrides() {
        $C = new Config(dirname(__FILE__).'/pkg1/', array('version'=>'1.2.3'));
        $config = $C->getAll();
        $this->assertTrue(is_array($config));
        $this->assertEquals('1.2.3',$config['version']);
    }


    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage does not match the expected JSON schema.
     */
    public function testBadConfig() {
        $C = new Config(dirname(__FILE__).'/pkg2/');
        $config = $C->getAll();
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Invalid namespace
     */
    public function testInvalidNamespace() {
        $C = new Config(dirname(__FILE__).'/pkg1/', array('namespace'=>'@#$^@%#$&'));
        $config = $C->getAll();
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Invalid version
     */
    public function testInvalidVersion() {
        $C = new Config(dirname(__FILE__).'/pkg1/', array('version'=>'donkey(duck)#punch'));
        $config = $C->getAll();
    }
}
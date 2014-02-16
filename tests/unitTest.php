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


class unitTest extends PHPUnit_Framework_TestCase {

    // Must be static because we set it up inside a static function
    public static $modx;
    public static $repoman;
    
    /**
     * Load up MODX for our tests.
     *
     */
    public static function setUpBeforeClass() {        
        $docroot = dirname(dirname(__FILE__));
        while (!file_exists($docroot.'/config.core.php')) {
            if ($docroot == '/') {
                die('Failed to locate config.core.php');
            }
            $docroot = dirname($docroot);
        }
        if (!file_exists($docroot.'/config.core.php')) {
            die('Failed to locate config.core.php');
        }
        
        include_once $docroot . '/config.core.php';
        
        if (!defined('MODX_API_MODE')) {
            define('MODX_API_MODE', false);
        }
        
        include_once MODX_CORE_PATH . 'model/modx/modx.class.php';
        require_once dirname(dirname(__FILE__)).'/model/repoman/repoman.class.php';         
        
        self::$modx = new modX();
        self::$modx->initialize('mgr');          
        
        self::$repoman = new Repoman(self::$modx);
        
    }

    /**
     *
     */
    public function testMODX() {
        $this->assertTrue(defined('MODX_CORE_PATH'), 'MODX_CORE_PATH not defined.');
        $this->assertTrue(defined('MODX_ASSETS_PATH'), 'MODX_ASSETS_PATH not defined.');
        $this->assertTrue(is_a(self::$modx, 'modX'), 'Invalid modX instance.');
    
    }
    

    public function testConfig() {
        $config = Repoman::load_config(dirname(__FILE__).'/pkg1/');
        $this->assertTrue($config['namespace'] == 'packagename', 'Namespace not detected.');
        $this->assertTrue($config['description'] == 'My description here.', 'Description not detected.');
    }
    
    /**
     * Test for bad JSON
     *
     * @expectedException Exception
     * @expectedExceptionMessage Invalid JSON in composer.json
     */
    public function testConfig2() {
        $config = Repoman::load_config(dirname(__FILE__).'/pkg2/');
    }

    /**
     * Test for bad version number
     *
     * @expectedException Exception
     * @expectedExceptionMessage Invalid version in composer.json
     */
    public function testConfig3() {
        $config = Repoman::load_config(dirname(__FILE__).'/pkg3/');
    }
    
}
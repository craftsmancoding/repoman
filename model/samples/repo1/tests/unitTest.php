<?php
/**
 * Write your unit tests here: create functions whose names begin with "test".
 * To run them, pass the test directory as the 1st argument to phpunit:
 *
 *   phpunit path/to/tests
 *
 * or if you're having any trouble installing phpunit, download its .phar file, and 
 * then run the tests like this:
 *
 *  php phpunit.phar path/to/tests
 *
 * Refer to http://phpunit.de/ for more documentation 
 */

class unitTest extends PHPUnit_Framework_TestCase {

    // Must be static because we set it up inside a static function
    public static $modx;
    
    /**
     * This special function loads before any of the tests execute.
     * We use it to load the MODx instance so it will be available throughout the class
     * as self::$modx
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
        require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';
        include_once MODX_CORE_PATH . 'model/modx/modx.class.php';

        
        self::$modx = new modX();
        self::$modx->initialize('mgr');          
        
    }

    
    /**
     * Example Test: use assertTrue or assertFalse to test for many different conditions.
     * Here we are testing that MODx got loaded.
     */
    public function testExample() {
        $this->assertTrue(defined('MODX_CORE_PATH'), 'MODX_CORE_PATH not defined.');
        $this->assertTrue(defined('MODX_ASSETS_PATH'), 'MODX_ASSETS_PATH not defined.');
        $this->assertTrue(is_a(self::$modx, 'modX'), 'Invalid modX instance.');
    }
}
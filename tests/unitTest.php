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
     * @expectedExceptionMessage Invalid version
     */
    public function testConfig3() {
        $config = Repoman::load_config(dirname(__FILE__).'/pkg3/');
    }

    /**
     *
     */    
    public function testPrep() {
        $config = Repoman::load_config(dirname(__FILE__).'/pkg4/');
        $config['dry_run'] = true;
        self::$modx->setLogLevel(modX::LOG_LEVEL_FATAL);
        self::$modx->setLogTarget('ECHO'); 
        $Repoman = new Repoman(self::$modx,$config);
        $Repoman->import(dirname(__FILE__).'/pkg4/');

        $assets_url = MODX_BASE_URL .preg_replace('#'.MODX_BASE_PATH.'#', '', dirname(__FILE__).'/pkg4/').$config['assets_path'];
        $assets_path = dirname(__FILE__).'/pkg4/'.$config['assets_path'];
        $core_path = dirname(__FILE__).'/pkg4/'.$config['core_path'];

        $this->assertTrue(Repoman::$queue['modSystemSetting']['pkg4.assets_url']['value'] == $assets_url, 'Improper value for assets_url');
        $this->assertTrue(Repoman::$queue['modSystemSetting']['pkg4.assets_path']['value'] == $assets_path, 'Improper value for assets_path');
        $this->assertTrue(Repoman::$queue['modSystemSetting']['pkg4.core_path']['value'] == $core_path, 'Improper value for core_path');        
    }

    
    /**
     *
     */
    public function testGraph() {
        $Repoman = new Repoman(self::$modx,array());
        $out = $Repoman->graph('modDocument');
        $this->assertTrue($out['type'] == 'document', 'Type attribute should be "document"');
        
        self::$modx->setOption('repoman.dir', dirname(__FILE__).'/repos/');
        $out = $Repoman->graph('Product');
        $this->assertTrue(is_array($out['Store']), 'Related objects should be included.');
    }
    
    public function testImport() {
    
    }
    
    public function testSeed() {
    
    }
    
    public function testBuild() {
    
    }

    /**
     *
     */
    public function testRtfm() {
        $out = Repoman::rtfm('asdfasdf');
        $this->assertTrue('No manual page found.' == $out, 'Expected missing manual page.');
    }

    /**
     * Test loading data from a file
     * @expectedException Exception
     * @expectedExceptionMessage not an array     
     */
    public function testLoadData1() {
        $Repoman = new Repoman(self::$modx,array());
        $data = $Repoman->load_data(dirname(__FILE__).'/repos/pkg5/bad_data/modChunk.no_array.php');
    }

    /**
     * Test loading data from a file
     * @expectedException Exception
     * @expectedExceptionMessage Errors parsing
     */
    public function testLoadData2() {
        $Repoman = new Repoman(self::$modx,array());
        $data = $Repoman->load_data(dirname(__FILE__).'/repos/pkg5/bad_data/modChunk.bad_syntax.php');
    }

    /**
     * Test loading data from a file
     * @expectedException Exception
     * @expectedExceptionMessage Bad JSON in
     */
    public function testLoadData3() {
        $Repoman = new Repoman(self::$modx,array());
        $data = $Repoman->load_data(dirname(__FILE__).'/repos/pkg5/bad_data/modChunk.bad_json.json',true);
    }
    
    /**
     *
     */
    public function testParseArgs() {
        $args = array('--flag','--x=y','skip=me');
        $parsed = Repoman::parse_args($args);
        $this->assertTrue($parsed['flag'], 'Flag should be set.');   
        $this->assertTrue($parsed['x'] == 'y', 'Value should be set.');   
        $this->assertTrue(count($parsed) == 2, 'Two arguments should come through.');   
        $this->assertFalse(isset($parsed['skip']), 'Value should not be set.');   
    }
}
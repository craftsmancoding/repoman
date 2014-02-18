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
     * @expectedExceptionMessage Invalid JSON
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
    
    /**
     * Big and important... make sure this stuff gets loaded correctly.
     */
    public function testImport() {
        if ($Chunk = self::$modx->getObject('modChunk', array('name'=>'test_pkg6'))) {
            $Chunk->remove();
        }
        if ($Snippet = self::$modx->getObject('modSnippet', array('name'=>'test_pkg6'))) {
            $Snippet->remove();
        }
        if ($Plugin = self::$modx->getObject('modPlugin', array('name'=>'test_pkg6'))) {
            $Plugin->remove();
        }
        if ($Template = self::$modx->getObject('modTemplate', array('templatename'=>'test_template_pkg6'))) {
            $Template->remove();
        }
        if ($TV = self::$modx->getObject('modTemplateVar', array('name'=>'test_pkg6'))) {
            $TV->remove();
        }
                
        self::$modx->setOption('repoman.dir', dirname(__FILE__).'/repos/'); // prob'ly not req'd
        $pkg_root = dirname(__FILE__).'/repos/pkg6/';
        $config = Repoman::load_config($pkg_root);
        $Repoman = new Repoman(self::$modx,$config);
        $Repoman->import($pkg_root);

        $Chunk = self::$modx->getObject('modChunk', array('name'=>'test_pkg6'));
        $this->assertTrue(is_object($Chunk), 'Chunk should have been imported.');
        $this->assertTrue($Chunk->get('description') == "C'mon Barbie let's go party", 'Chunk should have been imported.');
        $this->assertTrue(strpos($Chunk->getContent(), 'This is a test chunk.') !== false, 'Chunk should have been imported.');

        $Snippet = self::$modx->getObject('modSnippet', array('name'=>'test_pkg6'));
        $this->assertTrue(is_object($Snippet), 'Snippet should have been imported.');
        $this->assertTrue($Snippet->get('description') == "Let me make you some coffee", 'Snippet should have been imported.');
        $this->assertTrue(strpos($Snippet->getContent(), "return date('Y-m-d H:i:s');") !== false, 'Snippet should have been imported.');

        $Plugin = self::$modx->getObject('modPlugin', array('name'=>'test_pkg6'));
        $this->assertTrue(is_object($Plugin), 'Plugin should have been imported.');
        $this->assertTrue($Plugin->get('description') == "Ladies and Gentlemen...", 'Plugin should have been imported.');
        $this->assertTrue(strpos($Plugin->getContent(), "return date('Y-m-d H:i:s');") !== false, 'Plugin should have been imported.');
        if ($Events = $Plugin->getMany('PluginEvents')) {
            foreach($Events as $E) {
                $this->assertTrue($E->get('event') == 'OnPageNotFound', 'Plugin Events should have been imported.');
            }
        }
        $this->assertTrue(is_array($Events), 'Plugin should have events attached.');        

        $TV = self::$modx->getObject('modTemplateVar', array('name'=>'test_pkg6'));
        $this->assertTrue(is_object($TV), 'TV should have been imported.');
        $this->assertTrue($TV->get('description') == "Now that is a Kankle", 'TV should have been imported.');
        
        $Template = self::$modx->getObject('modTemplate', array('templatename'=>'test_template_pkg6'));
        $this->assertTrue(is_object($Template), 'Template should have been imported.');
        $this->assertTrue($Template->get('description') == 'Gnar gnar description', 'Template should have been imported.');
        $this->assertTrue(strpos($Template->getContent(), 'This is my template.') !== false, 'Template should have been imported.');
        if ($TVTs = $Template->getMany('TemplateVarTemplates')) {
            foreach($TVTs as $t) {
                if ($TVs = $t->getMany('TemplateVar')) {
                    foreach ($TVs as $tv)
                    $this->assertTrue($tv->get('name') == 'test_pkg6', 'TV should have been imported.');            
                }
            }
        }
        
        // Cleanup
        if ($Chunk) {
            $Chunk->remove();
        }
        if ($Snippet) {
            $Snippet->remove();
        }
        if ($Plugin) {
            $Plugin->remove();
        }
        if ($Template) {
            $Template->remove();
        }
        if ($TV) {
            $TV->remove();
        }
    }
    
    /**
     * Make sure we are getting our objects loaded up from the directory.
     */
    public function testCrawlDir() {
        $config = Repoman::load_config(dirname(dirname(__FILE__)));
        $Repoman = new Repoman(self::$modx,$config);
        $objects = $Repoman->crawl_dir(dirname(__FILE__).'/pkg7/seeddata/');

        $this->assertTrue(isset($objects['modMenu']), 'modMenu should have been detected in directory.');
        $this->assertTrue(isset($objects['modResource']), 'modResource should have been detected in directory.');
        foreach ($objects as $classname => $info) {
            foreach ($info as $k => $Obj) {
                if ($classname == 'modMenu') {
                    $this->assertTrue($Obj->get('description') == '88CQMLEZMS', 'Menu description incorrect.');                    
                }
                if ($classname == 'modResource') {
                    $this->assertTrue($Obj->get('description') == 'ZHD5I3KWRN', 'Page description incorrect.');                                                    
                }
            }
        }
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid filename
     */
    public function testCrawlDir2() {
        $Repoman = new Repoman(self::$modx	);
        $Repoman->crawl_dir(dirname(__FILE__).'/badseeddata/');        
    }
    
    public function testSeed() {
        if ($Menu = self::$modx->getObject('modMenu', array('text'=>'88CQMLEZMS'))) {
            $Menu->remove();
        }
        if ($Resource = self::$modx->getObject('modResource', array('alias'=>'ZHD5I3KWRN'))) {
            $Resource->remove();
        }
        
        $config = Repoman::load_config(dirname(__FILE__).'/pkg7/');
        $config['seed'] = 'seeddata/';
        $Repoman = new Repoman(self::$modx,$config);
        $Repoman->seed(dirname(__FILE__).'/pkg7/');

        $Menu = self::$modx->getObject('modMenu', array('text'=>'88CQMLEZMS'));
        $this->assertTrue(is_object($Menu), 'Menu object should have been seeded.');                    
        $this->assertTrue($Menu->get('description')=='88CQMLEZMS', 'Menu object should have been seeded.');                    
                
        $Resource = self::$modx->getObject('modResource', array('alias'=>'ZHD5I3KWRN'));
        $this->assertTrue(is_object($Resource), 'Resource object should have been seeded.');                    
        $this->assertTrue($Resource->get('alias')=='ZHD5I3KWRN', 'Resource object should have been seeded.');                    
        
        if ($Menu) {
            $Menu->remove();
        }
        if ($Resource) {
            $Resource->remove();
        }
    }
    
    /**
     *
     */
    public function testBuildPrep() {

        $pkg_root_dir = dirname(__FILE__).'/repos/pkg8/';
        $config = Repoman::load_config($pkg_root_dir);
        // Generate a random namespace for the package to ensure that the proper folder structure is generated.
        $charset='abcdefghijklmnopqrstuvwxyz';
        $str = '';
        $length = 8;
        $count = strlen($charset);
        while ($length--) {
            $str .= $charset[mt_rand(0, $count-1)];
        }

        $config['namespace'] = $str;

        $Repoman = new Repoman(self::$modx,$config);
        $core_path = $Repoman->get_core_path($pkg_root_dir);

        $Repoman->build_prep($pkg_root_dir);
    
        $core_dir = MODX_CORE_PATH.'cache/repoman/_build/core/components/'.$str;
        $this->assertTrue(file_exists($core_dir), 'Build prep should have created a valid directory structure.');
        $this->assertTrue(is_dir($core_dir), 'Build prep should have created a valid directory structure.');
    }

    /**
     *
     */
    public function testBuild() {
        // Generate a random namespace for the package to ensure that the proper folder structure is generated.
        $charset='abcdefghijklmnopqrstuvwxyz';
        $namespace = '';
        $length = 8;
        $count = strlen($charset);
        while ($length--) {
            $namespace .= $charset[mt_rand(0, $count-1)];
        }
        // Generate random version number
        $ver = rand(0,100).'.'.rand(0,100).'.'.rand(0,100);
        $release = 'beta';


        $pkg_root_dir = dirname(__FILE__).'/repos/pkg8/';
        $config = Repoman::load_config($pkg_root_dir);

        $config['namespace'] = $namespace;
        $config['package_name'] = $namespace;
        $config['version'] = $ver;
        $config['release'] = $release;


        $Repoman = new Repoman(self::$modx,$config);

        $Repoman->build($pkg_root_dir);
    
        $pkg = MODX_CORE_PATH.'packages/'.$namespace.'-'.$ver.'-'.$release;
        $this->assertTrue(file_exists($pkg.'.transport.zip'), 'Building should have created a package: '.$pkg.'.transport.zip');
        $this->assertTrue(is_dir($pkg), 'Building should have created a package directory: '.$pkg);

        // Check the manifest
        $this->assertTrue(file_exists($pkg.'/manifest.php'), 'Building should have created a manifest: '.$pkg.'/manifest.php');
        Repoman::rrmdir($pkg);
        unlink($pkg.'.transport.zip');
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
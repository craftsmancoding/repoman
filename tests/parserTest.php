<?php
class parserTest extends PHPUnit_Framework_TestCase {

    // Must be static because we set it up inside a static function
    public static $modx;
    public static $repoman;

    /**
     * Load up MODX for our tests.
     *
     */
    public static function setUpBeforeClass()
    {
        $docroot = dirname(dirname(__FILE__));
        while (!file_exists($docroot . '/config.core.php')) {
            if ($docroot == '/') {
                die('Failed to locate config.core.php');
            }
            $docroot = dirname($docroot);
        }
        if (!file_exists($docroot . '/config.core.php')) {
            die('Failed to locate config.core.php');
        }

        include_once $docroot . '/config.core.php';

        if (!defined('MODX_API_MODE')) {
            define('MODX_API_MODE', false);
        }
        require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
        include_once MODX_CORE_PATH . 'model/modx/modx.class.php';
        //require_once dirname(dirname(__FILE__)) . '/model/repoman/repoman.class.php';

        self::$modx = new modX();
        self::$modx->initialize('mgr');

        self::$repoman = new Repoman(self::$modx);

    }

    public static function tearDownAfterClass()
    {

    }
    //-----------------------------------------------------

    public function testgetObjAttributesFalse()
    {
        $docblock = file_get_contents(dirname(__FILE__).'/docblocks/false.txt');
        $Parser = new \Repoman\Parser\modchunk(self::$repoman);
        $result = $Parser->getObjAttributes($docblock);
        $this->assertFalse($result);
    }

    public function testgetObjAttributesSnippet()
    {
        $docblock = file_get_contents(dirname(__FILE__).'/docblocks/one.txt');
        $Parser = new \Repoman\Parser\modsnippet(self::$repoman);
        $result = $Parser->getObjAttributes($docblock);
        $this->assertEquals($result['name'], 'RepomanSample');
        $this->assertEquals($result['description'], 'Iterates over pages containing location data to draw a Google Map with markers on it.');

    }

    public function testgetObjAttributesChunk()
    {
        $docblock = file_get_contents(dirname(__FILE__).'/docblocks/two.txt');
        $Parser = new \Repoman\Parser\modchunk(self::$repoman);
        $result = $Parser->getObjAttributes($docblock,'<!--','-->');
        $this->assertEquals($result['name'], 'RepomanSample');
        $this->assertEquals($result['description'], 'Iterates over pages containing location data to draw a Google Map with markers on it.');
    }

    public function testGetDefault()
    {

        $Parser = new \Repoman\Parser\modchunk(self::$repoman);

        // Double-quoted
        $line = 'Some long line default="XYZ" ignore me options="zzz"';
        $result = $Parser->getDefault($line);
        $this->assertEquals($result, 'XYZ');
        $this->assertEquals('Some long line  ignore me options="zzz"', $line);

        // Single-quoted
        $line = "Some long line default='XYZ' ignore me options=\"zzz\"";
        $result = $Parser->getDefault($line);
        $this->assertEquals('XYZ', $result);
        $this->assertEquals('Some long line  ignore me options="zzz"',$line);

        // Not Quoted
        $line = 'Some long line default=XYZ ignore me options="zzz"';
        $result = $Parser->getDefault($line);
        $this->assertEquals('XYZ', $result);
        $this->assertEquals('Some long line  ignore me options="zzz"', $line);

        // Mis Quoted
        $line = 'Some long line default="XYZ ignore me';
        $result = $Parser->getDefault($line);
        $this->assertEquals('', $result);

    }

    public function testGetOptions()
    {

        $Parser = new \Repoman\Parser\modchunk(self::$repoman);

        // Double-quoted
        $line = 'Some long line default="XYZ" ignore me options="quoted word"';
        $result = $Parser->getOptions($line);
        $this->assertEquals($result, 'quoted word');
        $this->assertEquals('Some long line default="XYZ" ignore me', $line);

        // Single-quoted
        $line = "Some long line default='XYZ' ignore me options='quoted word again'";
        $result = $Parser->getOptions($line);
        $this->assertEquals('quoted word again', $result);
        $this->assertEquals("Some long line default='XYZ' ignore me",$line);

        // Unquoted JSON Hash
        $line = 'Some long line default="XYZ" ignore me options={"int":"Integer","str":"String","bool":"True/False"}';
        $result = $Parser->getOptions($line);
        $this->assertTrue(is_array($result));
        $this->assertEquals('Integer', $result['int']);
        $this->assertEquals('String', $result['str']);
        $this->assertEquals('Some long line default="XYZ" ignore me',$line);

        // Unquoted JSON Array
        $line = 'Some long line default="XYZ" ignore me options=["int","str","bool"]';
        $result = $Parser->getOptions($line);
        $this->assertTrue(is_array($result));
        $this->assertEquals('int', $result['int']);
        $this->assertEquals('str', $result['str']);
        $this->assertEquals('Some long line default="XYZ" ignore me',$line);

        // Not Quoted
        $line = 'Some long line default=XYZ ignore me options=zzz';
        $result = $Parser->getOptions($line);
        $this->assertEquals('zzz', $result);
        $this->assertEquals('Some long line default=XYZ ignore me', $line);

        // Mis Quoted
        $line = 'Some long line default="XYZ ignore me';
        $result = $Parser->getOptions($line);
        $this->assertEquals('', $result);

    }
    public function testGetProperties()
    {

        $docblock = file_get_contents(dirname(__FILE__).'/docblocks/props.txt');
        $Parser = new \Repoman\Parser\modchunk(self::$repoman);
        $result = $Parser->getProperties($docblock,'<!--','-->');
        //print_r($result);
        $this->assertTrue(is_array($result));
        $this->assertEquals(4, count($result));
    }


}
/*EOF*/
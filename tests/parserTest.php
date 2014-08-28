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

use Repoman\Bridge;
use Repoman\Config;
use Repoman\Parser\modChunk;
use Repoman\Parser\modPlugin;
use Repoman\Parser\modSnippet;
use Repoman\Parser\modTemplate;
use Repoman\Parser\modTemplatevar;
use Repoman\Parser;
use Repoman\Repoman;

class parserTest extends \PHPUnit_Framework_TestCase
{

    // Must be static because we set it up inside a static function
    public static $modx;
    public static $repoman;

    /**
     * Load up MODX for our tests.
     *
     */
    public static function setUpBeforeClass()
    {

        self::$modx = Bridge::getMODX();
        self::$modx->initialize('mgr');
        self::$repoman = new Repoman(self::$modx, new Config());
    }

    public function testRepossess()
    {
        $result = Parser::repossess('');
        $this->assertFalse($result);
    }

    public function testRepossess2()
    {
        $result = Parser::repossess('
        /**
         * @name Donk
         * @description This is my donky donk
         * @author IgnoreMe
         */');
        $this->assertTrue(is_array($result));
        $this->assertEquals($result['name'], 'Donk');
        $this->assertEquals($result['description'], 'This is my donky donk');
        $this->assertFalse(isset($result['author']));
    }

    public function testGetSubDir()
    {
        $P = new modChunk(self::$repoman);
        $subdir = $P->getSubDir();
        $this->assertEquals(self::$repoman->getCorePath() . 'elements/chunks/', $subdir);

        $P = new modPlugin(self::$repoman);
        $subdir = $P->getSubDir();
        $this->assertEquals(self::$repoman->getCorePath() . 'elements/plugins/', $subdir);

        $P = new modSnippet(self::$repoman);
        $subdir = $P->getSubDir();
        $this->assertEquals(self::$repoman->getCorePath() . 'elements/snippets/', $subdir);

        $P = new modTemplate(self::$repoman);
        $subdir = $P->getSubDir();
        $this->assertEquals(self::$repoman->getCorePath() . 'elements/templates/', $subdir);

        $P = new modTemplatevar(self::$repoman);
        $subdir = $P->getSubDir();
        $this->assertEquals(self::$repoman->getCorePath() . 'elements/tvs/', $subdir);
    }


    public function testPrepareForBuild()
    {
        self::$repoman = new Repoman(self::$modx, new Config(__DIR__ . '/pkg4/'));
        $P = new modChunk(self::$repoman);
        $actual = $P->prepareForBuild('<!--
        @name MyChunk
        @description This is my chunk
        -->
        [[++pkg4.assets_url]]
        ');
        $expected = '<!--
        @name MyChunk
        @description This is my chunk
        -->
        [[++assets_url]]';
        $this->assertEquals(normalize_string($expected), normalize_string($actual));
    }

    public function testPrepareForBuildStripDocBlocks()
    {
        self::$repoman = new Repoman(self::$modx, new Config(__DIR__ . '/pkg4/', array('strip_docblocks' => true)));
        $P = new modChunk(self::$repoman);
        $actual = $P->prepareForBuild('<!--
        @name MyChunk
        @description This is my chunk
        -->
        <!-- Second Comment -->
        [[++pkg4.assets_url]]
        ');
        $expected = '
        <!-- Second Comment -->
        [[++assets_url]]';
        $this->assertEquals(normalize_string($expected), normalize_string($actual));

    }

    public function testPrepareForBuildStripDocBlocks2()
    {
        self::$repoman = new Repoman(self::$modx, new Config(__DIR__ . '/pkg4/', array('strip_docblocks' => true)));
        $P = new modSnippet(self::$repoman);
        $actual = $P->prepareForBuild('
        <?php
        /**
         * @name MySnippet
         * @description This is my snippet
         */
        // Second Comment
        [[++pkg4.assets_url]]
        ');
        $expected = '
        <?php
        // Second Comment
        [[++assets_url]]';
        $this->assertEquals(normalize_string($expected), normalize_string($actual));

    }

    public function testPrepareForBuildStripDocComments()
    {
        self::$repoman = new Repoman(self::$modx, new Config(__DIR__ . '/pkg4/', array('strip_comments' => true)));
        $P = new modChunk(self::$repoman);
        $actual = $P->prepareForBuild('<!--
        @name MyChunk
        @description This is my chunk
        -->
        <!-- Second Comment -->
        [[++pkg4.assets_url]]
        ');
        $expected = '[[++assets_url]]';
        $this->assertEquals(normalize_string($expected), normalize_string($actual));
    }

    public function testPrepareForBuildStripDocComments2()
    {

        self::$repoman = new Repoman(self::$modx, new Config(__DIR__ . '/pkg4/', array('strip_comments' => true)));
        $P = new modSnippet(self::$repoman);
        $actual = $P->prepareForBuild('
        <?php
        /**
         * @name MySnippet
         * @description This is my snippet
         */
        // Second Comment
        [[++pkg4.assets_url]]
        ');
        $expected = '<?php
        [[++assets_url]]';
        $this->assertEquals(normalize_string($expected), normalize_string($actual));

    }


    public function testGather() {

        self::$repoman = new Repoman(self::$modx, new Config(__DIR__ . '/pkg7/'));
        $P = new modSnippet(self::$repoman);
        $dir = __DIR__.'/repos/pkg6/doesnotexist';
        $objects = $P->gather($dir);
        $this->assertTrue(empty($objects));

        $dir = __DIR__.'/repos/pkg6/snippets';
        $objects = $P->gather($dir);
        $this->assertEquals(1,count($objects));


    }
}
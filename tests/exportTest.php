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

use Repoman\Repoman;
use Repoman\Utils;
use Repoman\Config;
use Repoman\Filesystem;
use Symfony\Component\Finder\Finder;

class exportTest extends \PHPUnit_Framework_TestCase
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

        self::$modx = Utils::getMODX();
        self::$modx->initialize('mgr');
        self::$repoman = new Repoman(self::$modx, new Config());
    }


    public function testExportChunks() {

        $dir = self::$modx->getOption('core_path').'cache/repoman/tmp-'.rand(100,999);
        $Filesystem = new Filesystem();
        $Filesystem->mkdir($dir);

        $C1 = self::$modx->newObject('modChunk');
        $C1->fromArray(array(
            'name' => 'test-1-'.rand(100,999),
            'description' => 'This is a test1',
            'snippet' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur non imperdiet enim. Sed vel erat non metus euismod posuere vitae nec odio. Etiam malesuada interdum leo quis mattis. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Cras faucibus dolor quis mi fermentum euismod. Praesent eget sodales lectus, vitae blandit ante. Vestibulum tristique faucibus nibh, quis faucibus mauris ornare sed. Nam dapibus elementum massa vel facilisis. Praesent scelerisque augue convallis est consequat feugiat.',
        ));
        $result = $C1->save();
        $this->assertTrue($result);

        $C2 = self::$modx->newObject('modChunk');
        $C2->fromArray(array(
            'name' => 'test-2-'.rand(100,999),
            'description' => 'This is a test2',
            'snippet' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin eget venenatis magna. In laoreet imperdiet nisl, posuere aliquet ex pulvinar vitae. In a magna id metus ornare sollicitudin in ac massa. Nam dictum magna et dui pretium, vitae convallis enim dignissim. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Aliquam condimentum pretium mollis. Nullam sodales lorem ac fermentum aliquam. Donec lobortis malesuada orci in mollis. Sed ac urna a nunc egestas sagittis. Fusce fermentum sed metus feugiat mollis. Nunc elementum risus sit amet ante pretium maximus. Mauris mattis magna eget ligula sollicitudin, eu semper lectus scelerisque. Vestibulum malesuada non nulla vel porttitor. Pellentesque iaculis tristique velit, vitae tristique neque ullamcorper sed.',
        ));
        $result = $C2->save();
        $this->assertTrue($result);

        $options = array(
            'where' => '{"name:LIKE":"test-%"}',
            //'where' => array('name:LIKE' => 'test-%'),
            'debug' => true
        );
        $str = self::$repoman->export('modChunk', $dir, $options);

        error_log($str);

        $this->assertTrue((bool) strpos($str, 'Export Debugging Info'));

        $options['debug'] = false;
        $str = self::$repoman->export('modChunk', $dir, $options);

        $Finder = new Finder();
        $Finder->files()->in($dir);
        foreach ($Finder as $f) {
            // Print the absolute path
            error_log($f->getRealpath());
        }
        error_log('WROTE TO: '.$dir);
        $C1->remove();
        $C2->remove();
        $Filesystem->remove($dir);


    }
}
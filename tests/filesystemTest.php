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

use Repoman\Filesystem;

class filesystemTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Get a randome name
     *
     */
    private function _get_rand_name($length = 8)
    {
        // Generate a random namespace for the package to ensure that the proper folder structure is generated.
        $charset = 'abcdefghijklmnopqrstuvwxyz';
        $str = '';
        $count = strlen($charset);
        while ($length--) {
            $str .= $charset[mt_rand(0, $count - 1)];
        }
        return $str;
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage SplFileInfo::__construct() expects parameter 1 to be string, array given
     */
    public function testBadDirnametype()
    {
        $result = Filesystem::getDir(array());
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Path is not a directory
     */
    public function testNotDir()
    {
        $result = Filesystem::getDir(__FILE__);
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Path is not a directory
     */
    public function testDirDoesNotExist()
    {
        $result = Filesystem::getDir('/does/not/exist');
    }

    function testGetDir()
    {
        $fs = new Filesystem();
        $dir = dirname(__FILE__) . '/tmp';
        $fs->mkdir($dir);
        $this->assertTrue($fs->exists($dir));
        $actual = Filesystem::getDir($dir);
        $this->assertEquals($actual, $dir . '/');
        $fs->remove($dir);
    }

    function testRecursivelyRmDir()
    {
        $fs = new Filesystem();
        $dirs = array();
        $dirs[] = dirname(__FILE__) . '/tmp';
        $dirs[] = dirname(__FILE__) . '/tmp/a';
        $dirs[] = dirname(__FILE__) . '/tmp/b';
        $dirs[] = dirname(__FILE__) . '/tmp/c';
        $dirs[] = dirname(__FILE__) . '/tmp/a/1';
        $dirs[] = dirname(__FILE__) . '/tmp/a/2';
        $dirs[] = dirname(__FILE__) . '/tmp/a/3';

        $fs->mkdir($dirs);

        foreach ($dirs as $d) {
            $this->assertTrue($fs->exists($d));
        }

        $fs->remove($dirs);

        foreach ($dirs as $d) {
            $this->assertFalse($fs->exists($d));
        }
    }

    function testFilesEqual()
    {
        $fs = new Filesystem();

        $content = md5(time());
        $file1 = dirname(__FILE__) . '/tmp/test1.txt';
        $file2 = dirname(__FILE__) . '/tmp/test2.txt';
        $fs->dumpFile($file1, $content);
        $fs->dumpFile($file2, $content);

        $result = $fs->areEqual($file1, $file2);

        $this->assertTrue($result);

        $fs->remove($file1);
        $fs->remove($file2);
    }

    function testFilesNotEqual()
    {
        $fs = new Filesystem();

        $content1 = md5(time() . 'xxx');
        $content2 = md5(time() . 'yyy');
        $file1 = dirname(__FILE__) . '/tmp/test1.txt';
        $file2 = dirname(__FILE__) . '/tmp/test2.txt';
        $fs->dumpFile($file1, $content1);
        $fs->dumpFile($file2, $content2);

        $result = $fs->areEqual($file1, $file2);

        $this->assertFalse($result);

        $fs->remove($file1);
        $fs->remove($file2);
    }

    /**
     * Test recursive copying of directories
     *
     *
     */
    public function testRCopy()
    {
        $fs = new Filesystem();

        $name = $this->_get_rand_name();

        $source_dir = dirname(__FILE__) . '/samples/repo1';
        $target_dir = dirname(__FILE__) . '/tmp/' . $name;

        $fs->rcopy($source_dir, $target_dir, array());

        $this->assertTrue(file_exists($target_dir . '/elements') && is_dir($target_dir . '/elements'), 'elements directory must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/plugins') && is_dir($target_dir . '/elements/plugins'), 'elements/plugins directory must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/snippets') && is_dir($target_dir . '/elements/snippets'), 'elements/snippets directory must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/templates') && is_dir($target_dir . '/elements/templates'), 'elements/templates directory must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/tvs') && is_dir($target_dir . '/elements/tvs'), 'elements/tvs directory must exist');

        $this->assertTrue(file_exists($target_dir . '/elements/chunks/MyChunk.html'), 'elements/chunks/MyChunk.html file must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/plugins/MyPlugin.php'), 'elements/plugins/MyPlugin.php file must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/snippets/MySnippet.php'), 'elements/snippets/MySnippet.php file must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/templates/MyTemplate.html'), 'elements/templates/MyTemplate.html file must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/tvs/myTV.php'), 'elements/tvs/myTV.php file must exist');

        $fs->remove($target_dir);
    }

    /**
     * Test recursive copying of directories with omissions
     */
    public function testRCopy2()
    {
        $fs = new Filesystem();

        $name = $this->_get_rand_name();

        $source_dir = dirname(__FILE__) . '/samples/repo1';
        $target_dir = dirname(__FILE__) . '/tmp/' . $name;
        $omissions = array(
            dirname(__FILE__) . '/samples/repo1/elements/chunks/MyChunk.html'
        );

        $fs->rcopy($source_dir, $target_dir, $omissions);

        $this->assertTrue(file_exists($target_dir . '/elements') && is_dir($target_dir . '/elements'), 'elements directory must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/plugins') && is_dir($target_dir . '/elements/plugins'), 'elements/plugins directory must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/snippets') && is_dir($target_dir . '/elements/snippets'), 'elements/snippets directory must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/templates') && is_dir($target_dir . '/elements/templates'), 'elements/templates directory must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/tvs') && is_dir($target_dir . '/elements/tvs'), 'elements/tvs directory must exist');

        $this->assertFalse(file_exists($target_dir . '/elements/chunks/MyChunk.html'), 'elements/chunks/MyChunk.html file must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/plugins/MyPlugin.php'), 'elements/plugins/MyPlugin.php file must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/snippets/MySnippet.php'), 'elements/snippets/MySnippet.php file must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/templates/MyTemplate.html'), 'elements/templates/MyTemplate.html file must exist');
        $this->assertTrue(file_exists($target_dir . '/elements/tvs/myTV.php'), 'elements/tvs/myTV.php file must exist');

        $fs->remove($target_dir);
    }
}
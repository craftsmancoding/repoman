<?php
/**
 * Tried leveraging Symfony as much as possible, but there were still a few custom bits
 */
namespace Repoman;

class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{

    /**
     * Compares 2 files for equality given their paths
     *
     * @param string $path1
     * @param string $path2
     * @return boolean true if equal
     */
    public function areEqual($path1, $path2)
    {
        if (!file_exists($path1)) {
            return false;
        }
        if (!file_exists($path2)) {
            return false;
        }

        if (filesize($path1) == filesize($path2) && md5_file($path1) == md5_file($path2)) {
            return true;
        }

        return false;
    }

    /**
     * Verify a directory, converting for any OS variants and convert
     * any relative paths to absolute.  Output includes trailing slash.
     *
     * @param string $path path (or relative path) to package
     * @return string full path with trailing slash
     */
    public static function getDir($path)
    {
        // Will throw RuntimeException on array
        $file = new \SplFileInfo($path);
        if (!$file->isDir()) {
            throw new \Exception('Path is not a directory: ' . $path);
        }
        $path = $file->getRealPath() . '/';
        return $path;
    }

    /**
     * Recursively copy files and directories but with an array of files to omit.
     *
     * @param string $source dir
     * @param string $destination dir
     * @param array $omissions full paths to source files that you optionally want to omit from copying to the destination
     */
    static public function rcopy($source, $destination, $omissions = array())
    {

        $source = rtrim($source, '/');
        $destination = rtrim($destination, '/');
        if (is_dir($source)) {

            if (!file_exists($destination)) {
                if (mkdir($destination, 0777) === false) {
                    throw new \Exception('Could not create directory ' . $destination);
                }
            }

            $directory = dir($source);
            while (false !== ($readdirectory = $directory->read())) {
                if ($readdirectory == '.' || $readdirectory == '..') {
                    continue;
                }
                $PathDir = $source . '/' . $readdirectory;
                if (in_array($PathDir, $omissions)) {
                    continue; // skip
                }
                if (is_dir($PathDir)) {
                    self::rcopy($PathDir, $destination . '/' . $readdirectory, $omissions);
                    continue;
                } else {
                    copy($PathDir, $destination . '/' . $readdirectory);
                }
            }

            $directory->close();
        } else {
            print "$source is a file\n";
            return copy($source, $destination);
        }
    }
}
/*EOF*/
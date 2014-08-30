<?php
/**
 * Acts as the bridge between Composer and MODX.  This class handles doing things in MODX.
 */
namespace Repoman;

use modX;
use Repoman\Filesystem;
use xPDOTransport;
use Composer\Json\JsonFile;

class Utils
{

    public $modx;

    /**
     * @param $modx (optional existing modx instance)
     * @throws \Exception
     */
    public function __construct($modx = null)
    {
        if ($modx) {
            if (is_a($modx, '\\Modx')) {
                $this->modx =& $modx;
            } else {
                throw new \Exception('Passed argument must be instance of Modx class.');
            }
        } else {
            $this->modx = $this->getMODX();
        }
    }


    /**
     * Convert a string (e.g. package name) to a string "safe" for filenames and URLs:
     * no spaces or weird character hanky-panky.
     * See http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
     *
     * Parameters:
     *     $string - The string to sanitize.
     *     $force_lowercase - Force the string to lowercase?
     *     $anal - If set to *true*, will remove all non-alphanumeric characters.
     */
    public static function sanitize($string, $force_lowercase = true, $anal = false)
    {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
            "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
            "â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean;

        return ($force_lowercase) ?
            (function_exists('mb_strtolower')) ?
                mb_strtolower($clean, 'UTF-8') :
                strtolower($clean) :
            $clean;
    }

    /**
     * Finds the MODX instance in the current working directory, instantiates it and returns it.
     * Only requirement is that this file exists somewhere inside of a MODX directory (e.g. webroot).
     * This will sniff out a valid MODX_CORE_PATH and force the MODX_CONFIG_KEY too.
     * Syntax {$config_key}.inc.php
     *
     * @param string $dir optional directory to look in. Default: current dir
     *
     * @throws \Exception
     * @return object modx instance
     */
    public static function getMODX($dir = null)
    {
        $dir = ($dir) ? $dir : dirname(__FILE__);

        if (!defined('MODX_CORE_PATH') && !defined('MODX_CONFIG_KEY')) {

            while (true) {
                if ($dir == '/') {
                    throw new \Exception('Could not find a valid MODX config.core.php file.');
                }

                if (file_exists($dir . '/config.core.php')) {
                    include $dir . '/config.core.php';
                    break;
                }

                $dir = dirname($dir);
            }
        }

        if (!defined('MODX_CORE_PATH') || !defined('MODX_CONFIG_KEY')) {
            throw new \Exception('MODX_CORE_PATH or MODX_CONFIG_KEY undefined in ' . $dir . '/config.core.php');
        }

        if (!file_exists(MODX_CORE_PATH . 'model/modx/modx.class.php')) {
            throw new \Exception('modx.class.php not found at ' . MODX_CORE_PATH);
        }


        // fire up MODX
        require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
        require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
        require_once MODX_CORE_PATH . 'xpdo/transport/xpdotransport.class.php';

        $modx = new modx();
        $modx->initialize('mgr');
        $modx->setLogLevel(\modX::LOG_LEVEL_INFO);
        $modx->setLogTarget('ECHO');
        flush();
        return $modx;
    }

    /**
     * Check whether the given file contains valid PHP.
     *
     * @param $filename
     * @throws \Exception if the input is not a file or does not exist.
     * @return boolean true on valid syntax, boolean false if not php, exception on fail
     */
    public static function validPhpSyntax($filename)
    {
        $Filesystem = new Filesystem();
        if (!$Filesystem->exists($filename)) {
            throw new \Exception('File not found: '.$filename);
        }
        if (is_dir($filename)) {
            throw new \Exception('Directory found. File expected: '.$filename);
        }
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (strtolower($ext) != 'php') {
            return false; // skip 'em
        }
        $out = exec(escapeshellcmd("php -l $filename"));
        if (preg_match('/^Errors parsing/', $out)) {
            throw new \Exception($out);
        }
        return true;
    }
}
/*EOF*/
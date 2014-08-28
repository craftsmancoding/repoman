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
     * Get configuration for a given package path.
     * This reads the config.php (if present), and merges it with global config
     * settings.
     *
     * @param string $pkg_root_dir path to local package root (w or wo trailing slash)
     * @param array $overrides any run-time overrides
     * @return array combined config
     */
//    public static function loadConfig($pkg_root_dir, $overrides = array())
//    {
//        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);
//        $global = include dirname(dirname(__FILE__)) . '/includes/global.config.php';
//        $config = array();
//        if (file_exists($pkg_root_dir . 'composer.json')) {
//            $str = file_get_contents($pkg_root_dir . 'composer.json');
//
//            $composer = JsonFile::parseJson($str, $pkg_root_dir . 'composer.json');
//
//            if (isset($composer['extra']) && is_array($composer['extra'])) {
//                $config = $composer['extra'];
//                if (isset($composer['support'])) {
//                    $config['support'] = $composer['support'];
//                }
//                if (isset($composer['authors'])) {
//                    $config['authors'] = $composer['authors'];
//                }
//                if (isset($composer['license'])) {
//                    $config['license'] = $composer['license'];
//                }
//                if (isset($composer['homepage'])) {
//                    $config['homepage'] = $composer['homepage'];
//                }
//            }
//            if (!isset($config['namespace']) && $composer['name']) {
//                $config['namespace'] = substr($composer['name'], strpos($composer['name'], '/') + 1);
//            }
//            if (!isset($config['description']) && isset($composer['description'])) {
//                $config['description'] = $composer['description'];
//            }
//        }
//
//
//        $out = array_merge($global, $config, $overrides);
//
//        if (preg_match('/[^a-z0-9_\-]/', $out['namespace'])) {
//            throw new Exception('Invalid namespace: ' . $out['namespace']);
//        }
//        if (isset($out['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $out['version'])) {
//            throw new Exception('Invalid version.');
//        }
//
//        if ($out['core_path'] == $out['assets_path']) {
//            throw new Exception('core_path cannot match assets_path in ' . $pkg_root_dir);
//        } elseif ($out['core_path'] == $out['docs_path']) {
//            throw new Exception('core_path cannot match docs_path in ' . $pkg_root_dir);
//        }
//        // Todo... all path directives must be unique, e.g. assets cannot be the same path as core
//
//        // This nukes any deeply nested structure, e.g. build_attributes
//        $out['build_attributes'] = $global['build_attributes'];
//        if (isset($config['build_attributes']) && is_array($config['build_attributes'])) {
//            foreach ($config['build_attributes'] as $classname => $def) {
//                $out['build_attributes'][$classname] = $def;
//            }
//        }
//        return $out;
//    }

    /**
     * Finds the MODX instance in the current working directory, instantiates it and returns it.
     * Only requirement is that this file exists somewhere inside of a MODX directory (e.g. webroot).
     * This will sniff out a valid MODX_CORE_PATH and force the MODX_CONFIG_KEY too.
     * Syntax {$config_key}.inc.php
     *
     * @param string $dir optional directory to look in. Default: current dir
     * @return object modx instance
     */
    public static function getMODX($dir = '')
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
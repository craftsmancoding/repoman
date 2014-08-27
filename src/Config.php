<?php
/**
 *
 */
namespace Repoman;

use Composer\Json\JsonFile;
use Repoman\Filesystem;

class Config
{
    public $pkg_dir;
    public $overrides = array();
    public $params = array();

    /**
     * @param string $dir path to package root
     * @param array $overrides line-item overrides usually from console
     */
    public function __construct($dir,$overrides=array()) {
        $this->pkg_dir = $dir;
        $this->overrides = $overrides;
    }

    /**
     * Get all of the config, as an array
     *
     * @throws \Exception if namespace contains invalid characters
     * @throws \Exception if version is not valid
     * @return array
     */
    public function render() {
        $global = $this->getGlobal();
        $pkg = ($this->pkg_dir)? $this->getPkg($this->pkg_dir) : array();

        $out = array_merge($global, $pkg, $this->overrides);

        if (preg_match('/[^a-z0-9_\-]/', $out['namespace'])) {
            throw new \Exception('Invalid namespace: ' . $out['namespace']);
        }
        if (isset($out['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $out['version'])) {
            throw new \Exception('Invalid version.');
        }

        if ($out['core_path'] == $out['assets_path']) {
            throw new \Exception('core_path cannot match assets_path in ' . $pkg_root_dir);
        } elseif ($out['core_path'] == $out['docs_path']) {
            throw new \Exception('core_path cannot match docs_path in ' . $pkg_root_dir);
        }
        // Todo... all path directives must be unique.

        // This nukes any deeply nested structure, e.g. build_attributes
        $out['build_attributes'] = $global['build_attributes'];
        if (isset($config['build_attributes']) && is_array($config['build_attributes'])) {
            foreach ($config['build_attributes'] as $classname => $def) {
                $out['build_attributes'][$classname] = $def;
            }
        }
        return $out;
    }

    /**
     * Get global config
     * @return array
     */
    public function getGlobal() {
        $pkg_root_dir = $this->pkg_dir; // requires $pkg_root_dir
        $global = include dirname(dirname(__FILE__)) . '/includes/global.config.php';
        return $global;
    }
    /**
     * Get configuration for a given package path.
     * This reads the config.php (if present), and merges it with global config
     * settings.
     *
     * @param string $pkg_root_dir path to local package root (w trailing slash)
     *
     * @return array combined config
     */
    public static function getPkg($pkg_root_dir)
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);

        $config = array();
        if (file_exists($pkg_root_dir . 'composer.json')) {
            $str = file_get_contents($pkg_root_dir . 'composer.json');

            $composer = JsonFile::parseJson($str, $pkg_root_dir . 'composer.json');

            if (isset($composer['extra']) && is_array($composer['extra'])) {
                $config = $composer['extra'];
                if (isset($composer['support'])) {
                    $config['support'] = $composer['support'];
                }
                if (isset($composer['authors'])) {
                    $config['authors'] = $composer['authors'];
                }
                if (isset($composer['license'])) {
                    $config['license'] = $composer['license'];
                }
                if (isset($composer['homepage'])) {
                    $config['homepage'] = $composer['homepage'];
                }
            }
            if (!isset($config['namespace']) && $composer['name']) {
                $config['namespace'] = substr($composer['name'], strpos($composer['name'], '/') + 1);
            }
            if (!isset($config['description']) && isset($composer['description'])) {
                $config['description'] = $composer['description'];
            }
        }
        return $config;
    }

}
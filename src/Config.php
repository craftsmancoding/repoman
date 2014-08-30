<?php
/**
 *
 */
namespace Repoman;

use JsonSchema\Validator;
use Repoman\Filesystem;

class Config
{
    public $Filesystem;
    public $pkg_root_dir;
    public $config_file = 'composer.json';

    /**
     * @param Filesystem $Filesystem
     */
    public function __construct(Filesystem $Filesystem) {
        $this->Filesystem = $Filesystem;
    }

    /**
     * Set Root directory for the package, the one containing the root composer.json
     * @param $dir string
     */
    public function setPkgRootDir($dir) {
        $this->pkg_root_dir = $this->Filesystem->getDir($dir);
    }

    /**
     * Get Directory at the root of the package (the dir holding the composer.json)
     * @return string
     */
    public function getPkgRootDir() {
        return $this->pkg_root_dir;
    }

    /**
     * Get all of the config, as an array, intelligently merging overrides
     *
     * @param array $overrides
     * @throws \Exception if namespace contains invalid characters
     * @throws \Exception if version is not valid
     * @return array
     */
    public function getAll(array $overrides=array()) {
        $global = $this->getGlobal();
        $pkg = $this->getPkg();

        $out = array_merge($global, $pkg, $overrides);

        if (preg_match('/[^a-z0-9_\-]/', $out['namespace'])) {
            throw new \Exception('Invalid namespace: ' . $out['namespace']);
        }
        if (isset($out['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $out['version'])) {
            throw new \Exception('Invalid version.');
        }

        if ($out['core_path'] == $out['assets_path']) {
            throw new \Exception('core_path cannot match assets_path in ' . $this->pkg_root_dir);
        } elseif ($out['core_path'] == $out['docs_path']) {
            throw new \Exception('core_path cannot match docs_path in ' . $this->pkg_root_dir);
        }
        // Todo... all path directives must be unique.  Validate Config?

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
        $pkg_root_dir = $this->pkg_root_dir; // make available to the include $pkg_root_dir
        $global = include dirname(dirname(__FILE__)) . '/includes/global.config.php';
        return $global;
    }

    /**
     * Get configuration for a given package path.
     * This reads the package's composer.json (if present), and merges it with global config
     * settings.
     *
     * @internal param string pkg_dir path to local package root (w trailing slash)
     *
     * @return array combined config
     */
    public function getPkg()
    {
        $config = array();
        if (file_exists($this->pkg_root_dir . $this->config_file)) {

            $composer = $this->parseJson();

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

    /**
     * Double-dipping a bit here: we include the justinrainbow/json-schema package to validate composer.json
     * files when repoman is called as a stand-alone tool (and not as a composer plugin).  Otherwise we could
     * leverage composer's inclusion of the same justinrainbow/json-schema package.  We have also downloaded
     * a copy of the composer.json schema file (res/composer-schema.json)
     *
     * @throws \Exception if json schema is invalid
     * @return array
     */
    public function parseJson() {

        $schemaFile = __DIR__ . '/../res/composer-schema.json';
        $schemaData = json_decode(file_get_contents($schemaFile));
        $file = $this->getPkgRootDir().$this->config_file;
        $contents = file_get_contents($file);
        $data = json_decode($contents);
        $validator = new Validator();
        $validator->check($data, $schemaData);
        if (!$validator->isValid()) {
            $error_msg = "\n";
            foreach ((array) $validator->getErrors() as $error) {
                $error_msg .= ($error['property'] ? $error['property'].' : ' : '').$error['message']. "\n";
            }
            throw new \Exception('"'.$file.'" does not match the expected JSON schema.'.$error_msg);
        }

        return json_decode($contents,true); // we want it as an array
    }
}
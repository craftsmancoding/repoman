<?php
/**
 * This class has some static methods for utility functions that can be used
 * before the class is instantiated.
 *
 */
namespace Repoman;

use modX;
use xPDO;
use Repoman\Filesystem;
use Repoman\Utils;
use Repoman\Parser;

//use Repoman\Parser\modChunk;
//use Repoman\Parser\modPlugin;
//use Repoman\Parser\modSnippet;
//use Repoman\Parser\modTemplate;
//use Repoman\Parser\modTemplatevar;


class Repoman
{

    public $pkg_root_dir;
    public $modx;
    public $config = array();
    public $Filesystem;


    // Used when tracking build attributes and fromDeepArray
    public $breadcrumb = array();
    // Used to provide transparency and sorta log stuff
    public static $queue = array();
    public $readme_filenames = array('README.md', 'readme.md');

    public static $cache_opts = array();

    public $prepped = false;

    public $build_core_path;
    public $build_assets_path;

    const CACHE_DIR = 'repoman';

    /**
     * @param \modX  $modx
     * @param Config $Config
     * @param        $pkg_root_dir
     */
    public function __construct(modX $modx, Config $Config)
    {
        $this->modx         = & $modx;
        $this->config       = $Config->getAll();
        $this->pkg_root_dir = $Config->getPkgRootDir();
        $this->Filesystem   = new Filesystem();
        self::$cache_opts   = array(xPDO::OPT_CACHE_KEY => self::CACHE_DIR);
    }

    /**
     * Add packages to MODX's radar so we can use their objects.
     *
     * @param $pkg_root_dir
     */
    private function _addPkgs($pkg_root_dir)
    {
        $Config = new Config($pkg_root_dir);
        $args   = $Config->getAll();
        $pkg    = (isset($args['packages'])) ? $args['packages'] : array();

        foreach ($pkg as $p) {
            $this->modx->addPackage($p['pkg'], $pkg_root_dir . $p['path'], $p['table_prefix']);
        }
    }

    /**
     * Make sure build attributes have been defined for the current breadcrumb.
     *
     * @param array  $atts
     * @param string $classname (for messaging)
     *
     * @return void or throws error
     */
    private function _check_build_attributes($atts, $classname)
    {

        if ($this->get('overwrite')) return;

        foreach ($this->breadcrumb as $i => $alias) {
            if (isset($atts[related_object_attributes][$alias])) {
                $atts = $atts[related_object_attributes][$alias];
                // Do something?
            } else {
                throw new \Exception('build_attributes not set for ' . $classname . '-->' . implode('-->', $this->breadcrumb) . ' in composer.json. Make sure your definitions include "related_objects" and "related_object_attributes"');
            }
        }
    }

    /**
     * Create/Update the namespace
     *
     * @param string $pkg_root_dir to the repo
     */
    private function _create_namespace($pkg_root_dir)
    {
        $this->modx->log(modX::LOG_LEVEL_DEBUG, "Creating namespace: " . $this->get('namespace'));

        $name = $this->get('namespace');
        if (empty($name)) {
            throw new \Exception('namespace parameter cannot be empty.');
        }
        if (preg_match('/[^a-z0-9_\-\/]/', $this->get('namespace'))) {
            throw new \Exception('Invalid namespace: ' . $this->get('namespace'));
        }

        $N = $this->modx->getObject('modNamespace', $this->get('namespace'));
        if (!$N) {
            $N = $this->modx->newObject('modNamespace');
            $N->set('name', $this->get('namespace'));
        }
        // Infers where the controllers live
        $N->set('path', rtrim($this->getCorePath($pkg_root_dir), '/') . trim($this->get('controllers_path'), '/') . '/');
        $N->set('assets_path', $this->getAssetsPath($pkg_root_dir));

        // "Logging" the paper trail
        $this->remember('modNamespace', $this->get('namespace'), $N->toArray());

        if (!$this->get('dry_run')) {
            if (!$N->save()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, "Error saving Namespace: " . $this->get('namespace'));
            }
            // Prepare Cache folder for tracking object creation
            self::$cache_opts = array(xPDO::OPT_CACHE_KEY => self::CACHE_DIR . '/' . $this->get('namespace'));
            $data             = $this->getCriteria('modNamespace', $N->toArray());
            $this->modx->cacheManager->set('modNamespace/' . $N->get('name'), $data, 0, Repoman::$cache_opts);
            $this->modx->log(modX::LOG_LEVEL_INFO, "Namespace created/updated: " . $this->get('namespace'));
        }
    }

    /**
     * For creating Repoman's system settings (not for user created settings)
     *
     *     pkg_name.assets_path
     *     pkg_name.assets_url
     *     pkg_name.src_path
     *
     * @param $namespace
     * @param $key
     * @param $value
     *
     * @throws \Exception
     * @return void
     */
    private function _create_setting($namespace, $key, $value)
    {

        if (empty($namespace)) {
            throw new \Exception('namespace parameter cannot be empty.');
        }

        $Setting = $this->modx->getObject('modSystemSetting', array('key' => $key));

        if (!$Setting) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, "Creating new System Setting: $key");
            $Setting = $this->modx->newObject('modSystemSetting');
        }

        $Setting->set('key', $key);
        $Setting->set('value', $value);
        $Setting->set('xtype', 'textfield');
        $Setting->set('namespace', $namespace);
        $Setting->set('area', 'default');

        $this->remember('modSystemSetting', $key, $Setting->toArray());

        if (!$this->get('dry_run')) {
            if ($Setting->save()) {
                $this->modx->log(modX::LOG_LEVEL_INFO, "System Setting created/updated: $key");
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, "Error saving System Setting: $key");
            }
        }
        $this->modx->setOption($key, $value); // for cache
    }

    /**
     * Get an array of element objects for the given $objecttype
     *
     * @param string $objecttype
     * @param string $pkg_root_dir path to local package root (w trailing slash)
     *
     * @return array of objects of type $objecttype
     */
    private function _get_elements($objecttype, $pkg_root_dir)
    {
        //require_once dirname(__FILE__) . '/repoman_parser.class.php';
        //require_once dirname(__FILE__) . '/objecttypes/' . strtolower($objecttype) . '_parser.class.php';
        $classname = 'Parser\\' . $objecttype;
        $Parser    = new $classname($this);

        return $Parser->gather($pkg_root_dir);
    }

    //------------------------------------------------------------------------------
    //! Static
    //------------------------------------------------------------------------------

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
    function sanitize($string, $force_lowercase = true, $anal = false)
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

    //------------------------------------------------------------------------------
    //! Public
    //------------------------------------------------------------------------------
    /**
     * Assistence function for examining MODX objects and their relations.
     * _pkg (string) colon-separated string defining the arguments for addPackage() --
     *      package_name, model_path, and optionally table_prefix
     *      e.g. `tiles;[[++core_path]]components/tiles/model/;tiles_` or
     *      If only the package name is supplied, the path is assumed to be "[[++core_path]]components/$package_name/model/"
     *
     * Optional options:
     *      aggregates : if set, only aggregate relationships will be shown.
     *      composites : if set, only composite relationships will be shown.
     *      pkg : colon-separated input for loading a package via addPackage.
     *
     * @param       $classname
     * @param array $args
     *
     * @throws \Exception
     * @return array
     */
    public function graph($classname, $args = array())
    {

        $aggregates = (isset($args['aggregates'])) ? $args['aggregates'] : false;
        $composites = (isset($args['composites'])) ? $args['composites'] : false;


        //Load up configs packages
        if ($dir = $this->modx->getOption('repoman.dir')) {
            foreach (scandir($dir) as $file) {
                if ('.' === $file) continue;
                if ('..' === $file) continue;
                if (is_dir($dir . $file)) {
                    $attributes = self::load_config($dir . $file . '/');
                    $this->_addPkgs($attributes, $dir . $file . '/');
                }
            }
        }

        if (empty($classname)) {
            $out = "\n-------------------------\n";
            $out .= "All Available Classes\n";
            $out .= "-------------------------\n";
            foreach ($this->modx->classMap as $parentclass => $childclasses) {

                $out .= "\n" . $parentclass . "\n" . str_repeat('-', strlen($parentclass)) . "\n";
                foreach ($childclasses as $c) {
                    $out .= "    " . $c . "\n";
                }
            }

            return $out;
        }

        if (empty($classname)) {
            throw new \Exception('classname is required.');
        }

        $array = $this->modx->getFields($classname);

        // Default
        $related = array_merge($this->modx->getAggregates($classname), $this->modx->getComposites($classname));

        if ($aggregates) {
            $related = $this->modx->getAggregates($classname);
        } elseif ($composites) {
            $related = $this->modx->getComposites($classname);
        }

        foreach ($related as $alias => $def) {
            $array[$alias] = $def;
        }

        return $array;

    }


    /**
     * Parse command line arguments
     *
     * @param array $args
     *
     * @return array
     */
    public static function parse_args($args)
    {
        $overrides = array();
        foreach ($args as $a) {
            if (substr($a, 0, 2) == '--') {
                if ($equals_sign = strpos($a, '=', 2)) {
                    $key             = substr($a, 2, $equals_sign - 2);
                    $val             = substr($a, $equals_sign + 1);
                    $overrides[$key] = $val;
                } else {
                    $flag             = substr($a, 2);
                    $overrides[$flag] = true;
                }
            }
        }

        return $overrides;
    }

    /**
     * Add package settings to the local MODX instance:
     * create namespace and expected System Settings for the package.
     *      - {namsepace}.assets_url
     *      - {namespace}.assets_path
     *      - {namespace}.core_path
     *
     * @return void
     */
    public function prepModx()
    {
        if ($this->prepped) {
            return;
        }

        $this->modx->log(modX::LOG_LEVEL_DEBUG, "Prep: creating namespace and system settings.");

        $this->_create_namespace($this->pkg_root_dir);

        // Settings
        //$rel_path = preg_replace('/^' . preg_quote(MODX_BASE_PATH, '/') . '/', '', $pkg_root_dir); // convert path to url
        $rel_path   = $this->Filesystem->makePathRelative($this->modx->getOption('base_path'), $this->pkg_root_dir);
        $assets_url = $this->modx->getOption('base_url') . $rel_path . trim($this->get('assets_path'), '/') . '/'; // ensure trailing slash
        $this->_create_setting($this->get('namespace'), $this->get('namespace') . '.assets_url', $assets_url);
        $this->_create_setting($this->get('namespace'), $this->get('namespace') . '.assets_path', rtrim($this->pkg_root_dir, '/') . '/' . trim($this->get('assets_path'), '/') . '/');
        $this->_create_setting($this->get('namespace'), $this->get('namespace') . '.core_path', rtrim($this->pkg_root_dir, '/') . trim($this->get('core_path'), '/') . '/');
        $this->prepped = true;

        $this->modx->cacheManager->refresh(array('system_settings' => array()));
    }




    //------------------------------------------------------------------------------
    //! Public
    //------------------------------------------------------------------------------

    /**
     * Unified build script: build a MODX transport package from files contained
     * inside $pkg_root_dir
     * TODO: use Finder instead of glob() to iterate over files
     * @throws \Exception
     */
    public function build($force_static = false)
    {
        $this->build_prep();
        $this->config['is_build']     = true; // TODO
        $this->config['force_static'] = false; // TODO

        $required = array('package_name', 'namespace', 'version', 'release');
        foreach ($required as $k) {
            if (!$this->get($k)) {
                throw new \Exception('Missing required configuration parameter: ' . $k);
            }
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Beginning build of package "' . $this->get('package_name') . '"');

        $this->modx->loadClass('transport.modPackageBuilder', '', false, true);
        $builder                = new modPackageBuilder($this->modx);
        $sanitized_package_name = self::sanitize($this->get('package_name'), true, true);
        $builder->createPackage($sanitized_package_name, $this->get('version'), $this->get('release'));
        $builder->registerNamespace($this->get('namespace'), false, true, '{core_path}components/' . $this->get('namespace') . '/');

        // Tests (Validators): this is run BEFORE your package code is in place
        // so you cannot reference/include package files from your validator! They won't exist when the code is run.
        $validator_file = $this->getCorePath($pkg_root_dir) . rtrim($this->get('validators_dir'), '/') . '/install.php';
        if (file_exists($validator_file)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaging validator ' . $validator_file);
            $config               = $this->config;
            $config['source']     = $validator_file;
            $validator_attributes = array(
                'vehicle_class'                              => 'xPDOScriptVehicle',
                'source'                                     => $validator_file,
                xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => $this->get('abort_install_on_fail')
            );
            $vehicle              = $builder->createVehicle($config, $validator_attributes);
            $builder->putVehicle($vehicle);
        } else {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'No validator detected at ' . $validator_file);
        }

        $Category = $this->modx->newObject('modCategory');
        $Category->set('category', $this->get('category'));

        // Import Elements
        $chunks    = self::_get_elements('modChunk', $pkg_root_dir);
        $plugins   = self::_get_elements('modPlugin', $pkg_root_dir);
        $snippets  = self::_get_elements('modSnippet', $pkg_root_dir);
        $tvs       = self::_get_elements('modTemplateVar', $pkg_root_dir);
        $templates = self::_get_elements('modTemplate', $pkg_root_dir);

        if ($chunks) $Category->addMany($chunks);
        if ($plugins) $Category->addMany($plugins);
        if ($snippets) $Category->addMany($snippets);
        if ($templates) $Category->addMany($templates);
        if ($tvs) $Category->addMany($tvs);

        // TODO: skip this if there are no elements
        //if (empty($chunks) && empty($plugins) && empty($snippets) && empty($templates) && empty($tvs)) {
        $build_attributes = array();
        $build_attributes = $this->getBuildAttributes($Category, 'modCategory');
        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'build_attributes for ' . $Category->_class . "\n" . print_r($build_attributes, true));
        $vehicle = $builder->createVehicle($Category, $build_attributes);
        //}
        //$builder->putVehicle($vehicle);


        // Files...: TODO: these need their own builder
        // We package these from the temporary copies inside of repoman's cache.
        // Assets
        if (file_exists($this->build_assets_path) && is_dir($this->build_assets_path)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing assets from ' . $this->build_assets_path);
            $vehicle->resolve('file', array(
                'source' => rtrim($this->build_assets_path, '/'),
                'target' => "return MODX_ASSETS_PATH . 'components/';",
            ));
        }
        // Core
        if (file_exists($this->build_core_path) && is_dir($this->build_core_path)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing core files from ' . $this->build_core_path);
            $vehicle->resolve('file', array(
                'source' => rtrim($this->build_core_path, '/'),
                'target' => "return MODX_CORE_PATH . 'components/';",
            ));
        }

        $builder->putVehicle($vehicle);

        // Migrations: we attach our all-purpose resolver to handle migrations
        $config           = $this->config;
        $config['source'] = dirname(__FILE__) . '/resolver.php';
        $attributes       = array('vehicle_class' => 'xPDOScriptVehicle');
        $vehicle          = $builder->createVehicle($config, $attributes);
        $builder->putVehicle($vehicle);

        // Add Version Setting
        $repoman_version_build_attributes = array(
            xPDOTransport::UNIQUE_KEY    => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true, // Tricky: we need to update the value here
        );
        $VersionSetting                   = $this->modx->newObject('modSystemSetting');
        $VersionSetting->set('key', $this->get('namespace') . '.version');
        $VersionSetting->set('value', $this->get('version'));
        $VersionSetting->set('xtype', 'textfield');
        $VersionSetting->set('namespace', $this->get('namespace'));
        $VersionSetting->set('area', $this->get('namespace') . ':default');
        $vehicle = $builder->createVehicle($VersionSetting, $repoman_version_build_attributes);
        $builder->putVehicle($vehicle);


        // Optionally Load Seed data
        $dirs = $this->getSeedPaths($pkg_root_dir);
        foreach ($dirs as $d) {
            $objects = $this->crawlDir($d);
            foreach ($objects as $classname => $info) {
                foreach ($info as $k => $Obj) {
                    $build_attributes = $this->getBuildAttributes($Obj, $classname);
                    $this->modx->log(modX::LOG_LEVEL_DEBUG, $classname . ' created');
                    $vehicle = $builder->createVehicle($Obj, $build_attributes);
                    $builder->putVehicle($vehicle);
                }
            }
        }

        // Package Attributes (Documents)
        $dir = $this->getDocsPath($pkg_root_dir);
        // defaults
        $docs = array(
            'readme'    => 'This package was built using Repoman (https://github.com/craftsmancoding/repoman/)',
            'changelog' => 'No change log defined.',
            'license'   => file_get_contents(dirname(dirname(dirname(__FILE__))) . '/docs/license.txt'),
        );
        if (file_exists($dir) && is_dir($dir)) {
            $files      = array();
            $build_docs = $this->get('build_docs');
            if (!empty($build_docs) && is_array($build_docs)) {
                foreach ($build_docs as $d) {
                    $files[] = $dir . $d;
                }
            } else {
                $files = glob($dir . '*.{html,txt}', GLOB_BRACE);
            }

            foreach ($files as $f) {
                $stub        = basename($f, '.txt');
                $stub        = basename($stub, '.html');
                $docs[$stub] = file_get_contents($f);
                if (strtolower($stub) == 'readme') {
                    $docs['readme'] = $docs['readme'] . "\n\n"
                        . 'This package was built using Repoman (https://github.com/craftsmancoding/repoman/)';
                }
                $this->modx->log(modX::LOG_LEVEL_INFO, "Adding doc $stub from $f");
            }
        } else {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'No documents found in ' . $dir);
        }
        $builder->setPackageAttributes($docs);
        // Zip up the package
        $builder->pack();

        $zip = strtolower($sanitized_package_name) . '-' . $this->get('version') . '-' . $this->get('release') . '.transport.zip';
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Build complete: ' . MODX_CORE_PATH . 'packages/' . $zip);
        if (!file_exists(MODX_CORE_PATH . 'packages/' . $zip)) {
            throw new \Exception('Transport package not created: ' . $zip . ' Please review the logs.');
        }
    }

    /**
     * Move directories into place in preparation for build. This recreates the
     * directory structure MODx uses for packages.
     *
     * @throws \Exception
     */
    public function build_prep()
    {

        if (!$this->get('namespace')) {
            throw new \Exception('Namespace cannot be empty.');
        }

        $build_dir = $this->modx->getOption('core_path') . 'cache/repoman/_build/';

        $this->build_assets_path = $build_dir . 'assets/components/' . $this->get('namespace');
        $this->build_core_path   = $build_dir . 'core/components/' . $this->get('namespace');

        $assets_src = $this->getAssetsPath($this->pkg_root_dir);
        $core_src   = $this->getCorePath($this->pkg_root_dir);

        $this->Filesystem->remove($build_dir);

        $omissions = array();
        $omit      = $this->get('omit');
        foreach ($omit as $o) {
            $omissions[] = rtrim($this->pkg_root_dir . $o, '/');
        }
        $this->modx->log(modX::LOG_LEVEL_DEBUG, "Defined omissions from package build: \n" . print_r($omissions, true));

        // Assets
        if ($this->Filesystem->exists($assets_src)) {
            // This will throw its own IOException on fail
            $this->Filesystem->mkdir($this->build_assets_path, $this->get('dir_mode'));
            $this->Filesystem->mirror($assets_src, $this->build_assets_path);
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Mirrored assets directory to ' . $this->build_assets_path);
        }

        // Core
        // This will throw its own IOException on fail
        $this->Filesystem->mkdir($this->build_core_path, $this->get('dir_mode'));
        $this->Filesystem->rcopy($core_src, $this->build_core_path, $omissions);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Recursively copied core directory to ' . $this->build_core_path);

    }

    /**
     * Iterate over the specified $dir to load up either PHP or JSON arrays representing objects,
     * then return an array of the corresponding objects.  The classname of the objects must be
     * inherent in the filename.  Filenames may have the following format:
     *
     *     classname[.identifier].(php|json)
     *
     * For example, modSystemSetting.php contains a MODX System Setting, or modUser.1.json contains
     * a user.
     *
     * TODO: allow a modifier at the front of the filename so order can be manually defined.
     * TODO: use Finder instead of glob to itereate over files
     *
     * @param string $dir
     *
     * @throws \Exception
     * @return array of objects : keys for the classname
     */
    public function crawlDir($dir)
    {

        if (!file_exists($dir) || !is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not crawl directory. Directory does not exist: ' . $dir);

            return array();
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Crawling directory for objects ' . $dir);

        $objects = array();
        $dir     = rtrim($dir, '/');
        $files   = glob($dir . '/*{.php,.json}', GLOB_BRACE);

        foreach ($files as $f) {

            preg_match('/^(\w+)(.?\w+)?\.(\w+)$/', basename($f), $matches);
            if (!isset($matches[3])) throw new \Exception('Invalid filename ' . $f);

            $classname = $matches[1];
            $ext       = $matches[3];
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Processing object(s) in ' . basename($f));
            $fields = $this->modx->getFields($classname);
            if (empty($fields)) throw new \Exception('Unrecognized object classname ' . $classname . ' in file ' . $f);

            $is_json = (strtolower($ext) == 'php') ? false : true;

            $data = $this->load_data($f, $is_json);

            $i          = 0;
            $attributes = $this->get('build_attributes');
            if (!isset($attributes[$classname])) {
                throw new \Exception('build_attributes not defined for classname "' . $classname . '" in composer.json');
            }
            foreach ($data as $objectdata) {
                // Does the object already exist?
                if (!$this->get('is_build')) {
                    $Object = $this->modx->getObject($classname, $this->getCriteria($classname, $objectdata));
                    if ($Object && !$attributes[$classname]['update_object'] && !$this->get('overwrite')) {
                        $this->modx->log(modX::LOG_LEVEL_INFO, 'Skipping... Update Object not allowed without overwrite: ' . $classname);
                        continue;
                    }
                }

                $this->breadcrumb      = array();
                $objects[$classname][] = $this->fromDeepArray($classname, $objectdata, true, true, true, 0);
                $this->_check_build_attributes($attributes[$classname], $classname);

            }
        }

        return $objects;
    }

    /**
     * Extract objects (Settings, Snippets, Pages et al) from MODX and store them in the
     * repository as either object or seed data.
     *
     * --classname string valid classname for a defined object
     * --where JSON string or PHP array defining criteria
     * --target dir rel to pkg_root_dir where non-element objects are saved as seed data
     * --overwrite
     * --package : package_name, model_path, and optionally table_prefix
     *      e.g. `tiles:[[++core_path]]components/tiles/model/:tiles_` or
     *      If only the package name is supplied, the path is assumed to be "[[++core_path]]components/$package_name/model/"
     *
     *
     * @param       $classname
     * @param       $target_dir
     * @param array $options
     *
     * @throws \Exception
     *
     * @return void
     */
    public function export($classname, $target_dir, $options = array())
    {
        $defaults = array(
            'where'     => null,
            'graph'     => null,
            'limit'     => 1,
            'dir'       => array(),
            'move'      => false,
            'debug'     => false,
            'overwrite' => false,
        );

        $options = array_merge($defaults, $options);
        // Criteria must be passed as a dedicated variable
        $where = (isset($options['where'])) ? $options['where'] : array();
        $where = (!is_array($where)) ? json_decode($where, true) : $where;

        if (!$options['limit'] || $options['limit'] < 0) {
            $options['limit'] = 1; // no div by zero
        }

        if (empty($classname)) {
            throw new \Exception('Parameter "classname" is required.');
        }

        $this->Filesystem->mkdir($target_dir);
        $target_dir = $this->Filesystem->getDir($target_dir);

        $is_element = false;
        $Parser     = null;
        if (in_array($classname, array('modSnippet', 'modChunk', 'modTemplate', 'modPlugin', 'modTemplateVar'))) {
            $is_element    = true;
            $element_class = '\\Repoman\\Parser\\' . $classname;
            $Parser        = new $element_class($this);
        }


        foreach ($options['dir'] as $d) {
            $this->_addPkgs($d);
        }

        $criteria = $this->modx->newQuery($classname);
        if ($where) {
            $criteria->where($where); // This must be a variable, not an array['key'] !!!
        }
        $result_cnt = $this->modx->getCount($classname, $criteria);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Results found: ' . $result_cnt);

        if ($options['graph']) {
            $results = $this->modx->getCollectionGraph($classname, $options['graph'], $criteria);
            $graph = true;
        } else {
            $results = $this->modx->getCollection($classname, $criteria);
            $graph = false;
        }
        if ($options['debug']) {
            $criteria->prepare();
            $out = "----------------------------------\n";
            $out .= "Export Debugging Info\n";
            $out .= "----------------------------------\n\n";
            $out .= "Raw Where filters:\n" . print_r($where, true) . "\n";
            $out .= "Raw SQL Query:\n";
            $out .= $criteria->toSQL();
            $out .= "\n\nResults found: {$result_cnt}\n\n";

            return $out;
        }

        if ($results) {
            $i    = 1;
            $j    = 1; // tracks "groups" of records
            $pack = array();
            foreach ($results as $r) {
                if ($is_element) {
                    $filename = $target_dir . $j . '.' . $Parser->getBasename($r);
                    error_log('=====================> FILENAME: '.$filename);
                    $Parser->create($filename, $r, $graph, $options['move']);
                } else {
                    $array  = $r->toArray('', false, false, $graph);
                    $pack[] = $array;
                    // Write to file
                    if (!($i % $limit) || $i == $result_cnt) {
                        $filename = $target_dir . '/' . $j . '.' . $classname . '.json';
                        if (file_exists($filename) && !$this->get('overwrite')) {
                            throw new \Exception('Overwrite not permitted ' . $filename);
                        }
                        if (false === file_put_contents($filename, json_encode($pack))) {
                            throw new \Exception('Could not write to file ' . $filename);
                        } else {
                            $this->modx->log(modX::LOG_LEVEL_INFO, 'Created object file at ' . $filename);
                        }
                        $pack = array(); // reset
                        $j++;
                    }
                    $i++;
                }
            }
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'No matching results found for ' . $classname);
        }

    }


    /**
     * Return an object based on the $objectdata array.
     *
     * Our take-off from xPDO's fromArray() function, but one that can import whatever toArray()
     * spits out.  It's not a method on the object, however, so we have to do some dancing here
     * to determine whether we are creating a new objects or using existing ones.
     *
     * @param string  $classname
     * @param array   $objectdata
     * @param bool    $set_pks
     * @param boolean $rawvalues    e.g. for modUser, you'd enter the password plaintext and it gets hashed.
     *                              Set to true if you want to store the literal hash.
     * @param integer $breadcrumb_i tracks depth of breadcrumb
     *
     * @return object
     */
    function fromDeepArray($classname, $objectdata, $set_pks = false, $rawvalues = false, $breadcrumb_i = 0)
    {
        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'fromDeepArray begin setting ' . $classname . ' (set_pks: ' . $set_pks . ' rawvalues: ' . $rawvalues . "):\n" . print_r($objectdata, true));

        // Find existing object or make a new one
        if ($this->get('is_build')) {
            $Object = $this->modx->newObject($classname);
        } else {
            $Object = $this->modx->getObject($classname, $this->getCriteria($classname, $objectdata));
            if (!$Object) {
                $Object = $this->modx->newObject($classname);
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Creating new object for ' . $classname);
            } else {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Using existing object for ' . $classname);
            }
        }
        // The sincere hope is that we can rely on this glorious function...
        $Object->fromArray($objectdata, '', $set_pks, $rawvalues);
        // ...and not this alternative:
        //foreach ($objectdata as $k =>$v) {
        //    $Object->set($k,$v);
        //}

        $related = array_merge($this->modx->getAggregates($classname), $this->modx->getComposites($classname));

        foreach ($related as $alias => $def) {
            // Is there any data provided for related objects?
            if (isset($objectdata[$alias])) {
                $rel_data = $objectdata[$alias];
                $def      = $related[$alias];

                if (!is_array($rel_data)) {
                    $this->modx->log(modX::LOG_LEVEL_WARN, 'Data in ' . $classname . '[' . $alias . '] not an array.');
                    continue;
                }

                $this->breadcrumb[$breadcrumb_i] = $alias;

                if ($def['cardinality'] == 'one') {
                    $one = $this->fromDeepArray($def['class'], $rel_data, $set_pks, $rawvalues, $breadcrumb_i + 1);
                    $Object->addOne($one);
                } else {
                    if (!isset($rel_data[0])) {
                        $rel_data = array($rel_data);
                    }
                    $many = array();
                    foreach ($rel_data as $r) {
                        $many[] = $this->fromDeepArray($def['class'], $r, $set_pks, $rawvalues, $breadcrumb_i + 1);
                    }
                    $Object->addMany($many);
                }

            }
        }
        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'fromDeepArray completed setting ' . $classname . "\n" . print_r($Object->toArray(), true));

        return $Object;
    }

    /**
     * Our config getter
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return (isset($this->config[$key])) ? $this->config[$key] : null;
    }

    /**
     * Our config setter
     *
     * @param string $key
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * When building packages, these attributes govern how objects are updated
     * when the package is installed.  One difficulty here is that one instance
     * of an object may have many related objects (and thus require deeply nested
     * build attributes), whereas another object instance may have no related objects.
     * So this function traces out all of an object's relations and grows the build
     * attributes accordingly.
     *
     * @param object $Obj
     * @param string $classname
     *
     * @throws \Exception
     * @return array
     */
    public function getBuildAttributes($Obj, $classname)
    {

        $attributes = $this->get('build_attributes');
        if (!isset($attributes[$classname])) {
            throw new \Exception('build_attributes not defined for class "' . $classname . '"');
        }

        // The attributes for the base
        $out = $attributes[$classname];

        return $out;
        // BUG: dynamic detection is not working... TODO: fix the wormhole. Let the user specify this manually too.
        // see _check_build_attributes.
        // Any related objects?
        /*
                $related = array_merge($this->modx->getAggregates($classname), $this->modx->getComposites($classname));

                foreach ($related as $alias => $def) {
                    if (!empty($Obj->$alias)) {
                        // WTF?  Not sure why the Resources alias comes overloaded with info
                        // if unchecked, this will bomb out the memory usage
                        if ($classname == 'modTemplate' && $alias == 'Resources') {
                            continue;
                        }
                        if (in_array($alias, array('LexiconEntries'))) {
                            continue;
                        }
                        $out[xPDOTransport::RELATED_OBJECTS] = true;
                        $rel_class = $def['class'];
                        if ($def['cardinality'] == 'one') {
                            $relObj = $Obj->getOne($alias);
                        }
                        else {
                            $relObjs = $Obj->getMany($alias);
                            $relObj = array_shift($relObjs);
                        }
                        $out[related_object_attributes][$rel_class] = $this->getBuildAttributes($relObj,$def['class']);
                    }
                }
                return $out;
        */
    }

    /**
     * Generate an array that can be passed as filter criteria to getObject so that we
     * can identify and load existing objects. In practice, we don't always use the primary
     * key to load an object (because we are defining objects abstractly and the primary key
     * is a feature of the database where it gets installed) so for each classname, we need
     * a field (or fields) to consider when searching for existing records.  E.g. for
     * modSnippet or modChunk, we look only at the name, but for modResource we might look
     * at both context & uri.
     *
     * @param string $classname
     * @param array  $attributes data for a single object representing $classname
     *
     * @return array
     */
    public function getCriteria($classname, $attributes)
    {
        $build_attributes = $this->get('build_attributes');
        if (!isset($build_attributes[$classname]['unique_key'])) {
            throw new \Exception('Build attributes "unique_key" not defined for class ' . $classname);
        }
        $fields   = (array)$build_attributes[$classname]['unique_key'];
        $criteria = array();
        foreach ($fields as $f) {
            if (isset($attributes[$f]) && !empty($attributes[$f])) {
                $criteria[$f] = $attributes[$f];
            }
        }

        return $criteria;
    }

    /**
     * Get the readme file from a repo
     *
     * @param string $pkg_root_dir full path to file, without trailing slash
     *
     * @return string (contents of README.md file) or false if not found
     */
    public function get_readme($pkg_root_dir)
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);
        foreach ($this->readme_filenames as $f) {
            $readme = $pkg_root_dir . '/' . $f;
            if (file_exists($readme)) {
                return file_get_contents($readme);
            }
        }

        return false;
    }

    /**
     * Get a list of all seed directories
     *
     * @param string $pkg_root_dir path to local package root
     *
     * @return array
     */
    public function getSeedPaths($pkg_root_dir)
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);
        $dirs         = array();
        $seeds        = $this->get('seeds_path');
        if (!is_array($seeds)) {
            if (strpos($seeds, ',') !== false) {
                $seeds = explode(',', $seeds);
            } else {
                $seeds = array($seeds);
            }
        }
        foreach ($seeds as $s) {
            $d = $this->getCorePath($pkg_root_dir) . trim($s);
            if (file_exists($d) && is_dir($d)) {
                $dirs[] = $d;
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Invalid path in seeds_path. Directory does not exist: ' . $s);
            }
        }

        if (empty($dirs)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'No seed directories defined.');
        } else {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Seed directories set: ' . print_r($dirs, true));
        }

        return $dirs;
    }

    /**
     * Get the full path containing the goods.  In redundant MODX parlance, this is
     * usually core/components/<namespace>/
     * For better compatibility with composer, this is configurable.
     *
     * @return string dir with trailing slash
     */
    public function getCorePath()
    {
        // Handle any case where shorthand for current working dir has been used
        if (in_array($this->get('core_path'), array(null, '/', '.' . './'))) {
            return $this->pkg_root_dir;
        } else {
            return $this->pkg_root_dir . $this->get('core_path');
        }
    }

    /**
     * Get the dir containing the assets.  In redundant MODX parlance, this is
     * usually assets/components/<namespace>/
     * To reduce directory redundancy, this defaults to "assets/" (relative to the core_path)
     *
     * @param string $pkg_root_dir
     *
     * @return string dir with trailing slash
     */
    public function getAssetsPath($pkg_root_dir)
    {
        if ($this->get('assets_dir')) {
            return $pkg_root_dir . $this->get('assets_dir');
        }

        return $pkg_root_dir . 'assets/';
    }

    /**
     * Get the dir containing the assets.  In redundant MODX parlance, this is
     * usually assets/components/<namespace>/  (the default).
     * For better compatibility with composer, this is configurable.
     *
     * @param string $pkg_root_dir
     *
     * @return string dir with trailing slash
     */
    public function getDocsPath($pkg_root_dir)
    {
        if ($this->get('docs_path')) {
            return $pkg_root_dir . $this->get('docs_path');
        }

        return $pkg_root_dir . 'docs/';
    }

    /**
     * Import pkg elements (Snippets,Chunks,Plugins,Templates) into MODX from the filesystem.
     * They will be marked as static elements.
     *
     * @param string $pkg_root_dir path to local package root (w trailing slash)
     */
    public function import($pkg_root_dir)
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);

        // Is installed?
        $namespace = $this->get('namespace');
        if (!$Setting = $this->modx->getObject('modSystemSetting', array('key' => $namespace . '.version'))) {
            throw new \Exception('Package is not installed. Run "install" instead.');
        }

        // The gratis Category
        $Category = $this->modx->getObject('modCategory', array('category' => $this->get('category')));
        if (!$Category) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, "Creating new category: " . $this->get('category'));
            $Category = $this->modx->newObject('modCategory');
            $Category->set('category', $this->get('category'));
        } else {
            $this->modx->log(modX::LOG_LEVEL_INFO, "Using existing category: " . $this->get('category'));
        }

        // Import Elements
        $chunks    = self::_get_elements('modChunk', $pkg_root_dir);
        $plugins   = self::_get_elements('modPlugin', $pkg_root_dir);
        $snippets  = self::_get_elements('modSnippet', $pkg_root_dir);
        $tvs       = self::_get_elements('modTemplateVar', $pkg_root_dir);
        $templates = self::_get_elements('modTemplate', $pkg_root_dir);

        if ($chunks) $Category->addMany($chunks);
        if ($plugins) $Category->addMany($plugins);
        if ($snippets) $Category->addMany($snippets);
        if ($templates) $Category->addMany($templates);
        if ($tvs) $Category->addMany($tvs);

        if (!$this->get('dry_run') && $Category->save()) {
            $data = $this->getCriteria('modCategory', $Category->toArray());
            $this->modx->cacheManager->set('modCategory/' . $this->get('category'), $data, 0, self::$cache_opts);
            $this->modx->log(modX::LOG_LEVEL_INFO, "Category created/updated: " . $this->get('category'));
        }

        // TODO: query database
        if ($this->get('dry_run')) {
            $msg = "\n==================================\n";
            $msg .= "    Dry Run Enqueued Elements:\n";
            $msg .= "===================================\n";
            foreach (Repoman::$queue as $classname => $list) {
                $msg .= "\n" . $classname . "\n" . str_repeat('-', strlen($classname)) . "\n";
                foreach ($list as $k => $def) {
                    $msg .= "    " . $k . "\n";
                }
            }
            $this->modx->log(modX::LOG_LEVEL_INFO, $msg);
        }
    }

    /**
     * Install all elements and run migrations
     *
     */
    public function install()
    {
        // Is already installed?
        $namespace = $this->get('namespace');
        if ($Setting = $this->modx->getObject('modSystemSetting', array('key' => $namespace . '.version'))) {
            return $this->update();
        }
        $this->prepModx();
        $this->_create_setting($this->get('namespace'), $this->get('namespace') . '.version', trim($this->get('version')));
        $this->import();
        $this->migrate('install');
        $this->seed($pkg_root_dir); // TODO
        $this->modx->cacheManager->refresh();
    }

    /**
     * Given a filename, return the array of records stored in the file.
     *
     * @param string  $file (full path)
     * @param boolean $json if true, the file contains json data so it will be decoded
     *
     * @throws \Exception
     * @return array
     */
    public function load_data($file, $json = false)
    {
        if (!file_exists($file)) {
            throw new \Exception('Loading data failed. File does not exist: ' . $file);
        }

        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Processing object(s) in ' . $file . ' (json: ' . $json);

        if ($json) {
            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data)) {
                throw new \Exception('Bad JSON in ' . $file);
            }
        } else {
            // check file syntax (throws Exception on errors)
            Utils::validPhpSyntax($file);
            $data = include $file;
        }

        if (!is_array($data)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Data in ' . $file . ' not an array.');
            throw new \Exception('Data in ' . $file . 'not an array.');
        }
        if (!isset($data[0])) {
            $data = array($data);
        }

        return $data;
    }


    /**
     * Run database migrations:
     *      - create/remove custom database tables.
     *      - create objects from any seed data
     *
     * @param string $pkg_root_dir path to local package root (w trailing slash)
     * @param string $mode         install (default) | uninstall | update | refresh
     */
    public function migrate($pkg_root_dir, $mode = 'install')
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);
        $this->prepModx($pkg_root_dir);

        global $modx;
        // For compatibility
        $object = $this->config;
        // TODO: check for modx_transport_packages -- SELECT * FROM modx_transport_packages WHERE package_name = xxx
        // if this has been installed via a package, then skip??
        $migrations_path = $this->getCorePath($pkg_root_dir) . $this->get('migrations_path');

        if (!file_exists($migrations_path) || !is_dir($migrations_path)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, "No migrations detected at " . $migrations_path);

            return;
        }

        if (in_array($mode, array('refresh', 'uninstall')) && file_exists($migrations_path . '/uninstall.php')) {
            $this->modx->log(modX::LOG_LEVEL_INFO, "Running migrations uninstall.php");
            include $migrations_path . '/uninstall.php';
        }

        if (in_array($mode, array('refresh', 'install')) && file_exists($migrations_path . '/install.php')) {
            $this->modx->log(modX::LOG_LEVEL_INFO, "Running migrations install.php");
            include $migrations_path . '/install.php';
        }

        if ($mode != 'update') return; // nothing more to do unless we are updating the package.

        // Loop over remaining migrations
        $files = glob($migrations_path . '/*.php');
        foreach ($files as $f) {
            $base = basename($f);
            if (in_array($base, array('install.php', 'uninstall.php'))) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Skipping ' . $base);
                continue;
            }

            // Compare stored version to the version in the composer.json file
            if (version_compare($this->modx->getOption($this->get('namespace') . '.version'), $this->get('version'), '<=')) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Running migration ' . basename($f));
                include $f;
            } else {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Skipping migration ' . basename($f));
            }
        }

    }

    /**
     * Remember something that repoman saves or processed. TODO: store in database
     *
     * @param $object_type string
     * @param $identifier  string
     * @param $contents    string
     */
    public function remember($object_type, $identifier, $contents)
    {
        Repoman::$queue[$object_type][$identifier] = $contents;
    }

    /**
     * Load up seed data into the local modx install. (Not used by the build method).
     * Config should be loaded by this point, otherwise build_attributes won't be defined.
     *
     * @param string $pkg_root_dir path to local package root (w trailing slash)
     */
    public function seed($pkg_root_dir)
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);
        $this->_addPkgs($this->config, $pkg_root_dir);
        $dirs = $this->getSeedPaths($pkg_root_dir);
        foreach ($dirs as $d) {
            $objects = $this->crawlDir($d);
            foreach ($objects as $classname => $info) {
                foreach ($info as $k => $Obj) {
                    if (!$Obj->save()) {
                        $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error saving object ' . $classname);
                    } else {
                        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Saved object ' . $classname);
                    }
                }
            }
        }
    }

    /**
     * @param $path string
     */
    public function setPkgRootDir($path)
    {
        $this->pkg_root_dir = $this->Filesystem->getDir($path);
    }

    /**
     * Dev tool for parsing XML schema.  xyz.mysql.schema.xml maps to the model/xyz/ directory.
     *
     * Configuration options:
     *
     *  --model i.e. the name of the subdir identifying a collection of object ORM classes
     *  --orm_path
     *  --table_prefix
     *  --overwrite
     */
    public function schema_parse($pkg_root_dir)
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);
        $this->_addPkgs($this->config, $pkg_root_dir);

        $model           = trim(strtolower($this->get('model')), '/'); // stub-name of XML schema file
        $table_prefix    = $this->get('table_prefix');
        $restrict_prefix = $this->get('restrict_prefix');
        $overwrite       = strtolower($this->get('overwrite'));
        $dir_mode        = $this->get('dir_mode');
        $restore         = $this->get('restore');

        if ($overwrite && $overwrite != 'force') $overwrite = 'polite';

        if (empty($model)) {
            $model = $this->get('namespace');
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Model parameter not set. Falling back to namespace as model name.');
        }
        if (preg_match('/^[^a-z0-9_\-]/i', $model)) {
            throw new \Exception('Invalid model. Model name can only contain alphanumeric characters.');
        }

        $now         = time();
        $schema_file = $this->getCorePath($pkg_root_dir) . $this->get('orm_path') . 'schema/' . $model . '.mysql.schema.xml';
        $model_dir   = $this->getCorePath($pkg_root_dir) . $this->get('orm_path');

        $manager   = $this->modx->getManager();
        $generator = $manager->getGenerator();

        $renamed_files = array();

        $this->modx->setPackage($this->get('namespace'), $pkg_root_dir, $table_prefix);
        if (!file_exists($schema_file)) throw new \Exception('Schema file does not exist: ' . $schema_file);
        $class_dir = $model_dir . $model . '/';

        if (!file_exists($class_dir)) {
            if (!mkdir($class_dir, $dir_mode, true)) {
                throw new \Exception('Could not create directory ' . $class_dir);
            }
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Created directory ' . $class_dir);
        }
        if (!is_writable($class_dir)) {
            throw new \Exception('Directory is not writeable ' . $class_dir);
        }

        $xml = file_get_contents($schema_file);
        if ($xml === false) {
            throw new \Exception('Could not read XML schema file: ' . $schema_file);
        }
        // Check class files by reading the XML file
        preg_match_all('/<object class="(\w+)"/U', $xml, $matches);
        $class_files = array();
        if (isset($matches[1])) {
            foreach ($matches[1] as $f) {
                $class_file    = $class_dir . strtolower($f) . '.class.php';
                $class_files[] = $class_file;
                if (file_exists($class_file)) {
                    if ($overwrite == 'polite') {
                        $class_file_new = $class_dir . $now . '.' . strtolower($f) . '.class.php';
                        if (!rename($class_file, $class_file_new)) {
                            throw new \Exception('Could not rename class file ' . $class_file);
                        }
                        $renamed_files[$class_file] = $class_file_new;
                        $this->modx->log(modX::LOG_LEVEL_INFO, 'Renamed file ' . basename($class_file) . ' to ' . basename($class_file_new));
                    } elseif ($overwrite == 'force') {
                        if (!unlink($class_file)) {
                            throw new \Exception('Could not delete class file ' . $class_file);
                        }
                        $this->modx->log(modX::LOG_LEVEL_INFO, 'Deleted file ' . $class_file);
                    } else {
                        throw new \Exception('Class file exists: ' . $class_file . ' Refusing to overwrite unless forced.');
                    }
                }
            }

            // Check for metadata.mysql.php
            $metadata_file = $class_dir . 'metadata.mysql.php';
            if (file_exists($metadata_file)) {
                if ($overwrite == 'polite') {
                    $metadata_file_new = $class_dir . $now . '.metadata.mysql.php';
                    if (!rename($metadata_file, $metadata_file_new)) {
                        throw new \Exception('Could not rename metadata file  ' . $metadata_file);
                    }
                    $renamed_files[$metadata_file] = $metadata_file_new;
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Renamed file ' . $metadata_file);
                } elseif ($overwrite == 'force') {
                    if (!unlink($metadata_file)) {
                        throw new \Exception('Could not delete metadata file ' . $metadata_file);
                    }
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Deleted metadata file ' . $metadata_file);
                } else {
                    throw new \Exception('metadata.mysql.php file exits. Refusing to overwrite unless forced.');
                }
            }
            // Check mysql files
            $mysql_dir = $class_dir . 'mysql';
            if (file_exists($mysql_dir)) {
                if ($overwrite == 'polite') {
                    $new_mysql_dir = dirname($mysql_dir) . '/' . $now . '.mysql';
                    if (!rename($mysql_dir, $new_mysql_dir)) {
                        throw new \Exception('Could not rename mysql directory ' . $mysql_dir);
                    }
                    if (!mkdir($mysql_dir, $dir_mode, true)) {
                        throw new \Exception('Could not create directory ' . $mysql_dir);
                    }
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Created directory ' . $mysql_dir);
                } elseif ($overwrite == 'force') {
                    if (!$this->Filesystem->remove($mysql_dir)) {
                        throw new \Exception('Could not delete mysqld dir ' . $mysql_dir);
                    }
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Deleted directory ' . $mysql_dir);
                    if (!mkdir($mysql_dir, $dir_mode, true)) {
                        throw new \Exception('Could not create directory ' . $mysql_dir);
                    }
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Created directory ' . $mysql_dir);
                } else {
                    throw new \Exception('mysql directory exists: ' . $mysql_dir . ' Refusing to overwrite unless forced.');
                }
            } else {
                if (!mkdir($mysql_dir, $dir_mode, true)) {
                    throw new \Exception('Could not create directory ' . $mysql_dir);
                }
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Created directory ' . $mysql_dir);
            }
            $generator->parseSchema($schema_file, $model_dir);
        }

        // Polite cleanup
        if ($overwrite == 'polite') {
            //$this->modx->log(modX::LOG_LEVEL_INFO,'Renamed: '.print_r($renamed_files,true));
            foreach ($renamed_files as $old => $new) {
                if ($this->Filesystem->areEqual($old, $new)) {
                    if (!unlink($new)) {
                        throw new \Exception('Could not delete file ' . $new);
                    }
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Cleanup - removing unchanged file ' . basename($new));
                }
            }
            // Restore files
            $restore_array = (!is_array($restore)) ? explode(',', $restore) : $restore;
            $restore_array = array_map('trim', $restore_array);
            foreach ($renamed_files as $old => $new) {
                foreach ($restore_array as $r) {
                    if (preg_match('/^' . preg_quote($r, '/') . '/', basename($old))) {
                        if (file_exists($new) && !rename($new, $old)) {
                            throw new \Exception('Could not restore file ' . $new);
                        }
                        $this->modx->log(modX::LOG_LEVEL_INFO, 'Restoring original file ' . basename($old));
                    }
                }
            }
        } // end polite cleanup

    }

    /**
     * Dev tool for writing XML schema: sniff existing database tables and write
     * an xPDO XML schema file which describes the tables.
     *
     * There is redundancy in the names here (boo):
     *  xyz.mysql.schema.xml maps to the model/xyz/ directory.
     *
     * Configuration options:
     *
     *  --model -- name of the model, often equal to the namespace
     *  --orm_path -- usually "model/"
     *  --table_prefix
     *  --overwrite true|polite|force
     */
    public function schema_write()
    {
        $this->_addPkgs($this->config, $this->pkg_root_dir);
        // $this->prepModx($pkg_root_dir); // populate the system settings not req'd

        $action          = strtolower($this->get('action')); // write|parse|both
        $model           = trim(strtolower($this->get('model')), '/'); // name of the schema and the subdir
        $table_prefix    = $this->get('table_prefix');
        $restrict_prefix = $this->get('restrict_prefix');
        $overwrite       = strtolower($this->get('overwrite'));
        $dir_mode        = $this->get('dir_mode');

        if ($overwrite && $overwrite != 'force') $overwrite = 'polite';

        if (empty($model)) {
            $model = $this->get->config('namespace');
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Model parameter not set. Falling back to namespace as model name.');
        }
        if (preg_match('/^[^a-z0-9_\-]/i', $model)) {
            throw new \Exception('Invalid model. Model name can only contain alphanumeric characters.');
        }

        $now         = time();
        $schema_file = $this->getCorePath($pkg_root_dir) . $this->get('orm_path') . 'schema/' . $model . '.mysql.schema.xml';
        $model_dir   = $this->getCorePath($pkg_root_dir) . $this->get('orm_path');

        $manager   = $this->modx->getManager();
        $generator = $manager->getGenerator();

        $renamed_files = array();
        // Generate XML schema by reverse-engineering from existing database tables.
        if (file_exists($schema_file)) {
            if ($overwrite == 'polite') {
                $schema_file_new = $this->getCorePath($pkg_root_dir) . $this->get('orm_path') . 'schema/' . $model . '.' . $now . '.mysql.schema.xml';
                if (!rename($schema_file, $schema_file_new)) {
                    throw new \Exception('Could not rename schema file ' . $schema_file);
                }
                $renamed_files[$schema_file] = $schema_file_new;
            } elseif ($overwrite == 'force') {
                if (!unlink($schema_file)) {
                    throw new \Exception('Could not delete schema file ' . $schema_file);
                }
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Deleted file ' . $schema_file);
            } else {
                throw new \Exception('Schema already exists: ' . $schema_file . ' Refusing to overwrite unless forced.');
            }
        }
        $schema_dir = $model_dir . 'schema/';
        $dirs       = array($schema_dir);
        foreach ($dirs as $d) {
            if (!file_exists($d)) {
                if (!mkdir($d, $dir_mode, true)) {
                    throw new \Exception('Could not create directory ' . $d);
                }
            }
            if (!is_writable($d)) {
                throw new \Exception('Directory is not writeable ' . $d);
            }
        }
        $generator->writeSchema($schema_file, $this->get('namespace'), 'xPDOObject', $table_prefix, $restrict_prefix);


        // Polite cleanup
        if ($overwrite == 'polite') {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Renamed: ' . print_r($renamed_files, true));
            foreach ($renamed_files as $old => $new) {
                if ($this->Filesystem->areEqual($old, $new)) {
                    if (!unlink($new)) {
                        throw new \Exception('Could not delete file ' . $new);
                    }
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Cleanup - removing file ' . $new);
                }
            }
        }

    }


    /**
     * Clean up for dismount: opposite of prepModx
     *
     */
    public function tidy_modx()
    {

        $this->modx->log(modX::LOG_LEVEL_INFO, "Removing " . $this->get('namespace') . " namespace and system settings.");

        if ($N = $this->modx->getObject('modNamespace', $this->get('namespace'))) {
            if (!$N->remove()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error removing Namespace' . $this->get('namespace'));
            }
        }

        if ($Setting = $this->modx->getObject('modSystemSetting', array('key' => $this->get('namespace') . '.assets_url'))) {
            if (!$Setting->remove()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error removing System Setting ' . $this->get('namespace') . '.assets_url');
            }
        }
        if ($Setting = $this->modx->getObject('modSystemSetting', array('key' => $this->get('namespace') . '.assets_path'))) {
            if (!$Setting->remove()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error removing System Setting ' . $this->get('namespace') . '.assets_path');
            }
        }
        if ($Setting = $this->modx->getObject('modSystemSetting', array('key' => $this->get('namespace') . '.core_path'))) {
            if (!$Setting->remove()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error removing System Setting ' . $this->get('namespace') . '.core_path');
            }
        }
        if ($Setting = $this->modx->getObject('modSystemSetting', array('key' => $this->get('namespace') . '.version'))) {
            if (!$Setting->remove()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error removing System Setting ' . $this->get('namespace') . '.version');
            }
        }

        $this->modx->cacheManager->refresh();
    }

    /**
     * Update a package's elements and run outstanding migrations
     *
     * @param string $pkg_root_dir path to local package root (w trailing slash)
     */
    public function update($pkg_root_dir)
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);

        // Is already installed?
        $namespace = $this->get('namespace');
        if (!$Setting = $this->modx->getObject('modSystemSetting', array('key' => $namespace . '.version'))) {
            return $this->install($pkg_root_dir);
            //throw new \Exception('Package is not installed. Run "install" instead.');
        }
        $this->prepModx($pkg_root_dir);

        $this->import($pkg_root_dir);
        $this->migrate($pkg_root_dir, 'update');
        $this->seed($pkg_root_dir);
        $this->_create_setting($this->get('namespace'), $this->get('namespace') . '.version', trim($this->get('version')));
        $this->modx->cacheManager->refresh();
    }

    /**
     * Attempts to uninstall the default namespace, system settings, modx objects,
     * and any database migrations. The behavior is dependent on the MODX cache b/c
     * all new objects are registered in the repoman custom cache partition.
     *
     * No input parameters are required: this looks at the "namespace" config setting.
     *
     * php repoman.php uninstall --namespace=something
     */
    public function uninstall($pkg_root_dir)
    {
        $pkg_root_dir = Filesystem::getDir($pkg_root_dir);

        // uninstall migrations. Global $modx and $object variables
        $this->migrate($pkg_root_dir, 'uninstall');

        // Remove installed objects
        $cache_dir = MODX_CORE_PATH . 'cache/repoman/' . $this->get('namespace');
        if (file_exists($cache_dir) && is_dir($cache_dir)) {
            $obj_dirs = array_diff(scandir($cache_dir), array('..', '.'));

            foreach ($obj_dirs as $objectname_dir) {
                if (!is_dir($cache_dir . '/' . $objectname_dir)) {
                    continue; // wtf? Did you manually edit the cache dirs?
                }

                $objects    = array_diff(scandir($cache_dir . '/' . $objectname_dir), array('..', '.'));
                $objecttype = basename($objectname_dir);
                foreach ($objects as $o) {
                    $criteria = include $cache_dir . '/' . $objectname_dir . '/' . $o;
                    $Obj      = $this->modx->getObject($objecttype, $criteria);
                    if ($Obj) {
                        $Obj->remove();
                    } else {
                        // Some objects are removed b/c of relations before we get to them
                        $this->modx->log(modX::LOG_LEVEL_DEBUG, $objecttype . ' could not be located ' . print_r($criteria, true));
                    }
                }
            }

            $this->Filesystem->remove($cache_dir);
        } else {
            $this->modx->log(modX::LOG_LEVEL_WARN, 'No cached import data at ' . $cache_dir);
        }

        $this->tidy_modx();
    }

}
/*EOF*/
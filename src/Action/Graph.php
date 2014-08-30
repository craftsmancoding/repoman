<?php
/**
 * Handles graphing MODX objects: shows attributes and relations.  It is not dependent on
 * the configuration of any package, however, the option to load additional packages while
 * reporting on their data models does trigger some configuration lookups.
 */
namespace Repoman\Action;

use \modX;
use Repoman\Config;

class Graph
{

    public $modx;
    public $config;

    public function __construct(modX $modx, Config $config)
    {
        $this->modx = $modx;
        $this->config = $config;
    }

    /**
     * Assistence function for examining MODX object attributes and their relations.
     * _pkg (string) colon-separated string defining the arguments for addPackage() --
     *      package_name, model_path, and optionally table_prefix
     *      e.g. `tiles;[[++core_path]]components/tiles/model/;tiles_` or
     *      If only the package name is supplied, the path is assumed to be "[[++core_path]]components/$package_name/model/"
     *
     * Optional options:
     *      aggregates : if set, only aggregate relationships will be shown.
     *      composites : if set, only composite relationships will be shown.
     *      load : arry of directories for packages to add
     *
     * @param       $classname (optional)
     * @param array $args
     *
     * @throws \Exception
     * @return array
     */
    public function execute($classname = null, $args = array())
    {
        // Defaults
        $aggregates = (isset($args['aggregates'])) ? $args['aggregates'] : false;
        $composites = (isset($args['composites'])) ? $args['composites'] : false;
        $load       = array();
        if (isset($args['load'])) {
            $load = (is_array($args['load'])) ? $args['load'] : array($args['load']);
        }

        // Handle weird use-case where user sets both options
        if ($aggregates && $composites) {
            $aggregates = false;
            $composites = false;
        }

        //Load up configs packages
        foreach ($load as $dir) {
            $this->config->addModxPkgs($dir);
        }

        if (empty($classname)) {
            $out = "\n<bg=cyan>";
            $out .= str_repeat(' ', 30) . "\n";
            $out .= str_pad("All Available Classes", 30, ' ', STR_PAD_BOTH) . "\n";
            $out .= str_repeat(' ', 30) . "\n";
            $out .= "</bg=cyan>\n";
            $out .= "This is a list of all built-in MODX classes and those loaded by models listed in the extension_packages System Setting.\n";
            $out .= "Use the --load option to identify package root directories where other packages are defined in the composer.json.\n";

            foreach ($this->modx->classMap as $parentclass => $childclasses) {
                $out .= "\n<fg=green>" . $parentclass . "</fg=green>\n" . str_repeat('-', strlen($parentclass)) . "\n";
                foreach ($childclasses as $c) {
                    $out .= "    " . $c . "\n";
                    //$output->writeln("    " . $c);
                }
            }

            return $out;
        }

        $array = $this->modx->getFields($classname);

        if (empty($array)) {
            throw new \Exception('Classname not found. Call graph without arguments to see a list of registered classnames.');
        }

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
        $out = "\n<bg=cyan>";
        $out .= str_repeat(' ', 30) . "\n";
        $out .= str_pad($classname, 30, ' ', STR_PAD_BOTH) . "\n";
        $out .= str_repeat(' ', 30) . "\n";
        $out .= "</bg=cyan>\n";
        $out .= print_r($array, true);
        // Try to make the result pretty. TODO: make it have correct syntax!!!
        $out = str_replace(array('Array', '[', ']', ')'), array('array', "'", "'", '),'), $out);

        return $out;

    }


}
/*EOF*/
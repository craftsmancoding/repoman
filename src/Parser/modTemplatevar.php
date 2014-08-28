<?php
/**
 * The TV stores the "Default Value" as the "content"
 *
 */
namespace Repoman\Parser;

use Repoman\Parser;

class modTemplatevar extends Parser
{

    public $dir_key = 'tvs_path';
    public $ext = '*.php';
    public $write_ext = '.php';
    public $objecttype = 'modTemplateVar';

    public $dox_start = '';
    public $dox_end = '';
    public $dox_pad = ''; // left of line before the @attribute	

    /**
     * Create a TV from existing object.  We override the parent here because TVs are stored as PHP arrays,
     * not as textual elements with DocBlocks.
     *
     * @param string $pkg_dir
     * @param object $Obj
     * @param boolean $graph whether to include related data
     */
    public function create($pkg_dir, $Obj, $graph)
    {

        $array = $Obj->toArray('', false, false, $graph);

        //print '<pre>'.print_r($array,true).'</pre>'; exit;
        $name = $Obj->get('name');

        $dir = $this->Repoman->get_core_path($pkg_dir) . $this->Repoman->get($this->dir_key) . '/';
        $filename = $dir . '/' . $name . $this->write_ext;
        if (file_exists($filename) && !$this->Repoman->get('overwrite')) {
            throw new Exception('Element already exists. Overwrite not allowed. ' . $filename);
        }


        // Create the file stuff
        ob_start();
        include dirname(dirname(dirname(dirname(__FILE__)))) . '/views/tv.php';
        $content = ob_get_clean();
        $content = "<?php\n" . $content . "\n?>"; // make it prettier

        // Create dir if doesn't exist
        if (!file_exists($dir) && false === mkdir($dir, $this->Repoman->get('dir_mode'), true)) {
            throw new Exception('Could not create directory ' . $dir);
        }

        if (false === file_put_contents($filename, $content)) {
            throw new Exception('Could not write to file ' . $filename);
        } else {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Created static element at ' . $f);
        }
    }


    /**
     * We override the default functionality so we can support TVs as PHP arrays
     * @param string $pkg_dir name
     * @return array
     */
    public function gather($pkg_dir)
    {
        $objects = array();

        // Calculate the element's directory given the repo dir...
        $dir = $this->Repoman->get_core_path($pkg_dir) . $this->Repoman->get($this->dir_key) . '/';
        if (!file_exists($dir) || !is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Directory does not exist: ' . $dir);
            return array();
        }

        $files = glob($dir . $this->ext);
        $i = 0;
        foreach ($files as $f) {
            $data = $this->Repoman->load_data($f);
            $attributes = $data[0];
            if (!isset($attributes[$this->objectname])) {
                $name = str_replace(array('snippet.', '.snippet', 'chunk.', '.chunk'
                , 'plugin.', '.plugin', 'tv.', '.tv', 'template.', '.template'
                , '.html', '.txt', '.tpl'), '', basename($f));
                $attributes[$this->objectname] = $name;
            }

            $Obj = $this->modx->getObject($this->objecttype, $this->Repoman->get_criteria($this->objecttype, $attributes));
            if (!$Obj) {
                $Obj = $this->modx->newObject($this->objecttype);
            }

            // All elements will share the same category
            $attributes['category'] = 0;

            // Force to be NOT Static ?  
            // It'd be weird if a user set these... but I'ma gonna leave it alone for now
            /*
            $attributes['source'] = 0;
            $attributes['static'] = 0;
            $attributes['static_file'] = '';
            */

            $Obj->fromArray($attributes);

            $this->relate($attributes, $Obj);

            $this->modx->log(modX::LOG_LEVEL_INFO, 'Created/updated ' . $this->objecttype . ': ' . $Obj->get($this->objectname));
            Repoman::$queue[$this->objecttype][] = $Obj->get($this->objectname);

            if (!$this->Repoman->get('dry_run') && !$this->Repoman->get('is_build')) {
                $data = $this->Repoman->get_criteria($this->objecttype, $attributes);
                $this->modx->cacheManager->set($this->objecttype . '/' . $attributes[$this->objectname], $data, 0, Repoman::$cache_opts);
            }
            $i++;

            // For reasons I don't understand, packaging objects into a build req's that they have "fake" pk ids set
            if ($this->Repoman->get('is_build')) {
                $Obj->set('id', $i);
            }
            $objects[$i] = $Obj;
        }

        return $objects;
    }
}
/*EOF*/
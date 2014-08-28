<?php
/**
 * Used by Repman to parse MODX static elements (chunks, templates, snippets, tvs)
 * specifically to strip out object parameters from the docblocks in each.
 *
 * @package repoman
 */
namespace Repoman;

use modX;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Repoman\Repoman;

abstract class Parser
{

    public $modx;
    public $Repoman;
    public $Filesystem;
    public $Finder;

    public $classname; // identifies the object, e.g. modChunk
    public $dir_key; // key in the config which contains the directory.
    public $ext; // glob that ids the file extension.
    public $write_ext = '.php'; // extention to use when creating an element
    public $objecttype;
    public $objectname = 'name'; // most objects use the 'name' field to identify themselves

    public $dox_start = '/*';
    public $dox_end = '*/';
    public $dox_pad = ' * '; // left of line before the @attribute

    public $extensions = '';
    public $pkg_dir;

    /**
     * Any tags to skip in the doc block, e.g. @param, that may have significance for PHPDoc and
     * for general documentation, but which are not intended for RepoMan and do not describe
     * object attributes. Omit "@" from the attribute names.
     * See http://en.wikipedia.org/wiki/PHPDoc
     */
    public static $skip_tags = array('param', 'return', 'abstract', 'access', 'author', 'copyright',
        'deprecated', 'deprec', 'example', 'exception', 'global', 'ignore', 'internal', 'link', 'magic',
        'package', 'see', 'since', 'staticvar', 'subpackage', 'throws', 'todo', 'var', 'version'
    );


    /**
     * @param Repoman $Repoman
     */
    public function __construct(Repoman $Repoman)
    {
        $this->modx = $Repoman->modx;

        $this->Repoman =  $Repoman;
        $this->Filesystem = new Filesystem();
        $this->Finder = new Finder();

        $this->classname = get_class($this);
    }

    /**
     * Create file including a DocBlock to represent the inputted Object.  Used by the export command.
     *
     * @param string $dir target directory
     * @param object $Obj
     * @param boolean $graph whether to include related data
     * @throws \Exception
     */
    public function create($dir, $Obj, $graph)
    {
        $dir = $this->Filesystem->getDir($dir);

        $array = $Obj->toArray('', false, false, $graph);
        $content = $Obj->getContent();
        $attributes = self::repossess($content, $this->dox_start, $this->dox_end);
        if (!isset($attributes[$this->objectname])) {
            $name_attribute = $this->objectname;
            $name = $Obj->get($name_attribute);
        } else {
            $name = $attributes[$this->objectname];
        }

        $filename = $dir . '/' . $name . $this->write_ext;
        if ($this->Filesystem->exists($filename) && !$this->Repoman->get('overwrite')) {
            throw new \Exception('Element already exists. Overwrite not allowed. ' . $filename);
        }


        // Create DocBlock if it doesn't already exist
        if (empty($attributes)) {
            $docblock = $this->dox_start . "\n";
            $docblock .= $this->dox_pad . '@' . $this->objectname . ' ' . $array[$this->objectname] . "\n";
            $docblock .= $this->dox_pad . '@description ' . $array['description'] . "\n";
            $docblock .= $this->extendDocblock($Obj);
            $docblock .= $this->dox_end . "\n";
            $this->modx->log(modX::LOG_LEVEL_DEBUG, "DocBlock generated:\n" . $docblock);
            $content = $docblock . $content;
        }

        // Create dir if doesn't exist
        if (!$this->Filesystem->exists($dir)) {
            try {
                $this->Filesystem->mkdir($dir, $this->Repoman->get('dir_mode'));
            } catch (IOException $e) {
                throw new \Exception('Could not create directory ' . $dir);
            }
        }
        // Create the file
        try {
            $this->Filesystem->dumpFile($content, $filename);
        } catch (IOException $e) {
            throw new \Exception('Could not write to file ' . $filename);
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Created static element at ' . $f);

        // Do you want to mess with the original object?  Or just grab a snapshot of it?
        if ($this->Repoman->get('move')) {
            $Obj->set('static', true);
            $Obj->set('static_file', $this->Filesystem->makePathRelative($filename, MODX_BASE_PATH));
            if (!$Obj->save()) {
                throw new \Exception('Problem saving ' . $this->classname . ' ' . $array[$this->objectname]);
            }
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Original ' . $this->classname . ' ' . $array[$this->objectname] . ' updated to new location.');
        } else {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Original ' . $this->classname . ' ' . $array[$this->objectname] . ' copied only.');
        }
    }

    /**
     * Add any extended docblock attributes for this object type. This is where you would define
     * related object attributes such as plugin events that are related to the passed $Obj.
     *
     * @param object $Obj
     * @return string
     */
    public function extendDocblock(&$Obj)
    {
        return '';
    }

    /**
     * Gather all elements (i.e. files) in the given directory as an array of objects.
     * @param $dir
     * @return array of objects
     */
    public function gather($dir)
    {
        try {
            $dir = $this->Filesystem->getDir($dir);
        }
        catch (\Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, $e->getMessage());
            return array();
        }

        $objects = array();

        // Calculate the element's directory given the repo dir...
        //$dir = $this->Repoman->get_core_path($pkg_dir) . rtrim($this->Repoman->get($this->dir_key), '/') . '/';

        $i = 0;
        //foreach ($files as $f) {
        foreach ($this->Finder->in($dir)->name($this->ext) as $f) {
            Utils::validPhpSyntax($f->getRealpath());
            $content = $f->getContents();
            $attributes = self::repossess($content, $this->dox_start, $this->dox_end);
            if ($this->Repoman->get('is_build')) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'is_build = true: preparing for build.');
                $content = $this->prepareForBuild($content);
            }
            // Skip importing?
            if (isset($attributes['no_import'])) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, '@no_import detected in ' . $f->getFilename());
                continue;
            } elseif ($this->Repoman->get('require_docblocks') && empty($attributes)) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'require_docblocks set to true and no DocBlock detected in ' . $f->getFilename());
                continue;
            }

            if (!isset($attributes[$this->objectname])) {
                $name = str_replace(array('snippet.', '.snippet', 'chunk.', '.chunk'
                , 'plugin.', '.plugin', 'tv.', '.tv', 'template.', '.template'
                , '.html', '.txt', '.tpl'), '', basename($f->getFilename()));
                $attributes[$this->objectname] = $name;
            }

            $Obj = $this->modx->getObject($this->objecttype, $this->Repoman->getCriteria($this->objecttype, $attributes));
            // Building should always create a new object.
            if ($this->Repoman->get('is_build') || !$Obj) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Creating new object from file ' . $f);
                $Obj = $this->modx->newObject($this->objecttype);
            }

            // All elements will share the same category
            $attributes['category'] = 0;

            // Force Static
            if ($this->Repoman->get('force_static')) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'force_static = true');
                $attributes['source'] = 1;
                $attributes['static'] = 1;
                $attributes['static_file'] = $this->Filesystem->makePathRelative($f->getRealpath(), MODX_BASE_PATH);
            }

            $this->modx->log(modX::LOG_LEVEL_DEBUG, "Gathered object attributes:\n" . print_r($attributes, true));

            $Obj->fromArray($attributes);
            if (!$this->Repoman->get('force_static')) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, "Setting content for {$this->objecttype} \"" . $Obj->get($this->objectname) . "\":\n" . $content);
                $Obj->setContent($content);
            }

            $this->relate($attributes, $Obj);

            $this->modx->log(modX::LOG_LEVEL_INFO, 'Created/updated ' . $this->objecttype . ': ' . $Obj->get($this->objectname) . ' from file ' . $f);
            $this->Repoman->remember($this->objecttype,$this->objectname,$Obj->toArray());
            //Repoman::$queue[$this->objecttype][] = $Obj->get($this->objectname);

            if (!$this->Repoman->get('dry_run') && !$this->Repoman->get('is_build')) {
                $data = $this->Repoman->getCriteria($this->objecttype, $attributes);
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

    /**
     * Get the proper directory for the particular element class given the package root
     * @internal $Repoman->pkg_root_path
     * @return string full path with trailing slash
     */
    public function getSubDir()
    {
        return $this->Repoman->getCorePath() . rtrim($this->Repoman->get($this->dir_key), '/') . '/';
    }

    /**
     * Given an object of a type correlated with the classname, return a viable file basename (no dir).
     *
     * @param $Obj
     * @return string
     */
    public function getBasename($Obj) {
        return $Obj->get($this->objectname) . $this->write_ext;
    }

    /**
     * Run when files are being put into the package, this allows for last-minute adjustments
     * to the element content before being packaged, e.g. stripping out comment blocks
     * and adjustment of placeholders.
     * Config items affecting the behavior:
     *      strip_docblocks
     *      strip_comments
     *
     * @param string $string
     * @return string
     */
    public function prepareForBuild($string) {
        if ($this->Repoman->get('strip_docblocks')) {
            $string = preg_replace('#('.preg_quote($this->dox_start).')(.*)('.preg_quote($this->dox_end).')#Usi', '', $string,1);
            //$string = preg_replace("#{$this->dox_start}(.*){$this->dox_end}#msU", '',$string, 1);
        }
        if ($this->Repoman->get('strip_comments')) {
            $string = $this->removeComments($string);
        }
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.assets_url', '[[++assets_url', $string);
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.assets_path', '[[++assets_path', $string);
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.core_path', '[[++core_path', $string);

        return $string;

    }

    /**
     * Default behavior here requires nothing... really we only need this for Plugins and TVs...
     *
     * @param array $attributes
     * @param object $Obj
     */
    public function relate($attributes, &$Obj)
    {

    }

    /**
     * Strips out comments and whitespace from PHP
     * See http://stackoverflow.com/questions/503871/best-way-to-automatically-remove-comments-from-php-code
     * php_strip_whitespace
     * @param $string
     * @return string
     */
    public function removeComments($string)
    {
        // Write the contents to a temporary file
        $filename = tempnam($this->modx->getOption('core_path').'cache/','repo_');
        $this->Filesystem->dumpFile($filename, $string);
        $string = php_strip_whitespace($filename);
        $this->Filesystem->remove($filename);
        return $string;
    }

    /**
     * Read parameters out of a DocBlock... like a repoman repossessing
     * outstanding leased attributes.
     *
     * @param string $string the unparsed contents of a file
     * @param string $dox_start string designating the start of a doc block
     * @param string $dox_end
     * @return array on success | false on no doc block found
     */
    public static function repossess($string, $dox_start = '/*', $dox_end = '*/')
    {

        $dox_start = preg_quote($dox_start, '#');
        $dox_end = preg_quote($dox_end, '#');

        preg_match("#$dox_start(.*)$dox_end#msU", $string, $matches);

        if (!isset($matches[1])) {
            return false; // No doc block found!
        }

        // Get the docblock                
        $dox = $matches[1];

        // Loop over each line in the comment block
        $a = array(); // attributes
        foreach (preg_split('/((\r?\n)|(\r\n?))/', $dox) as $line) {
            preg_match('/^\s*\**\s*@(\w+)(.*)$/', $line, $m);
            if (isset($m[1]) && isset($m[2]) && !in_array($m[1], self::$skip_tags)) {
                $a[$m[1]] = trim($m[2]);
            }
        }

        return $a;
    }

}
/*EOF*/
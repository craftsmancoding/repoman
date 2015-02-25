<?php namespace Repoman\Parser;

/**
 * Used by Repman to parse MODX static elements (chunks, templates, snippets, tvs)
 * specifically to strip out object parameters from the docblocks in each.
 *
 * @package repoman
 */
abstract class Parser {

    public $modx;
    public $Repoman;
    public $classname; // identifies the object, e.g. modChunk
    public $dir_key; // key in the config which contains the directory.
    public $ext; // glob that ids the file extension.
    public $write_ext = '.php'; // extention to use when creating an element
    public $objecttype;
    public $objectname = 'name'; // most objects use the 'name' field to identify themselves

    public $dox_start = '/*';
    public $dox_end = '*/';
    public $dox_pad = ' * '; // left of line before the @attribute
    public $comment_start = '<!--REPOMAN_COMMENT_START';
    public $comment_end = 'REPOMAN_COMMENT_END-->';

    public $extensions = '';
    public $pkg_dir;

    private $source; // modMediaSource

    /**
     * Any tags to skip in the doc block, e.g. @param, that may have significance for PHPDoc and
     * for general documentation, but which are not intended for RepoMan and do not describe
     * object attributes. Omit "@" from the attribute names.
     * See http://en.wikipedia.org/wiki/PHPDoc
     */
    public static $skip_tags = array('param', 'return', 'abstract', 'access', 'author', 'copyright',
        'deprecated', 'deprec', 'example', 'exception', 'global', 'ignore', 'internal', 'link', 'magic',
        'package', 'see', 'since', 'staticvar', 'subpackage', 'throws', 'todo', 'url', 'var', 'version'
    );


    /**
     * @param \Repoman $Repoman
     *
     * @internal param object $modx
     * @internal param array $config
     */
    public function __construct(\Repoman $Repoman)
    {
        $this->modx = &$Repoman->modx;
        $this->Repoman = &$Repoman;
        $this->classname = str_replace('_parser', '', get_class($this));
    }

    /**
     * Create an element from attributes, including a DocBlock
     *
     * @param string  $pkg_dir
     * @param object  $Obj
     * @param boolean $graph whether to include related data
     *
     * @throws \Exception
     */
    public function create($pkg_dir, $Obj, $graph)
    {

        $array = $Obj->toArray('', false, false, $graph);
        $content = $Obj->getContent();
        $attributes = $this->getObjAttributes($content, $this->dox_start, $this->dox_end);
        // TODO: getProperties?
        if (!isset($attributes[$this->objectname])) {
            $name_attribute = $this->objectname;
            $name = $Obj->get($name_attribute);
        } else {
            $name = $attributes[$this->objectname];
        }

        $dir = $this->Repoman->get_core_path($pkg_dir) . rtrim($this->Repoman->get($this->dir_key), '/');

        $filename = $dir . '/' . $name . $this->write_ext;
        if (file_exists($filename) && !$this->Repoman->get('overwrite')) {
            throw new \Exception('Element already exists. Overwrite not allowed. ' . $filename);
        }


        // Create DocBlock if it doesn't already exist
        if (empty($attributes)) {
            $docblock = $this->dox_start . "\n";
            $docblock .= $this->dox_pad . '@' . $this->objectname . ' ' . $array[$this->objectname] . "\n";
            $docblock .= $this->dox_pad . '@description ' . $array['description'] . "\n";
            $docblock .= $this->extend_docblock($Obj);
            $docblock .= $this->dox_end . "\n";
            $this->modx->log(\modX::LOG_LEVEL_DEBUG, "DocBlock generated:\n" . $docblock);
            $content = $docblock . $content;
        }

        // Create dir if doesn't exist
        if (!file_exists($dir) && false === mkdir($dir, $this->Repoman->get('dir_mode'), true)) {
            throw new \Exception('Could not create directory ' . $dir);
        }

        if (false === file_put_contents($filename, $content)) {
            throw new \Exception('Could not write to file ' . $filename);
        } else {
            $this->modx->log(\modX::LOG_LEVEL_INFO, 'Created static element at ' . $f);
        }

        // Do you want to mess with the original object?  Or just grab a snapshot of it?
        if ($this->Repoman->get('move')) {
            $Obj->set('static', true);
            $Obj->set('static_file', self::path_to_rel($filename, MODX_BASE_PATH));
            $Obj->set('source', $this->getSource());
            if (!$Obj->save()) {
                throw new \Exception('Problem saving ' . $this->classname . ' ' . $array[$this->objectname]);
            }
            $this->modx->log(\modX::LOG_LEVEL_INFO, 'Original ' . $this->classname . ' ' . $array[$this->objectname] . ' updated to new location.');
        } else {
            $this->modx->log(\modX::LOG_LEVEL_DEBUG, 'Original ' . $this->classname . ' ' . $array[$this->objectname] . ' copied only.');
        }
    }

    /**
     * Add any extended docblock attributes for this object type
     *
     * @param object $Obj
     *
     * @return string
     */
    public function extend_docblock(&$Obj)
    {
        return '';
    }

    /**
     * Gather all files from the given directory, interpret them as Element Objects
     *
     * @param string $pkg_dir name
     *
     * @return array
     */
    public function gather($pkg_dir)
    {

        $this->pkg_dir = $pkg_dir;

        $objects = array();

        // Calculate the element's directory given the repo dir...
        $dir = $this->Repoman->get_core_path($pkg_dir) . rtrim($this->Repoman->get($this->dir_key), '/') . '/';
        if (!file_exists($dir) || !is_dir($dir)) {
            $this->modx->log(\modX::LOG_LEVEL_DEBUG, 'Directory does not exist: ' . $dir);

            return array();
        }

        $files = glob($dir . $this->ext);
        $i = 0;
        foreach ($files as $f) {
            $content = file_get_contents($f);
            $attributes = $this->getObjAttributes($content, $this->dox_start, $this->dox_end);
            $properties = $this->getProperties($content, $this->dox_start, $this->dox_end);
            if ($this->Repoman->get('is_build')) {
                $this->modx->log(\modX::LOG_LEVEL_DEBUG, 'is_build = true: preparing for build.');
                $content = $this->prepare_for_pkg($content);
            }
            // Skip importing?
            if (isset($attributes['no_import'])) {
                $this->modx->log(\modX::LOG_LEVEL_DEBUG, '@no_import detected in ' . $f);
                continue;
            } elseif ($this->Repoman->get('require_docblocks') && empty($attributes)) {
                $this->modx->log(\modX::LOG_LEVEL_DEBUG, 'require_docblocks set to true and no DocBlock detected in ' . $f);
                continue;
            }

            if (!isset($attributes[$this->objectname])) {
                $name = str_replace(array('snippet.', '.snippet', 'chunk.', '.chunk'
                , 'plugin.', '.plugin', 'tv.', '.tv', 'template.', '.template'
                , '.html', '.txt', '.tpl'), '', basename($f));
                $attributes[$this->objectname] = $name;
            }

            $Obj = $this->modx->getObject($this->objecttype, $this->Repoman->get_criteria($this->objecttype, $attributes));
            // Building should always create a new object.
            if ($this->Repoman->get('is_build') || !$Obj) {
                $this->modx->log(\modX::LOG_LEVEL_DEBUG, 'Creating new object from file ' . $f);
                $Obj = $this->modx->newObject($this->objecttype);
            }

            // All elements will share the same category
            $attributes['category'] = 0;

            // Force Static
            if ($this->Repoman->get('force_static')) {
                $this->modx->log(\modX::LOG_LEVEL_DEBUG, 'force_static = true');
                $attributes['source'] = 1;
                $attributes['static'] = 1;
                // see https://github.com/craftsmancoding/repoman/issues/20
                // The path gets prefixed with the default Media Source (even if you provide an absolute path)
                $attributes['source'] = $this->getSource();
                $attributes['static_file'] = self::path_to_rel($f, MODX_BASE_PATH);
            }

            $this->modx->log(\modX::LOG_LEVEL_DEBUG, "Gathered object attributes:\n" . print_r($attributes, true));

            $Obj->fromArray($attributes);
            if ($properties)
            {
                $Obj->setProperties($properties);
            }

            if (!$this->Repoman->get('force_static')) {
                $this->modx->log(\modX::LOG_LEVEL_DEBUG, "Setting content for {$this->objecttype} \"" . $Obj->get($this->objectname) . "\":\n" . $content);
                $Obj->setContent($content);
            }

            $this->relate($attributes, $Obj);

            $this->modx->log(\modX::LOG_LEVEL_INFO, 'Created/updated ' . $this->objecttype . ': ' . $Obj->get($this->objectname) . ' from file ' . $f);
            \Repoman::$queue[$this->objecttype][] = $Obj->get($this->objectname);

            if (!$this->Repoman->get('dry_run') && !$this->Repoman->get('is_build')) {
                $data = $this->Repoman->get_criteria($this->objecttype, $attributes);
                $this->modx->cacheManager->set($this->objecttype . '/' . $attributes[$this->objectname], $data, 0, \Repoman::$cache_opts);
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
     * Get the Media Source id for Repoman's static elements
     */
    public function getSource()
    {
        if (!$this->source) {
            if (!$obj = $this->modx->getObject('modMediaSource', array('name' => 'Repoman'))) {
                throw new \Exception('Could not find Repoman Media Source');
            }
            $this->source = $obj->get('id');
        }

        return $this->source;
    }

    /**
     * Given an absolute path (e.g. /home/user/public_html/assets/file.php),
     * return the file path relative to the MODX base path, e.g. assets/file.php
     *
     * @param string $path
     * @param string $base : the /full/path/to/base/ (MODX_BASE_PATH)
     *
     * @return string
     */
    public static function path_to_rel($path, $base)
    {
        return str_replace($base, '', $path); // convert path to url
    }


    /**
     * Run when files are being put into the package, this allows for
     * extraneous comment blocks to be filtered out and placeholders to be adjusted.
     *
     * @param string $string
     *
     * @return string
     */
    abstract function prepare_for_pkg($string);

    /**
     * Default behavior here requires nothing... really we only need this for Plugins and TVs...
     *
     * @param array  $attributes
     * @param object $Obj
     */
    public function relate($attributes, &$Obj)
    {

    }

    /**
     * Read object attributes out of a DocBlock
     *
     * @param string $string    the unparsed contents of a file
     * @param string $dox_start string designating the start of a doc block
     * @param string $dox_start string designating the start of a doc block
     *
     * @return array on success | false on no doc block found
     */
    public function getObjAttributes($string, $dox_start = '/*', $dox_end = '*/')
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


    /**
     * Parse a DocBlock string into an array of properties
     *
     * @param string $string    the unparsed contents of a file
     * @param string $dox_start string designating the start of a doc block
     * @param string $dox_start string designating the start of a doc block
     *
     * @return array on success | false on no doc block found
     */
    public function getProperties($string, $dox_start = '/*', $dox_end = '*/')
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
        $properties = array();
        foreach (preg_split('/((\r?\n)|(\r\n?))/', $dox) as $line) {

            preg_match('/^\s*\**\s*@param(.*)$/', $line, $m);

            if (isset($m[1])) {
                $p = array();
                $line = trim($m[1]);
                $pos = strpos($line, ' ');
                $p['type'] = substr($line,0,$pos);
                $line = trim(substr($line,$pos));
                $pos = strpos($line, ' ');
                $p['name'] = ltrim(substr($line,0,$pos),'$&');
                $line = trim(substr($line,$pos));
                $p['value'] = $this->getDefault($line);
                $p['options'] = $this->getOptions($line);
                $p['desc'] = $line;

                $properties[] = $p;
            }
        }

        return $properties;
    }

    /**
     * Parses out the default value and shortens the input $str. Built to parse a single line from the DocBlock.
     * @param $str
     * @return mixed
     */
    public function getDefault(&$str)
    {
        if (preg_match('/default\s?[=:]\s?(.*)/', $str, $m))
        {
            $match = $m[1];

            // Match double-quotes
            if ($match[0] == '"')
            {
                $str = preg_replace('/default\s?[=:]\s?".*"/U','', $str);
                $match = ltrim($match,'"');
                $pos = strpos($match,'"');
                return substr($match, 0, $pos);
            }
            // Match single-quotes
            elseif($match[0] == "'")
            {
                $str = preg_replace('/default\s?[=:]\s?\'.*\'/U','', $str);
                $match = ltrim($match,"'");
                $pos = strpos($match,"'");
                return substr($match, 0, $pos);
            }
            // Not quoted: get first word
            else
            {
                $str = preg_replace('/default\s?[=:]\s?\w+/','', $str); // greedy
                $match = trim($match);
                $pos = strpos($match, ' ');
                // End of line?
                if ($pos === false)
                {
                    return trim($match);
                }
                return trim(substr($match,0,$pos));
            }
        }
        return '';
    }

    /**
     * Parses out the options and shortens the $str. Built to parse a single line from the DocBlock.
     * The options can be a single value in theory, but in practice this
     * seems to be only used to provide options for a list.
     *
     * @param $str
     *
*@return mixed
     */
    public function getOptions(&$str)
    {
        if (preg_match('/options\s?=\s?(.*)/', $str, $m))
        {
            $match = ltrim($m[1]);

            // Match double-quotes
            if ($match[0] == '"')
            {
                $str = trim(preg_replace('/options\s?[=:]\s?".*"/U','', $str));
                $match = ltrim($match,'"');
                $pos = strpos($match,'"');
                return substr($match, 0, $pos);
            }
            // Match single-quotes
            elseif($match[0] == "'")
            {
                $str = trim(preg_replace('/options\s?[=:]\s?\'.*\'/U','', $str));
                $match = ltrim($match,"'");
                $pos = strpos($match,"'");
                return substr($match, 0, $pos);
            }
            // Unquoted JSON hash
            elseif($match[0] == '{')
            {
                $str = trim(preg_replace('/options\s?[=:]\s?\{.*\}/U','', $str));
                $options_raw = json_decode($match,true);
                $options = array();
                foreach ($options_raw as $k => $v)
                {
                    $options[] = array('value' => $k, 'text' => $v);
                }
                return $options;

            }
            // Unquoted JSON array
            elseif($match[0] == '[')
            {
                $str = trim(preg_replace('/options\s?[=:]\s?\[.*\]/U','', $str));
                $array = json_decode($match,true);
                $options_raw = array_combine($array,$array);
                $options = array();
                foreach ($options_raw as $k => $v)
                {
                    $options[] = array('value' => $k, 'text' => $v);
                }
                return $options;
            }
            // Not quoted: get first word
            else
            {
                $str = trim(preg_replace('/options\s?[=:]\s?\w+/','', $str)); // greedy
                $match = trim($match);
                $pos = strpos($match, ' ');
                // End of line?
                if ($pos === false)
                {
                    return trim($match);
                }
                return trim(substr($match,0,$pos));
            }
        }
        return '';
    }
}
/*EOF*/
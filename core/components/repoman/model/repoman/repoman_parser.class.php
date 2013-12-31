<?php
/**
 * Used by Repman to parse MODX static elements (chunks, templates, snippets, tvs)
 * specifically to strip out object parameters from the docblocks in each.
 *
 * @package repoman
 */
abstract class Repoman_parser {
    
    public $modx;
    public $config; // from Repoman
    public $Repoman; 
    
    public $dir_key; // key in the config which contains the directory.
    public $ext_key; // key in the config which ids the file extension.
    public $objecttype;
    public $objectname = 'name'; // most objects use the 'name' field to identify themselves
    
	public $dox_start = '/*';
	public $dox_end = '*/';
	public $comment_start = '<!--REPOMAN_COMMENT_START';
	public $comment_end = 'REPOMAN_COMMENT_END-->';
	
	public $extensions = '';

    /**
     * Any tags to skip in the doc block, e.g. @param, that may have significance for PHPDoc and 
     * for general documentation, but which are not intended for RepoMan and do not describe
     * object attributes. Omit "@" from the attribute names.
     * See http://en.wikipedia.org/wiki/PHPDoc
     */
    public static $skip_tags = array('param','return','abstract','access','author','copyright',
        'deprecated','deprec','example','exception','global','ignore','internal','link','magic',
        'package','see','since','staticvar','subpackage','throws','todo','var','version'
    );
    	
	
	/**
	 * @param object $modx
	 * @param array $config
	 */
	public function __construct(Repoman $Repoman) {
        $this->modx = &$Repoman->modx;
        $this->config = &$Repoman->config;
        $this->Repoman = &$Repoman;
	}
	
	
	/**
	 * Gather all elements as objects in the given directory
	 * @param string $dir name
	 * @return array
	 */
	public function gather($pkg_dir) {
        $objects = array();

        // Calculate the element's directory given the repo dir...
        // TODO: this should be configurable! Dept. of redundancy Dept.
        $dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/'.$this->get($this->dir_key).'/';

        if (!file_exists($dir) || !is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG,'Directory does not exist: '. $dir);
            return array();
        }

        $files = glob($dir.$this->get($this->ext_key));
        foreach($files as $f) {

            $content = file_get_contents($f);
            $attributes = self::repossess($content,$this->dox_start,$this->dox_end);
            
            // Skip importing?
            if (isset($attributes['no_import']) && $attributes['no_import']) {
                continue;
            }
            
            if (!isset($attributes[$this->objectname])) {
                $name = str_replace(array('snippet.','.snippet','chunk.','.chunk'
                    ,'plugin.','.plugin','tv.','.tv','template.','.template'
                    ,'.html','.txt','.tpl'),'',$name);
                $attributes[$this->objectname] = $name;
            }
                        
            $Obj = $this->modx->getObject($this->objecttype, $this->Repoman->get_criteria($this->objecttype,$attributes));
            if (!$Obj) {
                $Obj = $this->modx->newObject($this->objecttype);
            }
            
            // All elements will share the same category
            $attributes['category'] = 0; 

            // Force Static
            if ($this->get('force_static')) {
                $attributes['static'] = 1;
                $attributes['static_file'] = self::path_to_rel($f,MODX_BASE_PATH);
            }
            
            $Obj->fromArray($attributes);
            $Obj->setContent($content);
            
            $this->relate($attributes,$Obj);
              
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Created/updated '.$this->objecttype.': '. $Obj->get($this->objectname));
            Repoman::$queue[] = $this->objecttype.': '. $Obj->get($this->objectname);
            
            if (!$this->get('dry_run') && !$this->get('is_build')) {
                $data = $this->Repoman->get_criteria($this->objecttype,$attributes);
                $this->modx->cacheManager->set($this->objecttype.'/'.$attributes[$this->objectname], $data, 0, Repoman::$cache_opts);
            }
            $objects[] = $Obj;
        }
        
        return $objects;
	}

    
    /** 
     * Default behavior here requires nothing...
     *
     */
    public function relate($attributes,&$Obj) {
    
    }
    
	/**
	 * Our config getter
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
	   return (isset($this->config[$key])) ? $this->config[$key] : null;
	}


    /**
     * Given an absolute path, e.g. /home/user/public_html/assets/file.php
     * return the file path relative to the MODX base path, e.g. assets/file.php
     * @param string $path
     * @param string $base : the /full/path/to/base/ (MODX_BASE_PATH)
     * @return string
     */
    public function path_to_rel($path,$base) {
        return str_replace($base,'',$path); // convert path to url
    }
	
	/**
	 * Run as files are being put into the package, this allows for 
	 * extraneous comment blocks (aka dox) to be filtered out.
	 * 
	 * @param string $string
	 * @return string
	 */
	public function prepare_for_pkg($string) {
		return preg_replace('#('.preg_quote($this->comment_start).')(.*)('.preg_quote($this->comment_end).')#Usi', '', $string);
	}

    /**
     * Read parameters out of a (PHP) docblock... like a repoman repossessing 
     * outstanding leased objects.
     *
     * @param string $string the unparsed contents of a file
     * @param string $dox_start string designating the start of a doc block
     * @param string $dox_start string designating the start of a doc block 
     * @return array on success | false on no doc block found
     */
    public static function repossess($string,$dox_start='/*',$dox_end='*/') {
        
        $dox_start = preg_quote($dox_start,'#');
        $dox_end = preg_quote($dox_end,'#');
    
        preg_match("#$dox_start(.*)$dox_end#msU", $string, $matches);
    
        if (!isset($matches[1])) {
                return false; // No doc block found!
        }
        
        // Get the docblock                
        $dox = $matches[1];
        
        // Loop over each line in the comment block
        $a = array(); // attributes
        foreach(preg_split('/((\r?\n)|(\r\n?))/', $dox) as $line){
            preg_match('/^\s*\**\s*@(\w+)(.*)$/',$line,$m);
            if (isset($m[1]) && isset($m[2]) && !in_array($m[1], self::$skip_tags)) {
                    $a[$m[1]] = trim($m[2]);
            }
        }
        
        return $a;
    }
	
}
/*EOF*/
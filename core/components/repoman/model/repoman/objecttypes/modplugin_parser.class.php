<?php
/**
 *
 *
 */
class modPlugin_parser extends Repoman_parser {
	
	public $dir_key = 'plugins_dir';
	public $ext_key = 'plugins_ext';
	public $objecttype = 'modPlugin';
	
	/**
	 * Plugins have a caveat: they strip off the opening and closing PHP tags.
	 */
	public function prepare_for_pkg($string) {
	
	}
	
	/**
	 * Gather all elements as objects in the given directory
	 * @param string $dir name
	 * @return array
	 */
	public function gather($pkg_dir) {
        $objects = array();
        
        // Calculate the element's directory given the repo dir...
        $dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/'.$this->get($this->dir_key).'/';
        
        if (!file_exists($dir) || !is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_INFO,'Directory does not exist :'. $dir);
            return array();
        }
        $files = glob($dir.$this->get($this->ext_key));
        foreach($files as $f) {
            $events = array();
            $content = file_get_contents($f);
            $attributes = self::repossess($content,$this->dox_start,$this->dox_end);
            if (!isset($attributes['name'])) {
                $name = basename($f,'.php');
                $attributes['name'] = basename($name,'.plugin');
            }
            $Obj = $modx->getObject('modPlugin',array('name'=>$attributes['name']));
            if (!$Obj) {
                $Obj = $modx->newObject('modPlugin');
            }
            
            // All elements will share the same category
            $attributes['category'] = 0; 

            // Force Static
            if ($this->get('force_static')) {
                $attributes['static'] = 1;
                $attributes['static_file'] = self::path_to_rel($f);
            }
            
            // if Events...
            if (isset($attributes['events'])) {
                $event_names = explode(',',$attributes['events']);
                foreach ($event_names as $e) {
                    $Event = $modx->newObject('modPluginEvent');
                    $Event->set('event',trim($e));
                    $events[] = $Event;
                }
            }
            $Obj->fromArray($attributes);
            $Obj->setContent($content);
            $name = $Obj->get($this->objectname);
            if (empty($name)) {
                // strip any extension
                $name = basename($f,'.php');
                $name = str_replace(array('snippet.','.snippet','chunk.','.chunk'
                    ,'plugin.','.plugin','tv.','.tv','template.','.template'),'',$name);
                $Obj->set($this->objectname,$name);
            }
            $Obj->addMany($events);
    
            $objects[] = $Obj;
        }
        
        return $objects;
	}	
}
/*EOF*/
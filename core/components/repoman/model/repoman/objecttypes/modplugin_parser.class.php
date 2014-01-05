<?php
/**
 *
 *
 */
class modPlugin_parser extends Repoman_parser {
	
	public $dir_key = 'plugins_dir';
	public $ext = '*.php';
	public $objecttype = 'modPlugin';

    /**
     * Add any extended docblock attributes for this object type
     *
     * @param object $Obj
     * @return string
     */
    public function extend_docblock(&$Obj) {
        $out = '';
        if (isset($Obj->PluginEvents)) {
            $out = ' * @PluginEvents ';
            foreach ($Obj->PluginEvents as $e) {
                $out .= $e->get('event').',';
            }
        }
        return rtrim($out,',') ."\n";
    }
	
	/**
	 * Plugins have a caveat: they strip off the opening and closing PHP tags.
	 */
	public function prepare_for_pkg($string) {
	
	}
	
	
	/** 
	 * Attach events to the plugin
	 *
	 */
	public function relate($attributes,&$Obj) {
        $events = array();
        if (isset($attributes['PluginEvents'])) {
            $event_names = explode(',',$attributes['PluginEvents']);
            foreach ($event_names as $e) {
                $pluginid = $Obj->get('id');
                if ($pluginid) {
                    $Event = $this->modx->getObject('modPluginEvent', array('pluginid'=>$pluginid,'event'=>$e));
                }
                else {
                    $Event = $this->modx->newObject('modPluginEvent');
                    $Event->set('event',trim($e));                
                }
                
                Repoman::$queue[] = 'modPluginEvent: '. $Event->get('event');
                $events[] = $Event;
            }
        }
    	$Obj->addMany($events);
	}
}
/*EOF*/
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
	 * Run when files are being put into the package, this allows for 
	 * extraneous comment blocks to be filtered out and placeholders to be adjusted.
	 * 
	 * @param string $string
	 * @return string
	 */
	public function prepare_for_pkg($string) {
        $string = preg_replace('#('.preg_quote($this->comment_start).')(.*)('.preg_quote($this->comment_end).')#Usi', '', $string);		
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.assets_url', '[[++assets_url', $string);
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.assets_path', '[[++assets_path', $string);
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.core_path', '[[++core_path', $string);
        return $string;
	}
	
	/** 
	 * Attach and Remove plugin's events
	 *
	 */
	public function relate($attributes,&$Obj) {
        $events = array();
        $pluginid = $Obj->get('id');
        if (isset($attributes['PluginEvents'])) {
            $event_names = explode(',',$attributes['PluginEvents']);
            // Remove unassociated events
            $existingEvents = $this->modx->getCollection('modPluginEvent', array('pluginid'=>$pluginid, 'event:NOT IN'=> $event_names));
            foreach ($existingEvents as $ee) {
                $ee->remove();
            }
            
            foreach ($event_names as $e) {
                if (!$Event = $this->modx->getObject('modPluginEvent', array('pluginid'=>$pluginid,'event'=>trim($e)))) {
                    $Event = $this->modx->newObject('modPluginEvent');
                    $Event->set('event',trim($e));                
                }

                Repoman::$queue[$this->objecttype][] = 'modPluginEvent: '. $Event->get('event');
                $events[] = $Event;
            }
        }
        else {
        // Remove all Events
            $existingEvents = $this->modx->getCollection('modPluginEvent', array('pluginid'=>$pluginid));
            foreach ($existingEvents as $ee) {
                $ee->remove();
            }        
        }
    	$Obj->addMany($events);
	}
}
/*EOF*/
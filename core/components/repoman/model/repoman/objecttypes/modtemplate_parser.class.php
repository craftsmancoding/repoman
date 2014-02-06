<?php
/**
 *
 *
 */
class modTemplate_parser extends Repoman_parser {

    public $dir_key = 'templates_dir';
	public $ext = '*.*';
    public $write_ext = '.html';	  
    public $objecttype = 'modTemplate';
    public $objectname = 'templatename';
    
	public $dox_start = '<!--';
	public $dox_end = '-->';
    public $dox_pad = ''; // left of line before the @attribute
	
    /**
     * Add any extended docblock attributes for this object type
     *
     * @param object $Obj
     * @return string
     */
    public function extend_docblock(&$Obj) {
        $out = '';
        // 2 tiers here to get to the TVs TODO
        if (isset($Obj->TemplateVarTemplates)) {
            $TVs = $Obj->TemplateVarTemplates->getMany();
            $out = ' * @TVs ';
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
		// Strip out docblock entirely (i.e. the first comment)
		$string = preg_replace('#('.preg_quote($this->dox_start).')(.*)('.preg_quote($this->dox_end).')#Uis', '', $string,1);
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.assets_url', '[[++assets_url', $string);
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.assets_path', '[[++assets_path', $string);
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.core_path', '[[++core_path', $string);
        return $string;
	}
	
	/** 
	 * Attach TVs to the template
	 *
	 */
	public function relate($attributes,&$Obj) {
        $tvs = array();
        if (isset($attributes['TVs'])) {
            $tv_names = explode(',',$attributes['TVs']);
            foreach ($tv_names as $t) {
/*
                $templateid = $Obj->get('id');
                if ($templateid) {
                    $TV = $this->modx->getObject('modTemplateVar', array('name'=>trim($t)));
                    modTemplateVarTemplate
                }
                else {
//                    $TV = $this->modx->newObject('modPluginEvent');
//                    $TV->set('event',trim($e));                
                }
                
//                Repoman::$queue[] = 'modTemplateVarTemplate: '. $Event->get('event');
//                $events[] = $Event;
*/
            }
        }
    	//$Obj->addMany($events);
	}
	
}
/*EOF*/
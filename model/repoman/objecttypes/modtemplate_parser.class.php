<?php
/**
 *
 *
 */
class modTemplate_parser extends Repoman_parser {

    public $dir_key = 'templates_path';
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
	 * Attach and Remove Template's TVs
	 *
	 */
	public function relate($attributes,&$Obj) {
        $tvs = array();
        $templateid = $Obj->get('id');
        if (isset($attributes['TVs'])) {
            $tv_names = explode(',',$attributes['TVs']);
            // Remove unassociated TVs
/*
            $TVTs = $this->modx->getCollection('modTemplateVarTemplate', array('templateid'=>$templateid, 'event:NOT IN'=> $tv_names));
            foreach ($TVTs as $t) {
                $t->remove();
            }
*/
            
            foreach ($tv_names as $t) {
                $t = trim($t);
                if (!$TV = $this->modx->getObject('modTemplateVar', array('name'=>$t))) {
                    $TV = $this->modx->newObject('modTemplateVar');
                }
                // Set TV attributes?  This is like Seed data, but it lives in elements/tvs
                $filename = $this->Repoman->get_core_path($this->pkg_dir).$this->Repoman->get('tvs_dir').'/'.$t.'.php';
                $data = $this->Repoman->load_data($filename);
                $TV->fromArray($data[0]); // one at a time only.

                Repoman::$queue[$this->objecttype][] = 'modTemplateVarTemplate: '. $TV->get('name') .' '.$Obj->get('templatename');
                $tvs[] = $TV;
            }
        }
        else {
        // Remove all TVs
            $TVTs = $this->modx->getCollection('modTemplateVarTemplate', array('pluginid'=>$pluginid));
            foreach ($TVTs as $t) {
                $t->remove();
            }        
        }
    	$Obj->addMany($tvs);
	}

	
}
/*EOF*/
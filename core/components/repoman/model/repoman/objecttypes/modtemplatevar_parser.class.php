<?php
/**
 * The TV stores the "Default Value" as the "content"
 *
 */
class modTemplateVar_parser extends Repoman_parser {

    public $dir_key = 'tvs_dir';
	public $ext = '*.*';
    public $write_ext = '.tv';	
    public $objecttype = 'modTemplateVar';

	public $dox_start = '<!--';
	public $dox_end = '-->';
    public $dox_pad = ''; // left of line before the @attribute	
	
	/**
	 * Special behavior here for HTML doc blocks.
	 */
	public function prepare_for_pkg($string) {
		// Strip out docblock entirely (i.e. the first comment)
		$string = preg_replace('#('.preg_quote($this->dox_start).')(.*)('.preg_quote($this->dox_end).')#Uis', '', $string,1);
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.assets_url', '[[++assets_url', $string);
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.assets_path', '[[++assets_path', $string);
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.assets_url', '[[++core_path', $string);
        return $string;
	}
}
/*EOF*/
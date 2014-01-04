<?php
/**
 * REMEMBER: chunks are not cached anywhere except per Resource
 *
 */
class modChunk_parser extends Repoman_parser {

    public $dir_key = 'chunks_dir';
	public $ext = '*.*';
    public $objecttype = 'modChunk';
    
	public $dox_start = '<!--';
	public $dox_end = '-->';
	
		
	/**
	 * Special behavior here for HTML doc blocks.
	 */
	public function prepare_for_pkg($string) {
		// Strip out docblock entirely (i.e. the first comment)
		$string = preg_replace('#('.preg_quote($this->dox_start).')(.*)('.preg_quote($this->dox_end).')#Uis', '', $string,1);
		 
		// Strip out any additional areas
		return parent::prepare_for_pkg($string);	
	}
}
/*EOF*/
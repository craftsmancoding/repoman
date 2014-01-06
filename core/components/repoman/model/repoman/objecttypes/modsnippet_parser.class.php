<?php
/**
 *
 *
 */
class modSnippet_parser extends RepoMan_parser {
    public $dir_key = 'snippets_dir';
	public $ext = '*.php';
    public $objecttype = 'modSnippet';

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
        $string = str_replace('[[++'.$this->Repoman->get('namespace').'.assets_url', '[[++core_path', $string);
        return $string;
	}

}
/*EOF*/
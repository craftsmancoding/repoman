<?php
/**
 * "dox" is shorthand for document block: the comment block containing documentation
 *
 * @package repoman
 * @version 1.0
 */
abstract class RepoMan_parser {

	public $dox_start = '/*';
	public $dox_end = '*/';
	public $comment_start = '<!--REPOMAN_COMMENT_START';
	public $comment_end = 'REPOMAN_COMMENT_END-->';
	
	/**
	 * Used to get parameters out of a (PHP) docblock.
	 *
	 * @param string $string the unparsed contents of a file
	 * @return array
	 */
	public function get_attributes_from_dox($string) {
		
		$dox_start = preg_quote($this->dox_start,'#');
		$dox_end = preg_quote($this->dox_end,'#');
		
		preg_match("#$dox_start(.*)$dox_end/#msU", $string, $matches);
		
		if (!isset($matches[1])) {
			return array();
		}
		
		// Get the docblock		
		$dox = $matches[1];
		
		// Loop over each line in the comment block
		$a = array(); // attributes
		foreach(preg_split('/((\r?\n)|(\r\n?))/', $dox) as $line){
			preg_match('/^\s*\**\s*@(\w+)(.*)$/',$line,$m);
				if (isset($m[1]) && isset($m[2])) {
					$a[$m[1]] = trim($m[2]);
				}
		}
		
		return $a;
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
}
/*EOF*/
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
	
	// Which field/attribute stores the object's name?
	public $name_attribute = 'name';
	
	public $extensions = array('.php');
	
	// Any tags to skip in the doc block, e.g. @param, that may have significance for PHPDoc and 
	// for general documentation, but which are not intended for RepoMan and do not describe
	// object attributes. Omit "@" from the attribute names.
	// See http://en.wikipedia.org/wiki/PHPDoc
	public $skip_tags = array(
		'param',
		'return',
		'abstract',
		'access',
		'author',
		'copyright',
		'deprecated',
		'deprec',
		'example',
		'exception',
		'global',
		'ignore',
		'internal',
		'link',
		'magic',
		'package',
		'see',
		'since',
		'staticvar',
		'subpackage',
		'throws',
		'todo',
		'var',
		'version'
	);
	
	public $log = array();
	
	/**
	 * Used to get parameters out of a (PHP) docblock.
	 *
	 * @param string $string the unparsed contents of a file
	 * @return array on success | false on no doc block found
	 */
	public function get_attributes_from_dox($string) {
		
		$dox_start = preg_quote($this->dox_start,'#');
		$dox_end = preg_quote($this->dox_end,'#');

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
				if (isset($m[1]) && isset($m[2]) && !in_array($m[1], $this->skip_tags)) {
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
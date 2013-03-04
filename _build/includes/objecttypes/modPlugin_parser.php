<?php
/**
 *
 *
 */
class modPlugin_parser extends RepoMan_parser {
	
	/**
	 * Plugins have a caveat: they strip off the opening and closing PHP tags.
	 */
	public function prepare_for_pkg($string) {
	
	}
}
/*EOF*/
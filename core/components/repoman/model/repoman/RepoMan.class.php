<?php
class RepoMan {

	// TODO: make these configurable
	public $folder_object_map = array(
		'elements' => 'modChunk',
		'snippets' => 'modSnippet',
		'plugins' => 'modPlugin'
	);

	public $readme_filenames = array('README.md','readme.md');
	
	/**
	 * Get the readme file from a repo
	 *
	 * @param string $repo_path full path to file, without trailing slash
	 * @return string (contents of README.md file) or false if not found
	 */
	public function get_readme($repo_path) {
		return false;
		foreach ($this->readme_filenames as $f) {
			$readme = $repo_path.'/'.$f;
			if (file_exists($readme)) {
				return file_get_contents($readme);
			}
		}
		return false;
	}
	
	
	/**
	 * This is the raison d'etre of the entire package: this scans a local
	 * working copy (i.e. a local repository) for objects, then pushes them
	 * into MODX, creating or updating objects as required.
	 *
	 * Important here is the order: certain objects must be created first, e.g.
	 * a category must exist before you can add an object to it.
	 *
	 * @param string $repo_path (omit trailing slash)
	 * @return ???
	 */
	public function sync_modx($repo_path) {
		$repo = basename($repo_path);
		$dir = $repo_path .'/core/components/'.$repo.'/elements';
		
		// Which object directories are available?
		$items = '';
		foreach (new RecursiveDirectoryIterator($dir) as $filename) {
			if (is_dir($filename)) {
				$shortname = preg_replace('#^'.$dir.'/#','',$filename);
				if ($shortname != '.' && $shortname != '..') {
					$items .= $shortname .'<br/>';
				}
			}	
		}	
		return $items;
	}
}
/*EOF*/
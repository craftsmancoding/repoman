<?php
/**
 *
class RepoMan_crawler {


		// Substitutions
		$this->source_dir = str_replace('[+ABSPATH+]', ABSPATH, $this->source_dir);
		$this->source_dir = preg_replace('#/$#','',$this->source_dir); // strip trailing slash
		
		// Generate the regex pattern
		$exts = explode(',',$this->pattern);
		$exts = array_map('trim', $exts);
		$exts = array_map('preg_quote', $exts);
		$pattern = implode('|',$exts);
		
		
		// Get the files
		$options = array();
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->source_dir)) as $filename) {
			if (preg_match('/('.$pattern.')$/i',$filename)) {
				// Make the stored file relative to the source_dir
				$options[] = preg_replace('#^'.$this->source_dir.'/#','',$filename);
			}   
		}
}
/*EOL*/
<?php
/**
 * Defines default values for Repoman packages.
 * Override values by creating a config.php file at the root
 * of your package repository.
 *
 * @param string $pkg_path
 */
return array(
    'package_name' => basename($pkg_path),
    'package_name_lower' => strtolower(basename($pkg_path)),
    'description' => 'This package was built with Repoman (https://github.com/craftsmancoding/repoman)',
    'version' => '1.0.0',
    'release' => '',
    'author_name' => 'Unknown',
    'author_email' => '',
    'author_site' => '',    
    'author_url' => '',
    'documentation_url' => 'https://github.com/craftsmancoding/repoman/wiki/config.php',
    'repo_url' => '',   // https://github.com/username/pkg
    'clone_url' => '',  // git@github.com:username/pkg.git or https://github.com/username/pkg.git
    'copyright' => date('Y'),
    
    'namespace' => strtolower(basename($pkg_path)),
    'category' => basename($pkg_path), // Package Name
    'docblocks_reqd' => false,
    'import_docs' => '*', // you may include an array specifying basenames of specific files
    'log_level' => modX::LOG_LEVEL_INFO,
);
/*EOF*/
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
    'require_docblocks' => false, // if true, your elements *must* define docblocks in order to be imported
    'build_docs' => '*', // you may include an array specifying basenames of specific files in the build
    'log_level' => modX::LOG_LEVEL_INFO,
    
    // Dirs relative to core/components/$pkg_name/ 
    'chunks_dir' => 'elements/chunks',
    'plugins_dir' => 'elements/plugins',
    'snippets_dir' => 'elements/snippets',
    'templates_dir' => 'elements/templates',
    'tvs_dir' => 'elements/tvs',

    // Extensions for searching the element directories
    'chunks_ext' => '*.*',
    'plugins_ext' => '*.php',
    'snippets_ext' => '*.php',
    'templates_ext' => '*.*',
    'tvs_ext' => '*.*',
    
    // For dev importing, for elements to reference static file
    'force_static' => true,
    
);
/*EOF*/
<?php
/**
 * Defines default values for Repoman packages.
 * Override values by creating a config.php file at the root
 * of your package repository.
 *
 * modX and xPDOTransport constants are in context when this file is included,
 * as well as the $pkg_path (absolute path to the repository's base path.
 * 
 * @return array
 */
return array(
    'package_name' => basename($pkg_path),
    'namespace' => strtolower(basename($pkg_path)),
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
    
    'category' => basename($pkg_path), // For elements
    'require_docblocks' => false, // if true, your elements *must* define docblocks in order to be imported
    'build_docs' => '*', // you may include an array specifying basenames of specific files in the build
    'log_level' => modX::LOG_LEVEL_INFO,
    
    // Dirs relative to core/components/$pkg_name/ 
    'chunks_dir' => 'elements/chunks',
    'plugins_dir' => 'elements/plugins',
    'snippets_dir' => 'elements/snippets',
    'templates_dir' => 'elements/templates',
    'tvs_dir' => 'elements/tvs',
    
    'migrations_dir' => 'database/migrations',
    'objects_dir' => 'database/objects',
    'seeds_dir' => 'database/seeds',

    // Extensions for searching the element directories
    'chunks_ext' => '*.*',
    'plugins_ext' => '*.php',
    'snippets_ext' => '*.php',
    'templates_ext' => '*.*',
    'tvs_ext' => '*.*',
    
    // For import/install (dev), force elements to reference static file for easier editing
    'force_static' => true,
    
    'dry_run' => false, // use runtime setting: --dry_run to see which objects will be created.
    'dir_perms' => 0777,
    'seed' => null, // default database seed file to include during standard migrations

    'abort_install_on_fail' => true,
    
    /**
     * Used when building packages and for running install/import because we need to know 
     * which fields identify an object and how to handle them if they already exist.
     */
    'build_attributes' => array(
        'modCategory' => array(
                xPDOTransport::PRESERVE_KEYS => true,
                xPDOTransport::UPDATE_OBJECT => false, // <-- moot point when we only have a single column
                xPDOTransport::UNIQUE_KEY => array('category'),
            ),
        'modSystemSetting' => array(
        	xPDOTransport::UNIQUE_KEY => 'key',
        	xPDOTransport::PRESERVE_KEYS => true,
        	xPDOTransport::UPDATE_OBJECT => false, // <-- critical! We don't want to overwrite user's values	
        ),
        'modMenu' => array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'text',
        ),
        // Elements
        'modSnippet' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ),
        'modChunk' => array (
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ),
        'modTemplate' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'templatename',
        ),
        'modTemplateVar' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ),        
        'modPlugin' => array (
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::RELATED_OBJECTS => true,
			xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
		        'PluginEvents' => array(
		            xPDOTransport::PRESERVE_KEYS => true,
		            xPDOTransport::UPDATE_OBJECT => false,
		            xPDOTransport::UNIQUE_KEY => array('pluginid','event'),
		        ),
    		),
        ),
       'modAction' => array(
           xPDOTransport::PRESERVE_KEYS => false,
           xPDOTransport::UPDATE_OBJECT => true,
           xPDOTransport::UNIQUE_KEY => array(
               'namespace',
               'controller'
           ),
       ),
       'modContentType' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',       
       ),
       'modDashboard' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',       
       ),
       'modUserGroup' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',       
       ),
       'modUserGroupRole' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',       
       ),
       'modPropertySet' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',       
       ),
       'modUserGroupRole' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',       
       ),
       'modNamespace' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',       
       ),
       'modUser' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'username',       
       ),
       'modContext' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'key',   
       ),
       'modDashboardWidget' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => array('name','namespace'),
       ),

        
    ), // end build_attributes
);
/*EOF*/
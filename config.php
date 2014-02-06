<?php
/**
 * Config File for Repoman.  Dogfooding here... Repoman can read its own config
 * to package itself.
 *
 * @return array
 */
return array(
    'package_name' => 'Repoman',
    'namespace' => 'repoman',
    'description' => "Repository manager for MODX Revolution.",
    'version' => '0.8.0',
    'release' => 'dev',
    'author_name' => 'Everett Griffiths',
    'author_email' => 'everett@craftsmancoding.com',
    'author_site' => 'Craftsman Coding',    
    'author_url' => 'http://craftsmancoding.com/',
    'documentation_url' => 'https://github.com/craftsmancoding/repoman/wiki',
    'repo_url' => 'https://github.com/craftsmancoding/repoman',
    'clone_url' => 'https://github.com/craftsmancoding/repoman.git',
    'copyright' => date('Y'),
    'category' => 'Repoman',
    'seed' => array('base'),
);
/*EOF*/

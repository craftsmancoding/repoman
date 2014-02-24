<?php
/*------------------------------------------------------------------------------
This file is used to prototype newly created repositories... although we could
use a str_replace('\/','/',json_encode() function and prompt the user for more input via a GUI, they
are gonna be editing this file sooner or later, and not everyone has PHP 5.4 
with its JSON_PRETTY_PRINT and JSON_UNESCAPED_SLASHES options.  Besides... this
way lets me leave some comments...

Using str_replace('\/','/',json_encode here to ensure that variables get properly quoted in case some
incompatible value snuck past our validation.
------------------------------------------------------------------------------*/
?>
{
    "name": <?php print str_replace('\/','/',json_encode('vendorname/'.$config['namespace'])); ?>,
    "description": <?php print str_replace('\/','/',json_encode($config['description'])); ?>,
    "type": "library",
    "keywords": ["modx","repoman"],
    "homepage": "https://github.com/craftsmancoding/repoman",
    "license": "GPL-2.0+",
    "authors": [
        {
            "name": <?php print str_replace('\/','/',json_encode($config['author_name'])); ?>,
            "email": <?php print str_replace('\/','/',json_encode($config['author_email'])); ?>,
            "homepage" : <?php print str_replace('\/','/',json_encode($config['author_homepage'])); ?>
        }        
    ],
    "support": {
        "email": <?php print str_replace('\/','/',json_encode($config['author_email'])); ?>,
        "issues": "",
        "forum": "http://forums.modx.com/",
        "wiki": "https://raw2.github.com/craftsmancoding/repoman/master/screenshots/lack_of_docs.jpg",
        "source": ""
    },    
    "require": {
        "php": ">=5.3"
    },
    "autoload": {
        "classmap":[<?php print str_replace('\/','/',json_encode('model/'.$config['namespace'].'/')); ?>]
    },
    "extra": {
        "package_name": <?php print str_replace('\/','/',json_encode($config['package_name'])); ?>,
        "namespace": <?php print str_replace('\/','/',json_encode($config['namespace'])); ?>,
        "version": <?php print str_replace('\/','/',json_encode($config['version'])); ?>,
        "release": <?php print str_replace('\/','/',json_encode($config['release'])); ?>,
        "category": <?php print str_replace('\/','/',json_encode($config['package_name'])); ?>,
        "core_path": "",
        "assets_path" : "assets/"
    }
}

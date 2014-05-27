# Repoman for MODx Revolution

Repoman is a package for [MODx Revolution](http://modx.com/) that makes it easier for developers to develop their own MODx packages or contribute to existing MODx projects.  Its goal is to simplify MODx AddOn development and maintainance: no more headaches and steep learning curves writing custom build scripts or laborious click-work adding objects to the manager in preparation for development. Repoman is taking back simplicity!

[![Repoman Demo Video](https://raw2.github.com/craftsmancoding/repoman/master/screenshots/video_intro.jpg)](https://www.youtube.com/watch?v=byCNjy4Txfo)

-------------------------------

## Why is it Needed?

1. All MODx code is referenced in the database, so adding and updating templates, chunks, snippets etc. requires much patience and click-work in the manager GUI.  Repoman's "import" and "install" functionality streamlines development by detecting files in your package's directory and creating the necessary MODX records.
2. Even though MODX packages install files into _two_ locations (web accessible "assets" and non-public "core" files), Repoman codifies the workarounds to let you develop and version your code in _one_ directory.  Simplify your directory structure!
3. Although extremely useful, MODX's transport packages are a pain to create.  Repoman eliminates the need for developers to create customized build scripts. 
4. Packages created with Repoman are compatible with [Composer](https://getcomposer.org/), so you can sensibly manage PHP dependencies within your MODX packages.  This means you can use [autoloading](https://github.com/craftsmancoding/repoman/wiki/Autoloading) and namespaces too!

--------------------------------

## Who Should Download this?

Repoman is useful to MODX developers who version their packages with Git (or any other version control software) and who are releasing them to the public as a [MODX AddOn](http://modx.com/extras/).  It is also useful for developers who want to fork existing MODX repositories and make their own contributions.  Repoman makes the development process easier.

---------------------------------

## Installation

You can install Repoman via the standard MODx package manager, or you can install Repoman via Repoman (yes, some dog-fooding is possible here).

1. Clone the Repoman repository from https://github.com/craftsmancoding/repoman to a dedicated directory inside your MODx web root, e.g. "mypackages"
2. Run "composer install" on your new repository to pull in the package dependencies.
3. Run the command-line repoman tool on the repoman/ directory, e.g. "php repoman install /home/myuser/public_html/mypackages/"

NOTE: on MAMP environments, you must link "php" to the MAMP version of PHP, not to the native Mac version.  
Due to permissions issues, you may also need to run the commands as an admin user using "sudo", e.g.

 su - admin
 cd /path/to/modx/repos/repoman
 sudo composer install
 sudo php repoman install .
 
 
---------------------------------
## Conventions

Repman works when you (the developer) follow a few simple conventions in your code.  First, you should add 
[DocBlocks](https://github.com/craftsmancoding/repoman/wiki/DocBlocks) to your elements to identify their attributes.  
For example, a Snippet should begin with a comment like this:

    <?php
    /**
     * @name MySnippet
     * @description This is my description
     */

Next, you should make sure that any include or require statements defer to paths in the development environment (managed by Repoman).  This ensures that the 
paths will work both in your local dev environment, and inside a production environment when your package is installed via a MODx transport package.  
For example, look at this require statement for a PHP file in the "mypackage" package:

    $core_path = $modx->getOption('mypackage.core_path','', MODX_CORE_PATH.'components/mypackage/');
    require_once $core_path.'model/mypackage/mypackage.class.php';

Repoman will set the "mypackage.core_path" System Setting, and on a production site, that setting won't exist, so getOption will fall back to the produciton
location inside of core/components.  

The path in dev might be: /Users/myuser/Sites/modx/public_html/repos/mypackage/
The path in prod might be: /var/www/html/core/components/mypackage/

See the Wiki for more discussion and examples of Repoman's [Conventions](https://github.com/craftsmancoding/repoman/wiki/Conventions).

See some of the [Wiki Pages](https://github.com/craftsmancoding/repoman/wiki/_pages) to learn more about using Repoman!
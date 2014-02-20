# Repoman for MODX Revolution

Repoman is a package for [MODX Revolution](http://modx.com/) that makes it easier for developers to develop their own MODX packages or contribute to existing MODX projects.  Its goal is to simplify MODX AddOn development and maintainance: no more headaches and steep learning curves writing custom build scripts or laborious click-work adding objects to the manager in preparation for development. Repoman is taking back the simplicity!

![](https://raw2.github.com/craftsmancoding/repoman/master/screenshots/command-line-install.jpg)

-------------------------------

## Why is it Needed?

1. All MODx code is referenced in the database, so adding and updating templates, chunks, snippets etc. requires much patience and click-work in the manager GUI.  Repoman's "import" and "install" functionality streamlines development by detecting files in your package's directory and creating the necessary MODX records.
2. Even though MODX packages install files into _two_ locations (web accessible "assets" and non-public "core" files), Repoman codifies the workarounds to let you develop and version your code in _one_ directory.  Simply your directory structure!
3. Although extremely useful, MODX's transport packages are a pain to create.  Repoman eliminates the need for developers to create customized build scripts. 
4. Packages created with Repoman are compatible with [Composer](https://getcomposer.org/), so you can sensibly manage PHP dependencies within your MODX packages.  This means you can use [autoloading](https://github.com/craftsmancoding/repoman/wiki/Autoloading) and namespaces too!

--------------------------------

## Who Should Download this?

Repoman is useful to MODX developers who version their packages with Git (or any other version control software) and who are releasing them to the public as a [MODX AddOn](http://modx.com/extras/).  It is also useful for developers who want to fork existing MODX repositories and make their own contributions.  Repoman makes the development process easier.

---------------------------------
## Conventions

Repman works when you (the developer) follow a few simple [Conventions](https://github.com/craftsmancoding/repoman/wiki/Conventions) in your code. (Examples coming...)

See some of the [Wiki Pages](https://github.com/craftsmancoding/repoman/wiki/_pages) to learn more about using Repoman!
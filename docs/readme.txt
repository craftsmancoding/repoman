Repoman
=======

Repoman is a package for MODX Revolution that makes it easier for developers to 
develop their own MODX packages or contribute to existing MODX projects.  Its 
goal is to simplify MODX package development and maintenance: no more headaches 
and steep learning curves writing custom build scripts or laborious click-work 
adding objects to the manager in preparation for development. 

Repoman is taking back the simplicity!


Who Should Download this?
-------------------------

Repoman is useful to MODX developers who version their packages with Git (or any other 
version control software) and who are releasing them to the public as a MODX AddOn.  It is 
also useful for developers who want to fork existing MODX repositories and make their own 
contributions.  Repoman makes the development process easier.

Repoman should only be installed in MODX development environments.  It is not intended to 
be used on production sites.

If you are not a MODX developer or you are not using versioning software, then you have no need of Repoman.


Installation
------------

You can install Repoman via the standard MODx package manager, or you can install Repoman via Repoman (yes, some bootstrapping/dog-fooding here).

1. Clone the Repoman repository from https://github.com/craftsmancoding/repoman to a dedicated directory inside your MODx web root.
2. Run "composer install" on your new repository to pull in the package dependencies.
3. Run the command-line repoman tool on the repoman/ directory, e.g. "php repoman install ."


Conventions
-----------

Repman works when you (the developer) follow a few simple conventions in your code
(see https://github.com/craftsmancoding/repoman/wiki/Conventions).  

See some of the Wiki Pages to learn more about using Repoman: 
https://github.com/craftsmancoding/repoman/wiki/_pages
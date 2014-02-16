Repoman
=======

Repoman is a package for MODX Revolution that makes it easier for developers to 
develop their own MODX packages or contribute to existing MODX projects.  Its 
goal is to simplify MODX AddOn development and maintenance: no more headaches 
and steep learning curves writing custom build scripts or laborious click-work 
adding objects to the manager in preparation for development. 

Repoman is taking back the simplicity!


Why is it Needed?
-----------------

1. All MODX code is referenced in the database (!!!), so adding and updating templates, 
chunks, snippets etc. requires much patience and click-work in the manager GUI.  
Repoman's "import" and "install" functionality streamlines development by detecting 
files in your package's directory and creating the necessary MODX records.

2. Unlike some systems (e.g. Wordpress), MODX installs AddOn files in TWO locations 
(primarily for security): web-accessible files (images, css, js) belong in the "assets" 
folder, while PHP and any non-public files belong inside the "core" directory.  This 
makes it difficult to keep your project files under version control: Git brings ONE 
directory under version control.  Repoman codifies the work-arounds into a consistent 
pattern so you can easily keep your package's files in one place while developing.

3. Although extremely useful, MODX's transport packages are a pain to create.  Repoman 
eliminates the need for developers to create customized build scripts: just run Repoman's 
"build" utility on your project, and a MODX package will be created from your project's 
files, including any/all MODX objects and custom database tables.


Who Should Download this?
-------------------------

Repoman is useful to MODX developers who version their packages with Git (or any other 
version control software) and who are releasing them to the public as a MODX AddOn.  It is 
also useful for developers who want to fork existing MODX repositories and make their own 
contributions.  Repoman makes the development process easier.

Repoman should only be installed in MODX development environments.  It is not intended to 
be used on production sites.

If you are not a MODX developer or you are not using versioning software, then you have no need of Repoman.


Conventions
-----------

Repman works when you (the developer) follow a few simple conventions in your code
(see https://github.com/craftsmancoding/repoman/wiki/Conventions).  

See some of the Wiki Pages to learn more about using Repoman: 
https://github.com/craftsmancoding/repoman/wiki/_pages
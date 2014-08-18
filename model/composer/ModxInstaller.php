<?php
/**

supports(), here you test whether the passed type matches the name that you declared for this installer (see the example).
isInstalled(), determines whether a supported package is installed or not.
install(), here you can determine the actions that need to be executed upon installation.
update(), here you define the behavior that is required when Composer is invoked with the update argument.
uninstall(), here you can determine the actions that need to be executed when the package needs to be removed.
getInstallPath(), this method should return the location where the package is to be installed, relative from the location of composer.json.
*/
namespace Repoman\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class ModxInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function getPackageBasePath(PackageInterface $package)
    {

/*
        $prefix = substr($package->getPrettyName(), 0, 23);
        if ('phpdocumentor/template-' !== $prefix) {
            throw new \InvalidArgumentException(
                'Unable to install template, phpdocumentor templates '
                .'should always start their package name with '
                .'"phpdocumentor/template-"'
            );
        }

        return 'data/templates/'.substr($package->getPrettyName(), 23);
*/
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return 'modx-package' === $packageType;
    }
}
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
        error_log(__FUNCTION__);
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
        error_log(__FUNCTION__);
        return 'modx-package' === $packageType;
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        error_log(__CLASS__.'::'.__FUNCTION__);
        error_log('Repo: '.print_r($repo,true));
        error_log('Package: '.print_r($package,true));
        return parent::install($repo,$package);
/*
        $extra = $package->getExtra();
        if (empty($extra['class'])) {
            print var_dump($extra);
            throw new \UnexpectedValueException('Error while installing '.$package->getPrettyName().', composer-plugin packages should have a class defined in their extra key to be usable.');
        }

        parent::install($repo, $package);
        $this->composer->getPluginManager()->registerPackage($package);
*/
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        error_log(print_r($repo,true));
        error_log(print_r($initial,true));
        error_log(print_r($target,true));
        return parent::update($repo,$initial,$target);    
/*
        $extra = $target->getExtra();
        if (empty($extra['class'])) {
            throw new \UnexpectedValueException('Error while installing '.$target->getPrettyName().', composer-plugin packages should have a class defined in their extra key to be usable.');
        }

        parent::update($repo, $initial, $target);
        $this->composer->getPluginManager()->registerPackage($target);
*/
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return parent::uninstall($repo, $package);
/*
        if (!$repo->hasPackage($package)) {
            throw new \InvalidArgumentException('Package is not installed: '.$package);
        }

        $this->removeCode($package);
        $this->removeBinaries($package);
        $repo->removePackage($package);

        $downloadPath = $this->getPackageBasePath($package);
        if (strpos($package->getName(), '/')) {
            $packageVendorDir = dirname($downloadPath);
            if (is_dir($packageVendorDir) && $this->filesystem->isDirEmpty($packageVendorDir)) {
                @rmdir($packageVendorDir);
            }
        }
*/
    }
    
}
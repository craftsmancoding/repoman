<?php
/**
 * Remember that the methods only apply if the supports() method evaluates to true.  Only packages listed in the
 * "require" block are evaluated here; the root package is not evaluated (see the Script.php class for handling
 * stuff from the root package).
 *
 * Available methods:
 * supports(), here you test whether the passed type matches the name that you declared for this installer (see the example).
 * isInstalled(), determines whether a supported package is installed or not.
 * install(), here you can determine the actions that need to be executed upon installation.
 * update(), here you define the behavior that is required when Composer is invoked with the update argument.
 * uninstall(), here you can determine the actions that need to be executed when the package needs to be removed.
 * getInstallPath(), this method should return the location where the package is to be installed, relative from the location of composer.json.
 */
namespace Repoman\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

class Installer extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    /*
        public function getPackageBasePath(PackageInterface $package)
        {
            error_log(__CLASS__.'::'.__FUNCTION__.' called.');

            $prefix = substr($package->getPrettyName(), 0, 23);
            if ('phpdocumentor/template-' !== $prefix) {
                throw new \InvalidArgumentException(
                    'Unable to install template, phpdocumentor templates '
                    .'should always start their package name with '
                    .'"phpdocumentor/template-"'
                );
            }

            return 'data/templates/'.substr($package->getPrettyName(), 23);
        }
    */

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        if ('modx-package' === $packageType) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        error_log(__CLASS__.'::'.__FUNCTION__);

//        $vars = get_object_vars($repo);
//        error_log('Repo vars: '. print_r($vars,true));
//        $methods = get_class_methods($repo);
//        error_log('Repo methods: '.print_r($methods,true));


//        $vars = get_object_vars($package);
//        error_log('Package vars: '. print_r($vars,true));
//        $methods = get_class_methods($package);
//        error_log('Package methods: '.print_r($methods,true));
//        foreach ($methods as $m) {
//            error_log($m.': '.print_r($package->$m(), true));
//        }
        error_log('getRepositories: '. print_r($package->getRepositories(),true));
        error_log('getTargetDir: '. print_r($package->getTargetDir(),true));
        error_log('getInstallationSource: '. print_r($package->getInstallationSource(),true));
        error_log('getSourceType: '. print_r($package->getSourceType(),true));
        error_log('getName: '. print_r($package->getName(),true));
        error_log('getPrettyName: '. print_r($package->getPrettyName(),true));
        error_log('getNames: '. print_r($package->getNames(),true));
        $downloadPath = $this->getInstallPath($package); // eg. /path/to/vendor/xyz/abc
        error_log('installPath: '. print_r($downloadPath,true));
        $basePath = $this->getPackageBasePath($package);
        error_log('PackageBasePath: '. $basePath);
        error_log('vendorDir: '. $this->vendorDir);
        error_log('binDir:' . $this->binDir);
        
        return parent::install($repo, $package);
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
        error_log(__CLASS__.'::'.__FUNCTION__);

        $vars = get_object_vars($repo);
        error_log('Repo vars: '. print_r($vars,true));
        $methods = get_class_methods($repo);
        error_log('Repo methods: '.print_r($methods,true));


        $vars = get_object_vars($initial);
        error_log('Initial vars: '. print_r($vars,true));
        $methods = get_class_methods($initial);
        error_log('Initial methods: '.print_r($methods,true));



        $vars = get_object_vars($target);
        error_log('Target vars: '. print_r($vars,true));
        $methods = get_class_methods($target);
        error_log('Target methods: '.print_r($methods,true));



        return parent::update($repo, $initial, $target);
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
        error_log(__CLASS__.'::'.__FUNCTION__);

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
<?php
//namespace Composer;
namespace Repoman\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ModxInstallerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = new ModxInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }
}
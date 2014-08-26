<?php
/**
 * We need to use scripts to operate on the root package.  The Custom ModxInstaller will only
 * process any packages that are listed as dependencies, so if you are trying to install a
 * MODX Package that in turn has MODX Packages as dependencies, then the scripts here handle the
 * root package and the custom installer handles the packages that are listed in the required key.
 *
 * Composer methods: Array
 * (
 * [0] => setPackage
 * [1] => getPackage
 * [2] => setConfig
 * [3] => getConfig
 * [4] => setLocker
 * [5] => getLocker
 * [6] => setRepositoryManager
 * [7] => getRepositoryManager
 * [8] => setDownloadManager
 * [9] => getDownloadManager
 * [10] => setInstallationManager
 * [11] => getInstallationManager
 * [12] => setPluginManager
 * [13] => getPluginManager
 * [14] => setEventDispatcher
 * [15] => getEventDispatcher
 * [16] => setAutoloadGenerator
 * [17] => getAutoloadGenerator
 * )
 *
 * Event methods: Array
 * (
 * [0] => __construct
 * [1] => getOperation
 * [2] => getComposer
 * [3] => getIO
 * [4] => isDevMode
 * [5] => getName
 * [6] => getArguments
 * [7] => isPropagationStopped
 * [8] => stopPropagation
 * )
 *
 * Config methods: Array
 * (
 * [0] => __construct
 * [1] => setConfigSource
 * [2] => getConfigSource
 * [3] => setAuthConfigSource
 * [4] => getAuthConfigSource
 * [5] => merge
 * [6] => getRepositories
 * [7] => get
 * [8] => all
 * [9] => raw
 * [10] => has
 * )
 *
 * Package Methods:
 * Package methods: Array
(
[0] => setScripts
[1] => getScripts
[2] => setRepositories
[3] => getRepositories
[4] => setLicense
[5] => getLicense
[6] => setKeywords
[7] => getKeywords
[8] => setAuthors
[9] => getAuthors
[10] => setDescription
[11] => getDescription
[12] => setHomepage
[13] => getHomepage
[14] => setSupport
[15] => getSupport
[16] => __construct
[17] => isDev
[18] => setType
[19] => getType
[20] => getStability
[21] => setTargetDir
[22] => getTargetDir
[23] => setExtra
[24] => getExtra
[25] => setBinaries
[26] => getBinaries
[27] => setInstallationSource
[28] => getInstallationSource
[29] => setSourceType
[30] => getSourceType       git
[31] => setSourceUrl
[32] => getSourceUrl
[33] => setSourceReference
[34] => getSourceReference
[35] => setSourceMirrors
[36] => getSourceMirrors
[37] => getSourceUrls
[38] => setDistType
[39] => getDistType
[40] => setDistUrl
[41] => getDistUrl
[42] => setDistReference
[43] => getDistReference
[44] => setDistSha1Checksum
[45] => getDistSha1Checksum
[46] => setDistMirrors
[47] => getDistMirrors
[48] => getDistUrls
[49] => getVersion
[50] => getPrettyVersion
[51] => setReleaseDate
[52] => getReleaseDate
[53] => setRequires
[54] => getRequires
[55] => setConflicts
[56] => getConflicts
[57] => setProvides
[58] => getProvides
[59] => setReplaces
[60] => getReplaces
[61] => setDevRequires
[62] => getDevRequires
[63] => setSuggests
[64] => getSuggests
[65] => setAutoload
[66] => getAutoload
[67] => setDevAutoload
[68] => getDevAutoload
[69] => setIncludePaths
[70] => getIncludePaths
[71] => setNotificationUrl
[72] => getNotificationUrl
[73] => setArchiveExcludes
[74] => getArchiveExcludes
[75] => replaceVersion
[76] => getName             craftsmancoding/repoman
[77] => getPrettyName       craftsmancoding/repoman
[78] => getNames            Array([0] => craftsmancoding/repoman)
[79] => setId
[80] => getId
[81] => setRepository
[82] => getRepository
[83] => getTransportOptions
[84] => setTransportOptions
[85] => isPlatform
[86] => getUniqueName
[87] => equals
[88] => __toString
[89] => getPrettyString
[90] => __clone
)
 *
 *
 * Repo methods: Array
(
[0] => __construct
[1] => reload
[2] => write
[3] => getCanonicalPackages
[4] => findPackage
[5] => findPackages
[6] => search
[7] => hasPackage
[8] => addPackage
[9] => removePackage
[10] => getPackages
[11] => count
)
 * See https://getcomposer.org/doc/articles/scripts.md
 */
namespace Repoman\Composer;

use Composer\Script\PackageEvent;

class Script
{

    /**
     * @return bool
     */
    public static function preInstall(PackageEvent $event)
    {
        $package = $event->getComposer()->getPackage();
        if ($package->getType() !== 'modx-package') {
            return true;
        }
        // Does the package have any prompts?
        //$io = $event->getIO();
        //if ($io->askConfirmation("Are you sure you want to proceed? ", false)) {
        // ok, continue on to composer install
        return true;
        //}
    }

    public static function postInstall(PackageEvent $event)
    {
        $package = $event->getComposer()->getPackage();
        if ($package->getType() !== 'modx-package') {
            return false;
        }
    }

    public static function postUpdate(PackageEvent $event)
    {
        $package = $event->getComposer()->getPackage();
        if ($package->getType() !== 'modx-package') {
            return false;
        }
    }

    public static function preUninstall(PackageEvent $event)
    {
        $package = $event->getComposer()->getPackage();
        if ($package->getType() !== 'modx-package') {
            return false;
        }
    }
}
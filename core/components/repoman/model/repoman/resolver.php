<?php
/**
 * Our Swiss-Army Resolver.
 *
 * We can use the same resolver for all packages so long as they follow the correct 
 * Repoman syntax.
 *
 * 
 * @param $trasport - the transport class, containing the xpdo instance
 * @param $object - contains the Repoman config for the package being installed 
 *      (including all global config options)
 * @param $options - contains a few other options
 * 
 * @return boolean true on success
 */
 
/**
 * Our take-off from xPDO's fromArray() function, but one that can import whatever toArray() 
 * spits out.
 *
 * @param string $classname
 * @param array $objectdata
 * @param boolean $rawvalues set to true for modUser (e.g.) if you are passing the raw hash
 * @return object
 */
function fromDeepArray($classname, $objectdata, $rawvalues=false) {
    
    global $modx;
    
    $Object = $modx->newObject($classname);
    $Object->fromArray($objectdata,'',false,$rawvalues);
    $related = array_merge($modx->getAggregates($classname), $modx->getComposites($classname));
    foreach ($objectdata as $k => $v) {
        if (isset($related[$k])) {
            $alias = $k;
            $rel_data = $v;
            $def = $related[$alias];
            
            if (!is_array($def)) {
                $modx->log(modX::LOG_LEVEL_WARN, 'Data in '.$classname.'['.$alias.'] not an array.');
                continue;
            }
            if ($def['cardinality'] == 'one') {
                $one = fromDeepArray($def['class'],$rel_data,$rawvalues); // Avoid E_STRICT notices
                $Object->addOne($one);
            }
            else {
                if (!isset($rel_data[0])) {
                    $rel_data = array($rel_data);
                }
                $many = array();
                foreach ($rel_data as $r) {
                    $many[] = fromDeepArray($def['class'],$r,$rawvalues);   
                }
                $Object->addMany($many);
            }
            
        }
    }
    return $Object;
}

/** 
 * Given a filename, return the array of records stored in the file.
 *
 * @param string $fullpath
 * @param boolean $json if true, the file contains json data so it will be decoded
 * @return array
 */
function load_data($fullpath, $json=false) {
    global $modx;
    
    $modx->log(modX::LOG_LEVEL_DEBUG,'Processing object(s) in '.$fullpath);                                
        
    if ($json) {
        $data = json_decode(file_get_contents($fullpath),true);
    }
    else {
        $data = include $fullpath;
    }        
    
    if (!is_array($data)) {
        $modx->log(modX::LOG_LEVEL_ERROR,'Data in '.$fullpath.' not an array.');
        error_log('[MODX Package Install '.$object['namespace'].'] Data in '.$fullpath.' not an array.');
        return array();
    }
    if (!isset($data[0])) {
        $data = array($data);
    }
    return $data;
}


$modx =& $transport->xpdo;

switch ($options[xPDOTransport::PACKAGE_ACTION]) {

    case xPDOTransport::ACTION_INSTALL:
        $install_file = MODX_CORE_PATH.'components/'.$object['namespace'].'/'.$object['migrations_dir'].'/install.php';
        if (file_exists($install_file)) {
            include $install_file;
        }
        // Optionally Load Seed data
        if ($seed = $object['seed']) {
            if (!is_array($seed)) {
                $seed = explode(',',$seed);
            }
            $seeds_path = MODX_CORE_PATH.'components/'.$object['namespace'].'/'.$object['seeds_dir'];
            foreach ($seed as $s) {
                if (file_exists($seeds_path.'/'.$s) && is_dir($seeds_path.'/'.$s)) {
                    $modx->log(modX::LOG_LEVEL_INFO,'Walking seed directory '.$seeds_path.'/'.$s);
                    $files = glob($seeds_path.'/'.$s.'/*{.php,.json}',GLOB_BRACE);
                    foreach ($files as $f) {
                        preg_match('/^(\w+)(.?\w+)?\.(\w+)$/', basename($f), $matches);
                        if (!isset($matches[3])) {
                            $modx->log(modX::LOG_LEVEL_ERROR, 'Invalid filename '.$f);
                            error_log('[MODX Package Install '.$object['namespace'].'] Invalid filename '.$f);
                            continue;
                        }
                        $classname = $matches[1];
                        $ext = $matches[3];            
                        $is_json = (strtolower($ext) == 'php')? false : true;
                        if (!$fields = $modx->getFields($classname)) {
                            error_log('[MODX Package Install '.$object['namespace'].'] Unrecognized object classname: '.$classname);
                            $modx->log(modX::LOG_LEVEL_ERROR,'Unrecognized object classname: '.$classname);
                            continue;
                        } 

                        if (!$data = load_data($f, $is_json)) {
                            continue;
                        }
                        foreach ($data as $objectdata) {
                            if($Obj = fromDeepArray($classname, $objectdata)) {
                                if (!$Obj->save()) {
                                    error_log('[MODX Package Install '.$object['namespace'].'] Error saving object defined in '.$f);
                                    $modx->log(modX::LOG_LEVEL_ERROR,'Error saving object defined in '.$f);
                                }
                            }
                            else {
                                error_log('[MODX Package Install '.$object['namespace'].'] Error extracting object from '.$f);
                                $modx->log(modX::LOG_LEVEL_ERROR,'Error extracting object from '.$f);
                            }
                        }
                    }
                }
                else {
                    error_log('[MODX Package Install '.$object['namespace'].'] Seed directory does not exist: '.$seeds_path.'/'.$s);
                }
            }
        }

        break;
        
    case xPDOTransport::ACTION_UPGRADE:
        $dir = MODX_CORE_PATH.'components/'.$object['namespace'].'/'.$object['migrations_dir'].'/';
        if (file_exists($dir) && is_dir($dir)) {
            $files = glob($dir.'*.php');
            foreach($files as $f) {
                $file = strtolower(basename($f));
                if ($file == 'install.php' || $file == 'uninstall.php') {
                    continue;
                }
                include $f;
            }
        }
        break;
    // We never get here?
    case xPDOTransport::ACTION_UNINSTALL:
        $uninstall_file = MODX_CORE_PATH.'components/'.$object['namespace'].'/'.$object['migrations_dir'].'/uninstall.php';
        if (file_exists($uninstall_file)) {
            include $uninstall_file;
        }
        break;
}

return true;
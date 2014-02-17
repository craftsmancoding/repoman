<?php
class Review extends xPDOSimpleObject {

    /**
     * More name cleanup
     */
    public function fromArray($fldarray, $keyPrefix= '', $setPrimaryKeys= false, $rawValues= false, $adhocValues= false) {
        if (isset($fldarray['name'])) {
            $fldarray['name'] = strip_tags(trim(preg_replace('/\s+/', ' ', $fldarray['name'])));
        }
        return parent::fromArray($fldarray, $keyPrefix, $setPrimaryKeys, $rawValues, $adhocValues);
    }
    
    /**
     * Make sure we clean up names
     */
    public function set($k, $v= null, $vType= '') {
        if ($k == 'name') {
            $v = strip_tags(trim(preg_replace('/\s+/', ' ', $v)));
        }
        return parent::set($k,$v,$vType);
    }
}
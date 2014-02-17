<?php
class TaxonomyParents extends xPDOValidationRule {
    public function isValid($value, array $options = array()) {
        parent::isValid($value, $options);
        $result = false;
        $obj=& $this->validator->object;
        $xpdo=& $obj->xpdo;

        $validParentClasses = array('modDocument', 'modWebLink', 'modSymLink', 'modStaticResource');
        if ($obj->get('parent') === 0 || ($obj->Parent && in_array($obj->Parent->class_key, $validParentClasses))) {
           $result = true; 
        }
        if ($result === false) {
            $this->validator->addMessage($this->field, $this->name, $this->message);
        }
 
        return $result;
    }
}
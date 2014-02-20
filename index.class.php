<?php
require_once dirname(__FILE__) .'/controllers/abstract/repomanmanagercontroller.class.php';
/**
 * Per MODx parlance, this file must reside in the directory defined as the namespace's core path.
 *
 * Some "Department of Redundancy Department" here... the filename & classname here correspond to 
 * the name of the action.  In our case, we use the "index" as the action name, so 
 * filename = index.class.php
 * classname = IndexManagerController
 *
 * This "primary" controller for the defined action gets called when no
 * &action parameter is passed.  We use it to define the default controller
 * which will then handle the actual processing.
 *
 */
class IndexManagerController extends RepomanManagerController {


    /**
     * Defines the name or path to the default controller to load.
     * @return string
     */
    public static function getDefaultController() {
        return 'home';
    }
    
    public function process(array $scriptProperties = array()) {
        return 'XXxxxx';
    }
    
}
/*EOF*/
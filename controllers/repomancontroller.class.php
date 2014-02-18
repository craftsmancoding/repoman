<?php
require_once dirname(__FILE__) .'/moxycartcontroller.class.php';
/**
 * The Index Manager Controller is the default one that gets called when no
 * &action parameter is passed  We use it to define the default controller
 * which will then handle the actual processing.
 *
 * It is important to name this class "IndexManagerController" and making sure
 * it extends the abstract class we defined above 
 *
 * @package repoman
 */
class IndexManagerController extends RepomanManagerController {
    /**
     * Defines the name or path to the default controller to load.
     * @return string
     */
    public static function getDefaultController() {
        return 'home';
    }
}
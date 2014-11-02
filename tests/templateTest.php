<?php
class templateTest extends PHPUnit_Framework_TestCase {

    // Must be static because we set it up inside a static function
    public static $modx;
    public static $repoman;

    /**
     * Load up MODX for our tests.
     *
     */
    public static function setUpBeforeClass()
    {
        $docroot = dirname(dirname(__FILE__));
        while (!file_exists($docroot . '/config.core.php')) {
            if ($docroot == '/') {
                die('Failed to locate config.core.php');
            }
            $docroot = dirname($docroot);
        }
        if (!file_exists($docroot . '/config.core.php')) {
            die('Failed to locate config.core.php');
        }

        include_once $docroot . '/config.core.php';

        if (!defined('MODX_API_MODE')) {
            define('MODX_API_MODE', false);
        }
        require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
        include_once MODX_CORE_PATH . 'model/modx/modx.class.php';
        require_once dirname(dirname(__FILE__)) . '/model/repoman/repoman.class.php';

        self::$modx = new modX();
        self::$modx->initialize('mgr');

        self::$repoman = new Repoman(self::$modx);

    }

    public static function tearDownAfterClass()
    {
        if ($Template = self::$modx->getObject('modTemplate', array('templatename'=>'Test Template')))
        {
            $Template->remove();
        }
        if ($TV = self::$modx->getObject('modTemplateVar', array('name'=>'TestTV')))
        {
            $TV->remove();
        }
    }

    public function testTemplateAdd()
    {
        $Template = self::$modx->newObject('modTemplate');
        $Template->set('templatename', 'Test Template');
        $Template->set('content', '<html><body>Test....</body></html>');
        $result = $Template->save();
        $this->assertTrue($result);
        $templateid = $Template->get('id');

        $TV = self::$modx->newObject('modTemplateVar');
        $TV->set('name', 'TestTV');
        $TV->set('type', 'text');
        //$result = $TV->save();
        //$this->assertTrue($result);

        $TVT = self::$modx->newObject('modTemplateVarTemplate');
        $TVT->set('templateid',$templateid);
        $TVT->set('rank', 6);
        $TVT->addOne($TV);

        $tvts = array($TVT);
        $Template->addMany($tvts);
        $result = $Template->save();
        $this->assertTrue($result);


        $findTV = self::$modx->getObject('modTemplateVar', array('name'=>'TestTV'));
        $this->assertTrue(!empty($findTV));

    }
}
/*EOF*/
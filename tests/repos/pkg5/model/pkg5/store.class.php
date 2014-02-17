<?php
/*
Here a Store is a Product Container.

It has custom properties (stored as JSON). In other scenarios this data might be stored in 
related tables, but this data is mostly optional, overrideable, and presented as a convenience,
so the data structure is stored locally with the specific record.  

The properites include the following attributes (see getProperties() function below):

Array (
    'product_type'      => regular|subscription|download,
    'default_template'  => template id,
    'sort_order'        => name
    'qty_alert'         =>
    'track_inventory'   => 1|0 boolean
    'specs' => Array (
        1 => true
        2 => true
        ... etc...
    ),
    'variations' => Array(
        1 => true
        2 => true
        ... etc...    
    ),
    'taxonomies' => Array(
        1 => true
        2 => true
        ... etc...    
    )
 
)
*/
require_once MODX_CORE_PATH.'model/modx/modprocessor.class.php';
require_once MODX_CORE_PATH.'model/modx/processors/resource/create.class.php';
require_once MODX_CORE_PATH.'model/modx/processors/resource/update.class.php';

class Store extends modResource {

    public $showInContextMenu = true;

    /**
     *
     * @return string
     */ 
    function __construct(xPDO & $xpdo) {
        parent :: __construct($xpdo);
        $this->set('class_key','Store');
        $this->set('hide_children_in_tree',true);
    }

    /**
     *
     * @return string
     */     
    public static function getControllerPath(xPDO &$modx) {
        $x = $modx->getOption('moxycart.core_path',null,$modx->getOption('core_path')).'components/moxycart/controllers/store/';
        return $x;
    }
    
    /**
     *
     * @return array
     */     
    public function getContextMenuText() {
        $this->xpdo->lexicon->load('moxycart:default');
        return array(
            'text_create' => $this->xpdo->lexicon('container'),
            'text_create_here' => $this->xpdo->lexicon('container_create_here'),
        );
    }

    /**
     *
     * @return string
     */ 
    public function getResourceTypeName() {
        $this->xpdo->lexicon->load('moxycart:default');
        return $this->xpdo->lexicon('container');
    } 

    /**
     * Override the parent function to get our special properties.
     * @param string $namespace
     * @return array
     */
    public function getProperties($namespace='core') {
        $properties = parent::getProperties($namespace);
        //$this->xpdo->log(1, print_r($properties,true));
        if (!empty($properties)) {
            return $properties;
        }

        // Properties defaults
        $properties = array (
            'product_type'      => 'regular',
            'product_template'  => $this->xpdo->getOption(
                'moxycart.default_product_template','',
                $this->xpdo->getOption('default_template')
            ),
            'sort_order'        => 'name',
            'qty_alert'         => 0,
            'track_inventory'   => 0,
            'specs' => array (),
            'variations' =>   array(),
            'taxonomies' => array()
        );
        
        return $properties;
    }

    /**
     *
     */
    public function prepareTreeNode(array $node = array()) {
        $this->xpdo->lexicon->load('moxycart:default');
        $menu = array();
        $idNote = $this->xpdo->hasPermission('tree_show_resource_ids') ? ' <span dir="ltr">('.$this->id.')</span>' : '';

        $menu[] = array(
            'text' => '<b>'.$this->get('pagetitle').'</b>'.$idNote,
            'handler' => 'Ext.emptyFn',
        );
        $menu[] = '-'; // equiv. to <hr/>
        $menu[] = array(
            'text' => $this->xpdo->lexicon('product_create_here'),
            'handler' => "function(itm,e) { 
				var at = this.cm.activeNode.attributes;
		        var p = itm.usePk ? itm.usePk : at.pk;
	            Ext.getCmp('modx-resource-tree').loadAction(
	                'a='+MODx.action['moxycart:index']
	                + '&f=product_create'
	                + '&store_id='+p
	                + '&type=regular'
                );
        	}",
        );
        $menu[] = array(
            'text' => $this->xpdo->lexicon('download_create_here'),
            'handler' => "function(itm,e) { 
				var at = this.cm.activeNode.attributes;
		        var p = itm.usePk ? itm.usePk : at.pk;
	            Ext.getCmp('modx-resource-tree').loadAction(
	                'a='+MODx.action['moxycart:index']
	                + '&f=product_create'
	                + '&store_id='+p
	                + '&type=download'
                );
        	}",
        );
        $menu[] = array(
            'text' => $this->xpdo->lexicon('subscription_create_here'),
            'handler' => "function(itm,e) { 
				var at = this.cm.activeNode.attributes;
		        var p = itm.usePk ? itm.usePk : at.pk;
	            Ext.getCmp('modx-resource-tree').loadAction(
	                'a='+MODx.action['moxycart:index']
	                + '&f=product_create'
	                + '&store_id='+p
	                + '&type=subscription'
                );
        	}",
        );        
        $menu[] = '-'; // equiv. to <hr/>

        $menu[] = array(
            'text' => $this->xpdo->lexicon('manage_inventory'),
            'handler' => "function(itm,e) { 
				var at = this.cm.activeNode.attributes;
		        var p = itm.usePk ? itm.usePk : at.pk;
	            Ext.getCmp('modx-resource-tree').loadAction(
	                'a='+MODx.action['moxycart:index']
	                + '&store_id='+p
	                + '&f=manage_inventory'
                );
        	}",
        );
        
        $menu[] = '-'; // equiv. to <hr/>
        
        $menu[] = array(
            'text' => $this->xpdo->lexicon('container_duplicate'),
            'handler' => 'function(itm,e) { itm.classKey = "Term"; this.duplicateResource(itm,e); }',
        );
        
        if ($this->get('published')) {
            $menu[] = array(
                'text' => $this->xpdo->lexicon('container_unpublish'),
                'handler' => 'this.unpublishDocument',
            );
        } else {
            $menu[] = array(
                'text' => $this->xpdo->lexicon('container_publish'),
                'handler' => 'this.publishDocument',
            );
        }
        if ($this->get('deleted')) {
            $menu[] = array(
                'text' => $this->xpdo->lexicon('container_undelete'),
                'handler' => 'this.undeleteDocument',
            );
        } else {
            $menu[] = array(
                'text' => $this->xpdo->lexicon('container_delete'),
                'handler' => 'this.deleteDocument',
            );
        }
        $menu[] = '-';
        $menu[] = array(
            'text' => $this->xpdo->lexicon('container_view'),
            'handler' => 'this.preview',
        );

        $node['menu'] = array('items' => $menu);
        $node['hasChildren'] = false;
        return $node;
    }
}

//------------------------------------------------------------------------------
//! CreateProcessor
//------------------------------------------------------------------------------
class StoreCreateProcessor extends modResourceCreateProcessor {
    /** 
     * @var Store $object 
     */
    public $object;
    
    /**
     * Override modResourceCreateProcessor::afterSave to provide custom functionality, saving the container settings to a
     * custom field in the manager
     * {@inheritDoc}
     * @return boolean
     */
    public function afterSave() {
        //$this->modx->log(1, __FILE__ . print_r($this->object->toArray(), true));
        $this->object->set('class_key','Store');
        $this->object->set('cacheable',true);
        $this->object->set('isfolder',false);
        
        
        
        return parent::afterSave();
    }

    /**
     * Override modResourceUpdateProcessor::beforeSave to provide custom functionality, saving settings for the container
     * to a custom field in the DB
     * {@inheritDoc}
     * @return boolean
     */
    public function beforeSave() {
        $raw = $this->getProperties(); // <-- this will have raw values
        $properties = $this->object->getProperties('moxycart'); //<-- we need to update these values
        $this->object->set('class_key','Store');
        //$this->modx->log(1,'beforeSave raw values: '.print_r($raw,true));
        //$this->modx->log(1,'beforeSave raw POST values: '.print_r($_POST,true));
        
        $properties['product_type'] = $this->modx->getOption('product_type',$raw);
        $properties['product_template'] = $this->modx->getOption('product_template',$raw);
        $properties['track_inventory'] = ($this->modx->getOption('track_inventory',$raw) == 'Yes')? 1:0;
        $properties['sort_order'] = $this->modx->getOption('sort_order',$raw);
        $properties['qty_alert'] = $this->modx->getOption('qty_alert',$raw);

        $this->object->setProperties($properties,'moxycart');
        return parent::beforeSave();

    }

}

class StoreUpdateProcessor extends modResourceUpdateProcessor {
    /** 
     * @var Store $object 
     */
    public $object;
    
    /**
     * Override modResourceCreateProcessor::afterSave to provide custom functionality, saving the container settings to a
     * custom field in the manager
     * {@inheritDoc}
     * @return boolean
     */
    public function afterSave() {
        //$this->modx->log(1, __FILE__ . print_r($this->object->toArray(), true));
        $this->object->set('class_key','Store');
        $this->object->set('cacheable',true);
        $this->object->set('isfolder',true); // ensure we get a clean uri
        return parent::afterSave();
    }

    /**
     * Override modResourceUpdateProcessor::beforeSave to provide custom functionality, saving settings for the container
     * to a custom field in the DB.
     *
     * The Post data comes thru flattened (boo)
     *
            [specs_4] => on
            [specs_12] => on
            [taxonomies_3] => on
            [taxonomies_4] => on
            [variations_1] => Option Only
            [variations_2] => Variant
            [variations_3]
     * On the flip side, it should be available in JS via this path: MODx.activePage.config.record.properties.moxycart
     *
     * {@inheritDoc}
     * @return boolean
     */
    public function beforeSave() {
        $raw = $this->getProperties(); // <-- this will have raw values
        $properties = $this->object->getProperties('moxycart'); //<-- we need to update these values
        $this->object->set('class_key','Store');
        //$this->modx->log(1,'beforeSave raw values: '.print_r($raw,true));
        //$this->modx->log(1,'existing values: '.print_r($properties,true));
        //$this->modx->log(1,'beforeSave raw POST values: '.print_r($_POST,true));
        
        $properties['product_type'] = $this->modx->getOption('product_type',$raw);
        $properties['product_template'] = $this->modx->getOption('product_template',$raw);
        $properties['track_inventory'] = ($this->modx->getOption('track_inventory',$raw) == 'Yes')? 1:0;
        $properties['sort_order'] = $this->modx->getOption('sort_order',$raw);
        $properties['qty_alert'] = $this->modx->getOption('qty_alert',$raw);

        // Fresh start...
        $properties['specs'] = array();
        $properties['taxonomies'] = array();
        $properties['variations'] = array();
        foreach ($raw as $k => $v) {
            $len = strlen($k);
            if ($this->starts_with($k,'specs')) {
                $properties['specs'][substr($k,(6 - $len))] = true;
            }
            if ($this->starts_with($k,'taxonomies')) {
                $properties['taxonomies'][substr($k,(11 - $len))] = true;
            }
            if ($this->starts_with($k,'variations')) {
                $properties['variations'][substr($k,(11 - $len))] = $v;
            } 
        }

        $this->object->setProperties($properties,'moxycart');
        return parent::beforeSave();
    }

    // http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
    private function starts_with($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }

}
<?php
/**
 * moxycart class file for moxycart extra
 *
 * This file retrieves data for various Moxycart functions, e.g. product lists,
 * related products.  It is primarily accessed by the assets/components/moxycart/connector.php
 * file, but really, any 3rd party could use it to retrieve data as well.
 * Copyright 2013 by Everett Griffiths everett@craftsmancoding.com
 * Created on 07-05-2013
 *
 * moxycart is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * moxycart is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * moxycart; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package moxycart
 */


class Moxycart {
    
    public $modx;

    private $core_path;
    private $default_limit;


    // Classwide query params
    private $limit;
    private $start;
    private $sort;
    private $dir;

    

    public function __construct(&$modx) {

        $this->modx =& $modx;
        $this->core_path = $this->modx->getOption('moxycart.core_path', null, MODX_CORE_PATH);
        $this->modx->addPackage('moxycart',$this->core_path.'components/moxycart/model/','moxy_');
        // relative to the MODX_ASSETS_PATH or MODX_ASSETS_URL
        $this->default_limit = $this->modx->getOption('default_per_page'); // TODO: read from a MC setting?
    }
    
    /**
     * Catch all for bad function requests.
     *
     */
    public function __call($name,$args) {
        $this->modx->log(modX::LOG_LEVEL_ERROR,'[moxycart] Invalid function name '.__FUNCTION__);
        return $this->help($args);
    }

    //------------------------------------------------------------------------------
    //! Private
    //------------------------------------------------------------------------------
    /**
     * Generate a panel (e.g. containing instructions) for pages in the manager.
     * @param array: content1, content2, content3, help_link
     * @return string
     */
/*
    public function _get_panel($props) {
        $tpl = file_get_contents($this->core_path.'components/moxycart/layouts/panel.html');
        $uniqid = uniqid();
        $chunk = $this->modx->newObject('modChunk', array('name' => "{tmp}-{$uniqid}"));
        $chunk->setCacheable(false);
        $props['assets_url'] = $this->assets_url;    
        return $chunk->process($props, $tpl);    
    }
*/
    /**
     * Convert a variation matrix code (json encoded)
     *
     * @param string $json formatted text
     * @return string
     */
    public function _get_variant_info($json) {
        if (empty($json)) {
            return '';
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return $data;
        }
        $out = array();
        foreach ($data as $vtype_id => $vterm_id) {
            $variant = '';
            $Type = $this->modx->getObject('VariationType',$vtype_id);
            $Term = $this->modx->getObject('VariationTerm',$vterm_id);
            if ($Type && $Term) {
                $out[] = $Type->get('name') .': '.$Term->get('name');
            }
        }
        
        return implode(',', $out);
    }
    

    /**
     * Filters out limit, start, sort, dir from the incoming $args so they
     * can be passed as a criteria array to getCollection
     *
     * @param array $args
     * @param string $default_sort column to sort by
     * @param string $default_dir sorting direction for the sort
     * @return array
     */
    public function _get_criteria($args, $default_sort, $default_dir) {
        $limit = (int) $this->modx->getOption('limit', $args,$this->default_limit);
        $start = (int) $this->modx->getOption('start', $args,0);
        $sort = $this->modx->getOption('sort', $args, $default_sort);
        $dir = $this->modx->getOption('dir', $args, $default_dir);
        
        unset($args['limit']);
        unset($args['start']);
        unset($args['sort']);
        unset($args['dir']);
        
        return $args;
    }
    
    /**
     * Generates HTML for select <options> (NOT the wrapping <select>)
     * @param array $data recordset
     * @param string $selected which value is selected
     * @param string $column which column holds the id?
     * @param string $label which column holds the label?
     * @return string 
     */
    public function _get_options($data = array(),$selected=null, $column_id='id',$label='name') {
        $output = '';
        foreach ($data['results'] as $row) {
            $selected_str = '';
            if ($row[$column_id] == $selected) {
                $selected_str = ' selected="selected"';
            } 
            $output .= sprintf('<option value="%s"%s>%s</option>', $row[$column_id], $selected_str, $row[$label]);
        } 
        return $output;
    }
   
    //------------------------------------------------------------------------------
    //! AJAX Store stuff
    //------------------------------------------------------------------------------
    /**
     * FoxyCart Categories... TODO: query the API!
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_categories($args,$raw=false) {

        $total_pages = 1;
        
        // Init our array
        $data = array(
            'results'=>array(array('id'=>'default','name'=>'Default')),
            'total' => $total_pages,
        );

        if ($raw) {
            return $data;
        }
        return json_encode($data);    
    }

    /**
     *
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_currencies($args,$raw=false) {

        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'currency_id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('Currency');
        if (isset($args['is_active'])) {
            $criteria->where(array('is_active' => (int) $this->modx->getOption('is_active',$args)));
        }
        $total_pages = $this->modx->getCount('Currency',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Currency',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $data['results'][] = $p->toArray();
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);

        
    }
    

    /**
     * Get a list of products.
     *
     * @param array arguents including limit, start, sort, dir
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */

    public function json_products($args=array(),$raw=false) {
//        print '<pre>'.print_r($args,true).'</pre>'; exit;
/*
        if (!$this->modx->hasPermission($this->modx->getOption(__FUNCTION__, $this->perms, $this->default_perm))) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,'[moxycart::'.__FUNCTION__.'] User does not have sufficient privileges.');
            return false;
        }
*/
        //$this->modx->log(1, '[json_products] ' .print_r($args,true));
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'seq');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        unset($args['limit']);
        unset($args['start']);
        unset($args['sort']);
        unset($args['dir']);
        unset($args['_dc']);
        unset($args['HTTP_MODAUTH']);
        //$parent_id = (int) $this->modx->getOption('parent_id',$args);
        //$store_id = (int) $this->modx->getOption('store_id',$args);
        
        $criteria = $this->modx->newQuery('Product');
        if ($args) {
            $criteria->where($args);
       }

/*
        if ($parent_id) {
            $criteria->where(array('parent_id'=>$parent_id));
        }
        if ($store_id) {
            $criteria->where(array('store_id'=>$store_id));
        } 
*/
        $total_pages = $this->modx->getCount('Product',$criteria);

        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Product',$criteria);
//        $pages = $this->modx->getCollectionGraph('Product','{"Specs":{"Spec":{}}}',$criteria);
//        $criteria->bindGraph('{"Specs":{"Spec":{}}}');
//        print 'xxxx'.$criteria->toSQL(); //<-- useful for debugging
//        exit;
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );

       // set date and time (unix)
        $now = strtotime(date('Y-m-d H:i:s'));
//print_r($pages); exit;        
        foreach ($pages as $p) {
            //print_r($p->toArray()); exit;
            $calculated_price = $p->get('price');
            // if on sale use price sale
            if(strtotime($p->get('sale_start')) <= $now && strtotime($p->get('sale_end')) >= $now) {
                $calculated_price = $p->get('price_sale');
            }

            $row = array(
                'product_id' => $p->get('product_id'),
                'alias' => $p->get('alias'),
                'content' => stripslashes($p->get('content')),
                'name' => $p->get('name'),
                'sku' => $p->get('sku'),
                'type' => $p->get('type'),
                'track_inventory' => $p->get('track_inventory'),
                'qty_inventory' => $p->get('qty_inventory'),
                'qty_alert' => $p->get('qty_alert'), 
                'price' => $p->get('price'),
                'category' => $p->get('category'),
                'uri' => $p->get('uri'),
                'is_active' => $p->get('is_active'), 
                'seq' => $p->get('seq'), 
                'calculated_price'=> $calculated_price,
            );
            foreach ($p->Specs as $s) {
                $row[ $s->Spec->get('identifier') ] = $s->get('value');
            }
            $row['variant'] = $this->_get_variant_info($p->get('variant_matrix'));
            $data['results'][] = $row;
        }


        if ($raw) {
            return $data;
        }
        return json_encode($data);    
    }

    /**
     * Get a list of products related to the given produc
     *
     * @param array arguents including limit, start, sort, dir
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */

    public function json_product_relations($args=array(),$raw=false) {
        
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'ProductRelation.seq');
        $dir = $this->modx->getOption('dir',$args,'ASC');

        unset($args['limit']);
        unset($args['start']);
        unset($args['sort']);
        unset($args['dir']);
        unset($args['_dc']);
        unset($args['HTTP_MODAUTH']);
        //$parent_id = (int) $this->modx->getOption('parent_id',$args);
        //$store_id = (int) $this->modx->getOption('store_id',$args);
        
        $criteria = $this->modx->newQuery('ProductRelation');
        if ($args) {
            $criteria->where($args);
        }

        $total_pages = $this->modx->getCount('ProductRelation',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollectionGraph('ProductRelation','{"Relation":{}}',$criteria);
//        print $criteria->toSQL(); //<-- useful for debugging
//        exit;
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
                
        foreach ($pages as $p) {
            $row = $p->toArray();
//            $this->modx->log(1, print_r($row,true)); exit;
            $row['sku'] = $p->Relation->get('sku');
            $row['name'] = $p->Relation->get('name');
            $row['category'] = $p->Relation->get('category');
            $row['price'] = $p->Relation->get('price');
            $row['related.is_selected'] = ($row['type'] == 'related') ? ' selected="selected"' : '';
            $row['bundle-1:order.is_selected'] = ($row['type'] == 'bundle-1:order') ? ' selected="selected"' : '';
            $row['bundle-1:1.is_selected'] = ($row['type'] == 'bundle-1:1') ? ' selected="selected"' : '';
            $data['results'][] = $row;
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);    
    }

    /**
     * product_id
     
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */

    public function json_product_specs($args=array(),$raw=false) {
        
        $product_id = (int) $this->modx->getOption('product_id',$args);
        $spec_id = (int) $this->modx->getOption('spec_id',$args);
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'id');
        $dir = $this->modx->getOption('dir',$args,'ASC');

        $product_id = (int) $this->modx->getOption('product_id',$args);
        $spec_id = (int) $this->modx->getOption('spec_id',$args);
                
        $criteria = $this->modx->newQuery('ProductSpec');
        
        if ($product_id) {
            $criteria->where(array('product_id'=>$product_id));
        }
        if ($spec_id) {
            $criteria->where(array('spec_id'=>$spec_id));
        }
                
        $total_pages = $this->modx->getCount('ProductSpec',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);

        $pages = $this->modx->getCollectionGraph('ProductSpec','{"Spec":{},"Product":{}}',$criteria);

        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        if($pages) {
             foreach ($pages as $p) {
               $data['results'][] = array(
                'product_id' => $p->get('product_id'), 
                'spec_id' => $p->get('spec_id'), 
                'identifier' => $p->Spec->get('identifier'),
                'product' => $p->Product->get('name'),
                'name' => $p->Spec->get('name'),
                'value' => $p->get('value'),
                'description' => $p->Spec->get('description'),
                'type' => $p->Spec->get('type')
               );
             }
         }

        if($raw) {
            return $data;
        }
        return json_encode($data);
       

    }

    /**
     * Get the taxonomies associated with a given product.
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_product_taxonomies($args=array(),$raw=false) {
        $product_id = (int) $this->modx->getOption('product_id',$args);
        
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'ProductTaxonomy.id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('ProductTaxonomy');
        
        if ($product_id) {
            $criteria->where(array('ProductTaxonomy.product_id'=>$product_id));
        }
                
        $total_pages = $this->modx->getCount('ProductTaxonomy',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollectionGraph('ProductTaxonomy','{"Product":{},"Taxonomy":{}}',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $data['results'][] = array(
                'id' => $p->get('id'),
                'product_id' => $p->get('product_id'),
                'taxonomy_id' => $p->get('taxonomy_id'),
                'name' => $p->Taxonomy->get('pagetitle'),
                'product' => $p->Product->get('name'),
                'properties' => $p->Taxonomy->get('properties')
            );
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);

    }

    /**
     * Shows all the terms for the given product, filtered by product_id.
        * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)     
     */
    public function json_product_terms($args=array(),$raw=false) {
         
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'Term.id');
        $dir = $this->modx->getOption('dir',$args,'ASC');

        $product_id = (int) $this->modx->getOption('product_id',$args);

        $criteria = $this->modx->newQuery('ProductTerm');
        
        if ($product_id) {
            $criteria->where(array('ProductTerm.product_id'=>$product_id));
        }
        
        $total_pages = $this->modx->getCount('ProductTerm',$criteria);

        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);

        $pages = $this->modx->getCollectionGraph('ProductTerm','{"Product":{},"Term":{}}',$criteria);

//        return $criteria->toSQL(); // <-- useful for debugging

        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
     
        foreach ($pages as $p) {
            $data['results'][] = array(
                'id' => $p->get('id'),
                'product_id' => $p->get('product_id'),
                'term_id' => $p->get('term_id'),
                'term' => $p->Term->get('pagetitle'),
                'properties' => $p->Term->get('properties')
            );
        }

        if ($raw) {
            return $data;
        }

        return json_encode($data);

    }

    /**
     * product_id ?
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_images($args=array(),$raw=false) {
      
        $product_id = (int) $this->modx->getOption('product_id',$args);
        $seq = (int) $this->modx->getOption('seq',$args);
        $nav_dir = $this->modx->getOption('nav_dir',$args);
        $is_active = (int) $this->modx->getOption('is_active',$args);
        
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'seq');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        

        $criteria = $this->modx->newQuery('Image');


        if($nav_dir == 'next') {
            if(isset($seq) && is_int($seq)) {
                $criteria->where(array('seq:>'=>$seq));
            } 
        }

         if($nav_dir == 'prev') {
            if(isset($seq) && is_int($seq)) {
                 $criteria->where(array('seq:<'=>$seq));
            } 
        }

       
        if ($product_id) {
            $criteria->where(array('product_id'=>$product_id));
        }

         if ($is_active) {
            $criteria->where(array('is_active'=>$is_active));
        }
                
        $total_pages = $this->modx->getCount('Image',$criteria);
   
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Image',$criteria);
     
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $data['results'][] = $p->toArray();
        }

        if ($raw) {
            return $data;
        }

        return json_encode($data);
    }

    /**
     * product_id ?
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_reviews($args=array(),$raw=false) {
        $product_id = (int) $this->modx->getOption('product_id',$args);
        $state = $this->modx->getOption('state',$args);
  
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'timestamp_created');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('Review');
        
        if ($state) {
            $criteria->where(array('state'=>$state));
        }

        if ($product_id) {
            $criteria->where(array('product_id'=>$product_id));
        }
                
        $total_pages = $this->modx->getCount('Review',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Review',$criteria);

        //return $criteria->toSQL(); 
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $data['results'][] = $p->toArray();
        }
        if ($raw) {
            return $data;
        }

        return json_encode($data);

    }


    /**
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_specs($args=array(),$raw=false) {

        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'spec_id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('Spec');
        //$criteria->where();
        $total_pages = $this->modx->getCount('Spec',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Spec',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $data['results'][] = $p->toArray();
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);

        
    }

    /**
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_stores($args=array(),$raw=false) {
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'menuindex');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('Store');
        $criteria->where(array('class_key'=>'Store'));
        $total_pages = $this->modx->getCount('Store',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Store',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $data['results'][] = array(
                'id' => $p->get('id'),
                'name' => $p->get('pagetitle')
            );
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);

        
    }

    /**
     * PARAMS:
     * store_id (id)
     *
     * Gathers all available specs from the db, then gathers page's list of checked specs
     * and returns a list of all specs and a 1|0 value for each. 
     * Returns a recordset.
     *
     * In the store
     * 
     * Return: as json_specs, but with a 0|1 value set for 'is_checked' added
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_store_specs($args=array(),$raw=false) {
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'spec_id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        $store_id = (int) $this->modx->getOption('store_id',$args);

        $Store = $this->modx->getObject('Store', $store_id);
        if (!$Store) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Store not found: '.$store_id);
            return 'Invalid store. Include valid store_id'; 
        }
        $properties = $Store->get('properties');
        $specs = $this->modx->getOption('specs',$properties);
        
        $criteria = $this->modx->newQuery('Spec');
        //$criteria->where();
        $total_pages = $this->modx->getCount('Spec',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Spec',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $vals = $p->toArray();
            if (isset($specs[$p->get('spec_id')])) {
                $vals['is_checked'] = 1; 
            }
            else {
                $vals['is_checked'] = 0; 
            }
            
            $data['results'][] = $vals;
        }


        if ($raw) {
            return $data;
        }
        return json_encode($data);

    }


    /**
     * store_id (id)
     *
     * Gathers all available taxonomies from db, then gathers page's list of checked taxonomies
     * and returns a list of all taxonomies and a 1|0 value for each. is_checked
     *
     * Return: taxonomy.pagetitle, taxonomy.id, value (1|0)
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)     
     */
    public function json_store_taxonomies($args=array(),$raw=false) {
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        $store_id = (int) $this->modx->getOption('store_id',$args);

        $Store = $this->modx->getObject('Store', $store_id);
        if (!$Store) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Store not found: '.$store_id);
            return 'Invalid store. Include valid store_id'; 
        }
        $properties = $Store->get('properties');
        $taxonomies = $this->modx->getOption('taxonomies',$properties);
                
        $criteria = $this->modx->newQuery('Taxonomy');
        $criteria->where(array('class_key'=>'Taxonomy'));
        $total_pages = $this->modx->getCount('Taxonomy',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Taxonomy',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $is_checked = 0;
            if (isset($taxonomies[$p->get('id')])) {
                $is_checked = 1;    
            }
            $data['results'][] = array(
                'id' => $p->get('id'),
                'pagetitle' => $p->get('pagetitle'),
                'is_checked' => $is_checked
            );
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);

    }


    /**
     * store_id (id)
     *
     * Gathers all available variations from db, then gathers page's list of checked variations
     * and returns a list of all taxonomies and a 1|0 value for each.
     *
     * Return: variation_type.name, taxonomy.id, value (1|0)
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)     
     */
    public function json_store_variation_types($args=array(),$raw=false) {
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'vtype_id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        $store_id = (int) $this->modx->getOption('store_id',$args);

        $Store = $this->modx->getObject('Store', $store_id);
        if (!$Store) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Store not found: '.$store_id);
            return 'Invalid store. Include valid store_id'; 
        }
        $properties = $Store->get('properties');
        $specs = $this->modx->getOption('variation_types',$properties);
        
        $criteria = $this->modx->newQuery('VariationType');
        //$criteria->where();
        $total_pages = $this->modx->getCount('VariationType',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('VariationType',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $vals = $p->toArray();
            if (isset($specs[$p->get('vtype_id')])) {
                $vals['is_checked'] = 1; 
            }
            else {
                $vals['is_checked'] = 0; 
            }
            
            $data['results'][] = $vals;
        }


        if ($raw) {
            return $data;
        }
        return json_encode($data);

    }


    /**
     *
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */         
    public function json_taxonomies($args=array(),$raw=false) {
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('Taxonomy');
        $criteria->where(array('class_key'=>'Taxonomy'));
        $total_pages = $this->modx->getCount('Taxonomy',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Taxonomy',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $data['results'][] = array(
                'id' => $p->get('id'),
                'pagetitle' => $p->get('pagetitle')
            );
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);

        
    }

    /**
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */     
    public function json_terms($args=array(),$raw=false) {

        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'menuindex');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('Term');
        $criteria->where(array('class_key'=>'Term'));
        $total_pages = $this->modx->getCount('Term',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('Term',$criteria);
        // return $criteria->toSQL(); <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $data['results'][] = array(
                'id' => $p->get('id'),
                'name' => $p->get('pagetitle')
            );
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);

        
    }
    
    
    /**
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_templates($args=array(),$raw=false) {

        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('modTemplate');
        //$criteria->where();
        $total_pages = $this->modx->getCount('modTemplate',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollection('modTemplate',$criteria);
        
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            // $data['results'][] = $p->toArray(); // <-- too much info!
            $data['results'][] = array(
                'id' => $p->get('id'),
                'name' => $p->get('templatename')
            );
        }
        
        if ($raw) {
            return $data;
        }
        return json_encode($data);

    
    }

    /**
     * Putting this in like the rest of the data sources, even though this is hard-coded
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_types($args=array(),$raw=false) {

        // Init our array
        $data = array(
            'results'=>array(
                array('name'=>'Regular','id'=>'regular'),
                array('name'=>'Subscription','id'=>'subscription'),
                array('name'=>'Download','id'=>'download')
            ),
            'total' => 1,
        );

        if ($raw) {
            return $data;
        }
        return json_encode($data);

        
    }

    /**
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */    
    public function json_variation_types($args=array(),$raw=false) {
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = 'VariationType.'.$this->modx->getOption('sort',$args,'vtype_id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $criteria = $this->modx->newQuery('VariationType');
        //$criteria->where();
        $total_pages = $this->modx->getCount('VariationType',$criteria);
      
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollectionGraph('VariationType','{"Terms":{}}',$criteria);

//        return $criteria->toSQL();// <-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            
            $row = $p->toArray();
            $row['terms'] = '';
            if ($p->Terms) {
                $terms = array();
                $i = 1;
                foreach ($p->Terms as $t) {
                    $terms[] = $t->get('name');
                    $i++;
                    // Max number of terms to list
                    if ($i > 3) {
                        break;
                    }
                }
                $row['terms'] = implode(', ', $terms) .'...';
            }
            $data['results'][] = $row;
        }
        
        if ($raw) {
            return $data;
        }
        return json_encode($data);

        
    }
    
    /**
     * 
     *
     * @param boolean $raw if true, results are returned as PHP array default: false
     * @return mixed A JSON array (string), a PHP array (array), or false on fail (false)
     */
    public function json_variation_terms($args=array(),$raw=false) {
        $limit = (int) $this->modx->getOption('limit',$args,$this->default_limit);
        $start = (int) $this->modx->getOption('start',$args,0);
        $sort = $this->modx->getOption('sort',$args,'vterm_id');
        $dir = $this->modx->getOption('dir',$args,'ASC');
        
        $vtype_id = (int) $this->modx->getOption('vtype_id',$args);
        
        $criteria = $this->modx->newQuery('VariationTerm');
        if ($vtype_id) {
            $criteria->where(array('vtype_id'=>$vtype_id));
        }

        $total_pages = $this->modx->getCount('VariationTerm',$criteria);
        
        $criteria->limit($limit, $start); 
        $criteria->sortby($sort,$dir);
        $pages = $this->modx->getCollectionGraph('VariationTerm','{"Type":{}}',$criteria);
        //return $criteria->toSQL(); //<-- useful for debugging
        // Init our array
        $data = array(
            'results'=>array(),
            'total' => $total_pages,
        );
        foreach ($pages as $p) {
            $val = $p->toArray();
            $val['variation_type'] = $p->Type->get('name');
            $data['results'][] = $val;
        }

        if ($raw) {
            return $data;
        }
        return json_encode($data);
    
    }

}
<?php
return array( 
    array(
        'name' => 'Product Quantity Alert',
        'description' => 'List all Product which set to track their inventory.',
        'type' => 'snippet',
        'content' => 'ProductQtyAlert',
        'size'  => 'half',
        'namespace' => 'moxycart',
        'lexicon' =>  'core:dashboards',
        'Placements' => array(
            'dashboard' => 1,
            'widget' => 6,
            'rank' => 5,
        )
     ),
    array(
        'name' => 'Product Sales',
        'description' => 'Show All Time Sales. This was entended to be used only as Dashboard widget Content.',
        'type' => 'snippet',
        'content' => 'ProductSales',
        'size'  => 'full',
        'namespace' => 'moxycart',
        'lexicon' =>  'core:dashboards',
        'Placements' => array(
            'dashboard' => 1,
            'widget' => 7,
            'rank' => 6,
        )
     )

);
/*EOF*/
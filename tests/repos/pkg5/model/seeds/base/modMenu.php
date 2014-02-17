<?php
/**
 * The menu 'text' and 'description' fields should be Lexicon keys.
 *
 *
 */
return array(
    array(
        'text' => 'moxycart',
        'description' => 'moxycart_desc',
        'parent' => 'components',
        'action' => 0,
        'icon' => '',
        'menuindex' => 0,
        'params' => '',
        'handler' => '',
        'permissions' => '',
        'Action' => array (
            //'action' => 0, // Omit this so that it will inherit from the related object
            // This will create an ERROR: Attempt to set NOT NULL field action to NULL
            // But it's safe to ignore in this case.
            'namespace' => 'moxycart',
            'controller' => 'index',
            'haslayout' => 1,
            'lang_topics' => 'moxycart:default',
            'assets' => '',
            'help_url' =>  '',
        ),
    ),
);
/*EOF*/
<?php
/**
 * @name MyPlugin
 * @description 
 * @PluginEvents OnLoadWebDocument
 */

/*EOF*/

if ($modx->event->name == 'OnLoadWebDocument') {
    $content = 'You ever feel as if your mind had started to erode?';
    $modx->resource->Template->set('content',$content);
}
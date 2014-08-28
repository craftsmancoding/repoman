<?php
/**
 *
 *
 */
namespace Repoman\Parser;

use Repoman\Parser;

class modPlugin extends Parser
{

    public $dir_key = 'plugins_path';
    public $ext = '*.php';
    public $objecttype = 'modPlugin';

    /**
     * Add any extended docblock attributes for this object type
     *
     * @param object $Obj
     * @return string
     */
    public function extendDocblock(&$Obj)
    {
        $out = '';
        if (isset($Obj->PluginEvents) && is_array($Obj->PluginEvents)) {
            $out = ' * @PluginEvents ';
            foreach ($Obj->PluginEvents as $e) {
                $out .= $e->get('event') . ',';
            }
        }
        return rtrim($out, ',') . "\n";
    }


    /**
     * Attach and Remove plugin's events
     *
     */
    public function relate($attributes, &$Obj)
    {
        $events = array();
        $pluginid = $Obj->get('id');
        if (isset($attributes['PluginEvents'])) {
            $event_names = explode(',', $attributes['PluginEvents']);
            // Remove unassociated events
            $existingEvents = $this->modx->getCollection('modPluginEvent', array('pluginid' => $pluginid, 'event:NOT IN' => $event_names));
            foreach ($existingEvents as $ee) {
                $ee->remove();
            }

            foreach ($event_names as $e) {
                if (!$Event = $this->modx->getObject('modPluginEvent', array('pluginid' => $pluginid, 'event' => trim($e)))) {
                    $Event = $this->modx->newObject('modPluginEvent');
                    $Event->set('event', trim($e));
                }

                Repoman::$queue[$this->objecttype][] = 'modPluginEvent: ' . $Event->get('event');
                $events[] = $Event;
            }
        } else {
            // Remove all Events
            $existingEvents = $this->modx->getCollection('modPluginEvent', array('pluginid' => $pluginid));
            foreach ($existingEvents as $ee) {
                $ee->remove();
            }
        }
        $Obj->addMany($events);
    }

}
/*EOF*/
<?php
/**
 *
 *
 */
namespace Repoman\Parser;

use Repoman\Parser;

class modTemplate extends Parser
{

    public $dir_key = 'templates_path';
    public $ext = '*.*';
    public $write_ext = '.html';
    public $objecttype = 'modTemplate';
    public $objectname = 'templatename';

    public $dox_start = '<!--';
    public $dox_end = '-->';
    public $dox_pad = ''; // left of line before the @attribute

    /**
     * Add any extended docblock attributes for this object type
     *
     * @param object $Obj
     * @return string
     */
    public function extendDocblock(&$Obj)
    {
        $out = '';
        // 2 tiers here to get to the TVs.
        if (isset($Obj->TemplateVarTemplates) && is_array($Obj->TemplateVarTemplates)) {
            $out = '@TVs ';
            foreach ($Obj->TemplateVarTemplates as $tvt) {
                $out .= $tvt->TemplateVar->get('name') . ',';
            }
        }
        return rtrim($out, ',') . "\n";
    }

    /**
     * Attach and Remove Template's TVs
     *
     */
    public function relate($attributes, &$Obj)
    {
        $tvs = array();
        $templateid = $Obj->get('id');
        if (isset($attributes['TVs'])) {
            $tv_names = explode(',', $attributes['TVs']);
            // Remove unassociated TVs
            /*
                        $TVTs = $this->modx->getCollection('modTemplateVarTemplate', array('templateid'=>$templateid, 'event:NOT IN'=> $tv_names));
                        foreach ($TVTs as $t) {
                            $t->remove();
                        }
            */

            foreach ($tv_names as $t) {
                $t = trim($t);
                if (!$TV = $this->modx->getObject('modTemplateVar', array('name' => $t))) {
                    $TV = $this->modx->newObject('modTemplateVar');
                }
                // Set TV attributes?  This is like Seed data, but it lives in elements/tvs
                $filename = $this->Repoman->get_core_path($this->pkg_dir) . $this->Repoman->get('tvs_path') . '/' . $t . '.php';
                $data = $this->Repoman->load_data($filename);
                $TV->fromArray($data[0]); // one at a time only.

                Repoman::$queue[$this->objecttype][] = 'modTemplateVarTemplate: ' . $TV->get('name') . ' ' . $Obj->get('templatename');
                $tvs[] = $TV;
            }
        } else {
            // Remove all TVs
            $TVTs = $this->modx->getCollection('modTemplateVarTemplate', array('templateid' => $templateid));
            foreach ($TVTs as $t) {
                $t->remove();
            }
        }
        $Obj->addMany($tvs);
    }

    /**
     * Strips out comments and whitespace from HTML
     * See http://davidwalsh.name/remove-html-comments-php
     * @param $string
     * @return string
     */
    public function removeComments($string)
    {
        return preg_replace('/<!--(.|\s)*?-->/', '', $string);
    }

}
/*EOF*/
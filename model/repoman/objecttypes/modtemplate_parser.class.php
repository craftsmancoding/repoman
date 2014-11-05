<?php

/**
 *
 *
 */
class modTemplate_parser extends Repoman_parser {

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
     *
     * @return string
     */
    public function extend_docblock(&$Obj)
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
     * Run when files are being put into the package, this allows for
     * extraneous comment blocks to be filtered out and placeholders to be adjusted.
     *
     * @param string $string
     *
     * @return string
     */
    public function prepare_for_pkg($string)
    {
        // Strip out docblock entirely (i.e. the first comment)
        $string = preg_replace('#(' . preg_quote($this->dox_start) . ')(.*)(' . preg_quote($this->dox_end) . ')#Uis', '', $string, 1);
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.assets_url', '[[++assets_url', $string);
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.assets_path', '[[++assets_path', $string);
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.core_path', '[[++core_path', $string);

        return $string;
    }

    /**
     * Attach and Remove Template's TVs
     *
     */
    public function relate($attributes, &$Obj)
    {

        $tvts = array(); // join: templatevars-templates
        $templateid = $Obj->get('id');
        if (isset($attributes['TVs'])) {
            $tv_names = explode(',', $attributes['TVs']);

            $rank = 0;
            foreach ($tv_names as $t) {
                $t = trim($t);
                if (!$TV = $this->modx->getObject('modTemplateVar', array('name' => $t))) {
                    $TV = $this->modx->newObject('modTemplateVar');
                }
                // Set TV attributes?  This is like Seed data, but it lives in elements/tvs
                $filename = $this->Repoman->get_core_path($this->pkg_dir) . $this->Repoman->get('tvs_path') . '/' . $t . '.php';
                $data = $this->Repoman->load_data($filename);
                $TV->fromArray($data[0]); // one at a time only.

                // Add modTemplateVarTemplate to link TV w Template
                if (!$TVT = $this->modx->getObject('modTemplateVarTemplate', array('tmplvarid' => $TV->get('id'), 'templateid' => $templateid))) {
                    $TVT = $this->modx->newObject('modTemplateVarTemplate');
                }
                $TVT->set('rank', $rank);
                $TVT->addOne($TV);
                $rank++;

                $tvts[] = $TVT;
                //$TV->addMany($join);
                Repoman::$queue[$this->objecttype][] = 'modTemplateVarTemplate: ' . $TV->get('name') . ' ' . $Obj->get('templatename');
                //$tvs[] = $TV;
            }
        } else {
            // Remove all TVs
            $TVTs = $this->modx->getCollection('modTemplateVarTemplate', array('templateid' => $templateid));
            foreach ($TVTs as $t) {
                $t->remove();
            }
        }
        // Do the Join
        $Obj->addMany($tvts);
    }


}
/*EOF*/
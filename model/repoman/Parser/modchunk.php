<?php namespace Repoman\Parser;

/**
 * REMEMBER: chunks are not cached anywhere except per Resource
 *
 */
class modchunk extends Parser {

    public $dir_key = 'chunks_path';
    public $ext = '*.*';
    public $write_ext = '.tpl';
    public $objecttype = 'modChunk';

    public $dox_start = '<!--';
    public $dox_end = '-->';
    public $dox_pad = ''; // left of line before the @attribute	

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
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.assets_url]]', '[[++assets_url]]components/'.$this->Repoman->get('namespace').'/', $string);
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.assets_path]]', '[[++assets_path]]components/'.$this->Repoman->get('namespace').'/', $string);
        $string = str_replace('[[++' . $this->Repoman->get('namespace') . '.core_path]]', '[[++core_path]]components/'.$this->Repoman->get('namespace').'/', $string);

        return $string;
    }
}
/*EOF*/
<?php
/**
 * REMEMBER: chunks are not cached anywhere except per Resource
 *
 */
namespace Repoman\Parser;

use Repoman\Parser;

class modChunk extends Parser
{

    public $dir_key = 'chunks_path';
    public $ext = '*.*';
    public $write_ext = '.tpl';
    public $objecttype = 'modChunk';

    public $dox_start = '<!--';
    public $dox_end = '-->';
    public $dox_pad = ''; // left of line before the @attribute	

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
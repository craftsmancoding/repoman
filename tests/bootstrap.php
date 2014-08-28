<?php
require_once __DIR__.'/../vendor/autoload.php';

/**
 * Normalize (HTML) strings for valid comparison.
 */
function normalize_string($str)
{
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}
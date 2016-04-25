<?php

use Symphony\ApiFramework\Lib;

function renderer_json($mode){
    if (strtolower($mode) == 'administration') {
        throw new Lib\Exceptions\InvalidModeException('JSON Renderer launcher is only availalbe on the frontend');
    }

    $renderer = Frontend::instance();

    // #1808
    if (isset($_SERVER['HTTP_MOD_REWRITE']))
    {
        throw new Exception("mod_rewrite is required, however is not enabled.");
    }

    $output = $renderer->display(getCurrentPage());

    cleanup_session_cookies();

    if(in_array('JSON', Frontend::Page()->pageData()['type'])) {
        // Load the output into a SimpleXML Container and convert to JSON
        $xml = new SimpleXMLElement($output, LIBXML_NOCDATA);
        $output = json_encode(xml, JSON_PRETTY_PRINT);
    }

    echo $output;
    return $renderer;
}

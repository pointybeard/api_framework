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
        try{
          $xml = new SimpleXMLElement($output, LIBXML_NOCDATA);

          // Convert the XML to a plain array. This step is necessary as we cannot
          // use JSON_PRETTY_PRINT directly on a SimpleXMLElement object
          $outputArray = json_decode(json_encode($xml), true);

          // Get the transforer object ready. Other extensions will
          // add their transormations to this.
          $transformer = new Lib\Transformer();

          /**
           * Allow other extensions to add their own transformers
           */
          Symphony::ExtensionManager()->notifyMembers(
            'APIFrameworkJSONRendererAppendTransformations',
            '/frontend/',
            ['transformer' => &$transformer]
          );

          // Apply transformations
          $outputArray = $transformer->run($outputArray);

          // Now put the array through a json_encode
          $output = json_encode($outputArray, JSON_PRETTY_PRINT);

        } catch(\Exception $e) {
            // This happened because the input was not valid XML. This could
            // occur for a few reasons, but there are two scenarios
            // we are interested in.

            // 1) This is a devkit page (profile, debug etc). We want the data
            //    to be passed through and displayed rather than converted into
            //    JSON. There is no easy way in Symphony to tell if a devkit has
            //    control over the page, so instead lets inspect the output for
            //    any signs a devkit is rendering the page.

            // 2) It is actually bad XML. In that case we need to let the error
            //    bubble through.

            // Currently the easiest method is to check for the devkit.min.css
            // in the output. This may fail in the furture if this file is
            // renamed or moved.
            if(!preg_match("@\/symphony\/assets\/css\/devkit.min.css@", $output)) {
              throw $e;
            }
        }
    }

    echo $output;
    return $renderer;
}

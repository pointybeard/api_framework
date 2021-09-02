<?php

declare(strict_types=1);

/*
 * This file is part of the "RESTful API Framework Extension for Symphony CMS" repository.
 *
 * Copyright 2017-2021 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symfony\Component\HttpFoundation;

/**
 * This extends the core FrontendPage class of Symphony to give us a vector to
 * overload various functionality.
 */
class JsonFrontendPage extends \FrontendPage
{
    public const PAGE_TYPE_JSON = 'JSON';

    public const PAGE_TYPE_CACHEABLE = 'cacheable';

    /**
     * Constructor function sets the `$is_logged_in` variable, calls
     * XSLTPage constructor (bypassing FrontendPage::__construct()) and
     * set $resolvedPage which is used by overloaded resolvePage() method.
     */
    public function __construct()
    {
        // We need to skip the FrontendPage constructor as it turns off
        // our exception handling by creating a new instance of Frontend.
        // Instead, lets skip over to the parent->parent constructor.
        \XSLTPage::__construct();

        $this->is_logged_in = JsonFrontend::instance()->isLoggedIn();
    }

    // Accessor method for rendering the page headers.
    public function renderHeaders(): void
    {
        \Page::__renderHeaders();
    }

    public function render(HttpFoundation\Request $request, HttpFoundation\Response $response): HttpFoundation\Response
    {
        $output = parent::generate(rtrim($request->getPathInfo(), '/'));

        cleanup_session_cookies();

        // Load the output into a SimpleXML Container and convert to JSON
        try {
            $xml = new \SimpleXMLElement($output, LIBXML_NOCDATA);

            // Convert the XML to a plain array. This step is necessary as we cannot
            // use JSON_PRETTY_PRINT directly on a SimpleXMLElement object
            $outputArray = json_decode(json_encode($xml), true);

            // Get the transforer object ready. Other extensions will
            // add their transormations to this.
            $transformer = new Transformer();

            /*
             * Allow other extensions to add their own transformers
             */
            \Symphony::ExtensionManager()->notifyMembers(
                'APIFrameworkJSONRendererAppendTransformations',
                '/frontend/',
                ['transformer' => &$transformer]
            );

            // Apply transformations
            $outputArray = $transformer->run($outputArray);

            // Now put the array through a json_encode
            $output = json_encode(
                $outputArray,
                JsonFrontend::instance()->getEncodingOptions()
            );

            \Profiler::instance()->sample('API JSON Rendering complete.');
        } catch (\Exception $e) {
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
            if (false == preg_match("@\/symphony\/assets\/css\/devkit.min.css@", $output)) {
                throw $e;
            }
        }

        $response->setContent($output);

        return $response;
    }
}

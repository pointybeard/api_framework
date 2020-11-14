<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

/**
 * This extends the core FrontendPage class of Symphony to give us a vector to
 * overload various functionality.
 */
class JsonFrontendPage extends \FrontendPage
{
    const PAGE_TYPE_JSON = 'JSON';
    const PAGE_TYPE_CACHEABLE = 'cacheable';

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

    public function getController(): AbstractController
    {
        // #5 - Use the full page path to generate the controller class name
        // #7 - Use a PSR-4 folder structure and build the namespace accordingly
        // #14 - Each page has a parent-path (somtimes this is / when at root).

        // In order to find the correct controller path, we need to combine
        // current-page with parent-path

        // Determine the current page path
        $page = (new PageResolver((string)getCurrentPage()))->resolve();

        $controllerName = 'Controller'.ucfirst(trim($page->handle));

        // Next, do some processing over the parent path (if there is one) to
        // determine the folder path.
        $parts = array_map('ucfirst', preg_split("@\/@", trim((string)$page->path, '/')));
        $controllerPath = implode($parts, '\\').'\\';

        $controllerPath = sprintf(
            __NAMESPACE__ . "\\Controllers\\%s%s",
            ltrim($controllerPath, '\\'),
            $controllerName
        );

        // #6 - Check if the controller exists before trying to include it.
        // Throw an exception if it cannot be located.
        if (false == class_exists($controllerPath)) {
            throw new Exceptions\ControllerNotFoundException($controllerPath);
        }

        $controller = new $controllerPath();

        // Make sure the controller extends the AbstractController class
        if (false == ($controller instanceof AbstractController)) {
            throw new Exceptions\ControllerNotValidException("'{$controllerPath}' is not a valid controller.");
        }

        return $controller;
    }

    // Accessor method for rendering the page headers.
    public function renderHeaders(): void
    {
        \Page::__renderHeaders();
    }

    public function generateParent($page = null): string
    {
        return parent::generate($page);
    }

    public function generate($page = null): string
    {

        $output = $this->generateParent($page);

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
        return $output;
    }
}

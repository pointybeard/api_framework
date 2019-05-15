<?php declare(strict_types=1);
namespace Symphony\ApiFramework\ApiFramework;

/**
 * This extends the core FrontendPage class of Symphony to give us a vector to
 * overload various functionality.
 */
class JsonFrontendPage extends \FrontendPage
{
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
    public function renderHeaders() : void
    {
        \Page::__renderHeaders();
    }

    public function addRenderTimeToHeaders() : void
    {
        \Profiler::instance()->sample('API JSON Rendering complete.');

        $profile = (object)array_combine(
            ['message', 'elapsed', 'created', 'type', 'queries', 'memory'],
            \Symphony::Profiler()->retrieveLast()
        );

        JsonFrontend::Page()->addHeaderToPage(
            'X-API-Framework-Render-Time',
            number_format($profile->elapsed, 4)
        );
    }

    public function generate($page=null) : string
    {
        $output = parent::generate($page);
        cleanup_session_cookies();
        if (in_array('JSON', $this->pageData()['type'])) {

            // Load the output into a SimpleXML Container and convert to JSON
            try {
                $xml = new \SimpleXMLElement($output, LIBXML_NOCDATA);

                // Convert the XML to a plain array. This step is necessary as we cannot
                // use JSON_PRETTY_PRINT directly on a SimpleXMLElement object
                $outputArray = json_decode(json_encode($xml), true);

                // Get the transforer object ready. Other extensions will
                // add their transormations to this.
                $transformer = new Transformer();

                /**
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

                $this->addRenderTimeToHeaders();
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
                if (!preg_match("@\/symphony\/assets\/css\/devkit.min.css@", $output)) {
                    throw $e;
                }
            }
        }

        return $output;
    }
}

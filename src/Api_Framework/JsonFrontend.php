<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symphony;
use Symfony\Component\HttpFoundation;

/**
 * This extends the core Symphony class to give us a vector to
 * overload various functionality. There is a certain amount of code
 * duplication, taken from Frontend, since $_page is a private variable
 * and thus we cannot extend the Frontend class and inherit it's core features.
 */
class JsonFrontend extends Symphony
{
    // JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    const DEFAULT_ENCODING_OPTIONS = 207;

    protected $encodingOptions = self::DEFAULT_ENCODING_OPTIONS;

    /**
     * An instance of the Api_Framework\JsonFrontendPage class.
     *
     * @var JsonFrontendPage
     */
    protected static $_page;

    /**
     * Code duplication from core Frontend class.
     */
    protected function __construct()
    {
        parent::__construct();

        // When the parent construtor is called, the GenericExceptionHandler is
        // turned off, which is fine normally unless you want exceptions to
        // bubble up in a JSON api. Lets turn it back on.
        \GenericExceptionHandler::$enabled = true;

        $this->_env = [];
    }

    /**
     * Code duplication from core Frontend class, however it returns an
     * instance of JsonFrontendPage rather than FrontendPage.
     */
    public function display(string $page)
    {

        $resolvedPage = (new \FrontendPage())->resolvePage($page);

        // GET Requests on pages that are of type 'cacheable' can be cached.
        $isCacheable =
        (
            true == \Extension_API_Framework::isCacheEnabled()
            && 'GET' == $_SERVER['REQUEST_METHOD']
            && true == is_array($resolvedPage)
            && true == in_array(JsonFrontendPage::PAGE_TYPE_CACHEABLE, $resolvedPage['type'])
        );

        self::$_page = $isCacheable
            ? new CacheableJsonFrontendPage()
            : new JsonFrontendPage()
        ;

        $this->Page()->addHeaderToPage(
            'X-API-Framework-Page-Renderer',
            array_pop(explode('\\', get_class(self::$_page)))
        );

        \Symphony::ExtensionManager()->notifyMembers('FrontendInitialised', '/frontend/');
        
        // Get the controller
        try {
            $controller = $this->Page()->getController();
        } catch(Exceptions\ControllerNotFoundException $ex) {
            // It's okay if controller ends up as nothing
        }

        $this->Page()->addHeaderToPage(
            'X-API-Framework-Controller',
            true == ($controller instanceof AbstractController)
                ? get_class($controller)
                : "none"
        );

        // Built a HTTP request object
        try {
            $request = JsonRequest::createFromGlobals();

        // We want to allow non-JSON requests in certain situations.
        } catch (Exceptions\RequestJsonInvalidException $ex) {
            $request = HttpFoundation\Request::createFromGlobals();

            // The input is discarded, but we need to emulate the json
            // ParameterBag object.
            $request->json = new HttpFoundation\ParameterBag();
        }

        // There are a couple of pathways here:
        // 1. There is no controller so it should just
        //      pass on the normal page rendering process
        if(false == ($controller instanceof AbstractController)) {
            return self::$_page->generate($page);

        // 2a. There is a page controller
        //      but it does not respond to GET requests AND it indicates a 403
        //      should not be thrown, or 
        } elseif (HttpFoundation\Request::METHOD_GET == $request->getMethod() 
            && false == $controller->respondsToRequestMethod($request->getMethod()) 
            && false == $controller->throwMethodNotAllowedExceptionOnGet()
        ) {
            return self::$_page->generate($page);
        }

        // 2b. There is a page controller for this page and it takes over 
        //      generating the output, or 
        if (false == $controller->respondsToRequestMethod($request->getMethod())) {
            throw new Exceptions\MethodNotAllowedException($request->getMethod());
        }

        // Run any controller pre-flight code
        $controller->execute($request);

        // Prepare the response.
        $response = new HttpFoundation\JsonResponse();
        $response->headers->set('Content-Type', 'application/json');
        $response->setEncodingOptions(
            JsonFrontend::instance()->getEncodingOptions()
        );

        // Find any request or response schemas to apply
        if (true == $canValidate) {
            $schemas = $controller->schemas($request->getMethod());

            // Validate the request. We dont care about the returned data
            $controller->validate(
                $request->request->all(),
                $schemas->request
            );
        }

        // Run the controller's method that corresponds to the request method
        $response = call_user_func([$controller, strtolower($request->getMethod())], $request, $response);
        //$response = $controller->$method($request, $response);

        // Validate the response. We dont care about the returned data
        if (true == $canValidate) {
            $controller->validate($response->getContent(), $schemas->response);
        }

        return $response;

    }

    /**
     * Code duplication from core Frontend class, however it returns an
     * instance of self rather than hard coding the class name.
     */
    public static function instance(): self
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Code duplication from core Frontend class.
     */
    public static function Page()
    {
        return self::$_page;
    }

    /**
     * Code duplication from core Frontend class.
     */
    public static function isLoggedIn(): bool
    {
        if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && 8 == strlen($_REQUEST['auth-token'])) {
            return self::loginFromToken($_REQUEST['auth-token']);
        }

        return Symphony::isLoggedIn();
    }

    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    public function setEncodingOptions(int $options): void
    {
        $this->encodingOptions = $options;
    }
}

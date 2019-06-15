<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\JsonResponse;
use pointybeard\Symphony\Extensions\Api_Framework;

class eventController extends SectionEvent
{
    public static function about(): array
    {
        return [
            'name' => 'API Framework: Controller',
            'author' => [
                'name' => 'Alannah Kearney',
                'website' => 'http://alannahkearney.com',
                'email' => 'hi@alannahkearney.com',
            ],
            'release-date' => '2019-06-12',
            'trigger-condition' => 'POST|PUT|PATCH|DELETE',
        ];
    }

    public function load(): void
    {
        // This ensures the composer autoloader for the framework is included
        Extension_API_Framework::init();

        try {
            $request = ApiFramework\JsonRequest::createFromGlobals();

            // We want to allow non-JSON requests in certain situations.
        } catch (ApiFramework\Exceptions\RequestJsonInvalidException $ex) {
            $request = HttpFoundation\Request::createFromGlobals();

            // The input is discarded, but we need to emulate the json
            // ParameterBag object.
            $request->json = new HttpFoundation\ParameterBag();
        }

        // Event controller only responds to certain methods. GET is handled
        // by the data sources
        if ('GET' == $request->getMethod()) {
            return;
        }

        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true, 512, JSON_BIGINT_AS_STRING);
            $request->request->replace(is_array($data) ? $data : []);
        }

        // #5 - Use the full page path to generate the controller class name
        // #7 - Use a PSR-4 folder structure and build the namespace accordingly
        // #14 - Each page has a parent-path (somtimes this is / when at root).
        // In order to find the correct controller path, we need to combine
        // current-page with parent-path

        // Grab out the "current-page". Our controller will always be named
        // using this
        $controllerName = 'Controller'.ucfirst(trim(
            ApiFramework\JsonFrontend::instance()
                ->Page()
                ->Params()['current-page']
        ));

        // Next, do some processing over the "parent-path" (if there is one) to
        // determine the folder path.
        $currentPagePath = trim(
            ApiFramework\JsonFrontend::instance()
                ->Page()
                ->Params()['parent-path'],
            '/'
        );
        $parts = array_map('ucfirst', preg_split("@\/@", $currentPagePath));
        $controllerPath = implode($parts, '\\').'\\';

        $controllerPath = sprintf(
            "Symphony\ApiFramework\Controllers\\%s%s",
            ltrim($controllerPath, '\\'),
            $controllerName
        );

        // #6 - Check if the controller exists before trying to include it.
        // Throw an exception if it cannot be located.
        if (!class_exists($controllerPath)) {
            throw new ApiFramework\Exceptions\ControllerNotFoundException($controllerPath);
        }

        $controller = new $controllerPath();

        // Make sure the controller extends the AbstractController class
        if (!($controller instanceof ApiFramework\AbstractController)) {
            throw new ApiFramework\Exceptions\ControllerNotValidException(
                sprintf(
                    "'%s' is not a valid controller. Check implementation conforms to AbstractController and ControllerInterface",
                    $controllerPath
            )
            );
        }

        $method = strtolower($request->getMethod());

        if (!method_exists($controller, $method)) {
            throw new ApiFramework\Exceptions\MethodNotAllowedException($request->getMethod());
        }

        $canValidate = ($controller instanceof ApiFramework\Interfaces\JsonSchemaValidationInterface);

        // Run any controller pre-flight code
        $controller->execute($request);

        // Prepare the response.
        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/json');
        $response->setEncodingOptions(
            ApiFramework\JsonFrontend::instance()->getEncodingOptions()
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
        $response = $controller->$method($request, $response);

        // Validate the response. We dont care about the returned data
        if (true == $canValidate) {
            $controller->validate($response->getContent(), $schemas->response);
        }

        $response->send();
        exit;
    }

    public static function documentation(): string
    {
        return '<h3>Event Controller</h3><p>Handles passing off work to controllers depending on what has been requested.</p>';
    }
}

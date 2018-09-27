<?php

use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symphony\ApiFramework\Lib;

class eventController extends SectionEvent
{
    public static function about()
    {
        return [
            'name' => 'API Framework: Controller',
            'author' => [
                'name' => 'Alistair Kearney',
                'website' => 'http://alistairkearney.com',
                'email' => 'hi@alistairkearney.com'
            ],
            'version' => 'Symphony 2.6.7',
            'release-date' => '2016-06-17',
            'trigger-condition' => 'POST|PUT|PATCH|DELETE'
        ];
    }

    public function load()
    {

        try{
            $request = Lib\JsonRequest::createFromGlobals();

        // We want to allow non-JSON requests in certain situations.
        } catch(Lib\Exceptions\RequestJsonInvalidException $ex) {

            $request = HttpFoundation\Request::createFromGlobals();

            // The input is discarded, but we need to emulate the json ParameterBag
            // object.
            $request->json = new HttpFoundation\ParameterBag;
        }

        // This ensures the composer autoloader for the framework is included
        Symphony::ExtensionManager()->create('api_framework');

        // Event controllor only responds to certain methods. GET is handled by the data sources
        if($request->getMethod() == 'GET'){
            return;
        }

        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true, 512, JSON_BIGINT_AS_STRING);
            $request->request->replace(is_array($data) ? $data : []);
        }

        // #5 - Use the full page path to generate the controller class name
        // #7 - Use a PSR-4 folder structure and build the namespace accordingly
        // #14 - Each page has a parent-path (somtimes this is / when at root). In
        // order to find the correct controller path, we need to combine
        // current-page with parent-path

        // Grab out the "current-page". Our controller will always be named
        // using this
        $controllerName = "Controller" . ucfirst(trim(
            Lib\JsonFrontend::instance()
                ->Page()
                ->Params()["current-page"]
        ));

        // Next, do some processing over the "parent-path" (if there is one) to
        // determine the folder path.
        $currentPagePath = trim(
            Lib\JsonFrontend::instance()
                ->Page()
                ->Params()["parent-path"]
            ,
            '/'
        );
        $parts = array_map("ucfirst", preg_split("@\/@", $currentPagePath));
        $controllerPath = implode($parts, "\\") . "\\";

        $controllerPath = sprintf(
            "Symphony\ApiFramework\Controllers\\%s%s",
            ltrim($controllerPath, '\\'),
            $controllerName
        );

        // #6 - Check if the controller exists before trying to include it.
        // Throw an exception if it cannot be located.
        if(!class_exists($controllerPath)) {
            throw new Lib\Exceptions\ControllerNotFoundException($controllerPath);
        }

        $controller = new $controllerPath();

        // Make sure the controller extends the AbstractController class
        if(!($controller instanceof Lib\AbstractController)) {
            throw new Lib\Exceptions\ControllerNotValidException(sprintf(
                "'%s' is not a valid controller. Check implementation conforms to Lib\AbstractController.", $controllerPath)
            );
        }

        $method = strtolower($request->getMethod());

        if(!method_exists($controller, $method)){
            throw new Lib\Exceptions\MethodNotAllowedException($request->getMethod());
        }

        $canValidate = ($controller instanceof Lib\Interfaces\JsonSchemaValidationInterface);

        // Run any controller pre-flight code
        $controller->execute($request);

        // Prepare the response.
        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/json');
        $response->setEncodingOptions(
            Lib\JsonFrontend::instance()->getEncodingOptions()
        );

        // Find any request or response schemas to apply
        if($canValidate == true) {
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
        if($canValidate == true) {
            $controller->validate($response->getContent(), $schemas->response);
        }

        $response->send();
        exit;
    }

    public static function documentation()
    {
        return '<h3>Event Controller</h3><p>Handles passing off work to controllors depending on what has been requested.</p>';
    }
}

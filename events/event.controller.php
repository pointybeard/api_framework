<?php

use Symfony\Component\HttpFoundation\Request;
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
        $request = Request::createFromGlobals();

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
            Frontend::instance()
                ->Page()
                ->Params()["current-page"]
        ));

        // Next, do some processing over the "parent-path" (if there is one) to
        // determine the folder path.
        $currentPagePath = trim(
            Frontend::instance()
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
          throw new Lib\Exceptions\ControllerNotValidException("'{$controllerPath}' is not a valid controller. Check implementation conforms to Lib\AbstractController.");
        }

        $method = strtolower($request->getMethod());

        if(!method_exists($controller, $method)){
            throw new Lib\Exceptions\MethodNotAllowedException($request->getMethod());
        }

        $controller->execute();

        // Prepare the response.
        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/json');

        $response = $controller->$method($request, $response);
        $response->send();
        exit;
    }

    public static function documentation()
    {
        return '<h3>Event Controller</h3><p>Handles passing off work to controllors depending on what has been requested.</p>';
    }
}

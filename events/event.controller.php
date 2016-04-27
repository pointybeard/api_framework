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
            'release-date' => '2016-04-25T02:10:22+00:00',
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

        $controllerPath = WORKSPACE . '/controllers';

        // #5 - Use the full page path to generate the controller class name
        $controllerName = 'Controller';
        $currentPagePath = trim(Frontend::instance()->Page()->Params()["current-path"], '/');
        foreach(preg_split("@\/@", $currentPagePath) as $part) {
          $controllerName .= ucfirst($part);
        }

        // #6 - Check if the controller exists before trying to include it.
        // Throw an exception if it cannot be located.
        $controllerFullPath = sprintf("%s/controllers/%s.php", WORKSPACE, $controllerName);
        if(!file_exists($controllerFullPath) || !is_readable($controllerFullPath)) {
          throw new Lib\Exceptions\ControllerNotFoundException("Controller '{$controllerName}' does not exist.");
        }

        include_once WORKSPACE . "/controllers/$controllerName.php";
        $controller = new $controllerName();

        // Make sure the controller extends the AbstractConroller class
        if(!($controller instanceof Lib\AbstractController)) {
          throw new Lib\Exceptions\ControllerNotValidException("'{$controllerName}' is not a valid controller. Check implementation.");
        }

        $method = strtolower($request->getMethod());

        if(!method_exists($controller, $method)){
            throw new Lib\Exceptions\ControllerNotValidException("405 method not found (".$request->getMethod().")");
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

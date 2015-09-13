<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class eventController extends SectionEvent
{
    public static function about()
    {
        return [
            'name' => 'Symphony API Framework: Event Controller',
            'author' => array(
                'name' => 'Alistair Kearney',
                'website' => 'http://cp.kickd',
                'email' => 'alistair@ruleandmake.com'),
            'version' => 'Symphony 2.6.3',
            'release-date' => '2015-08-28T02:10:22+00:00',
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

        //@TODO: Use the current path to resolve controllers deeper than 1 level
        //Frontend::instance()->Page()->Params()['current-path']

        $controllerPath = WORKSPACE . '/controllers';
        $controllerName = 'Controller' . ucfirst(Frontend::instance()->Page()->pageData()['handle']);

        include_once WORKSPACE . "/controllers/$controllerName.php";
        $controller = new $controllerName();
        $method = strtolower($request->getMethod());

        if(!method_exists($controller, $method)){
            throw new \Exception("405 method not found (".$request->getMethod().")");
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

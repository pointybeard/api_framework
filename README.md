# RESTful API Framework for Symphony CMS

JSON renderer and event driven controller interface for Symphony CMS designed to quickly build a RESTful APIs.

## Installation

This is an extension for Symphony CMS. Add it to your `/extensions` folder in your Symphony CMS installation, then enable it though the interface.

### Requirements

This extension requires the **[Symfony HTTP Foundation](https://github.com/symfony/http-foundation)** (`symfony/http-foundation`) and **[Symphony PDO](https://github.com/pointybeard/symphony-pdo)** (`pointybeard/symphony-pdo`) to be installed via Composer. Either require both of these in your main composer.json file, or run `composer install` on the `extension/api_framework` directory.

    "require": {
      "php": ">=5.6.6",
      "symfony/http-foundation": "^3.0@dev",
      "pointybeard/symphony-pdo": "~0.1"
    }

## Usage

This extension has two parts: The JSON renderer, and the Controller event.

### JSON Renderer

Any page with a type `JSON` will trigger the new JSON renderer. This automatically converts your XML output into a JSON document. This includes output from any events.

### Controller Event

Use the `API Framework: Controller` event to listen for PUT, POST, PATCH and DELETE requests. To create your own controller, make a folder called `controllers` in your `/workspace` directory.

A controller will respond to the 4 methods (PUT, POST, PATCH and DELETE) via a same named public method. E.g. to respond to a PUT request, create a method called 'put' in your controller like so

    public function put(Request $request, Response $response)
    {
      ...
    }

Modify `$response` to set return values and status code. E.g.:

    $response->setStatusCode(Response::HTTP_OK);

Lastly, call the render method (which is inherited) to generate the output. E.g.

    return $this->render($response, ['data' => 'some output']);

To actually use a controller, name it the same as a top level page. E.g. if you had a page called "entry", and you wanted to provide PUT, POST, PATCH and DELETE functionality, name your controller class "ControllerEntry" and the file `ControllerEntry.php`

Here is an example of a completed controller:

    <?php

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symphony\ApiFramework\Lib\AbstractController;
    use Symphony\ApiFramework\Lib\Traits;

    final class ControllerExample extends AbstractController{

        use Traits\hasEndpointSchemaTrait;

        public function execute(){
          // Optional. Add any code here, such
          // as including extension or autoloaders.
          // This method is automatically invoked
          // anytime this controller is going to be used.
        }

        public function post(Request $request, Response $response)
        {
            $data = $request->request->all();

            try{
                // do some work here
                $response->setStatusCode(Response::HTTP_CREATED);
                $response->headers->set(
                    'Location', "some/path/to/resource"
                );
                return $this->render($response, [
                    'data' => [
                        'id' => $idOfNewResource,
                    ],
                    'status' => $response->getStatusCode(),
                    'message' => "Item was successfully created."
                ]);

            } catch(\Exception $ex) {
                // handle errors here.
            }
            return;
        }

        public function put(Request $request, Response $response)
        {
            $someEntryId = (int)Frontend::instance()
                ->Page()
                ->Params()['some-id'];

            $data = $request->request->all();
            $output = [];

            try{
                // do some work here with "someEntryId"
                $output = [
                    'message' => "Entry successfully updated.",
                    'status' => Response::HTTP_OK
                ];

            } catch(Exceptions\ModelEntryNotFoundException $ex) {
                $output = [
                    'message' => "Entry not found.",
                    'status' => Response::HTTP_NOT_FOUND
                ];
            }

            $response->setStatusCode($output['status']);
            return $this->render($response, $output);
        }

        public function patch(Request $request, Response $response)
        {
            // Sometimes it is okay just to use the
            // PUT code to handle PATCH requests also
            return $this->put($request, $response);
        }

        public function delete(Request $request, Response $response)
        {
            $someEntryId = (int)Frontend::instance()
                ->Page()
                ->Params()['some-id'];

            $output = [];

            try{
                // do some work here using "someEntryId"
                $output = [
                    'message' => "Entry successfully deleted.",
                    'status' => Response::HTTP_OK
                ];

            } catch(Exceptions\ModelEntryNotFoundException $ex) {
                $output = [
                    'message' => "Entry not found.",
                    'status' => Response::HTTP_NOT_FOUND
                ];
            }

            $response->setStatusCode($output['status']);
            return $this->render($response, $output);
        }
    }

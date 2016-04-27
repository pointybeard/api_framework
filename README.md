# RESTful API Framework for Symphony CMS

JSON renderer and event driven controller interface for Symphony CMS designed to quickly build a RESTful APIs.

## Installation

This is an extension for Symphony CMS. Add it to your `/extensions` folder in your Symphony CMS installation, then enable it though the interface.

### Requirements

This extension requires the **[Symfony HTTP Foundation](https://github.com/symfony/http-foundation)** (`symfony/http-foundation`) to be installed via Composer. Either require both of these in your main composer.json file, or run `composer install` on the `extension/api_framework` directory.

    "require": {
      "php": ">=5.6.6",
      "symfony/http-foundation": "^3.0@dev"
    }

## Usage

This extension has two parts: The JSON renderer, and the Controller event.

### JSON Renderer

Any page with a type `JSON` will trigger the new JSON renderer. This automatically converts your XML output into a JSON document. This includes output from any events.

#### Working with XML

The JSON renderer expects to get well formed XML data, which it then translates into JSON data. Although JSON is just as well structured as XML, XML does not translate directly to JSON. Here are a few examples of XML and how it translates into JSON:

##### Arrays

Arrays are formed when the same element is used and contains simple values (numbers or strings). Each element does not need to be directly after one another either, but it is best practise to group them in some way.

XML:

    <data>
    	...
		<array>1</array>
		<array>2</array>
    </data>

JSON:

	{
		...
    	"array": [
			"1",
   	     	"2"
    	]
	}


##### Objects

Typically Symphony will be outputting objects (Entries, Sections etc). E.g.

```
<data>
  <entries>
    <entry>
      <id>2</id>
      <title>Another Entry</title>
      <handle>another-entry</handle>
      <body>Blah Blah</body>
      <publish-date>
        <date>2016-04-25</date>
        <time>19:03</time>
      </publish-date>
    </entry>
    <entry>
      <id>1</id>
      <title>An Entry</title>
      <handle>an-entry</handle>
      <body>This is a dummy entry</body>
      <publish-date>
        <date>2016-04-25</date>
        <time>16:53</time>
      </publish-date>
    </entry>
  </entries>
</data>
```

Would result in the following JSON

```
{
    "entries": {
        "entry": [
            {
                "id": "2",
                "title": "Another Entry",
                "handle": "another-entry",
                "body": "Blah Blah",
                "publish-date": {
                    "date": "2016-04-25",
                    "time": "19:03"
                }
            },
            {
                "id": "1",
                "title": "An Entry",
                "handle": "an-entry",
                "body": "This is a dummy entry",
                "publish-date": {
                    "date": "2016-04-25",
                    "time": "16:53"
                }
            }
        ]
    }
}
```

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

### Transformers

Prior to converting the XML into JSON, transformers are run over it. Transformers mutate the result based on a test and action.

`@jsonForceArray`

This transformation will look for the attribute `jsonForceArray` on any XML elements. If it is set to "true", this transformation is applied. It relates to **[#issue-2](https://github.com/pointybeard/api_framework/issues/2)**. When there are multiple elements of the same name, for example 'entry', the JSON encode process will treat these as an array. E.g.

```
<data>
  <entries>
    <entry>
      <id>2</id>
      <title>Another Entry</title>
    </entry>
    <entry>
      <id>1</id>
      <title>An Entry</title>
    </entry>
  </entries>
</data>
```

becomes


```
{
    "entries": {
        "entry": [
            {
                "id": "2",
                "title": "Another Entry",
            },
            {
                "id": "1",
                "title": "An Entry",
            }
        ]
    }
}
```

However, if there is only a single 'entry' element, it is treated as an object. This is because internally it is just an associtive array, not an indexed array of 'entry' objects. E.g.

```
<data>
  <entries>
    <entry>
      <id>2</id>
      <title>Another Entry</title>
    </entry>
  </entries>
</data>
```

results in

```
{
    "entries": {
        "entry": {
            "id": "1",
            "title": "An Entry",
        }
    }
}
```

Notice that 'entry' is a JSON object. The problem with this is inconsistent data. It changes depending on how many entries are present. The solution is to set `jsonForceArray="true"` on the 'entry' element to trigger the transformation:

```
<data>
  <entries>
    <entry jsonForceArray="true">
      <id>2</id>
      <title>Another Entry</title>
    </entry>
  </entries>
</data>
```

Which results in JSON

```
{
    "entries": {
        "entry": [
            {
                "id": "2",
                "title": "Another Entry",
            }
        ]
    }
}
```

### Creating new Transformers

This extention provides the delegate `APIFrameworkJSONRendererAppendTransformations` on all frontend pages with the `JSON` type. The context includes an instance of `Lib\Transformer`. Use the `append()` method to add your own transformations. E.g.

```
<?php

use Symphony\ApiFramework\Lib;

Class extension_example extends Extension
{
  public function getSubscribedDelegates(){
    return[[
      'page' => '/frontend/',
      'delegate' => 'APIFrameworkJSONRendererAppendTransformations',
      'callback' => 'appendTransformations'
      ]];
  }

  public function appendTransformations($context) {

    $context['transformer']->append(
      new Lib\Transformation(

        // This is the test. If it returns true, the action will be run
        function(array $input, array $attributes=[]){
          // do some tests in here and return either true or false
          return true;
        },

        // This is the action. If the test passes, this code will be run
        function(array $input, array $attributes=[]){
          // Operate on $input and return the result.
          return $input;
        }
      )
    );
  }
}
```

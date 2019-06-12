# RESTful API Framework for Symphony CMS

-   Version: v1.1.0
-   Date: May 11th 2019
-   [Release notes](https://github.com/pointybeard/api_framework/blob/master/CHANGELOG.md)
-   [GitHub repository](https://github.com/pointybeard/api_framework)

JSON renderer and event driven controller interface to help rapidly prototype and build RESTful APIs in Symphony CMS

-   [Installation](#installation)
    -   [Dependencies](#dependencies)
-   [Usage](#usage)
    -   [JSON Renderer](#json-renderer)
        -   [Working with XML](#working-with-xml)
            -   [Arrays](#arrays)
            -   [Objects](#objects)
        -   [Dealing with Attributes](#dealing-with-attributes)
        -   [Headers](#headers)
            -   [X-API-Framework-Page-Renderer](#x-api-framework-page-renderer)
            -   [X-API-Framework-Render-Time](#x-api-framework-render-time)
            -   [X-API-Framework-Cache](#x-api-framework-cache)
            -   [X-API-Framework-Expired-Cache-Entries](#x-api-framework-expired-cache-entries)
    -   [Handling PUT, POST, PATCH and DELETE Requests](#handling-put-post-patch-and-delete-requests)
    -   [Validating with JSON Schema](#validating-with-json-schema)
    -   [Modifying rendered output with Transformers](#modifying-rendered-output-with-transformers)
        -   [@jsonForceArray](#jsonforcearray)
        -   [@convertEmptyElementsToString](#convertemptyelementstostring)
    -   [Writing Custom Transformers](#writing-custom-transformers)
    -   [Caching Page Output](#caching-page-output)
        -   [Removing Exired Cache Entries](#removing-exired-cache-entries)
-   [Support](#support)
-   [Contributing](#contributing)
-   [License](#license)

## Installation

This is an extension for Symphony CMS. Add it to your `/extensions` folder in your Symphony CMS installation, then enable it though the interface.

### Requirements

This extension requires PHP 7.3 or greater. For use with earlier version of PHP, please use v1.0.0 of this extension instead (`git clone -b1.0.0 https://github.com/pointybeard/api_framework.git`).

This extension also depends on the following Composer libraries:

-   [Symfony HTTP Foundation](https://github.com/symfony/http-foundation)
-   [JSON Schema for PHP](https://github.com/justinrainbow/json-schema)
-   [Symphony Section Class Mapper](https://github.com/pointybeard/symphony-classmapper)
-   [SymphonyCMS PDO Connector](https://github.com/pointybeard/symphony-pdo)
-   [PHP Helpers: Array Functions](https://github.com/pointybeard/helpers-functions-arrays)

Run `composer install` on the `extension/api_framework` directory to install all of these.

## Usage

This extension has two parts: The [JSON Renderer](#json-renderer), and the [Controller event](#handling-put-post-patch-and-delete-requests).

### JSON Renderer

Any page with a type `JSON` will trigger the new JSON renderer. This automatically converts page XML output into a JSON document (this includes output from any events).

#### Working with XML

The JSON renderer expects to get well formed XML data, which it then translates into JSON data. Although JSON is just as well structured as XML, XML does not translate directly to JSON. Here are a few examples of XML and how they translate into JSON.

##### Arrays

Arrays are formed when the same element is used and contains simple values (numbers or strings). Each element does not need to be directly after one another either, but it is best practise to group them in some way.

XML:

```xml
<data>
    <array>1</array>
    <array>2</array>
</data>
```

JSON:

```json
{
    "array": [ "1", "2" ]
}
```

##### Objects

Typically Symphony will be outputting objects (Entries, Sections etc). E.g.

```xml
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

```json
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

#### Dealing with Attributes

Since JSON does not have a concept of attributes in the same way XML does, all attributes are discarded to ensure a consistent result. Consequently, the field name `@attributes` is reserved and cannot be used.

#### Headers

The JSON renderer adds two new headers to the page output:

##### X-API-Framework-Page-Renderer

This allows you to see which page renderer was invoked. Currently there are only two possiblities: `JsonFrontendPage`, and `CacheableJsonFrontendPage`

##### X-API-Framework-Render-Time

This header displays how long the page took to render, in milliseconds (ms).

##### X-API-Framework-Cache

Will be `hit` (if a valid cache entry was located and is being used for rendering) or `miss` (if there was no valid cache entry located), in which case a cache entry will be created.

##### X-API-Framework-Expired-Cache-Entries

If cache cleanup hasn't been disabled, this will show the number of expired cache entries that were deleted (see [Removing Exired Cache Entries](#removing-exired-cache-entries) for more details).

### Handling PUT, POST, PATCH and DELETE Requests

Use the `API Framework: Controller` event to listen for PUT, POST, PATCH and DELETE requests. To create your own controller, make a folder called `controllers` in your `/workspace` directory.

A controller will respond to the 4 methods (PUT, POST, PATCH and DELETE) via a same named public method. E.g. to respond to a PUT request, create a method called 'put' in your controller like so

```php
public function put(Request $request, Response $response)
{
  ...
}
```

Modify `$response` to set return values and status code. E.g.:

```php
$response->setStatusCode(Response::HTTP_OK);
```

Lastly, call the render method (which is inherited) to generate the output. E.g.

```php
return $this->render($response, ['data' => 'some output']);
```

A controllers class and file name are the same. Each controller must sit in a folder path that matches your page path.

For example, if you had a page called "entry", and you wanted to provide PUT, POST, PATCH and DELETE functionality, name your controller class "ControllerEntry" and the file `ControllerEntry.php`. It should be placed in `/workspace/controllers`. If that same page was then a child of a page called "parent", you must create a new folder called `parent` inside `/workspace/controllers`. Place `ControllerEntry` in this new folder. Be sure to adjust its namespace accordingly.

Here is an example of a completed controller:

```php

namespace Symphony\ApiFramework\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symphony\Extensions\ApiFramework;
use Symphony\Extensions\ApiFrameworkAbstractController;
use Symphony\Extensions\ApiFramework\Traits;

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
        $someEntryId = (intApiFramework\JsonFrontend::instance()
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
```

### Validating with JSON Schema

Incoming request and output response JSON can be validated against a [JSON Schema](http://json-schema.org/) document. This allows validation of input before it gets to your controllers, removing the need for a lot of sanity checking code, and validation of controller output to ensure it is conforming to your API specifications.

**Note, JSON schema validation is not currently available for `GET` requests.**

To use JSON Schema validation, implement the `JsonSchemaValidationInterface` Interface and use the `Traits\hasEndpointSchemaTrait` trait in your controller. E.g.

```
namespace Symphony\ApiFramework\Controllers;

use Symphony\ApiFramework;

...

final class ControllerSchema extends ApiFramework\AuthenticatedAbstractController Implements ApiFramework\ApiFramework\Interfaces\JsonSchemaValidationInterface {

    use ApiFramework\ApiFramework\Traits\hasEndpointSchemaTrait;

```

The Interface `JsonSchemaValidationInterface` has two abstract methods: `schema()` and `validate()`. The Trait `hasEndpointSchemaTrait` implements these methods. It is also possible to implement both of these methods yourself.

Once the necessary libraries have been included, the Event controller will look for schemas by calling `->schema()` prior to rendering. It looks for the directory `workspace/schemas` and follows the same hierarchy as controllers (sub-folders exist for each level of the API). E.g. A schema for the endpoint `/order/item/` would need to reside in `workspace/schemas/Order`.

Schema files must be named using the format `[controller-name].[http-method].[request|response].json`. For example, if you have an endpoint controller called "Test", and you want to validate the output of a `PATCH` request, the name of the schema file would be `ControllerTest.patch.response.json`.

If validation fails, an exception is thrown which renders a 400 Bad Request response. It will look something like this:

```
HTTP/1.1 400 Bad Request
Date: Thu, 27 Sep 2018 03:28:06 GMT
Content-Type: application/json

{
    "status": 400,
    "error": [
        "[fruit] The property fruit is required"
    ],
    "message": "Validation failed. Errors where encountered while validating data against the supplied schema.",
    "validation": {
        "schema": "schemas/Test/ControllerTest.post.request.json",
        "input": {
            "animal": "lion"
        }
    }
}
```

### Modifying rendered output with Transformers

Prior to converting the XML into JSON, transformers are run. Transformers mutate the JSON result based on a test and action. The following built in transformers are available:

#### @jsonForceArray

This transformation will look for the attribute `jsonForceArray` on any XML elements. If it is set to "true", this transformation is applied. It relates to **[#issue-2](https://github.com/pointybeard/api_framework/issues/2)**. When there are multiple elements of the same name, for example 'entry', the JSON encode process will treat these as an array. E.g.

```xml
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


```json
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

However, if there is only a single 'entry' element, it is treated as an object. This is because internally it is just an associative array, not an indexed array of 'entry' objects. E.g.

```xml
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

```json
{
    "entries": {
        "entry": {
            "id": "1",
            "title": "An Entry",
        }
    }
}
```

Notice that 'entry' is a JSON object. The problem with this is inconsistent data and is a symptom of converting from XML to JSON using PHP's SimpleXML class. The solution is to set `jsonForceArray="true"` on the 'entry' element to trigger the transformation:

```xml
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

```json
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

*Note: `jsonForceArray="true"` should not be set if there is more than one entry otherwise the JSON result will contain unnecessary nesting. Use logic in the XSLT to omit or toggle this attribute when there is more than a single entry.*

#### @convertEmptyElementsToString

This transformation will look for the `convertEmptyElementsToString` attribute on all XML elements. Should the value within that element be empty, i.e. `<someElement></someElement>`, then the output will be an empty string rather than an empty array.

For example, XML such as this

```XML
<data>
    <entry>
        <name>My Entry</name>
        <optionalField></optionalField>
    </entry>
    <entry>
        <name>Another Entry</name>
        <optionalField>Yes</optionalField>
    </entry>
</data>
```

gets converted to JSON like this

```JSON
{
    "entry": [
        {
            "name": "My Entry",
            "optionalField": [],
        },
        {
            "name": "2",
            "optionalField": "Yes",
        }
    ]
}
```

Notice that `optionalField` in the first entry is an empty array (`[]`). If we set `convertEmptyElementsToString`, this will instead become an empty string (`""`). I.e.

```XML
<data>
    <entry convertEmptyElementsToString="true">
        <name>My Entry</name>
        <optionalField></optionalField>
    </entry>
    ...
</data>
```

instead becomes this

```JSON
{
    "entry": [
        {
            "name": "My Entry",
            "optionalField": "",
        },
        ...
    ]
}
```

### Writing Custom Transformers

This extension provides the delegate `APIFrameworkJSONRendererAppendTransformations` on all frontend pages with the `JSON` type. The context includes an instance of ApiFramework\Transformer`. Use the `append()` method to add your own transformations. E.g.

```php
use Symphony\Extensions\ApiFramework;

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
      new ApiFramework\Transformation(

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

### Caching Page Output

From version 1.0.0, it is possible to cache the output of GET requests. To do this, add a page type of `cacheable` to any page that requires caching. The length the cache remains valid can be set in `System > Preferences`.

Cache entries can be viewed by going to `System > Page Cache` in the Symphony admin menu.

#### Removing Exired Cache Entries

By default, every time a cacheable page is rendered, the system looks for expired cache entries and removes them. This can add overhead to a busy site with many cached pages so it can be disabled in `System > Preferences`.

It is possible to manually manage the page cache via `System > Page Cache` in the Symphony admin or via the terminal with the cache console command. e.g. `symphony api_framework cache clean` (requires the [Symphony Console Extension](https://github.com/pointybeard/console) to be installed).

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/api_framework/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/api_framework/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"RESTful API Framework for Symphony CMS" is released under the [MIT License](http://www.opensource.org/licenses/MIT).

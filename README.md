# RESTful API Framework Extension for Symphony CMS

[[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pointybeard/api_framework/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pointybeard/api_framework/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/pointybeard/api_framework/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/pointybeard/api_framework/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/pointybeard/api_framework/badges/build.png?b=master)](https://scrutinizer-ci.com/g/pointybeard/api_framework/build-status/master)

JSON renderer and HTTP method driven controller interface for [Symphony CMS][ext-Symphony CMS] which is designed to help rapidly prototype and build JSON based, RESTful, APIs.

-   [Installation](#installation)
-       [With Git and Composer](#with-git-and-composer)
-       [With Orchestra][#with-orchestra]
-   [Basic Usage](#basic-usage)
-   [About](#about)
    -   [Requirements](#dependencies)
    -   [Dependencies](#dependencies)
-   [Documentation](#documentation)
-   [Support](#support)
-   [Contributing](#contributing)
-   [License](#license)

## Installation

This is an extension for [Symphony CMS][ext-Symphony CMS]. Add it to the `/extensions` folder of your Symphony CMS installation, then enable it though the interface.

### With Git and Composer

```bash
$ git clone --depth 1 https://github.com/pointybeard/api_framework.git api_framework
$ composer update -vv --profile -d ./api_framework
```
After finishing the steps above, enable "API Framework" though the administration interface or, if using [Orchestra][ext-Orchestra], with `bin/extension enable api_framework`.

### With Orchestra

1. Add the following extension defintion to your `.orchestra/build.json` file in the `"extensions"` block:

```json
{
    "name": "api_framework",
    "repository": {
        "url": "https://github.com/pointybeard/api_framework.git"
    }
}
```

2. Run the following command to rebuild your Extensions

```bash
$ bin/orchestra build \
    --skip-import-sections \
    --database-skip-import-data \
    --database-skip-import-structure \
    --skip-create-author \
    --skip-seeders \
    --skip-git-reset \
    --skip-composer \
    --skip-postbuild
```

## Basic Usage

After installation, any page with a type of JSON will be rendered as JSON. Go to `Blueprints` > `Pages` and modify any page you wish to render as JSON by added the `JSON` page type to it. Viewing that page, you'll see that the `Content-Type` is now `application/json` and the contents is a valid JSON document.


## About

### Requirements

- This extension works with PHP 7.4 or above.
- The [Console Extension for Symphony CMS][req-console] must also be installed.

### Dependencies

This extension depends on the following Composer libraries:

-   [Symfony HTTP Foundation](https://github.com/symfony/http-foundation)
-   [JSON Schema for PHP](https://github.com/justinrainbow/json-schema)
-   [Symphony Section Class Mapper](https://github.com/pointybeard/symphony-classmapper)
-   [SymphonyCMS PDO Connector](https://github.com/pointybeard/symphony-pdo)
-   [PHP Helpers: Array Functions](https://github.com/pointybeard/helpers-functions-arrays)

## Documentation

Read the [full documentation here][ext-docs].

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker][ext-issues],
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing to this project][doc-CONTRIBUTING] documentation for guidelines about how to get involved.

## Author
-   Alannah Kearney - https://github.com/pointybeard
-   See also the list of [contributors][ext-contributor] who participated in this project

## License
"RESTful API Framework Extension for Symphony CMS" is released under the MIT License. See [LICENCE][doc-LICENCE] for details.

[doc-CONTRIBUTING]: https://github.com/pointybeard/api_framework/blob/master/CONTRIBUTING.md
[doc-LICENCE]: http://www.opensource.org/licenses/MIT
[req-console]: https://github.com/pointybeard/console
[ext-issues]: https://github.com/pointybeard/api_framework/issues
[ext-Symphony CMS]: http://getsymphony.com
[ext-Orchestra]: https://github.com/pointybeard/orchestra
[ext-contributor]: https://github.com/pointybeard/api_framework/contributors
[ext-docs]: https://github.com/pointybeard/api_framework/blob/master/.docs/toc.md

# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

#### Added
* Added extended FrontendPage class, `JsonFrontendPage` to handle json requests specifically.
* Added `JsonRequest` and `RequestJsonInvalidException`. They are used to grab the incoming json data. Also updated controller event to use `JsonRequest`.

#### Fixes
* Fixed the recursive method `recursiveApplyTransformationToArray()`. It had a logic bug that meant the result from lower levels was not propagated up the chain. Additionally, the `@attributes` array was not being dealt with consistently. The last transformer to run will trigger a cleanup which removes the `@attributes` array. (#15, #16, and #17)

#### Changed
* Updated controller event to pass Request object when calling execute method of a Controller object.
* Updated README for fixes #15 #16 and #17.
* Updated the json renderer to use `JsonFrontend` instead of the core Frontend class.
* Changed abstract method `execute()` to include a Request object, allowing controllers to manipulate the Request.
* Changed the way a controller path is discovered by using `current-page` and `parent-path` instead of `current-path`. (#14)
* Removed `boilerplate-xsl` feature. This is no longer required as `?debug` now works correctly. (#12)

## [0.2.1] - 2016-06-17
#### Added
- Added `JSON_UNESCAPED_SLASHES` to avoid unnecessary escaping of slashes in output. (#8)
- Added new abstract extension `AbstractApiException` which is used by `ControllerNotFoundException` and `MethodNotAllowedException`. Allows setting of HTTP response code to avoid every exception thrown appearing as a '500 Internal Server Error'

#### Changed
- Updated core controller event based on changes to `ControllerNotFoundException` and `MethodNotAllowedException`
- Updated `ControllerNotFoundException` and `MethodNotAllowedException` to extend the new `AbstractApiException` class
- Updated `ExceptionHandler` to check for overloaded http response code. Calls the method `getHttpStatusCode()` if it is available

#### Fixes
- Removed the use clause for Symphony as it is redundant and causes a PHP warning
- Using API Framework exception and error handlers instead of Symphony built in. (#9)

## [0.2.0] - 2016-05-03
#### Added
- Transformer and Transformation classes.
- Added APIFrameworkJSONRendererAppendTransformations delegate
- Added phpunit to composer require-dev
- Added unit tests for Transformation code
- Controller names are based on full page path (#5)
- Using PSR-4 folder structure for controllers. Controllers must have a namespace. (#7)

#### Fixed
- Checking that controller actually exists before trying to include it (#6)

#### Removed
- Symphony PDO is not longer a Composer requirement as it is not used

## [0.1.1] - 2016-04-25
#### Added
- Added CONTRIBUTING.md and CHANGELOG.md

#### Changed
- Improvements to the example controller code in README.md
- Code cleanup
- Improved README.md

#### Fixed
- Extension driver had include class name which meant could not install

## 0.1.0 - 2015-09-13
#### Added
- Initial release
- Added Symphony PDO as requirement

[Unreleased]: https://github.com/pointybeard/api_framework/compare/v0.2.1...integration
[0.2.1]: https://github.com/pointybeard/api_framework/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/pointybeard/api_framework/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/pointybeard/api_framework/compare/v0.1.0...v0.1.1

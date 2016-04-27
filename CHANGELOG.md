# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
#### Added
- Transformer and Transformation classes.
- Added APIFrameworkJSONRendererAppendTransformations delegate
- Added phpunit to composer require-dev
- Added unit tests for Transformation code

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

[Unreleased]: https://github.com/pointybeard/api_framework/compare/v0.1.1...master
[0.1.1]: https://github.com/pointybeard/api_framework/compare/v0.1.0...v0.1.1

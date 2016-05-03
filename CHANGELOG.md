# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
None

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

[Unreleased]: https://github.com/pointybeard/api_framework/compare/v0.2.0...integration
[0.1.2]: https://github.com/pointybeard/api_framework/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/pointybeard/api_framework/compare/v0.1.0...v0.1.1

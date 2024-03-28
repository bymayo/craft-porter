# Porter Changelog

## 1.0.4 - 2024-03-28
### Fixed
- Blank errors appearing when matching password rules

### Added
- Passwords changed in the CP now go through the password rules

## 1.0.3 - 2022-08-23
### Added
- `porter.php` config file example.

## 1.0.2 - 2022-08-23
### Added
- `craft.porter.deleteAccountFormProperties()` variable to get default delete account form template properties in Twig.
- `craft.porter.deactivateAccountFormProperties()` variable to get default deactivate account form template properties in Twig.
- `craft.porter.magicLinkFormProperties()` variable to get default magic link form template properties in Twig.

## 1.0.1 - 2022-08-23
### Fixed
- Array merge issue when adding custom properties to all forms (Thanks [@flo-bananzki[](https://github.com/bymayo/craft-porter/issues/3))
- Missing namespace (Thanks [@flo-bananzki[](https://github.com/bymayo/craft-porter/issues/2))
- `buttonLabel` should have been `buttonText` and wasn't overwriting the default options

## 1.0.0 - 2022-06-20
### Added
- Initial release

# Cart Rule Label

## About

Cart Rule Label is a Prestashop module for displaying cart rules labels in categories, below each product matching a featured cart rule. Following product selection types are currently supported:

- [x] products
- [x] categories
- [ ] attributes
- [ ] manufacturers
- [ ] suplliers

This module is currently used in production websites with Prestashop 1.6 and PHP 7+, but you may need to tweak some CSS and/or JS for your needs. The best way to make changes and still get updates is to create your own git branch and rebase/merge/cherry-pick new versions or specific commits.

## Installation

This module is best used with Composer managing your Prestashop project globally. This method follows best practices for managing external dependencies of a PHP project.

Create or edit `composer.json` in the Prestashop root directory:

```json
"repositories": [
  {
    "type": "git",
    "url": "https://github.com/creativewave/ps-cwcartrulelabel"
  },
  {
    "type": "git",
    "url": "https://github.com/creativewave/ps-module-configuration"
  }
],
"require": {
  "creativewave/ps-cwmedia": "^1"
},

```

Then run `composer update`.

## Todo

* Feature: support for other product selection types.
* Unit tests

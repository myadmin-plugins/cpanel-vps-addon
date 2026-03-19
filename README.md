# cPanel VPS Addon for MyAdmin

[![Tests](https://github.com/detain/myadmin-cpanel-vps-addon/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-cpanel-vps-addon/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-cpanel-vps-addon/version)](https://packagist.org/packages/detain/myadmin-cpanel-vps-addon)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-cpanel-vps-addon/downloads)](https://packagist.org/packages/detain/myadmin-cpanel-vps-addon)
[![License](https://poser.pugx.org/detain/myadmin-cpanel-vps-addon/license)](https://packagist.org/packages/detain/myadmin-cpanel-vps-addon)

A MyAdmin plugin that provides cPanel license management as a VPS addon. This package integrates with the MyAdmin hosting management platform to allow automated provisioning, activation, and deactivation of cPanel licenses on VPS instances.

## Features

- Automated cPanel license activation when a VPS addon is purchased
- Automated cPanel license deactivation on cancellation with admin email notification
- IP-based license status checking before activation/deactivation
- Integration with MyAdmin's event dispatcher for hook-based plugin loading
- Configurable addon cost via the MyAdmin settings interface

## Installation

Install via Composer:

```sh
composer require detain/myadmin-cpanel-vps-addon
```

The package registers itself automatically through the MyAdmin plugin system. The following event hooks are registered:

| Event                   | Handler            | Purpose                                    |
|-------------------------|--------------------|--------------------------------------------|
| `function.requirements` | `getRequirements`  | Registers the page requirement function     |
| `vps.load_addons`       | `getAddon`         | Registers the cPanel addon with the VPS module |
| `vps.settings`          | `getSettings`      | Adds the cPanel cost setting to the admin UI |

## Configuration

The addon cost is configurable through the MyAdmin admin panel under VPS module settings. The setting `VPS_CPANEL_COST` controls the price charged for the cPanel license addon.

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

To generate a coverage report:

```sh
vendor/bin/phpunit --coverage-html build/coverage
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) license.

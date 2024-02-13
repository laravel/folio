# Release Notes

## [Unreleased](https://github.com/laravel/folio/compare/v1.1.6...master)

## [v1.1.6](https://github.com/laravel/folio/compare/v1.1.5...v1.1.6) - 2024-02-12

* [1.x] Fixes routing when slug starts with `index` by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/folio/pull/133

## [v1.1.5](https://github.com/laravel/folio/compare/v1.1.4...v1.1.5) - 2023-12-12

* [1.x] Finish L11 support by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/folio/pull/126
* [1.x] Adds missing URI on `folio:list` command by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/folio/pull/127

## [v1.1.4](https://github.com/laravel/folio/compare/v1.1.3...v1.1.4) - 2023-10-18

- Fixed named routes resolution by [@zupolgec](https://github.com/zupolgec) in https://github.com/laravel/folio/pull/122

## [v1.1.3](https://github.com/laravel/folio/compare/v1.1.2...v1.1.3) - 2023-10-09

- Allows access to Pipeline via service container by [@inmanturbo](https://github.com/inmanturbo) in https://github.com/laravel/folio/pull/113
- Fixes Wildcard Directories Modifying State for Literal Views in the Base Folder by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/folio/pull/115

## [v1.1.2](https://github.com/laravel/folio/compare/v1.1.1...v1.1.2) - 2023-09-25

- Adds BelongTo and HasMany as the function return types by [@stewartmuhanuzi](https://github.com/stewartmuhanuzi) in https://github.com/laravel/folio/pull/109

## [v1.1.1](https://github.com/laravel/folio/compare/v1.1.0...v1.1.1) - 2023-09-08

- Fixes index views on nested model route bindings by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/folio/pull/108

## [v1.1.0](https://github.com/laravel/folio/compare/v1.0.0...v1.1.0) - 2023-08-30

- [1.x] Adds `render` function ([#100](https://github.com/laravel/folio/pull/100))

## [v1.0.0](https://github.com/laravel/folio/compare/v1.0.0-beta.7...v1.0.0) - 2023-08-23

- Stable release of Folio. For more information, please consult the [Folio documentation](https://laravel.com/docs/folio).

## [v1.0.0-beta.7](https://github.com/laravel/folio/compare/v1.0.0-beta.6...v1.0.0-beta.7) - 2023-08-23

- Adds `request()->routeIs(...)` support ([#93](https://github.com/laravel/folio/pull/93))

## [v1.0.0-beta.6](https://github.com/laravel/folio/compare/v1.0.0-beta.5...v1.0.0-beta.6) - 2023-08-16

- Adds route names to `folio:list` command ([#86](https://github.com/laravel/folio/pull/86))
- Fixes `route()` base uri, domain, and query parameters ([#88](https://github.com/laravel/folio/pull/88))

## [v1.0.0-beta.5](https://github.com/laravel/folio/compare/v1.0.0-beta.4...v1.0.0-beta.5) - 2023-08-15

- Adds `name` function ([#79](https://github.com/laravel/folio/pull/79))
- Improves testbench development workflow ([#74](https://github.com/laravel/folio/pull/74))
- Improves `folio:install` command ([#78](https://github.com/laravel/folio/pull/78))
- Fixes potencial collision with facade names ([#84](https://github.com/laravel/folio/pull/84))

## [v1.0.0-beta.4](https://github.com/laravel/folio/compare/v1.0.0-beta.3...v1.0.0-beta.4) - 2023-08-08

- Fixes `route:cache` command by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/folio/pull/76

## [v1.0.0-beta.3](https://github.com/laravel/folio/compare/v1.0.0-beta.2...v1.0.0-beta.3) - 2023-08-07

### Added

- Domain support ([#62](https://github.com/laravel/folio/pull/62))
- Terminable middleware support ([#66](https://github.com/laravel/folio/pull/66))
- Multiple Folio mounted paths support ([#67](https://github.com/laravel/folio/pull/67))

## [v1.0.0-beta.2](https://github.com/laravel/folio/compare/v1.0.0-beta.1...v1.0.0-beta.2) - 2023-07-31

### Added

- `ViewMatched` event ([#34](https://github.com/laravel/folio/pull/34))

### Fixed

- Windows support ([#47](https://github.com/laravel/folio/pull/47), [#46](https://github.com/laravel/folio/pull/46))

## v1.0.0-beta.1 - 2023-07-25

First pre-release.

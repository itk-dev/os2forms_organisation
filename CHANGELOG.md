<!-- markdownlint-disable MD024 -->
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic
Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Updated element formatting to display subelement title.
- Preselected `funktion` if only one is present.
- Added message to edit element explaining where data comes from.
- Aligned search element with other elements.
- Gave `search`-elements stylish overhaul
- Made all elements except selector readonly.
- Renamed `organisation_funktionsnavn` to `stillingsbetegnelse`.
- Upgraded to use [Organisation API](https://github.com/itk-dev/os2forms_organisation_api).
- Limited search option to `navn`.
- Fetched only manager employments when using `manager of user` data fetching method.

## [1.3.3] 2023-10-18

- Limited validation on `search_submit` and `search_user_apply` buttons.

## [1.3.2] 2023-10-09

### Fix

- Show loading indicator on all search results
- Made searching indicator text value translatable
- Ensure drupal states are run when updating form values with JavaScript.
  (<https://github.com/itk-dev/os2forms_organisation/pull/17>)

## [1.3.1] 2023-09-29

### Fix

- Fixed loading indicators

## [1.3.0] 2023-08-24

### Updated

- Updated `drush/drush` requirement
  (<https://github.com/itk-dev/os2forms_organisation/pull/8>)

### Added

- Added loading indicators on buttons for search and selection of search results
  (<https://github.com/itk-dev/os2forms_organisation/pull/11>)
- Appended wildcard to search queries
  (<https://github.com/itk-dev/os2forms_organisation/pull/12>)

## [1.2.0] 2023-08-03

- Added `OrganisationUserIdEvent` allowing other modules to set organisation
  user id. (<https://github.com/itk-dev/os2forms_organisation/pull/9>)

### Changed

## [1.1.1] 2023-05-04

- Fixed search
  (<https://github.com/itk-dev/os2forms_organisation/pull/7>)

## [1.1.0] 2023-05-02

### Added

- [FORMS-674](https://jira.itkdev.dk/browse/FORMS-674)
  Search for people

## [1.0.0] 2023-02-08

### Added

- Added OS2Forms organisation module
- Added OS2Forms Organisation OpenID Connect module

[Unreleased]: https://github.com/itk-dev/os2forms_organisation/compare/1.3.3...HEAD
[1.3.3]: https://github.com/itk-dev/os2forms_organisation/compare/1.3.2...1.3.3
[1.3.2]: https://github.com/itk-dev/os2forms_organisation/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/itk-dev/os2forms_organisation/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/itk-dev/os2forms_organisation/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/itk-dev/os2forms_organisation/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/itk-dev/os2forms_organisation/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/itk-dev/os2forms_organisation/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/itk-dev/os2forms_organisation/releases/tag/1.0.0

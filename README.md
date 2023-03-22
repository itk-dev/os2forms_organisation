# OS2Forms organisation

## Installation

```sh
composer require os2forms/os2forms_organisation
drush pm:enable os2forms_organisation
```

Edit settings on `/admin/os2forms_organisation/settings`.

**NOTE** This module creates and uses a user field called `field_organisation_user_id`.
It is up to the using party to ensure this field is set to the
[SF1500](https://digitaliseringskataloget.dk/integration/sf1500)
organisation "bruger" ID. If it is not, nothing is displayed.
Consider using [OS2Forms Organisation OpenID Connect](modules/os2forms_organisation_openid_connect/README.md)
for setting this user field if using OpenID Connect.

## Drush commands

```sh
drush os2forms_organisation:lookup --help
```

## Coding standards

```sh
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php8.1-fpm:latest composer install
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php8.1-fpm:latest composer coding-standards-check

docker run --rm --interactive --tty --volume ${PWD}:/app node:18 yarn --cwd /app install
docker run --rm --interactive --tty --volume ${PWD}:/app node:18 yarn --cwd /app coding-standards-check
```

## Code analysis

```sh
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php8.1-fpm:latest composer install
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php8.1-fpm:latest composer code-analysis
```

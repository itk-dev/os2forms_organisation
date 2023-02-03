# OS2Forms organisation

## Installation

```sh
composer require os2forms/os2forms_organisation
drush pm:enable os2forms_organisation
```

Edit settings on `/admin/os2forms_organisation/settings`.

## Drush commands

```sh
drush os2forms-organisation:lookup --help
```

## Coding standards

```sh
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php7.4-fpm:latest composer install
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php7.4-fpm:latest composer coding-standards-check

docker run --rm --interactive --tty --volume ${PWD}:/app node:18 yarn --cwd /app install
docker run --rm --interactive --tty --volume ${PWD}:/app node:18 yarn --cwd /app coding-standards-check
```

## Code analysis

```sh
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php7.4-fpm:latest composer install
docker run --rm --interactive --tty --volume ${PWD}:/app itkdev/php7.4-fpm:latest composer code-analysis
```

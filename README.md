# OS2Forms organisation

## Installation

```sh
composer require os2forms/os2forms_organisation
drush pm:enable os2forms_organisation
```

Configure organisation API endpoint on `/admin/os2forms_organisation/settings`.

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

## Development

We use [DDEV Drupal Contrib](https://github.com/ddev/ddev-drupal-contrib) for development[^1].

[^1]: The DDEV environment has been created by running

      ```shell
      ddev config --project-type=drupal11 --docroot=web
      ddev dotenv set .ddev/.env.web --drupal-core '^11.4.10'
      ```

Start the show by running

```shell
task ddev:start
```

Run `task` to see other useful development tasks.

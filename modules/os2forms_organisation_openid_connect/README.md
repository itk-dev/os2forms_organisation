# OS2Forms Organisation OpenID Connect

Sets the user field `Organisation user ID` used
in OS2Forms Organisation via OpenID Connect claims.

Subscribes to `OrganisationUserIdEvent` and attempts
setting id from user field, if it has not already been set.

## Installation

Enable the module:

```sh
drush pm:enable os2forms_organisation_openid_connect
```

Configure the OpenID Connect `Organisation user ID` User claim
mapping found at `/admin/config/people/openid-connect/settings`.

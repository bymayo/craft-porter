# Password Expiry

Forces a password reset once a password reaches a certain age. Enable under **Settings → Porter → Password Policy**.

## Settings

| Setting | Default | Description |
|---|---|---|
| Password Expiry | Off | The master switch |
| Expires after | `90 days` | Amount plus period — days, weeks, months or years |
| Warning window | `7` | Days before expiry that the warning email is sent |
| Front End Redirect | — | Where front-end users with an expired password are sent |

Admins and exempt groups are skipped. See [Exemptions](password-policy.md#exemptions).

## Running it

Expiry does nothing on its own. The command sends warning emails, then flags expired passwords for reset:

```sh
php craft porter/passwords/retention
```

Run it daily from cron. See [Scheduled tasks](../guides/scheduled-tasks.md).

Two narrower commands exist if you want to separate the steps:

```sh
php craft porter/passwords/warn-expiring   # emails only
php craft porter/passwords/force-reset     # flags only
```

## From the control panel

**Utilities → Porter** shows how many passwords are past their expiry and how many are expiring within the warning window, with a button to flag the expired ones immediately rather than waiting for cron.

The button needs the **Force reset expired passwords** permission.

## What users see

A flagged user is asked to set a new password the next time they sign in — Craft's own behaviour for `passwordResetRequired`. On the front end, set **Front End Redirect** to send them somewhere of your choosing.

Two emails go with this, both off by default: **Password Expiring Soon** and **Password Expired**. See [Email Notifications](email-notifications.md).

## Twig

```twig
{{ craft.porter.passwordExpired }}        {# true / false #}
{{ craft.porter.passwordExpiresAt }}      {# DateTime or null #}
{{ craft.porter.passwordExpiresInDays }}  {# integer or null #}
```

See [Twig](../templating/twig.md#password-expiry).

# Magic Link

Sign in with a link emailed to the user's inbox, instead of a password. Enable under **Settings → Porter → Login**.

## Front end

Switch on **Front End Login** and drop the form into a template:

```twig
{{ craft.porter.magicLinkForm() }}
```

The user enters their address, gets a link, and clicking it signs them in. The form only renders for guests. See [Form components](../templating/form-components.md#magic-link) for the properties you can override.

## Control panel

Switch on **Control Panel Access** and a **Sign in with a magic link** button appears on the control panel login screen, alongside the passkey option. It has its own sign in page that mirrors Craft's forgot-password screen.

## Dead links

An expired, used, or refused link redirects to the sign in screen — Craft's `loginPath` on the front end, Porter's own screen in the control panel. The message renders inside `magicLinkForm()`, so put the form on your login template to show it.

## Registering new users

Switch on **Register New Users** and an unrecognised address creates the account rather than failing. The same form becomes sign up and sign in, and no password is ever chosen.

Craft's own public registration must be on under **Settings → Users**. Porter won't create accounts on a site that has deliberately turned it off.

The account is created **pending** and the link activates it, so a sign up nobody confirms never leaves a live account behind. It gets a random password the user never sees. New users are added to the groups picked under **Add New Users To**, and land on **New User Redirect** rather than the usual one.

Unconfirmed sign ups stay pending until Craft's garbage collection clears them, which is off by default. Switch it on in `config/general.php`:

```php
'purgePendingUsersDuration' => 1209600, // 14 days
```

## Pending accounts

A magic link activates a pending account. Someone who registered through Craft but never clicked the activation email can finish through the link instead of being stuck.

## Settings

| Setting | Default | Description |
|---|---|---|
| Magic Link | Off | The master switch |
| Control Panel Access | Off | Adds the button to the control panel login screen |
| Front End Login | Off | Lets `magicLinkForm()` render |
| Expiry Timeframe | `300` | Seconds before a link expires |
| Redirect | `/` | Where users land after requesting a link |
| Register New Users | Off | Create the account when the address isn't recognised |
| Add New Users To | — | Groups new accounts are put in |
| New User Redirect | `/` | Where brand new accounts land |

Three more are config-file only. See [Config file](../guides/config-file.md).

| Setting | Default | Description |
|---|---|---|
| `magicLinkThrottleLimit` | `5` | Links one address may request per window, `0` for no cap |
| `magicLinkThrottleWindow` | `900` | The throttle window, in seconds |
| `magicLinkMinResponseMs` | `500` | Floor for how long a request takes to answer, `0` to disable |

## What a link can't do

> ⚠️ Admins cannot use magic links. Nor can accounts that are suspended, locked, inactive, flagged for a password reset, or using two-step verification, since a link can't present a second factor.

These are checked twice: when the link is created, and again when it's used, so an account suspended in the meantime is still refused.

## Security

Tokens are stored as a SHA-256 digest, so a copy of your database is not a set of usable sign in links. Requests are capped per address, and answer identically — same message, same time — whether or not the address has an account. See [How Porter protects your sign in forms](../guides/security.md).

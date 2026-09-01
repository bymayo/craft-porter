<img src="https://github.com/bymayo/craft-porter/blob/craft-5/resources/icon.png" width="60">

# Porter for Craft CMS 5

Porter is a Craft CMS plugin that is the missing toolbox for all things users.

<img src="https://raw.githubusercontent.com/bymayo/craft-porter/craft-5/resources/screenshot.png" width="850">

## Features

- [Delete Account](#delete-account)
    - Front-end form for users to delete their own account
    - Customisable confirmation keyword (e.g. `DELETE`) or user field
- [Deactivate Account](#deactivate-account)
    - Front-end form for users to deactivate their own account
- [Inactive Account Cleanup](#inactive-account-cleanup)
    - Warn users by email after a configurable period of inactivity
    - Delete accounts that don't return after a longer threshold
    - Admins and users with control panel access are always skipped
- [Magic Link](#magic-link)
    - Sign in via a link emailed to the user's inbox
    - Front end and/or control panel login, with configurable expiry
- [Block Burner / Disposable Emails](#block-burner--disposable-emails)
    - 22,000+ disposable email domains blocked
    - Domain validity, syntax and MX record checks
- [Password Policy](#password-policy)
    - Configurable min/max length, plus lower case, upper case, numeric and symbol requirements
    - Have I Been Pwned breach checking, using k-Anonymity
    - Live password strength indicator for the control panel and the front end
    - Blocklist for the user's own details, the site name, and your own banned words
    - Password history, so old passwords can't be reused
    - Password expiry with forced resets, warning emails, and a control panel utility
    - Admin and user group exemptions
- [Email Notifications](#email-notifications)
    - Transactional emails for key account events (sign in, password change, suspension, deletion, etc.)
    - Each email can be toggled on/off in the plugin settings
    - All email content is editable under `Settings > System Messages`

## Install

- Install with Composer: `composer require bymayo/porter`
- Enable the plugin under `Settings > Plugins`
- Configure under `Settings > Porter`

You can also install via the Plugin Store by searching for `Porter`.

## Requirements

- Craft CMS 5.x
- PHP 8.2
- MySQL (no PostgreSQL support)

## How to use

The Delete Account, Deactivate Account and Magic Link forms all work the same way: drop the Twig helper into a template, optionally pass a properties hash to override the defaults, or copy the full form template into your project for total control.

### Delete Account

Let users delete their own account from the front end. Enable under `Settings > Porter > Account` and pick a confirmation keyword or user field.

```twig
{{ craft.porter.deleteAccountForm() }}
```

Classes are prefixed with `porter__` by default. Override any property:

```twig
{{ craft.porter.deleteAccountForm({
    buttonClass: 'bg-black text-white hover:bg-white hover:text-black',
    buttonText: 'Delete Account',
}) }}
```

| Property | Default | Description |
|---|---|---|
| `redirect` | Plugin redirect setting | Where users are sent afterwards |
| `confirmationClass` | `porter__confirmation` | Class for the confirmation field |
| `alertClass` | `porter__alert` | Class for the success/error flash |
| `fieldClass` | `porter__field` | Class for the input |
| `fieldContainerClass` | `porter__field-container` | Class for the field wrapper |
| `fieldLabelClass` | `porter__field-label` | Class for the input label |
| `buttonClass` | `porter__button` | Class for the submit button |
| `buttonText` | `Delete Account` | Submit button text |

For full markup control, copy `bymayo/porter/src/templates/components/deleteAccountForm.twig` into your project. From Twig you can read the defaults via `craft.porter.deleteAccountFormProperties()`:

```twig
{% set formProperties = craft.porter.deleteAccountFormProperties() %}

{{ formProperties.buttonText }}
```

> ⚠️ Users need the "Delete Users" permission on themselves or their user group.

> ⚠️ Admins cannot delete their own account.

### Deactivate Account

Let users deactivate their own account from the front end. Enable under `Settings > Porter > Account`.

```twig
{{ craft.porter.deactivateAccountForm() }}
```

```twig
{{ craft.porter.deactivateAccountForm({
    buttonClass: 'bg-black text-white hover:bg-white hover:text-black',
    buttonText: 'Deactivate Account',
}) }}
```

| Property | Default | Description |
|---|---|---|
| `redirect` | Plugin redirect setting | Where users are sent afterwards |
| `alertClass` | `porter__alert` | Class for the success/error flash |
| `buttonClass` | `porter__button` | Class for the submit button |
| `buttonText` | `Deactivate Account` | Submit button text |

For full markup control, copy `bymayo/porter/src/templates/components/deactivateAccountForm.twig`. Defaults are available via `craft.porter.deactivateAccountFormProperties()`.

> ⚠️ Admins cannot deactivate their own account.

### Magic Link

Sign in via a link emailed to the user's inbox. Enable under `Settings > Porter > Login`.

```twig
{{ craft.porter.magicLinkForm() }}
```

```twig
{{ craft.porter.magicLinkForm({
    buttonClass: 'bg-black text-white hover:bg-white hover:text-black',
    buttonText: 'Send Magic Link',
}) }}
```

| Property | Default | Description |
|---|---|---|
| `redirect` | Plugin redirect setting | Where users are sent afterwards |
| `alertClass` | `porter__alert` | Class for the success/error flash |
| `fieldClass` | `porter__field` | Class for the email input |
| `fieldContainerClass` | `porter__field-container` | Class for the field wrapper |
| `fieldLabelClass` | `porter__field-label` | Class for the input label |
| `buttonClass` | `porter__button` | Class for the submit button |
| `buttonText` | `Send Magic Link` | Submit button text |

For full markup control, copy `bymayo/porter/src/templates/components/magicLinkForm.twig`. Defaults are available via `craft.porter.magicLinkFormProperties()`.

Switch on `Control Panel Access` and a `Sign in with a magic link` button appears on the control panel login screen, alongside the passkey option.

> ⚠️ Admins cannot use magic links. Nor can accounts that are suspended, locked, pending verification, flagged for a password reset, or using two-step verification, since a link can't present a second factor.

### Inactive Account Cleanup

Warn users who haven't signed in for a while, then delete them if they don't return. Enable under `Settings > Porter > Account`. Runs from cron:

```
0 3 * * * cd /path/to/site && php craft porter/users/cleanup-inactive
```

Each run emails users past the reminder threshold (once per inactive period, and signing back in resets it), then deletes accounts past the delete threshold. Deleted users get the `Account Deleted` notification if it's enabled.

Accounts are soft deleted, so they land in Craft's trash and can be restored from `Users` with the status filter set to `Trashed`. Craft purges them once they're older than [`softDeleteDuration`](https://craftcms.com/docs/5.x/reference/config/general.html#softdeleteduration), 30 days by default.

> ⚠️ Admins and anyone with control panel access are always skipped, as are users with no `lastLoginDate`.

### Block Burner / Disposable Emails

Block disposable and invalid emails at sign up. Enable under `Settings > Porter > Email` and add a free API key from <https://verifier.meetchopra.com/>.

### Password Policy

Enforce password rules everywhere a password is set: the control panel, front-end registration and set-password forms, the console and queue jobs. Configure under `Settings > Porter > Password Policy`.

Each feature has its own switch and works on its own, so you can run breach checking without enforcing symbols, or password history without a length rule. Rules go through Craft's own validation, so errors render inline in the control panel and are readable from `user.getErrors('newPassword')` in your own templates.

#### Settings

| Setting | Default | Description |
|---|---|---|
| `passwordConfirm` | `false` | Require a matching confirmation field |
| `passwordForcePolicy` | `false` | Enforce the length and character rules below |
| `passwordForcePolicyMin` | `8` | Minimum length. Craft won't go below 6 |
| `passwordForcePolicyMax` | `0` | Maximum length. `0` for no limit; Craft caps at 160 |
| `passwordForcePolicyRules` | `null` | Any of `lowercase`, `uppercase`, `numeric`, `symbol` |
| `passwordStrengthIndicator` | `false` | Show the strength meter and checklist |
| `passwordStrengthMinScore` | `0` | Reject passwords below this score. `0` doesn't enforce, otherwise `1`–`4` |
| `passwordCspNonce` | `false` | Stamp a nonce on the indicator's inline script, for sites running a CSP |
| `passwordPwned` | `false` | Reject passwords found in a known data breach |
| `passwordPwnedFailMode` | `'open'` | What to do if the breach API is unreachable. `open` allows the password, `closed` rejects it |
| `passwordBlocklist` | `false` | Reject passwords containing the blocked words |
| `passwordBlocklistSources` | `['userDetails', 'siteName', 'substitutions']` | Any of `userDetails`, `siteName`, `substitutions` (character swaps, so `password` also blocks `P4ssw0rd`) |
| `passwordBlocklistWords` | `null` | Your own banned words, one per line or comma separated |
| `passwordHistory` | `false` | Stop users reusing old passwords |
| `passwordHistoryCount` | `5` | How many previous passwords to remember, 1 to 24 |
| `passwordExpiry` | `false` | Force a reset once a password reaches a certain age |
| `passwordExpiryAmount` | `90` | |
| `passwordExpiryPeriod` | `'days'` | `days`, `weeks`, `months` or `years` |
| `passwordExpiryWarningDays` | `7` | How far ahead to send the "expires soon" email |
| `passwordExpiryFrontEndRedirect` | `null` | Where front end users with an expired password are sent. Blank to handle it yourself |
| `passwordExemptAdmins` | `false` | Exempt admins from everything on this page |
| `passwordExemptGroups` | `null` | User group UIDs to exempt from everything on this page |

#### Confirm Password

Makes users type their new password twice, so a typo can't be saved unnoticed. Craft's own set password screen and the control panel get the second field automatically. On your own forms, add:

```twig
<input type="password" name="confirmPassword">
```

Only enforced on posted forms, so console commands and queue jobs are unaffected.

#### Have I Been Pwned

Uses the [Pwned Passwords](https://haveibeenpwned.com/Passwords) k-Anonymity API, so only the first five characters of the password's SHA-1 hash leave your server, never the password itself. If the API can't be reached the password is allowed through, so an outage can't stop people changing their password. Set `passwordPwnedFailMode` to `closed` to reject instead.

#### Strength Indicator

Switch it on and password fields get a live meter and requirements checklist, in the control panel and on Craft's own set-password screen. Set a `Minimum Strength` to also reject anything below that score.

For the front end:

```twig
<input type="password" id="newPassword" name="newPassword">

{{ craft.porter.passwordStrengthIndicator() }}
```

| Property | Default | Description |
|---|---|---|
| `field` | `newPassword` | The `id` (or `name`) of the input to watch |
| `containerClass` | `porter__password-strength` | Class for the container |
| `showRules` | `true` | Whether to render the requirements checklist |

**Content Security Policy.** The indicator uses a small inline script, which a strict CSP will block. Switch on `passwordCspNonce` and include the same nonce in your header:

```twig
{% header "Content-Security-Policy: script-src 'self' 'nonce-#{craft.porter.cspNonce}'" %}
```

#### Password History

Remembers previous password hashes so they can't be set again. Only passwords changed *after* you switch it on are remembered, so nobody is locked out of the one they're already using.

#### Password Expiry

Flags users for a reset once their password reaches the configured age. Craft then blocks their next sign in and emails them a reset link, so this covers front-end users as well as control panel ones. Run it from cron:

```
0 4 * * * cd /path/to/site && php craft porter/passwords/retention
```

`retention` sends the warnings then force resets. `warn-expiring` and `force-reset` run each half on its own. All take `--dry-run` and `--verbose`; the two that reset also take `--queue` and `--include-never-changed`.

There's also a `Password Retention` utility in the control panel, behind the `Force reset expired passwords` permission.

> ⚠️ Admins are included by default. A flagged admin isn't locked out, since Craft emails them a reset link, but you can exempt them under `Exemptions`.

#### Twig

| Twig | Returns |
|---|---|
| `craft.porter.passwordPolicy()` | The active rules as an array of `{ key, label, ... }` |
| `craft.porter.passwordPolicyMessage()` | The rules as one readable sentence |
| `craft.porter.passwordStrength(password)` | A score from `0` (very weak) to `4` (strong) |
| `craft.porter.cspNonce` | The nonce stamped on the indicator script, for your CSP header |
| `craft.porter.passwordExpired` | Whether the current user's password has expired |
| `craft.porter.passwordExpiresAt` | When it expires, or `null` |
| `craft.porter.passwordExpiresInDays` | Whole days until it expires, or `null` |

### Email Notifications

Send transactional emails on key user events. Toggle each one under `Settings > Porter > Notifications`. Email content (heading, subject, body) is editable under `Settings > System Messages`.

| Email | Sent when |
|---|---|
| Welcome | A user's account is activated. |
| New Device Login Detected | A user signs in from a new IP or device. |
| Password Changed | A user's password is changed. |
| Email Address Changed | A user's email is changed (sent to the previous address). |
| Account Suspended | A user's account is suspended. |
| Account Restored | A suspended account is restored. |
| Account Deactivated | A user's account is deactivated. |
| Account Deleted | A user's account is deleted. |
| Failed Login Attempts | Consecutive failed sign in attempts cross the configured threshold. |
| Inactive Account Reminder | A user hasn’t signed in for a while, before their account is deleted. |
| Password Expiring Soon | A user's password is about to expire. |
| Password Expired | A user's password has expired and a reset is required. |

#### Porter's HTML email template

Porter ships with a clean, responsive HTML email layout. Craft expects the template inside your project's `templates/` folder, so copy it across once:

1. Copy the file:

   ```bash
   mkdir -p templates/_emails
   cp vendor/bymayo/porter/src/templates/email/_layout.twig templates/_emails/layout.twig
   ```

2. In the control panel, go to `Settings → Email` and set **HTML Email Template** to `_emails/layout`.
3. Send a test from `Settings → Email → Test` to verify it renders.

Tweak the copy (logo, colours, footer) to match your brand — Porter won't overwrite it on plugin updates.

> ⚠️ **New Device Login Detected — behind a proxy?**
>
> If your site sits behind a reverse proxy, load balancer or CDN, set the following in `config/general.php` so Craft picks up the real client IP:
>
> ```php
> 'trustedHosts' => ['any'], // or restrict to your proxy's CIDR ranges
> 'secureHeaders' => ['X-Forwarded-For', 'X-Forwarded-Host', 'X-Forwarded-Proto'],
> ```

## Support

If you have any issues (Surely not!) then I'll aim to reply to these as soon as possible. If it's a site-breaking-oh-no-what-has-happened moment, then hit me up on the Craft CMS Discord - @bymayo

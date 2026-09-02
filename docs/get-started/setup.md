# Setup

Every Porter feature is off by default, so installing it changes nothing until you switch something on. Settings live under **Settings → Porter**, split across five tabs.

| Tab | What's in it |
|---|---|
| **Account** | [Delete Account](../features/delete-account.md), [Deactivate Account](../features/deactivate-account.md), [Inactive Account Cleanup](../features/inactive-accounts.md) |
| **Login** | [Magic Link](../features/magic-link.md), failed login attempt threshold |
| **Email** | [Burner & Disposable Emails](../features/burner-emails.md) |
| **Password Policy** | [Password Policy](../features/password-policy.md) and [Password Expiry](../features/password-expiry.md) |
| **Notifications** | [Email Notifications](../features/email-notifications.md) |

## A sensible starting point

If you're not sure where to begin, these three cost nothing and need no templates:

1. **Password Policy → Length & Characters.** Sets a minimum length above Craft's own floor of six.
2. **Password Policy → Breach Check.** Rejects passwords found in a known data breach.
3. **Email → Block disposable and undeliverable emails.** Blocks throwaway addresses at sign up.

Everything else — the front-end forms, expiry, cleanup — needs either a template change or a cron job.

## Front-end features need a template

[Delete Account](../features/delete-account.md), [Deactivate Account](../features/deactivate-account.md) and [Magic Link](../features/magic-link.md) render forms into your own templates. Switching the setting on isn't enough on its own; you also need the Twig tag. See [Form components](../templating/form-components.md).

## Scheduled features need cron

[Password Expiry](../features/password-expiry.md) and [Inactive Account Cleanup](../features/inactive-accounts.md) do nothing until their command runs. See [Scheduled tasks](../guides/scheduled-tasks.md).

## Settings in a config file

Anything on the settings page can be set in `config/porter.php` instead, which makes it environment-aware and takes it out of the control panel. See [Config file](../guides/config-file.md).

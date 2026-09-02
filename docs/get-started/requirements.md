# Requirements

Porter requires:

- **Craft CMS 5.0+**
- **PHP 8.2+**
- **MySQL** — PostgreSQL isn't currently supported

No other plugins or services are needed. Nothing requires an API key or an account.

## Optional

| Feature | Needs |
|---|---|
| [Breach checking](../features/password-policy.md#breach-check) | Outbound HTTPS to `api.pwnedpasswords.com` |
| [Burner email blocking](../features/burner-emails.md) | Outbound HTTPS once, to download the domain list |
| [Magic link registration](../features/magic-link.md#registering-new-users) | Craft's own public registration switched on |
| [Password expiry](../features/password-expiry.md) and [inactive cleanup](../features/inactive-accounts.md) | A cron job — see [Scheduled tasks](../guides/scheduled-tasks.md) |

Porter never sends a password anywhere. The breach check sends the first five characters of a hash and nothing else.

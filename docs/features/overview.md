# Features overview

Everything Porter does, at a glance. Each item links to a deeper page.

- **Magic Link** — passwordless sign in via an emailed link, on the front end and the control panel. Optional passwordless sign up, so an unrecognised address creates the account. Tokens are hashed at rest, single-use and expiring, requests are throttled, and the form gives the same answer either way so it can't be used to find out who has an account. See [Magic Link](magic-link.md).
- **Password Policy** — minimum and maximum length, character requirements, a live strength indicator, Have I Been Pwned breach checking, a blocklist built from the user's own details, password history, and a confirm-password field. Admins and chosen groups can be exempt. See [Password Policy](password-policy.md).
- **Password Expiry** — force a reset after a set age, warn people before it happens, and act on it from the control panel or cron. See [Password Expiry](password-expiry.md).
- **Burner & Disposable Emails** — blocks 75,000+ known disposable domains, plus syntax and MX checks. No API key, no account, and the check never leaves your server. See [Burner & Disposable Emails](burner-emails.md).
- **Delete Account** — a front-end form for users to delete their own account, behind a typed confirmation. See [Delete Account](delete-account.md).
- **Deactivate Account** — a front-end form for users to switch their own account off without deleting it. See [Deactivate Account](deactivate-account.md).
- **Inactive Account Cleanup** — email people who haven't signed in for a while, then delete them if they don't come back. See [Inactive Account Cleanup](inactive-accounts.md).
- **Email Notifications** — thirteen transactional emails for account events, each toggled separately and edited in Craft's own System Messages. See [Email Notifications](email-notifications.md).
- **Twig helpers** — read the policy, score a password, check expiry dates, and render any of the forms. See [Twig](../templating/twig.md).
- **Porter utility** — the disposable domain list and password retention counts, with buttons to act on both. Under **Utilities → Porter**.

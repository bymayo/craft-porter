# Email Notifications

Transactional emails for account events. Enable under **Settings → Porter → Notifications**. Each one is toggled separately and all are off by default.

## The emails

### Account

| Email | Sent when |
|---|---|
| Welcome | An account is activated |
| Email Address Changed | Sent to the *previous* address when the email changes |
| Account Suspended | An account is suspended |
| Account Unsuspended | A suspended account is restored |
| Account Deactivated | An account is deactivated |
| Account Deleted | An account is deleted |
| Inactive Account Reminder | Someone hasn't signed in for a while, before deletion |

### Security

| Email | Sent when |
|---|---|
| New Device Login | A sign in comes from a device that hasn't been seen before |
| Failed Login Attempts | Failed attempts reach the threshold, `3` by default |

### Password

| Email | Sent when |
|---|---|
| Password Changed | A user's password changes |
| Password Expiring Soon | Before a password expires, so they can change it first |
| Password Expired | A password expires and a reset is required |

## Editing the content

All content is editable under **Settings → System Messages**, alongside Craft's own emails. Subject, heading and body for each.

## The HTML template

Porter ships an HTML email layout that renders the Markdown body from System Messages. To use your own, copy `bymayo/porter/src/templates/email/_layout.twig` into your project and edit it there.

## New device detection

The **New Device Login** email compares a hash of the IP address and user agent against the last sign in. The raw address is never stored — only a SHA-256 hash.

It's a coarse signal: a new browser, a new phone, or a changed IP all count as a new device.

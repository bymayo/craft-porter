# Inactive Account Cleanup

Emails people who haven't signed in for a while, then deletes them if they don't come back. Enable under **Settings → Porter → Account**.

## Settings

| Setting | Default | Description |
|---|---|---|
| Inactive Account Cleanup | Off | The master switch |
| Send reminder after | `365` | Days of inactivity before the warning email |
| Delete after | `395` | Days of inactivity before the account is deleted |

The gap between the two is how long someone has to come back after being warned.

## Running it

Nothing happens until the command runs:

```sh
php craft porter/users/cleanup-inactive
```

Run it daily from cron. See [Scheduled tasks](../guides/scheduled-tasks.md).

## What gets skipped

Admins and anyone with control panel access are always skipped, whatever the thresholds say. The reminder is only sent once per period of inactivity — signing in resets the clock.

## Deletion is recoverable

Accounts are soft deleted, so they go to Craft's trash and can be restored until garbage collection purges them — 30 days by default, per `softDeleteDuration`.

## Email

**Inactive Account Reminder** is the warning, and **Account Deleted** fires when the account goes. Both are off by default. See [Email Notifications](email-notifications.md).

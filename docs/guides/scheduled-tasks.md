# Scheduled tasks

Three Porter features do nothing until their command runs. Everything else works without cron.

| Command | Feature | Suggested |
|---|---|---|
| `porter/passwords/retention` | [Password Expiry](../features/password-expiry.md) | Daily |
| `porter/users/cleanup-inactive` | [Inactive Account Cleanup](../features/inactive-accounts.md) | Daily |
| `porter/burner-emails/update` | [Burner & Disposable Emails](../features/burner-emails.md) | Daily |

## A full crontab

```sh
# Warn about expiring passwords, then force resets on expired ones
0 2 * * * cd /path/to/site && php craft porter/passwords/retention

# Remind inactive users, then delete the ones who didn't return
0 3 * * * cd /path/to/site && php craft porter/users/cleanup-inactive

# Refresh the disposable email domain list
0 4 * * * cd /path/to/site && php craft porter/burner-emails/update
```

Stagger the times rather than running them together, so a slow run doesn't overlap the next.

## The individual commands

### Password retention

```sh
php craft porter/passwords/retention
```

Runs both steps in order: emails users whose password is about to expire, then flags expired passwords for reset. Split them if you'd rather:

```sh
php craft porter/passwords/warn-expiring
php craft porter/passwords/force-reset
```

#### Options

| Option | Applies to | Description |
|---|---|---|
| `--dry-run` | all | Report what would happen without doing it |
| `--verbose` | all | Print each affected user rather than just the totals |
| `--queue` | `retention`, `force-reset` | Push the resets to Craft's queue instead of running inline |
| `--include-never-changed` | `retention`, `force-reset` | Also treat users who have **never** changed their password as expired |

`--include-never-changed` is worth knowing about. By default only passwords with a known age are expired, so an account created long ago whose password was never changed is left alone. Turn it on to sweep those too.

With `--queue`, resets run in batches, so a large user base won't time out.

### Inactive account cleanup

```sh
php craft porter/users/cleanup-inactive
```

Reports how many reminders were sent and how many accounts were deleted. Admins and users with control panel access are always skipped.

### Disposable domain list

```sh
php craft porter/burner-emails/update
```

Downloads the current list. A failed or implausibly short download is discarded rather than replacing a good list, so a bad night doesn't leave you unprotected.

## Without cron

[Password Expiry](../features/password-expiry.md) and the [domain list](../features/burner-emails.md) can both be run by hand from **Utilities → Porter**. Inactive account cleanup is command-line only, since it deletes accounts.

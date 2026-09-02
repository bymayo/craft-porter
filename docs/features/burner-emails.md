# Burner & Disposable Emails

Blocks throwaway and undeliverable addresses at sign up. Enable under **Settings → Porter → Email**. One switch, no API key, no account.

## What it checks

| Check | Catches |
|---|---|
| Syntax | Malformed addresses |
| Domain list | 75,000+ known disposable domains |
| MX lookup | Domains that can't receive mail |

The check only runs when an address is new or changed, so editing a user in the control panel doesn't trigger it.

## The domain list

The list lives at `storage/porter/disposable-domains.txt`. It's downloaded once, when you switch the feature on, and read from disk after that — so checking an address never leaves your server and there's nothing to install.

It comes from [disposable/disposable-email-domains](https://github.com/disposable/disposable-email-domains) (MIT), which is regenerated daily.

## Keeping it current

New disposable domains appear constantly, so an old list gets less effective over time.

From the control panel, go to **Utilities → Porter** and click **Update List**. It shows the domain count and when it was last updated.

Or from cron:

```sh
0 4 * * * cd /path/to/site && php craft porter/burner-emails/update
```

A download that fails, or returns a suspiciously short list, is discarded rather than replacing a good one.

## Un-blocking a domain

If the list gets a domain wrong, remove that line from `storage/porter/disposable-domains.txt`. Note that the next update will pull it back, so it's worth also opening an issue [upstream](https://github.com/disposable/disposable/issues) so it's fixed for everyone.

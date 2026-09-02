# Password Policy

Rules applied wherever a password is set: the control panel, Craft's set-password screen, front-end registration and front-end password changes. Enable under **Settings → Porter → Password Policy**.

Each section has its own switch, so you can use one part without the rest.

## Length & characters

Sets a minimum and maximum length, and which character types are required.

| Setting | Default | Description |
|---|---|---|
| Minimum | `8` | Can't be lower than 6, which is Craft's own minimum |
| Maximum | `0` | `0` for no limit. Craft caps at 160 |
| Required characters | none | Lower case, upper case, numeric, symbol |

Porter's minimum replaces Craft's six-character rule rather than sitting alongside it, so users get one error, not two.

## Confirm password

Requires the password to be typed twice. Added automatically to Craft's set-password screen and the control panel. For your own forms, add a field named `confirmPassword`.

The check runs server-side, so it holds even if the browser is bypassed.

## Strength indicator

A live meter and checklist, shown as the user types. Works on the control panel, Craft's set-password screen and the front end.

```twig
{{ craft.porter.passwordStrengthIndicator() }}
```

Set **Minimum Strength** to reject anything weaker than the level you pick. Leave it at *Don't enforce* to show the meter without blocking.

Scoring is entropy based — length against the size of the character pool used — and the browser and the server score identically, so the meter never disagrees with the error message.

> Rules the browser can't check — breach checking, history and the blocklist — are marked as checked on save rather than live.

## Breach check

Rejects passwords found in a known data breach, using [Have I Been Pwned](https://haveibeenpwned.com/).

Only the first five characters of the password's SHA-1 hash are sent, and the comparison happens on your server. The password never leaves. The request also sends a padding header, so response size gives nothing away.

`passwordPwnedFailMode` decides what happens when the service is unreachable: `open` (the default) accepts the password, `closed` rejects it. Config file only.

## Blocklist

Rejects passwords containing things that are easy to guess for this particular user.

| Source | Blocks |
|---|---|
| `userDetails` | Their own username, name and email |
| `siteName` | Your site's name |
| `substitutions` | Also catches swaps like `P4ssw0rd` |

Add your own banned words under **Blocked Words**, one per line.

## Password history

Stops old passwords being reused. Set how many to remember, from 1 to 24.

Previous passwords are stored as bcrypt hashes, never in the clear.

## Exemptions

Admins and chosen user groups can be excluded from everything on this tab, including [expiry](password-expiry.md). Both default to off, so by default the policy applies to everyone.

## Twig

The rules are readable from templates, so you can show them before someone starts typing. See [Twig](../templating/twig.md#password-policy).

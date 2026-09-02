# How Porter protects your sign in forms

Porter touches authentication, so a few decisions are worth stating plainly — both so you know what you're getting, and so you know what you still have to do yourself.

## Magic link tokens

Tokens are 64 random characters, and only their **SHA-256 digest** is stored. A copy of your database — a dump, a backup, a leak — is not a set of usable sign in links.

Each token is single-use and expires after **Expiry Timeframe** (300 seconds by default). Requesting a new link invalidates any outstanding one for that user.

## User enumeration

A sign in form that says "no account with that address" tells an attacker which addresses are worth attacking. Porter's magic link form gives the **same answer either way**:

- The same message, whether or not the address exists.
- The same response time, whether or not an email was actually sent.

Sending an email is much slower than not sending one, and that difference alone is enough to tell the two apart. Porter holds every response back to a floor — `magicLinkMinResponseMs`, 500ms by default — so the two paths look identical from outside. On a site with a slow mail server, raise it.

Only genuinely malformed input gets a different answer, since that reveals nothing.

## Mail bombing

Without a cap, a public form that emails an address on demand is a way to flood someone's inbox. Magic link requests are capped per address — `magicLinkThrottleLimit`, 5 per `magicLinkThrottleWindow` of 900 seconds.

Hitting the cap returns the success response, not an error, since "too many requests" would confirm the address is worth hammering.

## What a magic link refuses

A link is checked when it's created **and** again when it's used, so an account suspended in between is still refused. Refused: admins, and accounts that are suspended, locked, inactive, flagged for a password reset, or using two-step verification.

Two-step verification is the important one — a link can't present a second factor, so allowing it would step around 2FA entirely.

## Passwords

- The breach check sends the first five characters of a SHA-1 hash and nothing else. The password never leaves your server. See [Breach check](../features/password-policy.md#breach-check).
- Password history is stored as bcrypt hashes, never in the clear.
- The confirm-password check runs server-side, so bypassing the browser doesn't bypass it.
- The strength meter scores identically in the browser and on the server, so it can't tell a user something the server will disagree with.

## Disposable email checking

The domain list is a file on your server. Checking an address performs no outbound request and sends nothing to a third party.

## What Porter doesn't do

- **It isn't rate limiting for password logins.** Craft handles invalid login counts and lockouts; Porter's throttle only covers magic link requests.
- **It isn't two-factor authentication.** Craft handles 2FA itself, including on the front end from Craft 5.6.
- **It doesn't stop a determined attacker measuring your mail server.** The response floor bounds the common case; a mail send slower than the floor still shows.

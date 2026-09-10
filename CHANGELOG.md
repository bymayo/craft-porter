# Porter Changelog

## 5.3.6 - 2026-09-10

### Fixed
- Expired, used or refused magic links returned a 404. They now redirect to the sign in screen, where the message is shown ([#16](https://github.com/bymayo/craft-porter/issues/16))

## 5.3.5 - 2026-09-01

> [!WARNING]
> Fixes a privilege escalation. On sites with `allowAdminChanges` on, any logged-in user could change Porter's settings. Review yours after upgrading.

### Security
- Plugin settings could be saved by any logged-in user, including front-end members. `porter/settings/save` only required a POST, and assigned posted values unsafely. It now requires an admin in the control panel, and only accepts known settings. Sites with `allowAdminChanges` off were not writable, as Craft refuses the change
- The settings page was readable by any logged-in user, exposing every configured value. It now requires an admin in the control panel. This applied whatever `allowAdminChanges` was set to
- Removed a redundant `allowAnonymous` from the delete account controller. Not exploitable, as the action only acts on the signed-in user

### Changed
- The `Porter` utility uses its own icon

## 5.3.4 - 2026-09-01

> [!WARNING]
> Burner email blocking no longer uses the Verifier API, and `emailsBurnersVerifierApiKey` has been removed. The check now runs against a domain list on your own server instead of a third-party service.

### Added
- Burner email blocking works without an API key or an account. A list of 75,000+ disposable domains is downloaded when you switch the feature on, alongside syntax and MX checks ([#13](https://github.com/bymayo/craft-porter/issues/13))
- `porter/burner-emails/update` refreshes the list from [disposable/disposable-email-domains](https://github.com/disposable/disposable-email-domains), which is regenerated daily
- A button in the `Porter` utility to update the list without the command line

### Changed
- Checking an address no longer leaves the server. The domain list is a file in `storage/porter/`, read from disk
- `Password Retention` and the disposable domain list are now sections of a single `Porter` utility, rather than a utility each

### Removed
- The Verifier API and its `emailsBurnersVerifierApiKey` setting. The service was unreliable, sign up was reportedly impossible, and no equivalent is free and sustainable

### Fixed
- Burner email checking failed closed. If the Verifier API was unreachable or the key was rejected, every address was refused and the site quietly stopped accepting registrations
- Burner email checking ran on every user save, so editing any user in the control panel sent their address to a third party. It only runs when the address is new or changed

## 5.3.3 - 2026-09-01

### Security
- Magic link tokens are now stored as a SHA-256 digest. A copy of the database is no longer a set of usable sign in links. Links already sent keep working
- Magic link requests are capped per email address, so the form can't be used to mail bomb someone. Set `magicLinkThrottleLimit` and `magicLinkThrottleWindow` in `config/porter.php`
- Requesting a link now answers the same way whether or not the address has an account, so the form can no longer be used to find out who has one
- Requests also take the same time to answer either way. Sending an email is slower than not sending one, and the difference was enough to tell the two apart. Tune with `magicLinkMinResponseMs` in `config/porter.php`

### Changed
- A magic link now activates a pending account. Someone who registered but never clicked Craft's activation email can finish through the link instead of being stuck
- Magic link registration creates the account as pending, and the link activates it. A sign up nobody confirms no longer leaves a live account behind. Set `purgePendingUsersDuration` in `config/general.php` to have Craft clear them away, as it defaults to off

## 5.3.2 - 2026-09-01

### Added
- Magic link registration, so an unrecognised email creates the account and signs the user in. No password is ever chosen ([#7](https://github.com/bymayo/craft-porter/issues/7))
- `New User Redirect`, where brand new accounts land. Override it per form with `newUserRedirect`

## 5.3.1 - 2026-08-28

> [!WARNING]
> Inactive Account Cleanup now deletes accounts instead of deactivating them, and `inactiveAccountDeactivateDays` is now `inactiveAccountDeleteDays`. Check your threshold after upgrading.

### Added
- Confirm password field, so a typo can't be saved unnoticed. Added automatically to Craft's set password screen and the control panel; add a `confirmPassword` field to your own forms
- `Sign in with a magic link` on the control panel login screen, with its own sign in page

### Changed
- Magic links now run the same checks as a normal sign in. Suspended, locked, pending and password-reset-required accounts are refused, as are accounts using two-step verification, which a link can't satisfy
- Inactive Account Cleanup deletes accounts rather than deactivating them. They go to the trash and can be restored for 30 days
- The reminder email says the account will be deleted, and `Account Deleted` fires instead of `Account Deactivated`

## 5.3.0 - 2026-08-28

### Added
- Password Policy settings tab, with each feature switched on separately
- Have I Been Pwned checking, so passwords found in a data breach are rejected
- Password strength indicator for the control panel, the set password screen and the front end, with an optional minimum strength
- Blocklist, to reject passwords containing the user's own details, the site name or your own banned words
- Password history, so old passwords can't be reused
- Password expiry, with `porter/passwords/retention` to run from cron and a `Password Retention` utility
- `Password Expiring Soon` and `Password Expired` emails
- Exemptions for admins and chosen user groups
- Twig helpers for the strength indicator, the rules, password strength and expiry dates
- `Force reset expired passwords` permission

### Changed
- Password errors now appear inline on the field, and work in the console and queue
- Porter's minimum length replaces Craft's six character rule instead of sitting alongside it
- The `Email & Password` tab is now `Email`, with passwords moved to a new `Password Policy` tab
- All four `Redirect` settings share the same label, instructions and placeholder

### Fixed
- Static analysis wasn't running, due to a typo in `phpstan.neon`

## 5.2.2 - 2026-05-01

### Fixed
- Deactivate account form now returns proper JSON responses when called via AJAX

## 5.2.1 - 2026-05-01

### Added
- Inactive Account Reminder email and `porter/users/cleanup-inactive` console command (defaults: warn after 365 days of inactivity, deactivate after 395)
- Reactivating a user automatically clears their `lastLoginDate`, so the cleanup gives them a fresh inactivity clock

### Changed
- Settings tabs reorganised, with section headings and action-style toggle labels

## 5.2.0 - 2026-05-01

> [!WARNING]
> The legacy `Send Confirmation Email` toggles and their system messages have been removed. Enable `Account Deleted` and `Account Deactivated` under `Porter > Notifications` to keep sending confirmation emails, and re-apply any customisations to `porter_account_deleted_email` / `porter_account_deactivated_email`.

### Added
- Welcome email when a user’s account is activated
- New Device Login email when a sign in is detected from a new IP or user agent
- Password Changed email
- Email Address Changed email, sent to the user’s previous address
- Account Suspended and Account Restored emails
- Account Deactivated and Account Deleted emails, fired on any path
- Failed Login Attempts email when failures cross a configurable threshold (default 3)
- Responsive HTML email layout at `src/templates/email/_layout.twig`
- System messages for each notification, editable under `Settings > System Messages`
- `porter_user_logins` table tracking the last known IP/UA hash per user

### Removed
- `deleteAccountConfirmationEmail` / `deactivateAccountConfirmationEmail` settings
- `porter_delete_account_confirmation_email` / `porter_deactivate_account_confirmation_email` system messages

### Fixed
- Template-level `redirect` override on the deactivate account form was ignored on submit

## 5.1.3 - 2026-02-22

### Fixed
- Delete account and magic link forms now return proper JSON responses when called via AJAX ([#11](https://github.com/bymayo/craft-porter/pull/11))
- Error when enabling password policy but not selecting any rules ([#8](https://github.com/bymayo/craft-porter/issues/8))
- Password policy and email validation errors now also output as flash messages ([#9](https://github.com/bymayo/craft-porter/issues/9))

## 5.1.2 - 2026-02-22

### Changed
- Removed leftover debug code and unused imports

## 5.1.1 - 2026-02-22

### Fixed
- Magic link JSON response incorrectly returned success when it failed
- Password policy checks were running each rule twice unnecessarily
- Delete account confirmation field could error if the field name was invalid

### Changed
- Logging now uses Craft's built-in Monolog logger instead of a custom log file

## 5.1.0 - 2026-02-22

> [!WARNING]
> If you copied the deactivate account template into your project, you'll need to update it to use a `<form>` with a POST request instead of a plain `<a>` link. See `deactivateAccountForm.twig` for the updated markup.

### Fixed
- Password maximum length rule was never being applied
- Magic link request could error if the email address didn't belong to any user
- Magic link could error if the user account was deleted after the link was sent
- Deactivate account action was not protected against cross-site request forgery (CSRF)
- Email verifier API errors were being output directly instead of being logged
- Email verifier API could hang indefinitely if the service was unresponsive
- Email verifier could error if the service returned an unexpected response

### Changed
- Deactivate account form now uses a proper form submission instead of a plain link
- Added a database index on magic link tokens for faster lookups

## 5.0.4 - 2024-02-14
### Fixed
- Magic link not working for users without CP access, if CP control panel access setting was disabled (Thanks [@StuartMcD[](https://github.com/bymayo/craft-porter/issues/10))

## 5.0.3 - 2024-07-23
### Fixed
- Magic link expiring in certain timezones (Thanks [@RobinWissink[](https://github.com/bymayo/craft-porter/issues/5))

## 5.0.2 - 2024-07-11
### Fixed
- If password field is blank when saving a user in the CP, skip password validation

## 5.0.1 - 2024-05-30
### Changed
- Icon to a new shiny (literally) icon

## 5.0.0 - 2024-05-30
### Changed
- Craft 5 compatibility

## 1.0.4 - 2024-03-28
### Fixed
- Blank errors appearing when matching password rules

### Added
- Passwords changed in the CP now go through the password rules

## 1.0.3 - 2022-08-23
### Added
- `porter.php` config file example.

## 1.0.2 - 2022-08-23
### Added
- `craft.porter.deleteAccountFormProperties()` variable to get default delete account form template properties in Twig.
- `craft.porter.deactivateAccountFormProperties()` variable to get default deactivate account form template properties in Twig.
- `craft.porter.magicLinkFormProperties()` variable to get default magic link form template properties in Twig.

## 1.0.1 - 2022-08-23
### Fixed
- Array merge issue when adding custom properties to all forms (Thanks [@flo-bananzki[](https://github.com/bymayo/craft-porter/issues/3))
- Missing namespace (Thanks [@flo-bananzki[](https://github.com/bymayo/craft-porter/issues/2))
- `buttonLabel` should have been `buttonText` and wasn't overwriting the default options

## 1.0.0 - 2022-06-20
### Added
- Initial release

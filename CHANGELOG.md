# Porter Changelog

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

# Config file

Every Porter setting can live in `config/porter.php` instead of the control panel. Copy the template out of the plugin to get started:

```sh
cp vendor/bymayo/porter/src/config.php config/porter.php
```

Like Craft's own config files, it's multi-environment aware:

```php
return [
    '*' => [
        'passwordForcePolicy' => true,
        'passwordForcePolicyMin' => 12,
    ],
    'dev' => [
        'emailWelcome' => false,
    ],
];
```

Anything set here overrides the control panel and is shown as read-only there, which is a good way to lock a policy down in code.

## Config-only settings

Four settings are deliberately not on the settings page. They're the "you know if you need this" kind, and leaving them out keeps the control panel readable.

| Setting | Default | Description |
|---|---|---|
| `passwordPwnedFailMode` | `open` | What happens when the breach service is unreachable. `open` accepts, `closed` rejects |
| `magicLinkThrottleLimit` | `5` | Magic links one address may request per window, `0` for no cap |
| `magicLinkThrottleWindow` | `900` | The throttle window, in seconds |
| `magicLinkMinResponseMs` | `500` | Floor for how long a magic link request takes to answer, `0` to disable |

## Related Craft settings

Some Porter behaviour depends on Craft's own config:

| Setting | Where | Why it matters |
|---|---|---|
| `allowPublicRegistration` | Settings → Users | [Magic link registration](../features/magic-link.md#registering-new-users) won't create accounts without it |
| `purgePendingUsersDuration` | `config/general.php` | Clears unconfirmed magic link sign ups. Off by default |
| `softDeleteDuration` | `config/general.php` | How long deleted accounts stay restorable. 30 days by default |

# Twig

Everything Porter exposes to templates, under `craft.porter`.

Form components have their own page — see [Form components](form-components.md).

## Settings

```twig
{% set settings = craft.porter.settings() %}
{% if settings.magicLink %}…{% endif %}
```

Returns the settings model, so you can branch on what's enabled.

## Password policy

```twig
{{ craft.porter.passwordPolicy() }}
```

Returns the active rules as an array of descriptors, each with a `key` and a `label`. Useful for rendering your own checklist:

```twig
<ul>
    {% for rule in craft.porter.passwordPolicy() %}
        <li>{{ rule.label }}</li>
    {% endfor %}
</ul>
```

For the same thing as a sentence:

```twig
{{ craft.porter.passwordPolicyMessage() }}
```

## Password strength

```twig
{{ craft.porter.passwordStrength('correct horse battery staple') }}
```

Returns the score for a given password, using the same entropy calculation as the indicator and the server-side check.

## Strength indicator

```twig
{{ craft.porter.passwordStrengthIndicator() }}
```

Renders the live meter and checklist. Pass a hash to override the defaults:

```twig
{{ craft.porter.passwordStrengthIndicator({
    field: 'newPassword',
    containerClass: 'porter__password-strength',
    showRules: true
}) }}
```

| Property | Default | Description |
|---|---|---|
| `field` | `newPassword` | The `id` of the password input to watch |
| `containerClass` | `porter__password-strength` | Class for the wrapper |
| `showRules` | `true` | Whether to render the rule checklist |

Defaults are available via `craft.porter.passwordStrengthIndicatorProperties()`.

## Password expiry

```twig
{{ craft.porter.passwordExpired }}        {# true / false #}
{{ craft.porter.passwordExpiresAt }}      {# DateTime or null #}
{{ craft.porter.passwordExpiresInDays }}  {# integer or null #}
```

All three default to the signed-in user, or take a user:

```twig
{{ craft.porter.passwordExpiresInDays(someUser) }}
```

They return empty when [expiry](../features/password-expiry.md) is off, or when the user is exempt.

## Disposable domain list

```twig
{% set list = craft.porter.burnerEmailList() %}
{{ list.count }}    {# number of domains #}
{{ list.updated }}  {# timestamp, or null #}
```

## CSP nonce

```twig
{{ craft.porter.cspNonce() }}
```

Returns the nonce Porter uses on its inline scripts, when **Add a CSP nonce** is enabled.

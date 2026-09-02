# Form components

Porter ships three front-end forms. Each is a single Twig tag, renders only when its feature is enabled, and accepts a hash of properties to override the defaults.

Classes are prefixed `porter__` so they're easy to target or replace.

```twig
{{ craft.porter.magicLinkForm({
    buttonClass: 'bg-black text-white hover:bg-white hover:text-black',
    buttonText: 'Send me a link',
}) }}
```

## Magic link

```twig
{{ craft.porter.magicLinkForm() }}
```

Renders for guests only. See [Magic Link](../features/magic-link.md).

| Property | Default | Description |
|---|---|---|
| `redirect` | Plugin setting | Where users go after requesting a link |
| `newUserRedirect` | Plugin setting | Where brand new accounts go, when registration is on |
| `alertClass` | `porter__alert` | Class for the success/error flash |
| `fieldClass` | `porter__field` | Class for the email input |
| `fieldContainerClass` | `porter__field-container` | Class for the field wrapper |
| `fieldLabelClass` | `porter__field-label` | Class for the input label |
| `buttonClass` | `porter__button` | Class for the submit button |
| `buttonText` | `Send Magic Link` | Submit button text |

## Delete account

```twig
{{ craft.porter.deleteAccountForm() }}
```

Renders for signed-in users only. See [Delete Account](../features/delete-account.md).

| Property | Default | Description |
|---|---|---|
| `redirect` | Plugin setting | Where users go afterwards |
| `confirmation` | Plugin setting | The value that must be typed to confirm |
| `confirmationClass` | `porter__confirmation` | Class for the confirmation field |
| `alertClass` | `porter__alert` | Class for the success/error flash |
| `fieldClass` | `porter__field` | Class for the input |
| `fieldContainerClass` | `porter__field-container` | Class for the field wrapper |
| `fieldLabelClass` | `porter__field-label` | Class for the input label |
| `buttonClass` | `porter__button` | Class for the submit button |
| `buttonText` | `Delete Account` | Submit button text |

## Deactivate account

```twig
{{ craft.porter.deactivateAccountForm() }}
```

Renders for signed-in users only. See [Deactivate Account](../features/deactivate-account.md).

| Property | Default | Description |
|---|---|---|
| `redirect` | Plugin setting | Where users go afterwards |
| `alertClass` | `porter__alert` | Class for the success/error flash |
| `buttonClass` | `porter__button` | Class for the submit button |
| `buttonText` | `Deactivate Account` | Submit button text |

## Reading the defaults

Each form exposes its defaults, which is handy when writing your own markup:

```twig
{{ dump(craft.porter.magicLinkFormProperties()) }}
{{ dump(craft.porter.deleteAccountFormProperties()) }}
{{ dump(craft.porter.deactivateAccountFormProperties()) }}
```

## Full markup control

If the properties aren't enough, copy the template into your own project and render it yourself:

```
bymayo/porter/src/templates/components/magicLinkForm.twig
bymayo/porter/src/templates/components/deleteAccountForm.twig
bymayo/porter/src/templates/components/deactivateAccountForm.twig
```

Keep the hidden inputs — the action, the CSRF token, and the hashed redirects — or the post won't validate.

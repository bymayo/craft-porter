# Deactivate Account

A front-end form letting users switch their own account off without deleting it. Enable under **Settings → Porter → Account**.

```twig
{{ craft.porter.deactivateAccountForm() }}
```

The form only renders for a signed-in user. See [Form components](../templating/form-components.md#deactivate-account) for the properties you can override.

## Deactivate or delete?

Deactivating keeps everything — the account, its content, its email address — but the user can't sign in. An admin can reactivate them from the control panel at any point.

Use [Delete Account](delete-account.md) instead if the user should be removed rather than paused.

## Reactivating

When an account is reactivated, Porter clears its last login date so the [inactive account clock](inactive-accounts.md) starts fresh rather than counting from before the account was switched off.

> ⚠️ Admins cannot deactivate their own account through this form.

## Email

**Account Deactivated** fires if enabled. See [Email Notifications](email-notifications.md).

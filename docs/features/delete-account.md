# Delete Account

A front-end form letting users delete their own account. Enable under **Settings → Porter → Account**.

```twig
{{ craft.porter.deleteAccountForm() }}
```

The form only renders for a signed-in user. See [Form components](../templating/form-components.md#delete-account) for the properties you can override.

## Confirmation

Users have to type something to confirm, so it can't happen by accident. Two options:

| Type | The user types |
|---|---|
| **Keyword** | A word you choose, `DELETE` by default |
| **User field** | The value of one of their own fields, for example their email address |

## What happens

The account is soft deleted, so it goes to Craft's trash and can be restored until garbage collection purges it — 30 days by default, per `softDeleteDuration`. The user is signed out afterwards.

## Permissions

The signed-in user needs Craft's **Delete users** permission.

> ⚠️ Admins cannot delete their own account through this form.

## Email

**Account Deleted** fires if enabled. See [Email Notifications](email-notifications.md).

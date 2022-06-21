<img src="https://github.com/bymayo/craft-porter/blob/craft-4/resources/icon.png" width="60">

# Porter for Craft CMS 4

Porter is a Craft CMS plugin that is the missing toolbox for all things users.

<img src="https://raw.githubusercontent.com/bymayo/craft-porter/craft-4/resources/screenshot.png" width="850">

## Features

- [Delete Account](#delete-account)
    - Allow users to delete their account on the front end
    - Optionally send a email confirmation of account deletion
    - Customisable "keyword" the user needs to type e.g. DELETE or a user field
- [Deactivate Account](#deactivate-account) 
    - Allow users to deactivate their account on the front end
    - Optionally send a email confirmation of account deactivation
    - Deactivated users can optionally be deleted after X days
- [Magic Link](#magic-link)
    - Let's users sign in via a link sent to their email inbox
    - Allow front end and/or control panel login via a link
    - Set expiry timeframe for increased security
- [Block Burner / Disposable Emails](#block-burner--disposable-emails)
    - Validate from 22,000+ disposable emails
    - Checks if a domain is valid and exists.
    - Checks email syntax
    - Checks the existence of MX records by querying the DNS records
- Password Policy
    - Minimum and maximum password lengths
    - Lower case (a-z) and upper case rules (A-Z)
    - Numeric character rules (0-9)
    - Symbol rules (@,#,$ etc)

## Install

-  Install with Composer via `composer require bymayo/porter` from your project directory
-  Enable / Install the plugin in the Craft Control Panel under `Settings > Plugins`
-  Customise the plugin settings

You can also install the plugin via the Plugin Store in the Craft Admin CP by searching for `Porter`.

## Requirements

- Craft CMS 4.x
- PHP 8.1
- MySQL (No PostgreSQL support)

## How to use

### Delete Account

Allow users to delete their own account via a front end form. To enable this go to `Settings > Porter` and toggle the `Delete Account` field. Also edit the settings such as what confirmation keyword the user needs to type before their account is deleted.

The quickest way to add the form to your template is with using the `deleteAccountForm` method:

```
{{ craft.porter.deleteAccountForm() }}
```

By default it will add a set of classes prefixed with `porter__`, but you can add customisation parameters so you can change the styling, for example if your using Tailwind CSS:

```
{{ craft.porter.deleteAccountForm(
    {
        buttonClass: 'bg-black text-white hover:bg-white hover:text-black',
        buttonText: 'Delete Account'
    }
) }}
```

<table>
<tr>
<td><strong>Property</strong></td>
<td><strong>Default</strong></td>
<td><strong>Description</strong></td>
</tr>
<tr>
<td>redirect</td>
<td>Plugin redirect setting</td>
<td>When the users account is deleted, this is where they'll be redirected to.</td>
</tr>
<tr>
<td>confirmationClass</td>
<td>porter__confirmation</td>
<td>Class for the confirmation keyword</td>
</tr>
<tr>
<td>alertClass</td>
<td>porter__alert</td>
<td>Class for the flash that shows when a user account is deleted.</td>
</tr>
<tr>
<td>fieldClass</td>
<td>porter__field</td>
<td>Class for the input field.</td>
</tr>
<tr>
<td>fieldContainerClass</td>
<td>porter__field-container</td>
<td>Class for div that wraps label and input field.</td>
</tr>
<tr>
<td>fieldLabelClass</td>
<td>porter__field-label</td>
<td>Class for the input label.</td>
</tr>
<tr>
<td>buttonClass</td>
<td>porter__button</td>
<td>Class for the button.</td>
</tr>
<tr>
<td>buttonText</td>
<td>Delete Account</td>
<td>Text that appears in the button</td>
</tr>
</table>

If you want to have more control you can get the full template from `bymayo/porter/src/templates/components/deleteAccountForm.twig`

> ⚠️ Users will only be able to delete their own accounts if the permission setting "Delete Users" is enabled on the user, or user group.

> ⚠️ Admin users CANNOT delete their own accounts for security reasons

### Deactivate Account

Allow users to deactivate their own account via a front end form. To enable this go to `Settings > Porter` and toggle the `Deactivate Account` field.

The quickest way to add the form to your template is with using the `deactivateAccountForm` method:

```
{{ craft.porter.deactivateAccountForm() }}
```

By default it will add a set of classes prefixed with `porter__`, but you can add customisation parameters so you can change the styling, for example if your using Tailwind CSS:

```
{{ craft.porter.deactivateAccountForm(
    {
        buttonClass: 'bg-black text-white hover:bg-white hover:text-black',
        buttonText: 'Deactivate Account'
    }
) }}
```

<table>
<tr>
<td><strong>Property</strong></td>
<td><strong>Default</strong></td>
<td><strong>Description</strong></td>
</tr>
<tr>
<td>redirect</td>
<td>Plugin redirect setting</td>
<td>When the users account is deactivated, this is where they'll be redirected to.</td>
</tr>
<tr>
<td>alertClass</td>
<td>porter__alert</td>
<td>Class for the flash that shows when a user account is deleted.</td>
</tr>
<tr>
<td>buttonClass</td>
<td>porter__button</td>
<td>Class for the button.</td>
</tr>
<tr>
<td>buttonText</td>
<td>Deactivate Account</td>
<td>Text that appears in the button</td>
</tr>
</table>

If you want to have more control you can get the full template from `bymayo/porter/src/templates/components/deactivateAccountForm.twig`

> ⚠️ Admin users CANNOT deactivate their own accounts for security reasons

### Magic Link

Let users sign in quickly via a link that is emailed to their inbox. To enable this go to `Settings > Porter` and toggle the `Magic Link` field.

The quickest way to add the form to your template is with using the `magicLinkForm` method:

```
{{ craft.porter.magicLinkForm() }}
```

By default it will add a set of classes prefixed with `porter__`, but you can add customisation parameters so you can change the styling, for example if your using Tailwind CSS:

```
{{ craft.porter.magicLinkForm(
    {
        buttonClass: 'bg-black text-white hover:bg-white hover:text-black',
        buttonText: 'Send Magic Link'
    }
) }}
```

<table>
<tr>
<td><strong>Property</strong></td>
<td><strong>Default</strong></td>
<td><strong>Description</strong></td>
</tr>
<tr>
<td>redirect</td>
<td>Plugin redirect setting</td>
<td>When the users requests a login link, this is where they'll be redirected to.</td>
</tr>
<tr>
<td>alertClass</td>
<td>porter__alert</td>
<td>Class for the flash that shows when a user account is deleted.</td>
</tr>
<tr>
<td>fieldClass</td>
<td>porter__field</td>
<td>Class for the input field.</td>
</tr>
<tr>
<td>fieldContainerClass</td>
<td>porter__field-container</td>
<td>Class for div that wraps label and input field.</td>
</tr>
<tr>
<td>fieldLabelClass</td>
<td>porter__field-label</td>
<td>Class for the input label.</td>
</tr>
<tr>
<td>buttonClass</td>
<td>porter__button</td>
<td>Class for the button.</td>
</tr>
<tr>
<td>buttonText</td>
<td>Send Magic Link</td>
<td>Text that appears in the button</td>
</tr>
</table>

> ⚠️ Admin users CANNOT use magic links for security reasons

### Block Burner / Disposable Emails

Block disposable and invalid emails to reduce spam sign ups. To enable this go to `Settings > Porter` and toggle the `Block Burner / Disposable Emails` field.

You will need to register at  https://verifier.meetchopra.com/ to get a __FREE__ API key.

## Support

If you have any issues (Surely not!) then I'll aim to reply to these as soon as possible. If it's a site-breaking-oh-no-what-has-happened moment, then hit me up on the Craft CMS Discord - @bymayo

## Roadmap

- Widgets, widgets, widgets! Who doesn't love widgets. 
- Transfer content option for deleted users
- Console command for garbage collection on deactivated users 
- User moderation
- A cleaner/secure way of letting users choose member groups on sign up
- More 2FA features (SMS, Auth apps etc)
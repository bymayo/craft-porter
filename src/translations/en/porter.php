<?php

return [
    'Porter plugin loaded' => 'Porter plugin loaded.',
    'porter_delete_account_flash_success' => 'Your account has been deleted.',
    'porter_delete_account_flash_admins' => 'Admins cannot delete their accounts.',
    'porter_delete_account_flash_incorrect' => 'You didn\'t type the phrase correctly.',
    'porter_delete_account_flash_permission' => 'You don\'t have permission to delete your account.',
    'porter_deactivate_account_flash_success' => 'Your account has been deactivated.',
    'porter_deactivate_account_flash_admins' => 'Admins cannot deactivate their accounts.',
    'porter_deactivate_account_flash_permission' => 'You don\'t have permission to deactivate your account.',
    'porter_magic_link_token_expired' => 'Magic link token has expired.',
    'porter_magic_link_sent' => 'A magic link has been sent, if the email address exists as a user.',
    'porter_magic_link_failed' => 'A magic link could not be sent.',
    'porter_magic_link_email_heading' => 'When a user requests a magic link to login.',
    'porter_magic_link_email_subject' => 'Your magic link request.',
    'porter_magic_link_email_body' => 'Hey {{ user.friendlyName }},

    You asked us to send you a magic link so you can quickly sign in securely.

    {{link}}

    The link above is a magic link, only meant for you. Please don\'t share it with anyone.

    If you weren’t expecting this email, just ignore it.',
    'porter_welcome_email_heading' => 'When a user activates their account.',
    'porter_welcome_email_subject' => 'Welcome!',
    'porter_welcome_email_body' => 'Hey {{ user.friendlyName }},

    Welcome aboard! Your account has been activated and you’re all set to go.

    If you have any questions, just reply to this email and we’ll be happy to help.',
    'porter_new_device_login_email_heading' => 'When a user signs in from a new device or location.',
    'porter_new_device_login_email_subject' => 'New device login detected',
    'porter_new_device_login_email_body' => 'Hey {{ user.friendlyName }},

    We noticed a new sign in to your account from a device or location we haven’t seen before.

    When: {{ dateCreated|date(\'F j, Y \\a\\t g:ia\') }}
    IP address: {{ ipAddress }}
    Device: {{ userAgent }}

    If this was you, no further action is needed.

    If you don’t recognise this sign in, please change your password immediately and contact us.',
    'porter_password_changed_email_heading' => 'When a user’s password is changed.',
    'porter_password_changed_email_subject' => 'Your password was changed',
    'porter_password_changed_email_body' => 'Hey {{ user.friendlyName }},

    The password on your account was just changed.

    When: {{ dateCreated|date(\'F j, Y \\a\\t g:ia\') }}
    {% if ipAddress %}IP address: {{ ipAddress }}{% endif %}

    If this was you, no further action is needed.

    If you didn’t make this change, please reset your password immediately and contact us.',
    'porter_email_address_changed_email_heading' => 'When a user’s email address is changed (sent to the previous email address).',
    'porter_email_address_changed_email_subject' => 'Your email address was changed',
    'porter_email_address_changed_email_body' => 'Hey {{ user.friendlyName }},

    The email address on your account was just changed.

    Previous email: {{ oldEmail }}
    New email: {{ newEmail }}
    When: {{ dateCreated|date(\'F j, Y \\a\\t g:ia\') }}
    {% if ipAddress %}IP address: {{ ipAddress }}{% endif %}

    If this was you, no further action is needed and you can ignore this email.

    If you didn’t make this change, please contact us immediately — your account may have been compromised.',
    'porter_account_suspended_email_heading' => 'When a user’s account is suspended.',
    'porter_account_suspended_email_subject' => 'Your account has been suspended',
    'porter_account_suspended_email_body' => 'Hey {{ user.friendlyName }},

    Your account has been suspended and you won’t be able to sign in until it’s restored.

    When: {{ dateCreated|date(\'F j, Y \\a\\t g:ia\') }}

    If you believe this was a mistake, please contact us.',
    'porter_account_unsuspended_email_heading' => 'When a user’s suspended account is restored.',
    'porter_account_unsuspended_email_subject' => 'Your account has been restored',
    'porter_account_unsuspended_email_body' => 'Hey {{ user.friendlyName }},

    Good news — your account has been restored and you can sign in again.

    When: {{ dateCreated|date(\'F j, Y \\a\\t g:ia\') }}

    If you weren’t expecting this email, please contact us.',
    'porter_account_deactivated_email_heading' => 'When a user’s account is deactivated.',
    'porter_account_deactivated_email_subject' => 'Your account has been deactivated',
    'porter_account_deactivated_email_body' => 'Hey {{ user.friendlyName }},

    Your account has been deactivated.

    When: {{ dateCreated|date(\'F j, Y \\a\\t g:ia\') }}

    If you believe this was a mistake, please contact us.',
    'porter_account_deleted_email_heading' => 'When a user’s account is deleted.',
    'porter_account_deleted_email_subject' => 'Your account has been deleted',
    'porter_account_deleted_email_body' => 'Hey {{ user.friendlyName }},

    Your account has been deleted.

    When: {{ dateCreated|date(\'F j, Y \\a\\t g:ia\') }}

    If you believe this was a mistake, please contact us.',
    'porter_failed_login_attempts_email_heading' => 'When repeated failed sign in attempts are detected on a user’s account.',
    'porter_failed_login_attempts_email_subject' => 'Multiple failed sign in attempts on your account',
    'porter_failed_login_attempts_email_body' => 'Hey {{ user.friendlyName }},

    We’ve detected {{ attempts }} failed sign in attempts on your account.

    When: {{ dateCreated|date(\'F j, Y \\a\\t g:ia\') }}
    {% if ipAddress %}IP address of latest attempt: {{ ipAddress }}{% endif %}

    If this was you, no further action is needed.

    If you didn’t make these attempts, your account may be the target of a sign in attack. We recommend changing your password and contacting us if you’re unable to sign in yourself.'
];

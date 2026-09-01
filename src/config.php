<?php

/**
 * Porter config.php
 *
 * This file exists only as a template for the Porter settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'porter.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [

    // Delete Account
    'deleteAccount' => false,
    'deleteAccountConfirmationType' => 'confirmationKeyword',
    'deleteAccountConfirmationKeyword' => 'DELETE',
    'deleteAccountConfirmationField' => 'email',
    'deleteAccountRedirect' => '/', // Where users are sent afterwards
    'deleteAccountTransfer' => null,

    // Deactivate Account
    'deactivateAccount' => false,
    'deactivateAccountRedirect' => '/', // Where users are sent afterwards

    // Inactive Accounts
    'inactiveAccountCleanup' => false,
    'inactiveAccountReminderDays' => 365,
    'inactiveAccountDeleteDays' => 395,

    // Magic Link
    'magicLink' => false,
    'magicLinkControlPanel' => false,
    'magicLinkFrontEnd' => false,
    'magicLinkExpirySeconds' => '300',
    'magicLinkRedirect' => '/', // Where users are sent afterwards

    // Email
    'emailBurners' => false,
    'emailsBurnersVerifierApiKey' => null,

    // Password - Confirmation
    'passwordConfirm' => false,

    // Password - Length & Characters
    'passwordForcePolicy' => false,
    'passwordForcePolicyMin' => 8, // Craft's own floor is 6
    'passwordForcePolicyMax' => 0, // 0 for no limit, Craft caps at 160
    'passwordForcePolicyRules' => null, // 'lowercase', 'uppercase', 'numeric', 'symbol'

    // Password - Strength
    'passwordStrengthIndicator' => false,
    'passwordStrengthMinScore' => 0, // 0 doesn't enforce, otherwise 1-4
    'passwordCspNonce' => false, // Add a CSP nonce to the indicator script

    // Password - Breach Check
    'passwordPwned' => false,
    'passwordPwnedFailMode' => 'open', // 'open' allows passwords if the breach API is down, 'closed' rejects them

    // Password - Blocklist
    'passwordBlocklist' => false,
    'passwordBlocklistSources' => ['userDetails', 'siteName', 'substitutions'],
    'passwordBlocklistWords' => null,

    // Password - History
    'passwordHistory' => false,
    'passwordHistoryCount' => 5, // 1 to 24

    // Password - Expiry
    'passwordExpiry' => false,
    'passwordExpiryAmount' => 90,
    'passwordExpiryPeriod' => 'days', // 'days', 'weeks', 'months', 'years'
    'passwordExpiryWarningDays' => 7,
    'passwordExpiryFrontEndRedirect' => null, // Where front end users with an expired password are sent

    // Password - Exemptions
    'passwordExemptAdmins' => false,
    'passwordExemptGroups' => null,

    // Email Notifications
    'emailWelcome' => false,
    'emailNewDeviceLogin' => false,
    'emailPasswordChanged' => false,
    'emailAddressChanged' => false,
    'emailAccountSuspended' => false,
    'emailAccountUnsuspended' => false,
    'emailAccountDeactivated' => false,
    'emailAccountDeleted' => false,
    'emailInactiveAccountReminder' => false,
    'emailFailedLoginAttempts' => false,
    'emailFailedLoginAttemptsThreshold' => 3,
    'emailPasswordExpiring' => false,
    'emailPasswordExpired' => false

];

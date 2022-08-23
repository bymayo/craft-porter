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
    'deleteAccountConfirmationEmail' => true,
    'deleteAccountRedirect' => '/',
    'deleteAccountTransfer' => null,

    // Deactivate Account
    'deactivateAccount' => false,
    'deactivateAccountRedirect' => '/',
    'deactivateAccountConfirmationEmail' => true,
    'deactivateAccountDeleteDays' => 30,

    // Magic Link
    'magicLink' => false,
    'magicLinkControlPanel' => false,
    'magicLinkFrontEnd' => false,
    'magicLinkExpirySeconds' => '300',
    'magicLinkRedirect' => '/',

    // Email
    'emailBurners' => false,
    'emailsBurnersVerifierApiKey' => null,

    // Password
    'passwordForcePolicy' => false,
    'passwordForcePolicyMin' => 8,
    'passwordForcePolicyMax' => 128,
    'passwordForcePolicyRules' => null
    
];

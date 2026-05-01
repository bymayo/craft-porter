<?php

namespace bymayo\porter\models;

use bymayo\porter\Porter;

use Craft;
use craft\base\Model;
use craft\validators\ArrayValidator;
use craft\validators\DateTimeValidator;

/**
 * @author    Jason Mayo
 * @package   Porter
 * @since     1.0.0
 */
class Settings extends Model
{

    // Delete Account

    public $deleteAccount = 0;

    public $deleteAccountConfirmationType = 'confirmationKeyword';

    public $deleteAccountConfirmationKeyword = 'DELETE';

    public $deleteAccountConfirmationField = 'email';

    public $deleteAccountRedirect = '/';

    public $deleteAccountTransfer = null;

    // Deactivate Account

    public $deactivateAccount = 0;

    public $deactivateAccountRedirect = '/';

    // Inactive Accounts

    public $inactiveAccountCleanup = 0;

    public $inactiveAccountReminderDays = 365;

    public $inactiveAccountDeactivateDays = 395;

    // Email Notifications

    public $emailWelcome = 0;

    public $emailNewDeviceLogin = 0;

    public $emailPasswordChanged = 0;

    public $emailAddressChanged = 0;

    public $emailAccountSuspended = 0;

    public $emailAccountUnsuspended = 0;

    public $emailAccountDeactivated = 0;

    public $emailAccountDeleted = 0;

    public $emailInactiveAccountReminder = 0;

    public $emailFailedLoginAttempts = 0;

    public $emailFailedLoginAttemptsThreshold = 3;

    // Magic Link

    public $magicLink = 0;

    public $magicLinkControlPanel = 0;

    public $magicLinkFrontEnd = 0;

    public $magicLinkExpirySeconds = '300';

    public $magicLinkRedirect = '/';

    // Email

    public $emailBurners = 0;

    public $emailsBurnersVerifierApiKey = null;

    // Password

    public $passwordForcePolicy = 0;

    public $passwordForcePolicyMin = 8;

    public $passwordForcePolicyMax = 128;

    public $passwordForcePolicyRules = null;

    public function rules(): array
    {
        return [
            [
                [
                    'deleteAccount',
                    'deactivateAccount',
                    'emailWelcome',
                    'emailNewDeviceLogin',
                    'emailPasswordChanged',
                    'emailAddressChanged',
                    'emailAccountSuspended',
                    'emailAccountUnsuspended',
                    'emailAccountDeactivated',
                    'emailAccountDeleted',
                    'emailInactiveAccountReminder',
                    'emailFailedLoginAttempts',
                    'inactiveAccountCleanup',
                    'magicLink',
                    'magicLinkControlPanel',
                    'magicLinkFrontEnd',
                    'emailBurners',
                    'passwordForcePolicy'
                ],
                'boolean'
            ],
            [
                [
                    'magicLinkExpirySeconds',
                    'passwordForcePolicyMin',
                    'passwordForcePolicyMax',
                    'emailFailedLoginAttemptsThreshold',
                    'inactiveAccountReminderDays',
                    'inactiveAccountDeactivateDays'
                ],
                'integer'
            ],
            [
                [
                    'deleteAccountConfirmationType', 
                    'deleteAccountConfirmationKeyword', 
                    'deleteAccountConfirmationField', 
                    'deleteAccountRedirect', 
                    'deactivateAccountRedirect',
                    'magicLinkRedirect',
                    'emailsBurnersVerifierApiKey'
                ], 
                'string'
            ],
            [
                [
                    // 'deleteAccountTransfer',
                    'passwordForcePolicyRules'
                ], 
                ArrayValidator::class
            ]
        ];
    }

}

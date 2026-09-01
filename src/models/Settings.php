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

    public $inactiveAccountDeleteDays = 395;

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

    public $emailPasswordExpiring = 0;

    public $emailPasswordExpired = 0;

    // Magic Link

    public $magicLink = 0;

    public $magicLinkControlPanel = 0;

    public $magicLinkFrontEnd = 0;

    public $magicLinkExpirySeconds = '300';

    public $magicLinkRedirect = '/';

    public $magicLinkRegister = 0;

    public $magicLinkRegisterGroups = null;

    public $magicLinkRegisterRedirect = '/';

    /**
     * Links one address may request within the window, before further
     * requests are silently dropped. 0 disables the cap. Config file only.
     */
    public $magicLinkThrottleLimit = 5;

    /**
     * The throttle window, in seconds. Config file only.
     */
    public $magicLinkThrottleWindow = 900;

    /**
     * Floor for how long a magic link request takes to answer, in
     * milliseconds, so a request that sends no email can't be told apart from
     * one that does. 0 disables it. Config file only.
     */
    public $magicLinkMinResponseMs = 500;

    // Email

    public $emailBurners = 0;

    public $emailsBurnersVerifierApiKey = null;

    // Password

    public $passwordConfirm = 0;

    public $passwordForcePolicy = 0;

    public $passwordForcePolicyMin = 8;

    public $passwordForcePolicyMax = 0;

    public $passwordForcePolicyRules = null;

    // Password - Strength & Breach

    public $passwordPwned = 0;

    /**
     * 'open' or 'closed'. Config file only.
     */
    public $passwordPwnedFailMode = 'open';

    public $passwordStrengthIndicator = 0;

    /**
     * 0 is don't enforce, otherwise 1-4.
     */
    public $passwordStrengthMinScore = 0;

    /**
     * Config file only.
     */
    public $passwordCspNonce = 0;

    // Password - Blocklist

    public $passwordBlocklist = 0;

    /**
     * Any of 'userDetails', 'siteName', 'substitutions'.
     */
    public $passwordBlocklistSources = ['userDetails', 'siteName', 'substitutions'];

    public $passwordBlocklistWords = null;

    // Password - History

    public $passwordHistory = 0;

    public $passwordHistoryCount = 5;

    // Password - Expiry

    public $passwordExpiry = 0;

    public $passwordExpiryAmount = 90;

    public $passwordExpiryPeriod = 'days';

    public $passwordExpiryWarningDays = 7;

    /**
     * Config file only.
     */
    public $passwordExpiryFrontEndRedirect = null;

    // Password - Exemptions

    public $passwordExemptAdmins = 0;

    public $passwordExemptGroups = null;

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
                    'magicLinkRegister',
                    'emailBurners',
                    'passwordConfirm',
                    'passwordForcePolicy',
                    'passwordPwned',
                    'passwordStrengthIndicator',
                    'passwordCspNonce',
                    'passwordBlocklist',
                    'passwordHistory',
                    'passwordExpiry',
                    'passwordExemptAdmins',
                    'emailPasswordExpiring',
                    'emailPasswordExpired'
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
                    'inactiveAccountDeleteDays',
                    'passwordHistoryCount',
                    'passwordStrengthMinScore',
                    'passwordExpiryAmount',
                    'passwordExpiryWarningDays',
                    'magicLinkThrottleLimit',
                    'magicLinkThrottleWindow',
                    'magicLinkMinResponseMs'
                ],
                'integer'
            ],
            [
                ['magicLinkThrottleLimit', 'magicLinkThrottleWindow', 'magicLinkMinResponseMs'],
                'integer',
                'min' => 0
            ],
            [
                ['passwordPwnedFailMode'],
                'in',
                'range' => ['open', 'closed']
            ],
            [
                ['passwordStrengthMinScore'],
                'integer',
                'min' => 0,
                'max' => 4
            ],
            [
                ['passwordExpiryPeriod'],
                'in',
                'range' => ['days', 'weeks', 'months', 'years']
            ],
            [
                ['passwordHistoryCount'],
                'integer',
                'min' => 1,
                'max' => 24
            ],
            [
                ['passwordExpiryAmount'],
                'integer',
                'min' => 0
            ],
            [
                [
                    'deleteAccountConfirmationType', 
                    'deleteAccountConfirmationKeyword', 
                    'deleteAccountConfirmationField', 
                    'deleteAccountRedirect', 
                    'deactivateAccountRedirect',
                    'magicLinkRedirect',
                    'magicLinkRegisterRedirect',
                    'emailsBurnersVerifierApiKey',
                    'passwordPwnedFailMode',
                    'passwordExpiryPeriod',
                    'passwordBlocklistWords',
                    'passwordExpiryFrontEndRedirect'
                ], 
                'string'
            ],
            [
                [
                    // 'deleteAccountTransfer',
                    'passwordForcePolicyRules',
                    'magicLinkRegisterGroups',
                    'passwordBlocklistSources',
                    'passwordExemptGroups'
                ], 
                ArrayValidator::class
            ]
        ];
    }

}

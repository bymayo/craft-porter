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

    public $deleteAccountConfirmationEmail = 1;

    public $deleteAccountRedirect = '/';

    public $deleteAccountTransfer = null;

    // Deactivate Account

    public $deactivateAccount = 0;

    public $deactivateAccountRedirect = '/';

    public $deactivateAccountConfirmationEmail = 1;

    public $deactivateAccountDeleteDays = 30;

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
                    'deleteAccountConfirmationEmail', 
                    'deactivateAccount',
                    'deactivateAccountConfirmationEmail',
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
                    'deactivateAccountDeleteDays', 
                    'magicLinkExpirySeconds',
                    'passwordForcePolicyMin', 
                    'passwordForcePolicyMax'
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

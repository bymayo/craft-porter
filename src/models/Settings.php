<?php
/**
 * Porter plugin for Craft CMS 3.x
 *
 * A toolkit with lots of helpers for users and accounts
 *
 * @link      https://bymayo.co.uk
 * @copyright Copyright (c) 2020 Jason Mayo
 */

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

    public $magicLinkControlPanel = 0;

    public $magicLinkFrontEnd = 0;

    public $magicLinkExpirySeconds = '300';

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

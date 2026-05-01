<?php
/**
 * Porter plugin for Craft CMS
 *
 * A toolkit with lots of helpers for users and accounts
 *
 * @link      https://bymayo.co.uk
 * @copyright Copyright (c) 2022 Jason Mayo
 */

namespace bymayo\porter;

use bymayo\porter\services\Helper;
use bymayo\porter\services\MagicLink;
use bymayo\porter\services\EmailPassword;
use bymayo\porter\services\DeactivateAccount;
use bymayo\porter\services\DeleteAccount;
use bymayo\porter\services\EmailNotifications;
use bymayo\porter\services\InactiveAccounts;
use bymayo\porter\variables\PorterVariable;
use bymayo\porter\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\controllers\UsersController;
use craft\services\Plugins;
use craft\services\SystemMessages;
use craft\services\Users;
use craft\events\LoginFailureEvent;
use craft\events\PluginEvent;
use craft\events\UserEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\User as WebUser;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\elements\User;
use craft\log\MonologTarget;

use craft\events\ModelEvent as CraftModelEvent;

use Psr\Log\LogLevel;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\web\UserEvent as YiiUserEvent;

class Porter extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Porter
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.3.0';

    /**
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * @var bool
     */
    public bool $hasCpSection = false;

    // Public Methods
    // =========================================================================

    public static function log($message)
    {
        Craft::info($message, 'porter');
    }

    public static function warn($message)
    {
        Craft::warning($message, 'porter');
    }

    public function init()
    {
        parent::init();

        $this->_registerLogTarget();

        self::$plugin = $this;

        if (Craft::$app instanceof \craft\console\Application) {
            $this->controllerNamespace = 'bymayo\\porter\\console\\controllers';
        } else {
            $this->controllerNamespace = 'bymayo\\porter\\controllers';
        }

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('porter', PorterVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'porter',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        $this->setComponents([
            'helper' => Helper::class,
            'magicLink' => MagicLink::class,
            'deleteAccount' => DeleteAccount::class,
            'deactivateAccount' => DeactivateAccount::class,
            'emailPassword' => EmailPassword::class,
            'emailNotifications' => EmailNotifications::class,
            'inactiveAccounts' => InactiveAccounts::class
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'porter/magic-link/access';
            }
        );

        if (Craft::$app->getRequest()->getIsCpRequest()) {

            Event::on(
                UrlManager::class, 
                UrlManager::EVENT_REGISTER_CP_URL_RULES,
                function(RegisterUrlRulesEvent $event) {
                    
                    $event->rules = array_merge(
                        [
                            'settings/plugins/porter' => 'porter/settings/render'
                        ],
                        $event->rules
                    );
                }
                
            );

        }

        Event::on(
            SystemMessages::class,
            SystemMessages::EVENT_REGISTER_MESSAGES,
            function(RegisterEmailMessagesEvent $event) {

                $event->messages = array_merge(
                    $event->messages, [
                        [
                            'key' => 'porter_magic_link_email',
                            'heading' => Craft::t('porter', 'porter_magic_link_email_heading'),
                            'subject' => Craft::t('porter', 'porter_magic_link_email_subject'),
                            'body' => Craft::t('porter', 'porter_magic_link_email_body')
                        ],
                        [
                            'key' => 'porter_welcome_email',
                            'heading' => Craft::t('porter', 'porter_welcome_email_heading'),
                            'subject' => Craft::t('porter', 'porter_welcome_email_subject'),
                            'body' => Craft::t('porter', 'porter_welcome_email_body')
                        ],
                        [
                            'key' => 'porter_new_device_login_email',
                            'heading' => Craft::t('porter', 'porter_new_device_login_email_heading'),
                            'subject' => Craft::t('porter', 'porter_new_device_login_email_subject'),
                            'body' => Craft::t('porter', 'porter_new_device_login_email_body')
                        ],
                        [
                            'key' => 'porter_password_changed_email',
                            'heading' => Craft::t('porter', 'porter_password_changed_email_heading'),
                            'subject' => Craft::t('porter', 'porter_password_changed_email_subject'),
                            'body' => Craft::t('porter', 'porter_password_changed_email_body')
                        ],
                        [
                            'key' => 'porter_email_address_changed_email',
                            'heading' => Craft::t('porter', 'porter_email_address_changed_email_heading'),
                            'subject' => Craft::t('porter', 'porter_email_address_changed_email_subject'),
                            'body' => Craft::t('porter', 'porter_email_address_changed_email_body')
                        ],
                        [
                            'key' => 'porter_account_suspended_email',
                            'heading' => Craft::t('porter', 'porter_account_suspended_email_heading'),
                            'subject' => Craft::t('porter', 'porter_account_suspended_email_subject'),
                            'body' => Craft::t('porter', 'porter_account_suspended_email_body')
                        ],
                        [
                            'key' => 'porter_account_unsuspended_email',
                            'heading' => Craft::t('porter', 'porter_account_unsuspended_email_heading'),
                            'subject' => Craft::t('porter', 'porter_account_unsuspended_email_subject'),
                            'body' => Craft::t('porter', 'porter_account_unsuspended_email_body')
                        ],
                        [
                            'key' => 'porter_account_deactivated_email',
                            'heading' => Craft::t('porter', 'porter_account_deactivated_email_heading'),
                            'subject' => Craft::t('porter', 'porter_account_deactivated_email_subject'),
                            'body' => Craft::t('porter', 'porter_account_deactivated_email_body')
                        ],
                        [
                            'key' => 'porter_account_deleted_email',
                            'heading' => Craft::t('porter', 'porter_account_deleted_email_heading'),
                            'subject' => Craft::t('porter', 'porter_account_deleted_email_subject'),
                            'body' => Craft::t('porter', 'porter_account_deleted_email_body')
                        ],
                        [
                            'key' => 'porter_failed_login_attempts_email',
                            'heading' => Craft::t('porter', 'porter_failed_login_attempts_email_heading'),
                            'subject' => Craft::t('porter', 'porter_failed_login_attempts_email_subject'),
                            'body' => Craft::t('porter', 'porter_failed_login_attempts_email_body')
                        ],
                        [
                            'key' => 'porter_inactive_account_reminder_email',
                            'heading' => Craft::t('porter', 'porter_inactive_account_reminder_email_heading'),
                            'subject' => Craft::t('porter', 'porter_inactive_account_reminder_email_subject'),
                            'body' => Craft::t('porter', 'porter_inactive_account_reminder_email_body')
                        ]
                    ]
                );

            }
        );

        Event::on(
            Users::class,
            Users::EVENT_AFTER_ACTIVATE_USER,
            function (UserEvent $event) {
                Porter::getInstance()->emailNotifications->sendWelcome($event->user);
            }
        );

        Event::on(
            Users::class,
            Users::EVENT_AFTER_SUSPEND_USER,
            function (UserEvent $event) {
                Porter::getInstance()->emailNotifications->sendAccountSuspended($event->user);
            }
        );

        Event::on(
            Users::class,
            Users::EVENT_AFTER_UNSUSPEND_USER,
            function (UserEvent $event) {
                Porter::getInstance()->emailNotifications->sendAccountUnsuspended($event->user);
            }
        );

        Event::on(
            Users::class,
            Users::EVENT_AFTER_DEACTIVATE_USER,
            function (UserEvent $event) {
                Porter::getInstance()->emailNotifications->sendAccountDeactivated($event->user);
            }
        );

        Event::on(
            User::class,
            User::EVENT_BEFORE_DELETE,
            function (CraftModelEvent $event) {
                if ($event->sender instanceof User) {
                    Porter::getInstance()->emailNotifications->sendAccountDeleted($event->sender);
                }
            }
        );

        Event::on(
            WebUser::class,
            WebUser::EVENT_AFTER_LOGIN,
            function (YiiUserEvent $event) {
                if ($event->identity instanceof User) {
                    Porter::getInstance()->emailNotifications->handleLogin($event->identity);
                }
            }
        );

        Event::on(
            User::class,
            User::EVENT_BEFORE_SAVE,
            function (CraftModelEvent $event) {
                $emailNotifications = Porter::getInstance()->emailNotifications;
                $emailNotifications->capturePasswordChange($event->sender);
                $emailNotifications->captureEmailChange($event->sender);
            }
        );

        Event::on(
            User::class,
            User::EVENT_AFTER_SAVE,
            function (CraftModelEvent $event) {
                if (!$event->isNew) {
                    $emailNotifications = Porter::getInstance()->emailNotifications;
                    $emailNotifications->sendPasswordChanged($event->sender);
                    $emailNotifications->sendEmailAddressChanged($event->sender);
                }
            }
        );

        Event::on(
            UsersController::class,
            UsersController::EVENT_LOGIN_FAILURE,
            function (LoginFailureEvent $event) {
                if ($event->user instanceof User) {
                    Porter::getInstance()->emailNotifications->sendFailedLoginAttempts($event->user);
                }
            }
        );

        Event::on(
            User::class,
            User::EVENT_BEFORE_VALIDATE,
            function (ModelEvent $event) {

                $user = $event->sender;

                if ($this->settings->emailBurners && $this->settings->emailsBurnersVerifierApiKey)
                {

                    $errors = $this->emailPassword->checkBurnerEmail($user->email);

                    foreach ($errors as $error) {
                        $user->addError('email', $error);
                    }

                    if ($errors)
                    {
                        Craft::$app->getSession()->setFlash('porter', implode(' ', $errors));
                    }

                }

                if ($this->settings->passwordForcePolicy && ($user->newPassword || strlen($user->newPassword) > 0))
                {

                    $errors = $this->emailPassword->checkPasswordPolicy($user->newPassword);

                    if ($errors)
                    {

                        $event->isValid = 0;

                        foreach ($errors as $error) {
                            $user->addError('newPassword', $error);
                        }

                        Craft::$app->getSession()->setFlash('porter', implode(' ', $errors));

                    }

                }

            }
        );

    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    private function _registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'porter',
            'categories' => ['porter'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
        ]);
    }

}

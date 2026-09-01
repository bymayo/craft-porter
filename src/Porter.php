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
use bymayo\porter\services\PasswordPolicy;
use bymayo\porter\services\PasswordRetention;
use bymayo\porter\services\Security;
use bymayo\porter\assetbundles\porter\PorterMagicLinkAsset;
use bymayo\porter\rules\UserRules;
use bymayo\porter\utilities\PasswordRetentionUtility;
use bymayo\porter\variables\PorterVariable;
use bymayo\porter\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\controllers\UsersController;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\services\Users;
use craft\services\Utilities;
use craft\events\DefineRulesEvent;
use craft\events\LoginFailureEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\events\UserEvent;
use craft\web\Application;
use craft\web\twig\variables\CraftVariable;
use craft\web\User as WebUser;
use craft\web\UrlManager;
use craft\web\View;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\elements\User;
use craft\log\MonologTarget;

use craft\events\ModelEvent as CraftModelEvent;

use Psr\Log\LogLevel;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\web\UserEvent as YiiUserEvent;

/**
 * @property Helper $helper
 * @property MagicLink $magicLink
 * @property DeleteAccount $deleteAccount
 * @property DeactivateAccount $deactivateAccount
 * @property EmailPassword $emailPassword
 * @property EmailNotifications $emailNotifications
 * @property InactiveAccounts $inactiveAccounts
 * @property PasswordPolicy $passwordPolicy
 * @property PasswordRetention $passwordRetention
 * @property Security $security
 * @property Settings $settings
 */
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
    public string $schemaVersion = '1.6.0';

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
            'inactiveAccounts' => InactiveAccounts::class,
            'passwordPolicy' => PasswordPolicy::class,
            'passwordRetention' => PasswordRetention::class,
            'security' => Security::class
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
                            'settings/plugins/porter' => 'porter/settings/render',
                            // Not under 'porter/', because Craft treats a CP
                            // path starting with a plugin handle as a plugin
                            // page and forces guests to log in first.
                            'magic-link' => 'porter/magic-link/login'
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
                        ],
                        [
                            'key' => 'porter_password_expiring_email',
                            'heading' => Craft::t('porter', 'porter_password_expiring_email_heading'),
                            'subject' => Craft::t('porter', 'porter_password_expiring_email_subject'),
                            'body' => Craft::t('porter', 'porter_password_expiring_email_body')
                        ],
                        [
                            'key' => 'porter_password_expired_email',
                            'heading' => Craft::t('porter', 'porter_password_expired_email_heading'),
                            'subject' => Craft::t('porter', 'porter_password_expired_email_subject'),
                            'body' => Craft::t('porter', 'porter_password_expired_email_body')
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
                Porter::getInstance()->inactiveAccounts->resetLastLoginForUser($event->user);
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
                Porter::getInstance()->passwordRetention->capturePasswordChange($event->sender);
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
                Porter::getInstance()->passwordRetention->flushPasswordChange($event->sender);
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

            }
        );

        // Password rules go in through Craft's own validation rather than a
        // before-validate hook, so errors render inline in the control panel,
        // are readable from `user.getErrors('newPassword')` on the front end,
        // and don't rely on a session flash that isn't there in console and
        // queue requests.
        Event::on(
            User::class,
            User::EVENT_DEFINE_RULES,
            function (DefineRulesEvent $event) {

                $user = $event->sender instanceof User ? $event->sender : null;

                $event->rules = UserRules::applyTo($event->rules, $user);

            }
        );

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => 'Porter',
                    'permissions' => [
                        'porter:forceResetPasswords' => [
                            'label' => Craft::t('porter', 'Force reset expired passwords')
                        ]
                    ]
                ];
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITIES,
            function (RegisterComponentTypesEvent $event) {

                if (!Porter::getInstance()->passwordRetention->isEnabled()) {
                    return;
                }

                $event->types[] = PasswordRetentionUtility::class;

            }
        );

        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {

                // Control panel pages, plus Craft's own set-password and
                // login screens — those come in as site requests but render
                // in control panel template mode, and set-password is exactly
                // where a strength meter is wanted. The mode comes off the
                // event, since the view isn't switched over yet.
                $isCpTemplate = Craft::$app->getRequest()->getIsCpRequest()
                    || $event->templateMode === View::TEMPLATE_MODE_CP;

                if (!$isCpTemplate) {
                    return;
                }

                $policy = Porter::getInstance()->passwordPolicy;

                if ($policy->strengthIndicatorEnabled()) {
                    $policy->registerIndicatorAssets();
                } else {
                    $policy->registerConfirmAssets();
                }

            }
        );

        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {

                if (!in_array($event->template, ['login', 'login.twig'], true)) {
                    return;
                }

                if (!$this->settings->magicLink || !$this->settings->magicLinkControlPanel) {
                    return;
                }

                $view = Craft::$app->getView();

                $view->registerAssetBundle(PorterMagicLinkAsset::class);

                $view->registerScript(
                    'window.porterMagicLink = ' . Json::encode([
                        'linkText' => Craft::t('porter', 'Sign in with a magic link'),
                        'url' => UrlHelper::cpUrl('magic-link')
                    ]) . ';',
                    View::POS_HEAD,
                    [],
                    'porter-magic-link-config'
                );

            }
        );

        Event::on(
            Application::class,
            Application::EVENT_BEFORE_REQUEST,
            function () {
                $this->_redirectExpiredPasswords();
            }
        );

    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * Sends signed-in front-end users with an expired password to the
     * configured set-password page.
     *
     * Craft handles this itself in the control panel; front-end-only users
     * would otherwise never be prompted.
     */
    private function _redirectExpiredPasswords(): void
    {

        if (!Craft::$app->getIsInstalled()) {
            return;
        }

        if (!$this->settings->passwordExpiryFrontEndRedirect) {
            return;
        }

        $request = Craft::$app->getRequest();

        if (
            $request->getIsConsoleRequest() ||
            $request->getIsCpRequest() ||
            $request->getIsActionRequest() ||
            $request->getIsAjax()
        ) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        // Read through the service: UserQuery doesn't select
        // `passwordResetRequired`, so the identity's own value is never set.
        if (!$user || !Porter::getInstance()->passwordRetention->resetRequired($user)) {
            return;
        }

        $url = UrlHelper::siteUrl($this->settings->passwordExpiryFrontEndRedirect);

        // Don't bounce the set-password page back to itself.
        if (rtrim($request->getAbsoluteUrl(), '/') === rtrim($url, '/')) {
            return;
        }

        Craft::$app->getResponse()->redirect($url)->send();
        Craft::$app->end();

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

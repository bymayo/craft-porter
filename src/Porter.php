<?php
/**
 * Porter plugin for Craft CMS 3.x
 *
 * A toolkit with lots of helpers for users and accounts
 *
 * @link      https://bymayo.co.uk
 * @copyright Copyright (c) 2020 Jason Mayo
 */

namespace bymayo\porter;

use bymayo\porter\services\Helper;
use bymayo\porter\services\MagicLink;
use bymayo\porter\services\EmailPassword;
use bymayo\porter\services\DeactivateAccount;
use bymayo\porter\services\DeleteAccount;
use bymayo\porter\variables\PorterVariable;
use bymayo\porter\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\SystemMessages;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\helpers\FileHelper;
use craft\elements\User;

use yii\base\Event;
use yii\base\ModelEvent;

/**
 * Class Porter
 *
 * @author    Jason Mayo
 * @package   Porter
 * @since     1.0.0
 *
 * @property  PorterServiceService $porterService
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
    public string $schemaVersion = '1.0.0';

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
        $file = Craft::getAlias('@storage/logs/porter.log');
        $log = date('Y-m-d H:i:s'). ' ' . $message . "\n";
        FileHelper::writeToFile($file, $log, ['append' => true]);
    }

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->controllerNamespace = 'bymayo\porter\controllers';

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
            'emailPassword' => EmailPassword::class
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
                            'key' => 'porter_delete_account_confirmation_email',
                            'heading' => Craft::t('porter', 'porter_delete_account_confirmation_email_heading'),
                            'subject' => Craft::t('porter', 'porter_delete_account_confirmation_email_subject'),
                            'body' => Craft::t('porter', 'porter_delete_account_confirmation_email_body')
                        ],
                        [
                            'key' => 'porter_deactivate_account_confirmation_email',
                            'heading' => Craft::t('porter', 'porter_deactivate_account_confirmation_email_heading'),
                            'subject' => Craft::t('porter', 'porter_deactivate_account_confirmation_email_subject'),
                            'body' => Craft::t('porter', 'porter_deactivate_account_confirmation_email_body')
                        ],
                        [
                            'key' => 'porter_magic_link_email',
                            'heading' => Craft::t('porter', 'porter_magic_link_email_heading'),
                            'subject' => Craft::t('porter', 'porter_magic_link_email_subject'),
                            'body' => Craft::t('porter', 'porter_magic_link_email_body')
                        ]
                    ]
                );

            }
        );

        Event::on(
            User::class,
            User::EVENT_BEFORE_VALIDATE,
            function (ModelEvent $event) {

                if (!Craft::$app->getRequest()->getIsCpRequest()) {

                    $user = $event->sender;

                    if ($this->settings->emailBurners)
                    {

                        $errors = $this->emailPassword->checkBurnerEmail($user->email);

                        foreach ($errors as $error) {
                            $user->addError('email', $error);
                        }
                        
                    }

                    if ($this->settings->passwordForcePolicy && ($user->newPassword || strlen($user->newPassword) >= 0))
                    {

                        $errors = $this->emailPassword->checkPasswordPolicy($user->newPassword);

                        if ($errors)
                        {

                            $event->isValid = 0;

                            foreach ($errors as $error) {
                                $user->addError('newPassword', $error);
                            }    

                        }

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

}

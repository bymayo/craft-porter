<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use craft\base\ElementInterface;

class SettingsController extends Controller
{

    public function actionRender()
    {

        $variables = [];

        $settings = Porter::$plugin->settings;

        if (isset($settings['deleteAccountTransfer']))
        {

            // $settings['deleteAccountTransfer'] = Craft::$app->getElements()->getElementById($settings['deleteAccountTransfer'][0]);

            // $user = Craft::$app->getElements()->getElementById($settings['deleteAccountTransfer']);

            $users = [];
            foreach ($settings['deleteAccountTransfer'] as $user) {
                $purchasable = Craft::$app->getElements()->getElementById((int)$user);
                if ($purchasable && $purchasable instanceof ElementInterface) {
                    Porter::log('Test');
                    $class = get_class($purchasable);
                    Porter::log( $class);
                    $users[$class] = $users[$class] ?? [];
                    $users[$class][] = $purchasable;

                }
            }

            $settings['deleteAccountTransfer'] = $users;

            // var_dump( $users);

            // if ($user && $user instanceof ElementInterface) {
            //     $class = get_class($user);
            //     $settings['deleteAccountTransfer'][$class] = $user[$class] ?? [];
            //     $settings['deleteAccountTransfer'][$class][] = $user;
            // }
            // $settings['deleteAccountTransfer']['craft\\elements\\User'];
            // var_dump($settings['deleteAccountTransfer'] );
            // Porter::log($settings['deleteAccountTransfer']);
        }
        

        return $this->renderTemplate(
            'porter/settings/edit', 
            array(
                'settings' => $settings
            )
        );

    }

    public function actionSave()
    {

        $this->requirePostRequest();

        $request = Craft::$app->getRequest();        

        $postedSettings = $request->getBodyParam('settings', []);

        $settings = Porter::$plugin->settings;
        $settings->setAttributes($postedSettings, false);

        $settings->validate();

        if ($settings->hasErrors())
        {

            Craft::$app->getSession()->setError(Craft::t('porter', 'Couldn’t save plugin settings.'));
            return null;

        }

        Craft::$app->getPlugins()->savePluginSettings(
            Porter::$plugin, 
            $settings->getAttributes()
        );

        $notice = Craft::t('porter', 'Plugin settings saved.');
        $errors = [];

        if (!empty($errors)) {

            Craft::$app->getSession()->setError($notice . ' ' . implode(' ', $errors));
            return null;

        }

        Craft::$app->getSession()->setNotice($notice);

        return $this->redirectToPostedUrl();

    }

}
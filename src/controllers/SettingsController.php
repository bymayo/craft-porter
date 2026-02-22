<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class SettingsController extends Controller
{

    public function actionRender()
    {

        $settings = Porter::$plugin->settings;

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
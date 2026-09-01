<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class SettingsController extends Controller
{

    public function actionRender()
    {

        // Craft gates plugin settings behind the control panel and an admin.
        // Porter registers its own route for this page, so the same guards
        // have to be applied here rather than inherited.
        $this->requireCpRequest();
        $this->requireAdmin(false);

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
        $this->requireCpRequest();
        $this->requireAdmin();

        $request = Craft::$app->getRequest();

        $postedSettings = $request->getBodyParam('settings', []);

        $settings = Porter::$plugin->settings;

        // Safe attributes only. Assigning unsafely would let anything posted
        // reach any public property on the model.
        $settings->setAttributes($postedSettings);

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
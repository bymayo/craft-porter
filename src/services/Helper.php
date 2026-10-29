<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;

use Craft;
use craft\base\Component;
use craft\elements\User;

class Helper extends Component
{

    public function settings()
    {

        $settings = Porter::$plugin->getSettings();
        return $settings;
        
    }

    public function notify($key, $email, $params = null): bool
    {

        try {

            $message = Craft::$app
            ->getMailer()
            ->composeFromKey(
                $key,
                $params
            )
            ->setTo(
                $email
            );

            $user = $params['user'] ?? null;

            if ($user instanceof User)
            {

                if ($user->affiliatedSiteId && !Craft::$app->getRequest()->getIsSiteRequest())
                {
                    $message->siteId = $user->affiliatedSiteId;
                }

                $message->language = $user->getPreferredLanguage();

            }

            return $message->send();

        } catch (\Throwable $e) {

            Porter::warn('[Notify] Error sending notification: ' . $e->getMessage());

            return false;

        }

    }

}

<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;

use Craft;
use craft\base\Component;

class Helper extends Component
{

    public function settings()
    {

        $settings = Porter::$plugin->getSettings();
        return $settings;
        
    }

    public function notify($key, $email, $params = null)
    {

        $message = Craft::$app
        ->getMailer()
        ->composeFromKey(
            $key,
            $params
        )
        ->setTo(
            $email
        );

        try {

            $emailSent = $message->send();

        } catch (\Throwable $e) {

            Porter::log('[Notify] Error sending notification: ' . $e->getMessage());

        }

    }

}

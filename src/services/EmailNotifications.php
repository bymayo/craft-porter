<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;

use Craft;
use craft\base\Component;
use craft\elements\User;

class EmailNotifications extends Component
{

   private $settings;

   public function init(): void
   {
       $this->settings = Porter::getInstance()->helper->settings();
   }

   public function sendWelcome(User $user)
   {

      if (!$this->settings->emailWelcome)
      {
         return;
      }

      if (!$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_welcome_email',
         $user->email,
         array(
            'user' => $user
         )
      );

   }

}

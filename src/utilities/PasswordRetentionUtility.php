<?php

namespace bymayo\porter\utilities;

use bymayo\porter\Porter;

use Craft;
use craft\base\Utility;

class PasswordRetentionUtility extends Utility
{

   public static function displayName(): string
   {
      return Craft::t('porter', 'Password Retention');
   }

   public static function id(): string
   {
      return 'porter-password-retention';
   }

   public static function icon(): ?string
   {
      return Craft::getAlias('@bymayo/porter/icon-mask.svg');
   }

   public static function contentHtml(): string
   {

      $retention = Porter::getInstance()->passwordRetention;

      return Craft::$app->getView()->renderTemplate('porter/utilities/passwordRetention', [
         'expired' => $retention->countExpired(),
         'expiringSoon' => $retention->countExpiringSoon(),
         'canForceReset' => Craft::$app->getUser()->checkPermission('porter:forceResetPasswords')
      ]);

   }

}

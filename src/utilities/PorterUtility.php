<?php

namespace bymayo\porter\utilities;

use bymayo\porter\Porter;

use Craft;
use craft\base\Utility;

/**
 * One utility covering everything in Porter that needs a hands-on action.
 *
 * Each section only renders when its feature is switched on, so the utility
 * shows just what's relevant rather than a list of disabled things.
 */
class PorterUtility extends Utility
{

   public static function displayName(): string
   {
      return Craft::t('porter', 'Porter');
   }

   public static function id(): string
   {
      return 'porter';
   }

   public static function icon(): ?string
   {
      return Craft::getAlias('@bymayo/porter/icon-mask.svg');
   }

   /**
    * Whether there's anything worth showing.
    */
   public static function isAvailable(): bool
   {

      $porter = Porter::getInstance();

      return $porter->passwordRetention->isEnabled() || (bool) $porter->helper->settings()->emailBurners;

   }

   public static function contentHtml(): string
   {

      $porter = Porter::getInstance();
      $settings = $porter->helper->settings();

      $retentionEnabled = $porter->passwordRetention->isEnabled();
      $burnersEnabled = (bool) $settings->emailBurners;

      return Craft::$app->getView()->renderTemplate('porter/utilities/porter', [

         'retentionEnabled' => $retentionEnabled,
         'expired' => $retentionEnabled ? $porter->passwordRetention->countExpired() : 0,
         'expiringSoon' => $retentionEnabled ? $porter->passwordRetention->countExpiringSoon() : 0,
         'canForceReset' => Craft::$app->getUser()->checkPermission('porter:forceResetPasswords'),
         'exemptAdmins' => (bool) $settings->passwordExemptAdmins,
         'exemptGroups' => !empty($settings->passwordExemptGroups),

         'burnersEnabled' => $burnersEnabled,
         'list' => $burnersEnabled ? $porter->burnerEmails->listInfo() : null,
         'downloaded' => $burnersEnabled ? $porter->burnerEmails->hasList() : false,
         'source' => $porter->burnerEmails->sourceUrl()

      ]);

   }

}

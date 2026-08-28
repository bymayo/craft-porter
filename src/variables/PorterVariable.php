<?php

namespace bymayo\porter\variables;

use bymayo\porter\Porter;

use Craft;

class PorterVariable
{

   public function settings()
   {
      return Porter::getInstance()->helper->settings();
   }

   public function deleteAccountForm(Array $properties = null)
   {

      return Porter::getInstance()->deleteAccount->renderFormTemplate($properties);

   }

   public function deleteAccountFormProperties()
   {

      return Porter::getInstance()->deleteAccount->defaultTemplateProperties();

   }

   public function deleteAccountConfirmation()
   {

      return Porter::getInstance()->deleteAccount->confirmationType();

   }

   public function magicLinkForm(Array $properties = null)
   {

      return Porter::getInstance()->magicLink->renderFormTemplate($properties);

   }

   public function magicLinkFormProperties()
   {

      return Porter::getInstance()->magicLink->defaultTemplateProperties();

   }

   public function deactivateAccountForm(Array $properties = null)
   {

      return Porter::getInstance()->deactivateAccount->renderFormTemplate($properties);

   }

   public function deactivateAccountFormProperties()
   {

      return Porter::getInstance()->deactivateAccount->defaultTemplateProperties();

   }

   public function passwordStrengthIndicator(Array $properties = null)
   {

      return Porter::getInstance()->passwordPolicy->renderIndicatorTemplate($properties);

   }

   public function passwordStrengthIndicatorProperties()
   {

      return Porter::getInstance()->passwordPolicy->defaultIndicatorProperties();

   }

   /**
    * The active password rules, for building your own checklist.
    */
   public function passwordPolicy()
   {

      return Porter::getInstance()->passwordPolicy->describeRules();

   }

   /**
    * The password rules as one readable sentence.
    */
   public function passwordPolicyMessage()
   {

      return Porter::getInstance()->passwordPolicy->message();

   }

   /**
    * The strength score (0-4) for a password.
    */
   public function passwordStrength($password)
   {

      return Porter::getInstance()->passwordPolicy->score($password);

   }

   /**
    * The nonce stamped on the strength indicator's inline script.
    *
    * Include this in your own Content Security Policy header so the browser
    * will allow the script.
    */
   public function cspNonce()
   {

      return Porter::getInstance()->security->getNonce();

   }

   /**
    * Whether the current user's password has expired.
    */
   public function passwordExpired($user = null)
   {

      $user = $user ?: Craft::$app->getUser()->getIdentity();

      return Porter::getInstance()->passwordRetention->isExpired($user);

   }

   /**
    * When the current user's password expires, or null.
    */
   public function passwordExpiresAt($user = null)
   {

      $user = $user ?: Craft::$app->getUser()->getIdentity();

      return Porter::getInstance()->passwordRetention->expiresAt($user);

   }

   /**
    * Whole days until the current user's password expires, or null.
    */
   public function passwordExpiresInDays($user = null)
   {

      $user = $user ?: Craft::$app->getUser()->getIdentity();

      return Porter::getInstance()->passwordRetention->daysUntilExpiry($user);

   }

}

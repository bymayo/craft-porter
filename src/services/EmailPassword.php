<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\services\Helper;

use Craft;
use craft\base\Component;

class EmailPassword extends Component
{

   private $settings;

   public function init(): void
   {
       $this->settings = Porter::getInstance()->helper->settings();
   }

   /**
    * Kept for backwards compatibility.
    *
    * The policy now runs through Craft's own validation — see
    * bymayo\porter\rules\UserRules and the PasswordPolicy service. This
    * still works for anything calling it directly.
    */
   public function checkPasswordPolicy($password, $user = null)
   {

      $policy = Porter::getInstance()->passwordPolicy;

      $errors = $policy->check($password, $user);

      $min = $policy->minLength();

      if (strlen((string) $password) < $min)
      {
         array_unshift($errors, Craft::t('porter', 'Password must contain at least {min} characters.', ['min' => $min]));
      }

      $max = $policy->maxLength();

      if ($max && strlen((string) $password) > $max)
      {
         $errors[] = Craft::t('porter', 'Password can’t be more than {max} characters.', ['max' => $max]);
      }

      return $errors;

   }

   public function containsUppercase($password)
   {
      if(!preg_match('/[A-Z]/', $password)){
         return Craft::t('porter', 'Password must contain at least 1 uppercase character.');
      }
   }

   public function containsLowercase($password)
   {
      if(!preg_match('/[a-z]/', $password)){
         return Craft::t('porter', 'Password must contain at least 1 lowercase character.');
      }
   }

   public function containsNumeric($password)
   {
      if(!preg_match('/\d/', $password)){
         return Craft::t('porter', 'Password must contain at least 1 numeric character.');
      }
   }

   public function containsSymbol($password)
   {
      if(!preg_match('/[^a-zA-Z\d]/', $password)){
         return Craft::t('porter', 'Password must contain at least 1 symbol character e.g. #, ?, $.');
      }
   }

   /**
    * Kept for backwards compatibility.
    *
    * Burner checking now runs through the BurnerEmails service, against a
    * bundled domain list rather than a third-party API.
    */
   public function checkBurnerEmail($email)
   {
      return Porter::getInstance()->burnerEmails->check($email);
   }


}

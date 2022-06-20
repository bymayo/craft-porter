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

   public function checkPasswordPolicy($password)
   {

      $errors = [];

      foreach ($this->settings->passwordForcePolicyRules as $rule)
      {
         switch ($rule) {
            case 'lowercase':
               $errors[] = $this->containsLowercase($password);
               break;
            case 'uppercase':
               $errors[] = $this->containsUppercase($password);
               break;
            case 'numeric':
               $errors[] = $this->containsNumeric($password);
               break;
            case 'symbol':
               $errors[] = $this->containsSymbol($password);
               break;
         }
      }

      if (strlen($password) < $this->settings->passwordForcePolicyMin)
      {
         $errors[] = Craft::t('porter', 'Password must contain at least {min} characters.', ['min' => $this->settings->passwordForcePolicyMin]);
      }

      if (strlen($password) < $this->settings->passwordForcePolicyMax)
      {
         $errors[] = Craft::t('porter', 'Password must be less than {max} characters.', ['max' => $this->settings->passwordForcePolicyMax]);
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

   public function checkBurnerEmail($email)
   {

      $errors = [];

      if (!$this->verfierApi($email))
      {
         $errors[] = Craft::t('porter', 'Email addresses considered Disposable, Invalid or have a non-existent domain are not allowed.');
      }

      return $errors;

   }

   /**
   * https://github.com/email-verifier/verifier-php
   */
   public function verfierApi($email = null, $details = false)
   {

      $settings = Porter::getInstance()->helper->settings();

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, 'https://verifier.meetchopra.com/verify/'. $email .'?token='. $settings->emailsBurnersVerifierApiKey);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      $result = curl_exec($ch);
      
      if (curl_errno($ch)) {
         echo 'Error:'. curl_error($ch);
      }
      
      curl_close ($ch);

      if ($details) {
         return json_decode($result, true);
      } else {
         $data = json_decode($result, true);
         return $data['status'];
      }

   }

}

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
               $error = $this->containsLowercase($password);
               if ($error) { $errors[] = $error; }
               break;
            case 'uppercase':
               $error = $this->containsUppercase($password);
               if ($error) { $errors[] = $error; }
               break;
            case 'numeric':
               $error = $this->containsNumeric($password);
               if ($error) { $errors[] = $error; }
               break;
            case 'symbol':
               $error = $this->containsSymbol($password);
               if ($error) { $errors[] = $error; }
               break;
         }
      }

      if (strlen($password) < $this->settings->passwordForcePolicyMin)
      {
         $errors[] = Craft::t('porter', 'Password must contain at least {min} characters.', ['min' => $this->settings->passwordForcePolicyMin]);
      }

      if (strlen($password) > $this->settings->passwordForcePolicyMax)
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

      curl_setopt($ch, CURLOPT_TIMEOUT, 5);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

      $result = curl_exec($ch);

      if (curl_errno($ch)) {
         Craft::error('Porter email verifier error: ' . curl_error($ch), __METHOD__);
         curl_close($ch);
         return false;
      }

      curl_close($ch);

      $data = json_decode($result, true);

      if (!is_array($data)) {
         Craft::error('Porter email verifier returned invalid response', __METHOD__);
         return false;
      }

      if ($details) {
         return $data;
      }

      return $data['status'] ?? false;

   }

}

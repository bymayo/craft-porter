<?php

namespace bymayo\porter\validators;

use bymayo\porter\Porter;

use Craft;
use yii\validators\Validator;

/**
 * Checks a password against Have I Been Pwned using k-anonymity —
 * only the first five characters of the SHA-1 hash leave the server.
 */
class PwnedValidator extends Validator
{

   /**
    * @inheritdoc
    */
   public function validateAttribute($model, $attribute): void
   {

      $password = $model->$attribute;

      if ($password === null || $password === '')
      {
         return;
      }

      $policy = Porter::getInstance()->passwordPolicy;

      $breached = $policy->pwned($password);

      if ($breached === true)
      {
         $this->addError($model, $attribute, Craft::t('porter', 'This password has appeared in a data breach. Please choose another one.'));
         return;
      }

      // Couldn't reach the API. Fail open by default so a Pwned Passwords
      // outage can't lock everyone out of changing their password.
      if ($breached === null && $policy->pwnedFailsClosed())
      {
         $this->addError($model, $attribute, Craft::t('porter', 'Your password couldn’t be checked against known data breaches right now. Please try again shortly.'));
      }

   }

   /**
    * @inheritdoc
    */
   public function isEmpty($value): bool
   {

      if (isset($this->isEmpty))
      {
         return call_user_func($this->isEmpty, $value);
      }

      return $value === null;

   }

}

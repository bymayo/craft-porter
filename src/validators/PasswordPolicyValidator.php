<?php

namespace bymayo\porter\validators;

use bymayo\porter\Porter;

use craft\elements\User;
use yii\validators\Validator;

/**
 * Runs Porter's character class, blocklist, strength and history rules.
 *
 * Length is left to Craft's own UserPasswordValidator, which
 * bymayo\porter\rules\UserRules re-registers with Porter's min/max.
 */
class PasswordPolicyValidator extends Validator
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

      $user = $model instanceof User ? $model : null;

      foreach (Porter::getInstance()->passwordPolicy->check($password, $user) as $error)
      {
         $this->addError($model, $attribute, $error);
      }

   }

   /**
    * @inheritdoc
    *
    * An empty string is a real (bad) value here, not an absent one.
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

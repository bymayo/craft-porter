<?php

namespace bymayo\porter\rules;

use bymayo\porter\Porter;
use bymayo\porter\validators\PasswordPolicyValidator;
use bymayo\porter\validators\PwnedValidator;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\validators\UserPasswordValidator;

/**
 * Builds the password validation rules Porter layers onto the User element.
 *
 * These run through Craft's normal element validation, so the same rules
 * apply in the control panel, on front-end registration and set-password
 * forms, in the console, and in queue jobs — with errors landing on the
 * `newPassword` attribute where templates and the CP already look for them.
 */
class UserRules
{

   /**
    * Applies Porter's password rules to a User element's rule set.
    *
    * Each feature is independent — length rules, the blocklist, history and
    * breach checking can each be used on their own — so only the parts that
    * are actually switched on contribute anything.
    */
   public static function applyTo(array $rules, ?User $user = null): array
   {

      $policy = Porter::getInstance()->passwordPolicy;

      if ($policy->isExempt($user))
      {
         return $rules;
      }

      // Only take Craft's own rule over when we've got lengths to impose.
      if ($policy->lengthRulesEnabled())
      {
         $rules = self::withoutCraftPasswordRule($rules);
         $rules[] = self::lengthRule($policy, $user);
      }

      if ($policy->confirmEnabled() || $policy->blocklistEnabled() || $policy->historyEnabled() || $policy->minScore() > 0 || $policy->lengthRulesEnabled())
      {
         $rules[] = [
            ['newPassword'],
            PasswordPolicyValidator::class,
            'skipOnError' => false
         ];
      }

      // Kept last and skipped once anything else has failed, so a password
      // that's already going to be rejected never hits the network.
      if ($policy->pwnedEnabled())
      {
         $rules[] = [
            ['newPassword'],
            PwnedValidator::class,
            'skipOnError' => true
         ];
      }

      return $rules;

   }

   /**
    * Removes Craft's own password rule from a rule set.
    *
    * Matched on the validator class rather than the attribute name, so
    * Craft's other `newPassword` rules (if any get added later) survive.
    */
   public static function withoutCraftPasswordRule(array $rules): array
   {

      return array_values(array_filter($rules, function ($rule) {
         return !isset($rule[1]) || $rule[1] !== UserPasswordValidator::class;
      }));

   }

   /**
    * Craft's own password validator, re-registered with Porter's lengths.
    *
    * Reusing Craft's class rather than rolling our own keeps its "must differ
    * from your current password" behaviour on forced resets.
    */
   public static function lengthRule($policy, ?User $user = null): array
   {

      $min = $policy->minLength();
      $max = $policy->maxLength();

      $rule = [
         ['newPassword'],
         UserPasswordValidator::class,
         'min' => $min,
         'tooShort' => Craft::t('porter', 'Password must contain at least {min} characters.', ['min' => $min]),
         'forceDifferent' => $user ? $user->passwordResetRequired : false,
         'currentPassword' => self::currentPasswordHash($user),
         'skipOnError' => false
      ];

      if ($max)
      {
         $rule['max'] = $max;
         $rule['tooLong'] = Craft::t('porter', 'Password can’t be more than {max} characters.', ['max' => $max]);
      }

      return $rule;

   }

   /**
    * The user's current password hash, but only when Craft would have
    * looked it up itself (i.e. a forced reset is in progress).
    */
   public static function currentPasswordHash(?User $user = null): ?string
   {

      if (!$user || !$user->id || !$user->passwordResetRequired)
      {
         return null;
      }

      return (new Query())
         ->select(['password'])
         ->from([Table::USERS])
         ->where(['id' => $user->id])
         ->scalar() ?: null;

   }

}

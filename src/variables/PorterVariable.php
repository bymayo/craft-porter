<?php
/**
 * Porter plugin for Craft CMS 3.x
 *
 * A toolkit with lots of helpers for users and accounts
 *
 * @link      https://bymayo.co.uk
 * @copyright Copyright (c) 2020 Jason Mayo
 */

namespace bymayo\porter\variables;

use bymayo\porter\Porter;

use Craft;

/**
 * @author    Jason Mayo
 * @package   Porter
 * @since     1.0.0
 */
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

   public function deleteAccountConfirmation()
   {

      return Porter::getInstance()->deleteAccount->confirmationType();

   }

   public function magicLinkForm(Array $properties = null)
   {

      return Porter::getInstance()->magicLink->renderFormTemplate($properties);

   }

   public function deactivateAccountForm(Array $properties = null)
   {

      return Porter::getInstance()->deactivateAccount->renderFormTemplate($properties);

   }

}

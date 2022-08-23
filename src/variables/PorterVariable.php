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

}

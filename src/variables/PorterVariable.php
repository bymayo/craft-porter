<?php

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

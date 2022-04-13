<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\services\Helper;

use Craft;
use craft\base\Component;
use craft\helpers\Template;

class DeleteAccount extends Component
{

   private $settings;

   public function init()
   {
       $this->settings = Porter::getInstance()->helper->settings();
   }

   public function confirmationType()
   {

      $confirmationType = $this->settings->deleteAccountConfirmationType;
      
      if ($confirmationType == 'confirmationTypeKeyword')
      {
         return $this->settings->deleteAccountConfirmationKeyword;
      }
      else {

         $confirmationField = $this->settings->deleteAccountConfirmationField;

         $currentUser = Craft::$app->getUser()->getIdentity();

         if ($currentUser)
         {
            return $currentUser->$confirmationField;
         }

      }

   }

   public function renderFormTemplate($properties)
   {

      if (
            $this->settings->deleteAccount &&
            Craft::$app->request->getIsSiteRequest()
      ) {

         $defaultProperties = array(
            'redirect' => $this->settings->deleteAccountRedirect,
            'confirmation' => $this->confirmationType(),
            'alertClass' => 'bg-red-100 text-red-500 font-bold px-3 py-2 rounded mt-2',
            'fieldContainerClass' => 'mb-3',
            'fieldLabelClass' => 'block mb-2',
            'fieldClass' => 'transition appearance-none block w-full bg-white text-gray-700 border border-gray-400 px-3 py-3 rounded shadow leading-tight placeholder-gray-500 placeholder-opacity-100 hover:border-gray-500 focus:border-primary-500 focus:outline-none focus:shadow-outline',
            'buttonClass' => 'transition bg-primary-500 text-white inline-block font-medium py-3 px-6 rounded flex-shrink-0 hover:bg-primary-600 focus:outline-none focus:shadow-outline',
            'buttonLabel' => 'Delete Account',
            'confirmationClass' => 'font-bold'
         );

         $properties = array_unique(array_merge($defaultProperties, $properties), SORT_REGULAR);

         $view = Craft::$app->getView();

         $templatePath = $view->getTemplatesPath();

         $view->setTemplatesPath(Porter::getInstance()->getBasePath());

         $template = $view->renderTemplate('/templates/components/deleteAccountForm', $properties);

         $view->setTemplatesPath($templatePath);

         return Template::raw($template);

      }

   }

   public function deleteAccount($request)
   {

      $currentUser = Craft::$app->getUser()->getIdentity();

      $confirmationField = $request->getBodyParam('confirmationField');

      if ($currentUser && $currentUser->can('deleteUsers'))
      {

         if ($this->confirmationType() == $confirmationField)
         {

            if ($currentUser->admin) {

               if ($request->getAcceptsJson()) 
               {
                  return $this->asJson([
                     'success' => false,
                     'message' => Craft::t('porter', 'porter_delete_account_flash_admins')
                  ]);
               }

               Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_delete_account_flash_admins'));
               return;
               
            }

            $transferContentUser = $this->settings->deleteAccountTransfer;
            $transferContentTo = null;

            if ($transferContentUser)
            {
               // @TODO: Check this is working
               $transferContentTo = Craft::$app->getUsers()->getUserById($transferContentUser);
            }

            $currentUser->inheritorOnDelete = $transferContentTo;

            if (Craft::$app->getElements()->deleteElement($currentUser)) 
            {

                  Porter::getInstance()->helper->notify(
                     'porter_delete_account_confirmation_email',
                     $currentUser->email,
                     array(
                         'user' => $currentUser
                     )
                 );

                  Craft::$app->getUser()->logout(false);

                  if ($request->getAcceptsJson()) 
                  {
                     return $this->asJson([
                        'success' => true,
                        'message' => Craft::t('porter', 'porter_delete_account_flash_success')
                     ]);
                  }

                  Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_delete_account_flash_success'));
                  return true;

            }

         }
         else {

            if ($request->getAcceptsJson()) 
            {
               return $this->asJson([
                  'success' => false,
                  'message' => Craft::t('porter', 'porter_delete_account_flash_incorrect')
               ]);
            }

            Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_delete_account_flash_incorrect'));

         }

      }
      else {

         if ($request->getAcceptsJson()) 
         {
            return $this->asJson([
               'success' => false,
               'message' => Craft::t('porter', 'porter_delete_account_flash_permission')
            ]);
         }
         
         Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_delete_account_flash_permission'));

      }

      return;

   }

}

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

   public function init(): void
   {
       $this->settings = Porter::getInstance()->helper->settings();
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
            'confirmationClass' => 'porter__confirmation',
            'alertClass' => 'porter__alert',
            'fieldContainerClass' => 'porter__field-container',
            'fieldLabelClass' => 'porter__field-label',
            'fieldClass' => 'porter__field',
            'buttonClass' => 'porter__button',
            'buttonText' => 'Delete Account'
         );

         $properties = array_merge($defaultProperties, $properties);

         // Porter::log(print_r($properties, TRUE));

         $view = Craft::$app->getView();

         $templatePath = $view->getTemplatesPath();

         $view->setTemplatesPath(Porter::getInstance()->getBasePath());

         $template = $view->renderTemplate('/templates/components/deleteAccountForm', $properties);

         $view->setTemplatesPath($templatePath);

         return Template::raw($template);

      }

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

            // $transferContentUser = $this->settings->deleteAccountTransfer;
            // $transferContentTo = null;

            // if ($transferContentUser)
            // {
            //    // @TODO: Check this is working
            //    $transferContentTo = Craft::$app->getUsers()->getUserById($transferContentUser);
            // }

            // $currentUser->inheritorOnDelete = $transferContentTo;

            if (Craft::$app->getElements()->deleteElement($currentUser)) 
            {

                  if ($this->settings->deleteAccountConfirmationEmail)
                  {

                     Porter::getInstance()->helper->notify(
                        'porter_delete_account_confirmation_email',
                        $currentUser->email,
                        array(
                           'user' => $currentUser
                        )
                     );

                  }

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

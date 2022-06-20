<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\services\Helper;

use Craft;
use craft\base\Component;
use craft\helpers\Template;

class DeactivateAccount extends Component
{

   private $settings;

   public function init(): void
   {
       $this->settings = Porter::getInstance()->helper->settings();
   }

   public function renderFormTemplate($properties)
   {

      if (
            $this->settings->deactivateAccount &&
            Craft::$app->request->getIsSiteRequest()
      ) {

         $defaultProperties = array(
            'redirect' => $this->settings->deactivateAccountRedirect,
            'alertClass' => 'bg-red-100 text-red-500 font-bold px-3 py-2 rounded mt-2',
            'buttonClass' => 'transition bg-black text-white inline-block font-medium py-3 px-6 rounded flex-shrink-0 | hover:bg-primary-600 | focus:outline-none focus:shadow-outline',
            'buttonLabel' => 'Deactivate Account',
         );

         $properties = array_merge($defaultProperties, $properties);

         // Porter::log(print_r($properties, TRUE));

         $view = Craft::$app->getView();

         $templatePath = $view->getTemplatesPath();

         $view->setTemplatesPath(Porter::getInstance()->getBasePath());

         $template = $view->renderTemplate('/templates/components/deactivateAccountForm', $properties);

         $view->setTemplatesPath($templatePath);

         return Template::raw($template);

      }

   }

   public function deactivateAccount()
   {

      if ($this->settings->deactivateAccount)
      {

         $currentUser = Craft::$app->getUser()->getIdentity();

         if ($currentUser)
         {

            if ($currentUser->admin) {

               Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_deactivate_account_flash_admins'));
               return true;
               
            }

            if (Craft::$app->getUsers()->deactivateUser($currentUser)) 
            {

               if ($this->settings->deactivateAccountConfirmationEmail)
               {

                  Porter::getInstance()->helper->notify(
                     'porter_deactivate_account_confirmation_email',
                     $currentUser->email,
                     array(
                        'user' => $currentUser
                     )
                  );

               }

               Craft::$app->getUser()->logout(false);

               Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_deactivate_account_flash_success'));
               return $this->settings->deactivateAccountRedirect;

            }

         }
         else {
            
            Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_deactivate_account_flash_permission'));

            return false;

         }

      }

   }

}

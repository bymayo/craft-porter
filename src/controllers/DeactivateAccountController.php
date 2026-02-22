<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class DeactivateAccountController extends Controller
{

    public function actionIndex()
    {

         $this->requirePostRequest();

         $action = Porter::getInstance()->deactivateAccount->deactivateAccount();

         if ($action)
         {
            return $this->redirect($action);
         }

   }

}
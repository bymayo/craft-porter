<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class DeactivateAccountController extends Controller
{

    protected array|int|bool $allowAnonymous = ['index'];

    public function actionIndex()
    {

         $action = Porter::getInstance()->deactivateAccount->deactivateAccount();

         if ($action)
         {
            return $this->redirect($action);
         }

   }

}
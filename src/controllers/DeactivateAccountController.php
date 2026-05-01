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

         $request = Craft::$app->getRequest();

         $action = Porter::getInstance()->deactivateAccount->deactivateAccount($request);

         if (is_array($action))
         {
            return $this->asJson($action);
         }

         if ($action)
         {
            return $this->redirectToPostedUrl();
         }

   }

}
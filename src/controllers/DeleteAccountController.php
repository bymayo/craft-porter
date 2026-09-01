<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class DeleteAccountController extends Controller
{

    public function actionDelete()
    {

         $this->requirePostRequest();

         $request = Craft::$app->getRequest();

         $action = Porter::getInstance()->deleteAccount->deleteAccount($request);

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
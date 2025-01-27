<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class DeleteAccountController extends Controller
{

    protected array|int|bool $allowAnonymous = ['delete'];

    public function actionDelete()
    {

         $this->requirePostRequest();

         $request = Craft::$app->getRequest();
         $action = Porter::getInstance()->deleteAccount->deleteAccount($request);

         if ($action)
         {
            if ($action['message']) {
               return $this->asJson($action);
            } else {
               return $this->redirectToPostedUrl();
            }
         }

   }

}
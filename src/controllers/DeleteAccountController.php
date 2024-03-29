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

         if (Porter::getInstance()->deleteAccount->deleteAccount($request))
         {
            return $this->redirectToPostedUrl();
         }

   }

}
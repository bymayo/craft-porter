<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class DeleteAccountController extends Controller
{

    protected $allowAnonymous = ['delete'];

    public function actionDelete()
    {

         $this->requirePostRequest();

         $request = Craft::$app->getRequest();

         if (Porter::getInstance()->deleteAccount->deleteAccount($request))
         {
            $this->redirectToPostedUrl();
            return true;
         }

   }

}
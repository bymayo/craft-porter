<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class BurnerEmailsController extends Controller
{

   /**
    * Downloads the current disposable domain list.
    */
   public function actionUpdate()
   {

      $this->requirePostRequest();
      $this->requireCpRequest();
      $this->requirePermission('utility:porter');

      $count = Porter::getInstance()->burnerEmails->updateList();

      if ($count === null)
      {

         Craft::$app->getSession()->setError(Craft::t('porter', 'Couldn’t download the list. The previous one is still in place.'));

         return $this->redirectToPostedUrl();

      }

      Craft::$app->getSession()->setNotice(Craft::t('porter', '{count} domains downloaded.', ['count' => number_format($count)]));

      return $this->redirectToPostedUrl();

   }

}

<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;

class RetentionController extends Controller
{

   /**
    * Queues a force reset for every user with an expired password.
    */
   public function actionForceResetPasswords()
   {

      $this->requirePostRequest();
      $this->requireCpRequest();
      $this->requirePermission('porter:forceResetPasswords');

      $retention = Porter::getInstance()->passwordRetention;

      if (!$retention->isEnabled())
      {

         Craft::$app->getSession()->setError(Craft::t('porter', 'Password expiry is disabled.'));

         return $this->redirectToPostedUrl();

      }

      $retention->queueResets();

      Craft::$app->getSession()->setNotice(Craft::t('porter', 'Password resets queued.'));

      return $this->redirectToPostedUrl();

   }

}

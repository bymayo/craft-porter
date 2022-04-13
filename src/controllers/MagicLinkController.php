<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;

class MagicLinkController extends Controller
{

   protected $allowAnonymous = array('request', 'access');

   public function actionRequest()
   {

      $this->requirePostRequest();

      $request = Craft::$app->getRequest();

      $email = $request->getBodyParam('email');

      if (Porter::getInstance()->magicLink->request($email))
      {
         $this->redirectToPostedUrl();
      }

   }

   public function actionAccess()
   {

      $request = Craft::$app->getRequest();

      $token = $request->getParam('authToken');

      if (Porter::getInstance()->magicLink->validateToken($token))
      {
          return $this->redirect(UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getPostLoginRedirect()));
      }

      return true;

   }

}
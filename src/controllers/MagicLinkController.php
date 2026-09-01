<?php

namespace bymayo\porter\controllers;

use bymayo\porter\Porter;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;

class MagicLinkController extends Controller
{

   protected array|int|bool $allowAnonymous = array('request', 'access', 'login');

   /**
    * The control panel's magic link sign in screen.
    */
   public function actionLogin()
   {

      $this->requireCpRequest();

      $settings = Porter::getInstance()->helper->settings();

      if (!$settings->magicLink || !$settings->magicLinkControlPanel)
      {
         throw new \yii\web\NotFoundHttpException();
      }

      Craft::$app->getView()->registerAssetBundle(
         \bymayo\porter\assetbundles\porter\PorterMagicLinkAsset::class
      );

      return $this->renderTemplate('porter/cp/magicLink', [], \craft\web\View::TEMPLATE_MODE_CP);

   }

   public function actionRequest()
   {

      $this->requirePostRequest();

      $request = Craft::$app->getRequest();

      $action = Porter::getInstance()->magicLink->request($request);

      if (is_array($action))
      {
         return $this->asJson($action);
      }

      if ($action)
      {
         return $this->redirectToPostedUrl();
      }

   }

   public function actionAccess()
   {

      $request = Craft::$app->getRequest();

      $token = $request->getParam('authToken');

      $result = Porter::getInstance()->magicLink->validateToken($token);

      if ($result === 'cp')
      {
          return $this->redirect(UrlHelper::cpUrl(Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect()));
      }

      if ($result)
      {
          return $this->redirect(UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getPostLoginRedirect()));
      }

   }

}
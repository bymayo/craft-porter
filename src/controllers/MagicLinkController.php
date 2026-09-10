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

          $settings = Porter::getInstance()->helper->settings();

          // Brand new accounts can go somewhere of their own, so a sign up
          // can land on a welcome or profile page rather than the usual spot.
          // The template's override, stored on the token, wins over the setting.
          if ($result === 'new')
          {

              $newUserRedirect = Porter::getInstance()->magicLink->newUserRedirect()
                  ?: $settings->magicLinkRegisterRedirect;

              if ($newUserRedirect)
              {
                  return $this->redirect(UrlHelper::siteUrl($newUserRedirect));
              }

          }

          return $this->redirect(UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getPostLoginRedirect()));

      }

      // Returning nothing here 404s, and the flash validateToken() set is
      // never rendered.
      return $this->redirect($this->_failedAccessUrl());

   }

   /**
    * Where someone who opened a dead link is sent.
    */
   private function _failedAccessUrl(): string
   {

      if (Craft::$app->getRequest()->getIsCpRequest())
      {

         $settings = Porter::getInstance()->helper->settings();

         // Porter's screen renders the flash, Craft's doesn't - but it's
         // all that's left if the screen has since been switched off.
         return ($settings->magicLink && $settings->magicLinkControlPanel)
            ? UrlHelper::cpUrl('magic-link')
            : UrlHelper::cpUrl('login');

      }

      $loginPath = Craft::$app->getConfig()->getGeneral()->getLoginPath();

      // Not always a path: false in headless mode, or if login is off.
      return UrlHelper::siteUrl(is_string($loginPath) ? $loginPath : '');

   }

}
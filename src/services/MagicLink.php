<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\services\Helper;
use bymayo\porter\records\MagicLinkRecord;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Template;

class MagicLink extends Component
{

    private $settings;
    private $defaultTemplateProperties;

    public function init(): void
    {

        $this->settings = Porter::getInstance()->helper->settings();

        $this->defaultTemplateProperties = array(
            'redirect' => $this->settings->magicLinkRedirect,
            'alertClass' => 'porter__alert',
            'fieldContainerClass' => 'porter__field-container',
            'fieldLabelClass' => 'porter__field-label',
            'fieldClass' => 'porter__field',
            'buttonClass' => 'porter__button',
            'buttonText' => 'Send Magic Link',
         );

    }

    public function defaultTemplateProperties()
    {
       return $this->defaultTemplateProperties;
    }

    public function renderFormTemplate($properties)
    {
 
       if (
             $this->settings->magicLink &&
             $this->settings->magicLinkFrontEnd &&
             Craft::$app->request->getIsSiteRequest()
       ) {
 
          $properties = $properties ? array_merge($this->defaultTemplateProperties, $properties) : $this->defaultTemplateProperties;

          $view = Craft::$app->getView();
 
          $templatePath = $view->getTemplatesPath();
 
          $view->setTemplatesPath(Porter::getInstance()->getBasePath());
 
          $template = $view->renderTemplate('/templates/components/magicLinkForm', $properties);
 
          $view->setTemplatesPath($templatePath);
 
          return Template::raw($template);
 
       }
 
    }

   public function request($request)
   {

        if ($this->settings->magicLink) 
        {

            $email = $request->getBodyParam('email');

            $user = $email ? Craft::$app->getUsers()->getUserByUsernameOrEmail($email) : null;

            $token = $user ? $this->createToken($user) : false;

            if ($token)
            {

                Porter::getInstance()->helper->notify(
                    'porter_magic_link_email',
                    $user->email,
                    array(
                        'user' => $user,
                        'link' => $this->createTokenLink($token)
                    )
                );

                if ($request->getAcceptsJson())
                {
                   return [
                      'success' => true,
                      'message' => Craft::t('porter', 'porter_magic_link_sent')
                   ];
                }
 
                Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_sent'));
                return true;
            }

            if ($request->getAcceptsJson())
            {
                return [
                    'success' => false,
                    'message' => Craft::t('porter', 'porter_magic_link_failed')
                ];
            }

            Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_failed'));

        }

   }

   public function invalidateTokens($user)
   {

        MagicLinkRecord::deleteAll(
            [
                'userId' => $user->id
            ]
        );

   }

   public function createTokenLink($token)
   {
    
       return UrlHelper::actionUrl('/porter/magic-link/access', array('authToken' => $token));

   }

   public function createToken($user)
   {

        if (!$user || $user->admin || (!$this->settings->magicLinkControlPanel && $user->can('accessCp')))
        {
            return false;
        }

        if (!$this->canSignInWithLink($user))
        {
            return false;
        }

        $this->invalidateTokens($user);

        $record = new MagicLinkRecord();
        $record->userId = $user->id;
        $record->token = Craft::$app->getSecurity()->generateRandomString(64);

        // Remembered so the link lands them where they asked from, rather
        // than guessing from whether they happen to have control panel access.
        $record->cpLogin = Craft::$app->getRequest()->getIsCpRequest();

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {

            $success = $record->save(false);

            if ($success) {

                $transaction->commit();

                return $record->token;

            }

        } catch (\Throwable $e) {

            $transaction->rollBack();
            throw $e;

        }

   }

   /**
    * Whether this user may be signed in by a magic link.
    *
    * Craft's own login runs these checks in User::authenticate(). A magic
    * link calls Craft::$app->getUser()->login() directly, which only checks
    * the user agent and IP, so without this a link would sign in accounts
    * that are suspended, locked, pending, or flagged for a password reset.
    */
   public function canSignInWithLink(?User $user = null): bool
   {

      if (!$user)
      {
         return false;
      }

      // Mirrors the checks Craft runs in User::authenticate(). Not using
      // craft\helpers\User::getAuthStatus(), which only exists in later 5.x
      // releases and Porter supports ^5.0.
      if ($user->getStatus() !== User::STATUS_ACTIVE)
      {
         // Inactive, archived, pending verification, or suspended.
         return false;
      }

      if ($user->locked)
      {
         return false;
      }

      // Read through the service because UserQuery doesn't select
      // `passwordResetRequired`, so it's false on a user loaded by ID.
      // Without this a link would walk straight past password expiry.
      if (Porter::getInstance()->passwordRetention->resetRequired($user))
      {
         return false;
      }

      if (Craft::$app->getRequest()->getIsCpRequest() && !$user->can('accessCp'))
      {
         return false;
      }

      // A link can't present a second factor, so allowing one would step
      // around two-step verification entirely.
      $auth = Craft::$app->getAuth();

      if ($auth->hasActiveMethod($user) || $auth->is2faRequired($user))
      {
         return false;
      }

      return true;

   }

   public function validateToken($token)
   {

    $query = MagicLinkRecord::findOne(
        [
            'token' => $token
        ]
    );

    if ($query)
    {

        $user = Craft::$app->users->getUserById($query->userId);

        if (!$user) {
            $query->delete();
            Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_token_expired'));
            return;
        }

        $this->invalidateTokens($user);

        if (DateTimeHelper::currentTimeStamp() <= (DateTimeHelper::toDateTime($query->dateCreated)->format('U') + $this->settings->magicLinkExpirySeconds))
        {

            // Re-checked here, not just when the link was created: the account
            // could have been suspended, locked or flagged for a password
            // reset in the meantime.
            if (!$this->canSignInWithLink($user))
            {

                Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_not_allowed'));

                return;

            }

            if (Craft::$app->getUser()->login($user))
            {

                return $query->cpLogin ? 'cp' : true;

            }

        }

    }

    Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_token_expired'));

    return;

   }

}

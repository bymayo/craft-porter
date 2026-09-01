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
    private ?string $newUserRedirect = null;

    public function init(): void
    {

        $this->settings = Porter::getInstance()->helper->settings();

        $this->defaultTemplateProperties = array(
            'redirect' => $this->settings->magicLinkRedirect,
            'newUserRedirect' => $this->settings->magicLinkRegisterRedirect,
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

            $isNew = false;

            // Passwordless sign up: no account yet, so make one and send them
            // a link. The link is what proves they own the address.
            if (!$user && $email && $this->registrationEnabled())
            {

               $user = $this->registerUser($email, $request);

               $isNew = $user !== null;

            }

            $token = $user ? $this->createToken($user, $isNew, $this->postedNewUserRedirect($request)) : false;

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

   /**
    * Where a brand new account should land, once its token has been spent.
    *
    * Only meaningful straight after validateToken() returned 'new', since the
    * token row is deleted as it's used.
    */
   public function newUserRedirect(): ?string
   {
      return $this->newUserRedirect;
   }

   /**
    * The template's `newUserRedirect` override, if there is one.
    *
    * Read through getValidatedBodyParam so a tampered form field can't turn
    * this into an open redirect - Craft hashes the value on the way out and
    * refuses it here if it doesn't match.
    */
   private function postedNewUserRedirect($request): ?string
   {

      if (!$request || !$request->getIsPost())
      {
         return null;
      }

      try
      {
         $redirect = $request->getValidatedBodyParam('newUserRedirect');
      }
      catch (\yii\web\BadRequestHttpException $e)
      {
         return null;
      }

      return $redirect ?: null;

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

   public function createToken($user, bool $isNew = false, ?string $newUserRedirect = null)
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
        $record->newUser = $isNew;

        // Stored on the token rather than the session: the link is opened in
        // a fresh request, often on a different device to the one that asked
        // for it, so there's no session to carry it.
        $record->newUserRedirect = $isNew ? $newUserRedirect : null;

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
    * Whether a magic link request may create a missing account.
    *
    * Gated on Craft's own public registration setting, so this can't be used
    * to create users on a site that has deliberately switched it off.
    */
   public function registrationEnabled(): bool
   {

      if (!$this->settings->magicLink || !$this->settings->magicLinkRegister)
      {
         return false;
      }

      $users = Craft::$app->getProjectConfig()->get('users') ?? [];

      return (bool) ($users['allowPublicRegistration'] ?? false);

   }

   /**
    * Creates an account for a passwordless sign up.
    *
    * Active rather than pending, because the emailed link is what verifies
    * the address, and canSignInWithLink() refuses pending accounts.
    */
   public function registerUser(string $email, $request = null): ?User
   {

      $user = new User();
      $user->email = $email;
      $user->username = $email;
      $user->active = true;
      $user->newPassword = $this->generatePassword();

      if ($request)
      {
         $user->setFieldValuesFromRequest('fields');
      }

      // Saved without validation on purpose: the password is machine
      // generated, so running it through the password policy would be
      // pointless, and would fire a Have I Been Pwned lookup on every sign up.
      if (!Craft::$app->getElements()->saveElement($user, false))
      {

         Porter::warn('[Magic Link] Couldn’t register ' . $email . ': ' . implode(' ', $user->getErrorSummary(true)));

         return null;

      }

      $groupIds = $this->registrationGroupIds();

      if ($groupIds)
      {
         Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
      }

      return $user;

   }

   /**
    * A password the user will never see or need.
    */
   public function generatePassword(): string
   {
      return Craft::$app->getSecurity()->generateRandomString(32);
   }

   /**
    * The groups new accounts are put in.
    */
   public function registrationGroupIds(): array
   {

      $uids = $this->settings->magicLinkRegisterGroups;

      if (!is_array($uids) || !$uids)
      {
         return [];
      }

      $ids = [];

      foreach ($uids as $uid)
      {

         $group = Craft::$app->getUserGroups()->getGroupByUid($uid);

         if ($group)
         {
            $ids[] = $group->id;
         }

      }

      return $ids;

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

                if ($query->cpLogin)
                {
                    return 'cp';
                }

                $this->newUserRedirect = $query->newUserRedirect;

                return $query->newUser ? 'new' : true;

            }

        }

    }

    Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_token_expired'));

    return;

   }

}

<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\services\Helper;
use bymayo\porter\records\MagicLinkRecord;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Template;

class MagicLink extends Component
{

    private $settings;

    public function init(): void
    {
        $this->settings = Porter::getInstance()->helper->settings();
    }

    public function renderFormTemplate($properties)
    {
 
       if (
             $this->settings->magicLink &&
             $this->settings->magicLinkFrontEnd &&
             Craft::$app->request->getIsSiteRequest()
       ) {
 
          $defaultProperties = array(
             'redirect' => $this->settings->magicLinkRedirect,
             'alertClass' => 'porter__alert',
             'fieldContainerClass' => 'porter__field-container',
             'fieldLabelClass' => 'porter__field-label',
             'fieldClass' => 'porter__field',
             'buttonClass' => 'porter__button',
             'buttonText' => 'Send Magic Link',
          );
 
          $properties = $properties ? array_merge($defaultProperties, $properties) : $defaultProperties;
 
          // Porter::log(print_r($properties, TRUE));
 
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

            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($email);

            $token = $this->createToken($user);

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
                   return $this->asJson([
                      'success' => true,
                      'message' => Craft::t('porter', 'porter_magic_link_sent')
                   ]);
                }
 
                Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_sent'));
                return true;
            }

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

        if ($user && !$user->admin && (!$this->settings->magicLinkControlPanel && !$user->can('accessCp') || $this->settings->magicLinkControlPanel && $user->can('accessCp')))
        {

            $this->invalidateTokens($user);

            $record = new MagicLinkRecord();
            $record->userId = $user->id;
            $record->token = Craft::$app->getSecurity()->generateRandomString(64);

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

        return false;

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

        $this->invalidateTokens($user);

        if (DateTimeHelper::currentTimeStamp() <= (strtotime($query->dateCreated) + $this->settings->magicLinkExpirySeconds))
        {

            if (Craft::$app->getUser()->login($user))
            {

                return true;

            }

        }

    }

    Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_token_expired'));

    return;

   }

}

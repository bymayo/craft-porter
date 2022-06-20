<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\services\Helper;
use bymayo\porter\records\MagicLinkRecord;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use craft\helpers\DateTimeHelper;

class MagicLink extends Component
{

    private $settings;

    public function init(): void
    {
        $this->settings = Porter::getInstance()->helper->settings();
    }

   public function request($email)
   {

        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($email);

        $token = $this->createToken($user);

        if ($token)
        {

            Porter::log($this->createTokenLink($token));

            Porter::getInstance()->helper->notify(
                'porter_magic_link_email',
                $user->email,
                array(
                    'user' => $user,
                    'link' => $this->createTokenLink($token)
                )
            );
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

    // @TODO: Check users who can access the CP can't login

        if ($user && !$user->admin)
        {

            // Only allow users to have 1 valid token alive
            $this->invalidateTokens($user);

            $record = new MagicLinkRecord();
            $record->userId = $user->id;
            $record->token = Craft::$app->getSecurity()->generateRandomString(32);

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
        // Check to see if token is invalid

        $user = Craft::$app->users->getUserById($query->userId);

        $this->invalidateTokens($user);

        Porter::log(DateTimeHelper::currentTimeStamp());
        Porter::log(strtotime($query->dateCreated));
        Porter::log($this->settings->magicLinkExpirySeconds);

        if (DateTimeHelper::currentTimeStamp() <= (strtotime($query->dateCreated) + $this->settings->magicLinkExpirySeconds))
        {

            if (Craft::$app->getUser()->login($user))
            {

                return true;
            }

        }

        return Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'Magic link token has expired.'));

    }

   }

}

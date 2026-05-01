<?php

namespace bymayo\porter\console\controllers;

use bymayo\porter\Porter;

use Craft;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Porter user maintenance commands.
 */
class UsersController extends Controller
{

   /**
    * Sends inactive-account reminder emails and deactivates accounts past
    * the configured threshold. Skips admins and any user with CP access.
    */
   public function actionCleanupInactive(): int
   {

      $stats = Porter::getInstance()->inactiveAccounts->cleanupInactive();

      $this->stdout("Sent {$stats['warned']} reminder email(s), deactivated {$stats['deactivated']} account(s).\n");

      return ExitCode::OK;

   }

}

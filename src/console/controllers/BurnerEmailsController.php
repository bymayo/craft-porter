<?php

namespace bymayo\porter\console\controllers;

use bymayo\porter\Porter;

use Craft;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Disposable email domain list maintenance.
 */
class BurnerEmailsController extends Controller
{

   /**
    * Refreshes the disposable domain list from upstream. The list is
    * regenerated daily, so this is worth running on a schedule.
    */
   public function actionUpdate(): int
   {

      $service = Porter::getInstance()->burnerEmails;

      $had = $service->hasList();
      $before = $service->listInfo()['count'];

      $count = $service->updateList();

      if ($count === null)
      {

         $this->stderr($had
            ? "Couldn't update the list. The previous one is still in place ({$before} domains).\n"
            : "Couldn't download the list. Disposable domains aren't being blocked.\n");

         return ExitCode::UNAVAILABLE;

      }

      $this->stdout($had
         ? "Updated: {$count} domains (was {$before}).\n"
         : "Downloaded: {$count} domains.\n");

      return ExitCode::OK;

   }

}

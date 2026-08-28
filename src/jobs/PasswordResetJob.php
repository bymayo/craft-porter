<?php

namespace bymayo\porter\jobs;

use bymayo\porter\Porter;
use bymayo\porter\batchers\PasswordResetBatcher;

use Craft;
use craft\base\Batchable;
use craft\elements\User;
use craft\queue\BaseBatchedJob;

/**
 * Flags every user with an expired password as needing a reset.
 *
 * Batched so sites with a lot of users don't try to do it in one request.
 */
class PasswordResetJob extends BaseBatchedJob
{

   /**
    * @var bool Whether users who have never changed their password count as expired.
    */
   public bool $includeNeverChanged = false;

   public function init(): void
   {

      parent::init();

      $this->batchSize = 500;

      // Set on the job, not just on the push. BaseBatchedJob re-queues its
      // own continuation with $this->priority and $this->ttr, so anything
      // passed to Queue::push() alone is lost after the first batch. The TTR
      // also drives its "stop before we run out of time" check, which is
      // skipped entirely while it's null.
      $this->priority = 10;
      $this->ttr = 300;

   }

   protected function loadData(): Batchable
   {
      return new PasswordResetBatcher(
         Porter::getInstance()->passwordRetention->expiredUserIds($this->includeNeverChanged)
      );
   }

   protected function processItem(mixed $item): void
   {

      if ($item instanceof User)
      {
         Porter::getInstance()->passwordRetention->forceReset($item);
      }

   }

   protected function defaultDescription(): ?string
   {
      return Craft::t('porter', 'Resetting expired passwords');
   }

}

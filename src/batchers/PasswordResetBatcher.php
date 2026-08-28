<?php

namespace bymayo\porter\batchers;

use bymayo\porter\Porter;

use craft\base\Batchable;

/**
 * Batches users for the password reset job.
 *
 * Holds IDs rather than User elements, and hydrates a batch at a time, so a
 * site with a large user table doesn't have to fit all of them in memory.
 */
class PasswordResetBatcher implements Batchable
{

   /**
    * @param int[] $userIds
    */
   public function __construct(
      private array $userIds
   ) {
   }

   public function count(): int
   {
      return count($this->userIds);
   }

   public function getSlice(int $offset, int $limit): iterable
   {

      $ids = array_slice($this->userIds, $offset, $limit);

      return Porter::getInstance()->passwordRetention->usersById($ids);

   }

}

<?php

namespace bymayo\porter\services;

use Craft;
use craft\base\Component;

class Security extends Component
{

   private ?string $nonce = null;

   /**
    * A per-request nonce for the strength indicator script.
    */
   public function getNonce(): string
   {

      if ($this->nonce === null)
      {
         $this->nonce = Craft::$app->getSecurity()->generateRandomString(32);
      }

      return $this->nonce;

   }

}

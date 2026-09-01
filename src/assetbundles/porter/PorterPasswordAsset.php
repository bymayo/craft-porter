<?php

namespace bymayo\porter\assetbundles\porter;

use Craft;
use craft\web\AssetBundle;

/**
 * The strength indicator assets.
 *
 * Deliberately has no CpAsset dependency, so the same bundle can be
 * registered on front-end templates without dragging the whole control
 * panel bundle along with it.
 */
class PorterPasswordAsset extends AssetBundle
{

    public function init()
    {
        $this->sourcePath = "@bymayo/porter/assetbundles/porter/dist";

        $this->js = [
            'js/PorterPasswordStrength.js',
            'js/PorterPasswordConfirm.js',
        ];

        $this->css = [
            'css/PorterPassword.css',
        ];

        parent::init();
    }

}

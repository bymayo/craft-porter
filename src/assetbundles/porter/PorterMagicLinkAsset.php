<?php

namespace bymayo\porter\assetbundles\porter;

use Craft;
use craft\web\AssetBundle;

/**
 * The control panel login screen magic link entry point.
 */
class PorterMagicLinkAsset extends AssetBundle
{

    public function init()
    {
        $this->sourcePath = "@bymayo/porter/assetbundles/porter/dist";

        $this->js = [
            'js/PorterMagicLink.js',
        ];

        $this->css = [
            'css/PorterMagicLink.css',
        ];

        parent::init();
    }

}

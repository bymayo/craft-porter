<?php

namespace bymayo\porter\assetbundles\porter;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PorterAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@bymayo/porter/assetbundles/porter/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Porter.js',
        ];

        $this->css = [
            'css/Porter.css',
        ];

        parent::init();
    }
}

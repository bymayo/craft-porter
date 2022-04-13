<?php
/**
 * Porter plugin for Craft CMS 3.x
 *
 * A toolkit with lots of helpers for users and accounts
 *
 * @link      https://bymayo.co.uk
 * @copyright Copyright (c) 2020 Jason Mayo
 */

namespace bymayo\porter\assetbundles\porter;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Jason Mayo
 * @package   Porter
 * @since     1.0.0
 */
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

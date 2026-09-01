/**
 * Porter — magic link entry point on the control panel login screen.
 *
 * Craft's login template has no template hook, so the link is added here.
 * It's styled to match the passkey button and sits alongside it.
 *
 * Config arrives as window.porterMagicLink.
 */
(function (window, document) {
    'use strict';

    function build(config) {

        var anchor = document.querySelector('.porter-magic-link');

        if (anchor) {
            return true;
        }

        var container = document.querySelector('.login-container');

        if (!container) {
            return false;
        }

        var wrapper = document.createElement('div');
        wrapper.className = 'flex alternative-login-methods porter-magic-link';

        var link = document.createElement('a');
        link.className = 'btn fullwidth porter-magic-link__btn';
        link.href = config.url;
        link.textContent = config.linkText;

        wrapper.appendChild(link);

        // After the passkey buttons where they exist, so the alternatives
        // stay grouped together.
        var alternatives = document.querySelector('.alternative-login-methods');
        var anchorPoint = alternatives || container;

        anchorPoint.parentNode.insertBefore(wrapper, anchorPoint.nextSibling);

        return true;

    }

    function init() {

        var config = window.porterMagicLink;

        if (!config || !config.url) {
            return;
        }

        // Craft writes the login form with document.write, so it's there by
        // now, but retry once in case that changes.
        if (!build(config)) {
            setTimeout(function () { build(config); }, 250);
        }

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window, document);

/**
 * Porter — password confirmation field.
 *
 * Adds a second password field next to Craft's own, so a typo can't be saved
 * unnoticed. The server does the actual comparison; this is the input plus
 * immediate feedback.
 *
 * Config arrives as window.porterPasswordConfirm.
 */
(function (window, document) {
    'use strict';

    function build(config) {

        var field = document.getElementById('newPassword');

        if (!field || document.getElementById(config.name)) {
            return !!field;
        }

        var wrapper = field.closest('.field') || field.parentNode;

        var container = document.createElement('div');
        container.className = 'field porter-password-confirm';

        var heading = document.createElement('div');
        heading.className = 'heading';

        var label = document.createElement('label');
        label.setAttribute('for', config.name);
        label.textContent = config.label;

        heading.appendChild(label);

        var input = document.createElement('div');
        input.className = 'input ltr';

        // Wrapper and 'password' class are what Craft.PasswordInput looks for
        // when it builds the Show toggle.
        var passwordWrapper = document.createElement('div');
        passwordWrapper.className = 'passwordwrapper';

        var confirm = document.createElement('input');
        confirm.type = 'password';
        confirm.id = config.name;
        confirm.name = config.name;
        confirm.className = 'text password fullwidth';
        confirm.autocomplete = 'new-password';

        passwordWrapper.appendChild(confirm);
        input.appendChild(passwordWrapper);

        container.appendChild(heading);
        container.appendChild(input);

        // Directly under the password input, so it sits above the strength
        // meter and checklist rather than below them.
        var inputWrapper = field.closest('.input');

        if (inputWrapper && inputWrapper.parentNode === wrapper) {
            wrapper.insertBefore(container, inputWrapper.nextSibling);
        } else {
            wrapper.parentNode.insertBefore(container, wrapper.nextSibling);
        }

        // Let the checklist judge the confirm rule live.
        confirm.setAttribute('data-porter-password-confirm', '1');

        // Craft's Show toggle swaps the input for a clone, so both fields are
        // looked up fresh each time rather than held in a closure.
        function current(id) {
            return document.getElementById(id);
        }

        function compare() {

            var confirmEl = current(config.name);
            var passwordEl = current('newPassword');

            if (!confirmEl || !passwordEl) {
                return;
            }

            container.classList.toggle(
                'has-error',
                confirmEl.value !== '' && confirmEl.value !== passwordEl.value
            );

        }

        // Delegated for the same reason: a swapped-in clone would lose a
        // listener bound directly to the original element.
        document.addEventListener('input', function (event) {
            var target = event.target;
            if (target && target.id && (target.id === config.name || target.id === 'newPassword')) {
                compare();
            }
        });

        // Reuse Craft's own Show toggle rather than rebuilding it. Only
        // present in the control panel, so the front end simply goes without.
        if (window.Craft && window.Craft.PasswordInput && window.jQuery) {
            try {
                new window.Craft.PasswordInput(confirm);
            } catch (e) {
                // Not fatal: the field still works, just without the toggle.
            }
        }

        return true;

    }

    function init() {

        var config = window.porterPasswordConfirm;

        if (!config) {
            return;
        }

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

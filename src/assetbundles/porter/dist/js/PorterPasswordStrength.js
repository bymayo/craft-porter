/**
 * Porter — password strength indicator.
 *
 * Scoring here is a line-for-line mirror of
 * bymayo\porter\services\PasswordPolicy::score(). This copy is advisory
 * feedback only — the PHP one is what actually gets enforced on save.
 *
 * Config arrives as window.porterPasswordPolicy.
 */
(function (window, document) {
    'use strict';

    var LEET = {
        '@': 'a', '4': 'a', '8': 'b', '(': 'c', '3': 'e', '6': 'g',
        '1': 'i', '!': 'i', '|': 'i', '0': 'o', '$': 's', '5': 's',
        '7': 't', '+': 't', '2': 'z'
    };

    function normalise(value) {

        var lower = String(value == null ? '' : value).toLowerCase();
        var out = '';

        for (var i = 0; i < lower.length; i++) {
            var character = lower.charAt(i);
            out += Object.prototype.hasOwnProperty.call(LEET, character) ? LEET[character] : character;
        }

        return out;

    }

    function longestRepeatRun(password) {

        var longest = 1;
        var run = 1;

        for (var i = 1; i < password.length; i++) {
            if (password.charAt(i) === password.charAt(i - 1)) {
                run++;
                longest = Math.max(longest, run);
            } else {
                run = 1;
            }
        }

        return longest >= 3 ? longest : 0;

    }

    function longestSequenceRun(password) {

        var longest = 1;
        var run = 1;
        var direction = 0;

        for (var i = 1; i < password.length; i++) {

            var step = password.charCodeAt(i) - password.charCodeAt(i - 1);

            if (step === 1 || step === -1) {

                if (step === direction) {
                    run++;
                } else {
                    direction = step;
                    run = 2;
                }

                longest = Math.max(longest, run);

            } else {
                direction = 0;
                run = 1;
            }

        }

        return longest >= 3 ? longest : 0;

    }

    function isCommon(password, config) {

        var normalised = normalise(password);
        var common = (config && config.common) || [];

        for (var i = 0; i < common.length; i++) {
            if (common[i] === normalised) {
                return true;
            }
        }

        return false;

    }

    function score(password, config) {

        if (!password) {
            return 0;
        }

        var length = password.length;
        var pool = 0;

        if (/[a-z]/.test(password)) { pool += 26; }
        if (/[A-Z]/.test(password)) { pool += 26; }
        if (/[0-9]/.test(password)) { pool += 10; }
        if (/[^a-zA-Z0-9]/.test(password)) { pool += 33; }

        if (pool === 0) {
            return 0;
        }

        var entropy = length * (Math.log(pool) / Math.log(2));

        if (isCommon(password, config)) {
            entropy = Math.min(entropy, 12);
        }

        entropy -= longestRepeatRun(password) * 2;
        entropy -= longestSequenceRun(password) * 2;

        var seen = {};
        var distinct = 0;
        var lower = password.toLowerCase();

        for (var i = 0; i < lower.length; i++) {
            if (!seen[lower.charAt(i)]) {
                seen[lower.charAt(i)] = true;
                distinct++;
            }
        }

        entropy *= Math.max(0.5, Math.min(1, distinct / length));

        if (entropy < 28) { return 0; }
        if (entropy < 36) { return 1; }
        if (entropy < 60) { return 2; }
        if (entropy < 80) { return 3; }

        return 4;

    }

    /**
     * Evaluates one rule descriptor.
     *
     * Returns true (met), false (not met) or null when it can only be
     * checked on the server — history and breach lookups.
     */
    function checkRule(rule, password, config) {

        switch (rule.key) {

            case 'minLength':
                return password.length >= rule.min;

            case 'maxLength':
                return password.length <= rule.max;

            case 'blocklist':
                if (config && config.blocklistCheckable === false) {
                    return null;
                }
                var swaps = !config || config.blocklistSubstitutions !== false;
                var haystack = swaps ? normalise(password) : password.toLowerCase();
                var words = (config && config.blocklist) || [];
                for (var i = 0; i < words.length; i++) {
                    var needle = swaps ? normalise(words[i]) : String(words[i]).toLowerCase();
                    if (needle.length >= 3 && haystack.indexOf(needle) !== -1) {
                        return false;
                    }
                }
                return true;

            case 'strength':
                return score(password, config) >= rule.minScore;

            case 'history':
            case 'pwned':
                return null;

            default:
                if (rule.pattern) {
                    return new RegExp(rule.pattern).test(password);
                }
                return null;

        }

    }

    /**
     * Whether a rule can only be judged on the server.
     *
     * History and breach lookups need the password sending somewhere, and the
     * blocklist can't be checked when the word list is incomplete (the control
     * panel withholds the edited user's own details).
     */
    function isServerOnly(rule, config) {

        if (rule.key === 'history' || rule.key === 'pwned') {
            return true;
        }

        return rule.key === 'blocklist' && config && config.blocklistCheckable === false;

    }

    function buildIndicator(config, showRules) {

        var wrapper = document.createElement('div');
        wrapper.className = 'porter-password-strength';

        var meter = document.createElement('div');
        meter.className = 'porter-password-strength__meter';

        for (var i = 0; i < 5; i++) {
            var segment = document.createElement('span');
            segment.className = 'porter-password-strength__segment';
            meter.appendChild(segment);
        }

        var label = document.createElement('p');
        label.className = 'porter-password-strength__label';
        label.setAttribute('aria-live', 'polite');

        wrapper.appendChild(meter);
        wrapper.appendChild(label);

        var list = null;
        var liveRules = [];

        if (showRules !== false && config.rules && config.rules.length) {

            var deferredRules = [];

            config.rules.forEach(function (rule) {
                (isServerOnly(rule, config) ? deferredRules : liveRules).push(rule);
            });

            // The pass/fail list — only rules the browser can actually judge,
            // so nothing sits there permanently unticked looking like a failure.
            if (liveRules.length) {

                list = document.createElement('ul');
                list.className = 'porter-password-strength__rules';

                liveRules.forEach(function (rule) {
                    var item = document.createElement('li');
                    item.className = 'porter-password-strength__rule';
                    item.setAttribute('data-rule', rule.key);
                    item.textContent = rule.label;
                    list.appendChild(item);
                });

                wrapper.appendChild(list);

            }

            // Everything else is stated up front, but clearly as information
            // rather than as something waiting to go green.
            if (deferredRules.length) {

                var deferredNote = document.createElement('p');
                deferredNote.className = 'porter-password-strength__deferred-label';
                deferredNote.textContent = config.deferredLabel || 'Checked when you save:';

                var deferredList = document.createElement('ul');
                deferredList.className = 'porter-password-strength__rules porter-password-strength__rules--deferred';

                deferredRules.forEach(function (rule) {
                    var item = document.createElement('li');
                    item.className = 'porter-password-strength__rule porter-password-strength__rule--deferred';
                    item.setAttribute('data-rule', rule.key);
                    item.textContent = rule.label;
                    deferredList.appendChild(item);
                });

                wrapper.appendChild(deferredNote);
                wrapper.appendChild(deferredList);

            }

        }

        return { wrapper: wrapper, meter: meter, label: label, list: list, liveRules: liveRules };

    }

    function attach(field, config, container) {

        // Only ever bind to a real input — ids like "password" get used for
        // other things (tab panes, wrappers) more often than you'd think.
        if (!field || field.tagName !== 'INPUT') {
            return;
        }

        if (field.getAttribute('data-porter-strength-attached')) {
            return;
        }

        field.setAttribute('data-porter-strength-attached', '1');

        var showRules = !container || container.getAttribute('data-porter-rules') !== '0';
        var indicator = buildIndicator(config, showRules);

        var fieldWrapper = field.closest('.field');

        if (container) {
            container.appendChild(indicator.wrapper);
        } else if (fieldWrapper) {
            // Inside the field rather than after it, so the field's own
            // bottom margin sits below the whole indicator instead of
            // opening a gap between the input and the meter.
            fieldWrapper.appendChild(indicator.wrapper);
        } else {
            field.parentNode.insertBefore(indicator.wrapper, field.nextSibling);
        }

        function render() {

            var password = field.value || '';
            var current = password.length ? score(password, config) : null;

            if (current === null) {
                indicator.wrapper.removeAttribute('data-score');
            } else {
                indicator.wrapper.setAttribute('data-score', String(current));
            }

            var segments = indicator.meter.querySelectorAll('.porter-password-strength__segment');

            for (var i = 0; i < segments.length; i++) {
                segments[i].className = 'porter-password-strength__segment' +
                    (current !== null && i <= current ? ' is-active' : '');
            }

            // Built from nodes rather than innerHTML — the labels come from
            // the server, so they never get parsed as markup.
            indicator.label.textContent = '';

            if (current !== null) {
                indicator.label.appendChild(
                    document.createTextNode((config.strengthLabel || 'Password strength') + ': ')
                );
                // Named scoreEl, not score — a `var score` here would hoist
                // and shadow the score() function for this whole scope.
                var scoreEl = document.createElement('strong');
                scoreEl.className = 'porter-password-strength__score';
                scoreEl.textContent = config.labels[current] || '';
                indicator.label.appendChild(scoreEl);
            }

            if (indicator.list) {

                var items = indicator.list.querySelectorAll('.porter-password-strength__rule');

                for (var r = 0; r < items.length; r++) {

                    var rule = indicator.liveRules[r];
                    var met = password.length ? checkRule(rule, password, config) : null;

                    items[r].className = 'porter-password-strength__rule' +
                        (met === true ? ' is-met' : '') +
                        (met === false ? ' is-unmet' : '');

                }

            }

        }

        field.addEventListener('input', render);
        render();

    }

    function init() {

        var config = window.porterPasswordPolicy;

        if (!config) {
            return;
        }

        // Containers placed by craft.porter.passwordStrengthIndicator(), which
        // name the field they belong to and control where the indicator sits.
        var containers = document.querySelectorAll('[data-porter-password-strength-for]');

        for (var c = 0; c < containers.length; c++) {

            var name = containers[c].getAttribute('data-porter-password-strength-for');
            var target = document.getElementById(name) || document.querySelector('[name="' + name + '"]');

            attach(target, config, containers[c]);

        }

        // Craft's control panel new-password field. Deliberately not the
        // `password` field, which is the current-password confirmation.
        attach(document.getElementById('newPassword'), config);

    }

    window.PorterPasswordStrength = {
        score: function (password) {
            return score(password, window.porterPasswordPolicy || {});
        },
        attach: function (field) {
            attach(field, window.porterPasswordPolicy || { rules: [], labels: {} });
        },
        init: init
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window, document);

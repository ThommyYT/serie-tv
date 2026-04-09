let timeout = null;
let changeButton = null;
if (changeButton === null) {
    changeButton = () => {
        var buttons = document.querySelectorAll('button');

        buttons.forEach(button => {
            if (button.getAttribute('data-mode-id') !== '5bf011840784117a'
                && button.getAttribute('aria-checked') === 'true') {
                button.classList.remove(['is-selected', 'cdk-focused', 'cdk-mouse-focused']);
                button.setAttribute('aria-checked', 'false');
            } else if (button.getAttribute('data-mode-id') === '5bf011840784117a'
                && button.getAttribute('aria-checked') === 'false') {
                button.setAttribute('aria-checked', 'true');
                button.classList.add(['is-selected', 'cdk-focused', 'cdk-mouse-focused']);
                if (button.hasAttribute('disabled')) {
                    button.removeAttribute('disabled');
                    button.setAttribute('tabindex', '0');
                    button.setAttribute('aria-disabled', 'false');
                }
            }
        });
    }

    if (timeout !== null) window.clearTimeout(timeout);
    else timeout = window.setTimeout(changeButton, 1000);
}
import './bootstrap';

/*
|--------------------------------------------------------------------------
| KAPE-CHA
|--------------------------------------------------------------------------
| The printed menu needs almost no scripting. Every action is a plain form
| post and every quantity is a real number input, so the whole order flow
| works with JavaScript disabled.
|
| What scripting adds is only ever an improvement on that baseline:
|   1. the -/+ stepper moves the number in place
|   2. a cart stepper saves the edit instead of waiting for Update
|   3. a submitting button shows what it is doing and refuses a second press
|   4. a destructive action opens a <dialog> that can explain itself
|
| Nothing here is load-bearing. Turn it off and every screen still works.
*/

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

/*
| Quantity stepper
|-----------------
| The visible number input carries the value, so the form submits a correct
| quantity regardless. These buttons move it within the bounds the product
| allows, and disable themselves at the ends so nobody is left pressing a
| button that cannot do anything.
*/

const syncStepper = (stepper) => {
    const input = stepper.querySelector('input[type="number"]');

    if (!input) {
        return;
    }

    const min = Number(input.min || 1);
    const max = Number(input.max || 99);

    input.value = clamp(Number(input.value) || min, min, max);

    stepper.querySelectorAll('[data-step]').forEach((button) => {
        const step = Number(button.dataset.step);
        const next = Number(input.value) + step;

        button.disabled = step < 0 ? next < min : next > max;
    });
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-step]');

    if (!button || button.disabled) {
        return;
    }

    const stepper = button.closest('[data-stepper]');
    const input = stepper?.querySelector('input[type="number"]');

    if (!input) {
        return;
    }

    const min = Number(input.min || 1);
    const max = Number(input.max || 99);
    const next = (Number(input.value) || min) + Number(button.dataset.step);

    input.value = clamp(next, min, max);
    syncStepper(stepper);

    input.dispatchEvent(new Event('change', { bubbles: true }));

    // In the cart, moving a stepper is the whole intent of the control, so
    // the change is saved at once and the server supplies fresh totals.
    if (button.hasAttribute('data-autosubmit')) {
        button.closest('form')?.requestSubmit();
    }
});

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-stepper] input[type="number"]');

    if (input) {
        syncStepper(input.closest('[data-stepper]'));
    }
});

/*
| Submitting state
|-----------------
| A button that posts is marked busy, has its label replaced with what is
| actually happening, and stops accepting further presses. That is what keeps
| a nervous double tap from placing the same order twice.
*/

const busyForms = new WeakSet();

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || busyForms.has(form)) {
        event.preventDefault();

        return;
    }

    busyForms.add(form);

    const button = form.querySelector('[type="submit"][data-loading-label]');

    if (!button) {
        return;
    }

    const label = button.dataset.loadingLabel;

    button.setAttribute('aria-busy', 'true');
    button.dataset.originalLabel = button.textContent.trim();

    const spinner = document.createElement('span');
    spinner.className = 'kc-btn__spinner';
    spinner.setAttribute('aria-hidden', 'true');

    button.textContent = '';
    button.append(spinner, label);
});

/*
| Moving the customer to the thing they have to fix
|-------------------------------------------------
| The error summary at the top of a form lists every problem, and each entry
| is a link. Following it should do more than scroll: the control the message
| belongs to is marked with data-focus, so focus lands there and a keyboard
| user can correct the field without hunting for it.
|
| On arrival at a form that came back from a failed submission, the first
| invalid control is focused straight away. That is only appropriate because
| this markup only exists when there is something to correct — a page the
| customer loaded normally has no [aria-invalid] on it at all.
*/

const focusControl = (target) => {
    if (!target) {
        return false;
    }

    const control = document.getElementById(target);

    if (!control) {
        return false;
    }

    control.focus({ preventScroll: true });
    control.scrollIntoView({ block: 'center', behavior: 'smooth' });

    return true;
};

document.addEventListener('click', (event) => {
    const link = event.target.closest('.kc-error-summary__link');

    if (!link) {
        return;
    }

    const id = link.getAttribute('href')?.slice(1);
    const error = id ? document.getElementById(id) : null;
    const focusId = error?.dataset.focus;

    // The summary is keyed on the field name, but a field that appears more
    // than once on a page (a quantity on every cart line) has a suffixed id.
    // If the exact target is absent, fall back to the first invalid control.
    if (!focusControl(focusId) && !focusControl(id)) {
        const firstInvalid = document.querySelector('[aria-invalid="true"]');

        if (firstInvalid) {
            firstInvalid.focus();
            firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }

    event.preventDefault();
});

document.addEventListener('DOMContentLoaded', () => {
    const firstInvalid = document.querySelector('[aria-invalid="true"]');

    if (firstInvalid) {
        // Not animated: this happens on arrival, and a moving page is
        // disorienting when you have just come back from a wrong page.
        firstInvalid.focus({ preventScroll: true });
        firstInvalid.scrollIntoView({ block: 'center' });
    }
});

/*
| Confirmation dialogs
|---------------------
| A native <dialog> is focus-trapped, Escape-closable and announced, which is
| why it is used instead of window.confirm: the destructive action can say
| what it is about to do and name the thing it will do it to.
|
| The submit button only exists inside the dialog, so with scripting off the
| action is unavailable rather than accidentally one tap away.
*/

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-confirm-open]');

    if (opener) {
        const dialog = document.getElementById(opener.dataset.confirmOpen);

        if (dialog instanceof HTMLDialogElement) {
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
        }

        return;
    }

    if (event.target.closest('[data-confirm-cancel]')) {
        event.target.closest('dialog')?.close();
    }
});

// Clicking the backdrop (the dialog element itself) dismisses it.
document.addEventListener('click', (event) => {
    const dialog = event.target.closest('dialog');

    if (dialog && event.target === dialog) {
        dialog.close();
    }
});

import './bootstrap';
import './student-image-upload';

const enableFormLoadingStates = () => {
    document.querySelectorAll('form[data-submit-state]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');

            if (!button || button.disabled) return;

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.textContent = button.dataset.loadingLabel || 'Please wait…';
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', enableFormLoadingStates, { once: true });
} else {
    enableFormLoadingStates();
}

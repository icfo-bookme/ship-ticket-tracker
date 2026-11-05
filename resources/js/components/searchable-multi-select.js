export function initializeSearchableMultiSelect(element) {
    const search = element.querySelector('[data-multi-select-search]');
    const options = [...element.querySelectorAll('[data-multi-select-option]')];
    const checkboxes = options.map(option => option.querySelector('input'));
    const selected = element.querySelector('[data-multi-select-selected]');
    const empty = element.querySelector('[data-multi-select-empty]');
    const toggle = element.querySelector('[data-multi-select-toggle]');
    const dropdown = element.querySelector('[data-multi-select-dropdown]');
    const caption = element.querySelector('[data-multi-select-caption]');
    const values = () => checkboxes.filter(input => input.checked).map(input => input.value);

    function closeDropdown() {
        dropdown.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', () => {
        if (!dropdown.hidden) {
            closeDropdown();
            return;
        }
        dropdown.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        search.focus();
    });
    document.addEventListener('click', event => {
        if (!element.contains(event.target)) closeDropdown();
    });
    element.addEventListener('focusout', event => {
        if (!element.contains(event.relatedTarget)) closeDropdown();
    });
    element.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !dropdown.hidden) {
            event.preventDefault();
            event.stopPropagation();
            closeDropdown();
            toggle.focus();
        }
    });

    function filterOptions() {
        const term = search.value.trim().toLowerCase();
        options.forEach((option, index) => {
            option.hidden = !checkboxes[index].dataset.label.toLowerCase().includes(term);
            option.classList.toggle('hidden', option.hidden);
        });
        empty.hidden = options.some(option => !option.hidden);
    }

    function renderSelection() {
        selected.replaceChildren();
        checkboxes.filter(input => input.checked).forEach(input => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500';
            button.setAttribute('aria-label', `Remove ${input.dataset.label}`);
            const label = document.createElement('span');
            label.textContent = input.dataset.label;
            const icon = document.createElement('span');
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = '×';
            button.append(label, icon);
            button.addEventListener('click', () => {
                input.checked = false;
                updateSelection();
                (selected.querySelector('button') || toggle).focus();
            });
            selected.append(button);
        });
        selected.classList.toggle('hidden', values().length === 0);
        caption.textContent = values().length ? `${values().length} selected` : caption.dataset.placeholder;
    }

    function updateSelection() {
        renderSelection();
        element.dispatchEvent(new CustomEvent('multi-select:change', {
            bubbles: true,
            detail: { values: values() },
        }));
    }

    function clear({ notify = true } = {}) {
        checkboxes.forEach(input => { input.checked = false; });
        search.value = '';
        filterOptions();
        if (notify) {
            updateSelection();
        } else {
            renderSelection();
        }
    }

    search.addEventListener('input', filterOptions);
    search.addEventListener('search', filterOptions);
    checkboxes.forEach(input => input.addEventListener('change', updateSelection));
    filterOptions();
    renderSelection();

    return { values, clear };
}

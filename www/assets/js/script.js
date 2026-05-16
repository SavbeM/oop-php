document.addEventListener('DOMContentLoaded', () => {
    const tables = document.querySelectorAll('table');
    tables.forEach((table) => table.classList.add('table-row-hover'));

    const animTargets = document.querySelectorAll('h1, h2, h3, form, table, ul, p');
    animTargets.forEach((el, index) => {
        el.classList.add('reveal');
        setTimeout(() => {
            el.classList.add('reveal-visible');
        }, index * 30);
    });

    const forms = document.querySelectorAll('form');
    forms.forEach((form) => {
        form.setAttribute('autocomplete', 'off');
    });
});

/**
 * @param {String} HTML representing a single element.
 * @param {Boolean} flag representing whether or not to trim input whitespace, defaults to true.
 * @return {Element | HTMLCollection | null}
 */
function fromHTML(html, trim = true) {
    // Process the HTML string.
    html = trim ? html.trim() : html;
    if (!html) return null;

    // Then set up a new template element.
    const template = document.createElement('template');
    template.innerHTML = html;
    const result = template.content.children;

    // Then return either an HTMLElement or HTMLCollection,
    // based on whether the input HTML had one or more roots.
    if (result.length === 1) return result[0];
    return result;
}

const objectMap = (obj, fn) => {
    if (!obj) return [];
    return Object.entries(obj).map(
        ([k, v]) => fn(v, k)
    )
}

/**
 * submit a form
 */
const submitForm = () => {
    document.querySelector("form button[type=submit][value=save]").click()
}

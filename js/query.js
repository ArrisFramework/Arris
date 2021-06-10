/**
 * Return params coded at string as array
 *
 * Location.hash: parseQueryParams( window.location.hash.substr(1) );
 * GET params: parseQueryParams(window.location.search);
 *
 * @param search
 * @returns {{[string]: string}}
 */
function parseQueryParams(search) {
    let hashes = search.slice(search.indexOf('?') + 1).split('&')
    return hashes.reduce((params, hash) => {
        let [key, val] = hash.split('=')
        return Object.assign(params, {[key]: decodeURIComponent(val)})
    }, {});
}

/**
 * Helper, return query GET params as ARRAY
 *
 * @returns {{[string]: string}}
 */
function parseGETParams() {
    return parseQueryParams(window.location.search);
}

/**
 * Helper, return query LOCATION.HASH params as ARRAY
 *
 * @returns {{[string]: string}}
 */
function parseLocHashParams() {
    return parseQueryParams( window.location.hash.substr(1) );
}
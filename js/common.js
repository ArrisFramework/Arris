/**
 * Возвращает форму числительного
 *
 * @param number
 * @param one
 * @param two
 * @param five
 * @returns {*}
 */
function pluralForm(number, one, two, five) {
    let n = Math.abs(number);
    n %= 100;
    if (n >= 5 && n <= 20) {
        return five || one;
    }
    n %= 10;
    if (n === 1) {
        return one;
    }
    if (n >= 2 && n <= 4) {
        return two || one;
    }
    return five || one;
}


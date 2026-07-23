import { createI18n } from 'vue-i18n'

/*
 * Translations are populated in app.js from Inertia shared `translations` prop,
 * which mirrors the contents of lang/ru/*.php and lang/tk/*.php on every request.
 * Single source of truth lives in PHP — this module is just the runtime glue.
 */
export default createI18n({
    legacy: false,
    locale: localStorage.getItem('locale') || 'ru',
    fallbackLocale: 'ru',
    missingWarn: false,
    fallbackWarn: false,
    messages: { ru: {}, tk: {} },
})

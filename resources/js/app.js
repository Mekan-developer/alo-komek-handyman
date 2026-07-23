import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Alo-kömek';

// PHP lang files use Laravel-style `:placeholder`, but vue-i18n interpolates `{placeholder}`.
// Bridge the two so `t('key', { placeholder })` works on the frontend while PHP stays the
// single source of truth. The negative lookbehind keeps vue-i18n linked messages (`@:key`) intact.
const PLACEHOLDER_RE = /(?<!@):([a-zA-Z][a-zA-Z0-9_]*)/g;

function normalizePlaceholders(value) {
    if (typeof value === 'string') {
        return value
            // Escape a literal `@` (e.g. in "name@example.com") so vue-i18n's message
            // compiler doesn't parse it as a linked-message alias and crash at render.
            // Genuine linked messages (`@:key`, `@.modifier:key`) are followed by `:`/`.`
            // and are left intact.
            .replace(/@(?![:.])/g, "{'@'}")
            .replace(PLACEHOLDER_RE, '{$1}');
    }
    if (Array.isArray(value)) {
        return value.map(normalizePlaceholders);
    }
    if (value && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value).map(([key, val]) => [key, normalizePlaceholders(val)]),
        );
    }
    return value;
}

function applyTranslations(translations) {
    if (!translations) { return; }
    Object.entries(translations).forEach(([locale, messages]) => {
        i18n.global.setLocaleMessage(locale, normalizePlaceholders(messages));
    });
}

createInertiaApp({
    title: (title) => `${title}  ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        applyTranslations(props.initialPage.props.translations);

        // Re-apply on every Inertia visit so lang file edits propagate after a page reload.
        router.on('success', (event) => {
            applyTranslations(event.detail.page.props.translations);
        });

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(createPinia())
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

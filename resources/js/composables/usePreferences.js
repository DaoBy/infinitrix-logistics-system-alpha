import '../css/app.css';
import './bootstrap';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { MotionPlugin } from '@vueuse/motion'

import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import vuetify from './plugins/vuetify';
import 'leaflet/dist/leaflet.css';
import { loadPreferences, applyGlobalPreferences } from './stores/preferences';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Load and apply preferences immediately
loadPreferences();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify)
            .use(MotionPlugin)
            .mount(el);

        // Re-apply preferences after navigation
        router.on('navigate', () => {
            setTimeout(() => {
                applyGlobalPreferences();
            }, 100);
        });

        return vueApp;
    },
    progress: {
        color: '#4B5563',
    },
});
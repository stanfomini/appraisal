import '../css/app.css';


import './echo'; 
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy.js'; 

import { initializeTheme } from './composables/useAppearance';
import Draggable from 'vuedraggable';
import SlackSettings from './components/SlackSettings.vue';




const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            
            .component('draggable', Draggable)
            .component('slack-settings', SlackSettings)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});


// This will set light / dark mode on page load...
initializeTheme();

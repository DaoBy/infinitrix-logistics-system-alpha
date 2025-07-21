import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vuetify from 'vite-plugin-vuetify';
import path from 'path'; // Add this import

export default defineConfig({
  plugins: [
    laravel({
      input: 'resources/js/app.js',
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    vuetify({
      autoImport: true,
      styles: 'expose',
    }),
  ],
  resolve: {
    alias: {
      'leaflet': path.resolve(__dirname, 'node_modules/leaflet'),
    },
  },
  optimizeDeps: {
    include: ['leaflet'],
  },
});
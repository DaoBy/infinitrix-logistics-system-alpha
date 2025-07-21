// resources/js/plugins/vuetify.js

import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import '@mdi/font/css/materialdesignicons.css'; // Import Material Design Icons

export default createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi', // Use Material Design Icons as the default icon set
  },
  theme: {
    themes: {
      light: {
        colors: {
          primary: '#1976D2', // Main color
          'input-border': '#4A4A4A', // Darker outline
        },
      },
    },
  },
});
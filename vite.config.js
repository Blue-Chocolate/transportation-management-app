import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/filament/Admin/theme.css',
        'resources/css/filament/Driver/theme.css',
      ],
      refresh: true,
    }),
  ],
})

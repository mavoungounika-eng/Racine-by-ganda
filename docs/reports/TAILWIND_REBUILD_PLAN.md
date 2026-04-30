# Tailwind/Vite Recovery Plan

## Current repository findings
- The repository snapshot currently lacks Node/Vite assets (no `package.json`, `vite.config.js`, `postcss.config.js`, or Tailwind config).
- The frontend files live in `Racine by GANDA/` as static PHP/HTML, SCSS, CSS, and JS assets, so no compiled Tailwind build is in place right now.
- Because the Tailwind v3 ↔ v4 churn is not represented in this snapshot, we need to rebuild the toolchain cleanly rather than continue debugging missing config files.

## Recommended strategy — clean Tailwind v3 toolchain with Vite
Tailwind v3 remains the most stable choice today for Laravel/Vite stacks and aligns with your design-heavy workflow. The steps below reset the toolchain from scratch; adapt paths if your Laravel app lives elsewhere.

### 1) Add the frontend toolchain
1. Initialize Node tooling in the Laravel root:
   ```bash
   npm init -y
   npm install -D tailwindcss@^3.4.11 postcss autoprefixer laravel-vite-plugin@^1.0.3 vite@^5.4.10
   npx tailwindcss init -p
   ```
   This creates `package.json`, `tailwind.config.js`, and `postcss.config.js`.

### 2) Configure Tailwind
Edit `tailwind.config.js` to point at Blade, Vue/React (if any), and JS entrypoints. Example content:
```js
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './resources/js/**/*.vue',
    './resources/js/**/*.jsx',
    './resources/js/**/*.tsx',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'Montserrat', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        brand: {
          50: '#f5f7f9',
          100: '#e6ebf0',
          200: '#c8d2dd',
          300: '#a3b4c7',
          400: '#6a7f9d',
          500: '#3b4f73',
          600: '#2f405f',
          700: '#24324c',
          800: '#1b263b',
          900: '#141c2c',
        },
        accent: '#c19a57',
      },
      boxShadow: {
        premium: '0 25px 50px -12px rgba(0, 0, 0, 0.35)',
      },
    },
  },
  plugins: [forms],
}
```
- Fonts: wire Inter/Montserrat via local files or `@import` in `resources/css/app.css`.
- Colors: customize to your brand palette.

### 3) PostCSS configuration
Use the classic PostCSS stack (created by `tailwindcss init -p`):
```js
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
```

### 4) Vite configuration
Create `vite.config.js` using the Laravel plugin:
```js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
})
```
- Remove any `@tailwindcss/vite` plugin (v4-only) to avoid conflicts.

### 5) Application CSS
In `resources/css/app.css` restore the v3 directives plus brand helpers:
```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@500;600;700&display=swap');

@tailwind base;
@tailwind components;
@tailwind utilities;

:root {
  color-scheme: light;
}

.btn-brand {
  @apply inline-flex items-center justify-center rounded-full bg-brand-700 px-5 py-3 text-sm font-semibold text-white shadow-premium transition hover:bg-brand-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500;
}

.card-premium {
  @apply rounded-2xl bg-white/80 p-6 shadow-premium backdrop-blur;
}
```

### 6) Blade layout includes
Ensure your main layout calls the compiled assets only via Vite (no CDN Tailwind):
```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```
Remove `<link>` tags that point to Tailwind CDN on the same pages to prevent style duplication.

### 7) Verification steps
1. Install dependencies: `npm install`.
2. Run the dev server: `npm run dev` (or `npm run build` for production).
3. Load a page that uses `.btn-brand` or `.card-premium`; confirm styles render.
4. If using Laravel Breeze/Jetstream, clear caches: `php artisan view:clear && php artisan config:clear`.

## Anti-patterns to avoid
- Mixing Tailwind CDN with the compiled stylesheet in Blade templates.
- Keeping v4-only packages/plugins (`@tailwindcss/vite`) when targeting Tailwind v3.
- Leaving `content` paths incomplete; include **all** Blade/components to prevent purging needed classes.
- Dropping `@tailwind base/components/utilities` from `app.css`.
- Running Vite with mismatched Node/Tailwind versions; align with the versions above for stability.

Following the steps above will rebuild a stable Tailwind v3 + Vite pipeline and keep your premium design assets consistent across auth, boutique, marketplace, and ERP experiences.

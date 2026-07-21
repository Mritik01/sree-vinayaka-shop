/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        // maroon/gold/cream/ivory resolve through CSS custom properties (defined as :root
        // defaults in resources/css/app.css, matching today's exact colors) instead of literal
        // hex — this is what makes Admin → Application Customization's theme picker possible: a
        // per-request <style> override in layouts/app.blade.php (customer-only — admin/rider
        // layouts never include it) can swap these variables' values without any rebuild. The
        // `rgb(var(--x) / <alpha-value>)` form is Tailwind's documented pattern for this — it's
        // required specifically so opacity modifiers (bg-maroon-900/80 etc, used throughout this
        // app) keep working unchanged. pista stays literal hex on purpose: it's the fixed
        // semantic success/positive color (accepted-note chips, in-stock badges) and isn't part
        // of any of the 10 brand themes — see config/customer_themes.php.
        maroon: {
          50:  'rgb(var(--color-maroon-50) / <alpha-value>)',
          100: 'rgb(var(--color-maroon-100) / <alpha-value>)',
          400: 'rgb(var(--color-maroon-400) / <alpha-value>)',
          500: 'rgb(var(--color-maroon-500) / <alpha-value>)',
          600: 'rgb(var(--color-maroon-600) / <alpha-value>)',
          700: 'rgb(var(--color-maroon-700) / <alpha-value>)',
          800: 'rgb(var(--color-maroon-800) / <alpha-value>)',
          900: 'rgb(var(--color-maroon-900) / <alpha-value>)',
        },
        gold: {
          50:  'rgb(var(--color-gold-50) / <alpha-value>)',
          100: 'rgb(var(--color-gold-100) / <alpha-value>)',
          300: 'rgb(var(--color-gold-300) / <alpha-value>)',
          400: 'rgb(var(--color-gold-400) / <alpha-value>)',
          500: 'rgb(var(--color-gold-500) / <alpha-value>)',
          600: 'rgb(var(--color-gold-600) / <alpha-value>)',
        },
        pista: {
          100: '#e4f0e4',
          400: '#5c9468',
          500: '#3d7a52',
          600: '#2d5f3e',
        },
        // never themed per-brand (see resources/css/app.css) — the light neutral canvas stays
        // constant across every theme, only maroon/gold vary
        cream: 'rgb(var(--color-cream) / <alpha-value>)',
        ivory: 'rgb(var(--color-ivory) / <alpha-value>)',
      },
      fontFamily: {
        display: ['"Poppins"', 'sans-serif'],
        body: ['"Poppins"', 'sans-serif'],
        hindi: ['"Baloo 2"', 'sans-serif'],
      },
    },
  },
  plugins: [],
}

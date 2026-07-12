/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        maroon: {
          50:  '#fdf2f2',
          100: '#f9d9d9',
          400: '#8a1c2b',
          500: '#7a1622',
          600: '#6b1420',
          700: '#4a0e17',
          800: '#3a0b12',
          900: '#2e0910',
        },
        gold: {
          50:  '#fdf8ec',
          100: '#f8ecc9',
          300: '#e9c873',
          400: '#d4a940',
          500: '#c8962e',
          600: '#a97a1f',
        },
        pista: {
          100: '#e4f0e4',
          400: '#5c9468',
          500: '#3d7a52',
          600: '#2d5f3e',
        },
        cream: '#fdf6e9',
        ivory: '#fffbf2',
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

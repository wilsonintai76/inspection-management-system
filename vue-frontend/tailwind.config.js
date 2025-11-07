/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        teal: '#0d9488',
        emerald: '#10b981',
      },
    },
  },
  plugins: [],
}

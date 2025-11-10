/** @type {import('tailwindcss').Config} */
import daisyui from 'daisyui'

export default {
  prefix: 'tw-',
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
  corePlugins: {
    preflight: false, // avoid interfering with existing custom CSS
  },
  plugins: [daisyui],
  daisyui: {
    themes: ["light", "dark"],
    prefix: "tw-", // Apply the same prefix to DaisyUI components
  }
}

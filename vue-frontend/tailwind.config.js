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
    preflight: true, // Enable preflight for DaisyUI to work properly
  },
  plugins: [daisyui],
  daisyui: {
    themes: ["light", "dark"],
    prefix: "tw-", // Apply the same prefix to DaisyUI components
    styled: true,
    base: true,
    utils: true,
  }
}

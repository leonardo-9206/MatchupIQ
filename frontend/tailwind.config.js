/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  darkMode: 'class',
  theme: {
    extend: {
      // Official tokens from the MatchupIQ design system
      colors: {
        canvas: '#080C14',
        surface: { 1: '#0E1626', 2: '#152238', elevated: '#1C2E4A' },
        primary: { electric: '#00D2FF', deep: '#0066FF' },
        edge: '#00E676',
        streak: '#FF9100',
        danger: '#FF3B30',
      },
    },
  },
  plugins: [],
};

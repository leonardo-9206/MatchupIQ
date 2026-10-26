/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx,ts,tsx}'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Deep obsidian / ultra dark backgrounds
        canvas: '#050811',
        surface: {
          1: '#0A0F1D',
          2: '#0F172A',
          card: '#0D1527',
          cardHover: '#131E35',
          elevated: '#17233D',
        },
        border: {
          subtle: '#1E293B',
          strong: '#334155',
          accent: 'rgba(0, 212, 198, 0.25)',
        },
        brand: {
          cyan: '#00D4C6',
          teal: '#0FA69D',
          blue: '#0066FF',
          glow: 'rgba(0, 212, 198, 0.15)',
        },
        edge: {
          positive: '#00E676',
          mint: '#1DBD8E',
          positiveBg: 'rgba(0, 230, 118, 0.12)',
          amber: '#FF9100',
          amberBg: 'rgba(255, 145, 0, 0.12)',
          danger: '#FF3B30',
          dangerBg: 'rgba(255, 59, 48, 0.12)',
          gold: '#FFD700',
        },
        sport: {
          nfl: '#38BDF8',
          ligamx: '#10B981',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Outfit', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      boxShadow: {
        'glow-cyan': '0 0 25px -5px rgba(0, 212, 198, 0.3)',
        'glow-emerald': '0 0 25px -5px rgba(0, 230, 118, 0.3)',
        'glow-card': '0 10px 30px -10px rgba(0, 0, 0, 0.6), 0 0 1px 1px rgba(0, 212, 198, 0.15)',
        'glow-drawer': '-15px 0 35px rgba(0, 0, 0, 0.75)',
      },
    },
  },
  plugins: [],
};

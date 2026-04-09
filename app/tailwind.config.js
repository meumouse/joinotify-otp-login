export default {
    content: ['./src/**/*.{vue,js}', '../*.php', '../admin/**/*.php', '../templates/**/*.php'],
  theme: {
    extend: {
      colors: {
        joinotify: {
          50: '#f0fdfa',
          100: '#ccfbf1',
          200: '#99f6e4',
          300: '#5eead4',
          400: '#2dd4bf',
          500: '#14b8a6',
          600: '#0f766e',
          700: '#115e59',
          800: '#134e4a',
          900: '#042f2e',
        },
      },
      boxShadow: {
        card: '0 24px 48px rgba(15, 23, 42, 0.12)',
      },
    },
  },
  plugins: [],
};

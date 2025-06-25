/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,ts}", // Configure Tailwind to scan Angular templates and TypeScript files
  ],
  theme: {
    extend: {
      colors: {
        primary: '#4CAF50', // Vert Agriflow
        secondary: '#FF9800', // Orange Agriflow
        // Ajoutez d'autres couleurs personnalisées ici
      },
      borderRadius: {
        'none': '0px',
        'sm': '4px',
        DEFAULT: '8px',
        'md': '12px',
        'lg': '16px',
        'xl': '20px',
        '2xl': '24px',
        '3xl': '32px',
        'full': '9999px',
        'button': '8px' // Custom button border radius
      }
    },
  },
  plugins: [],
}

module.exports = {
  content: [
    "./*.html",
    "./user/*.html",
    "./mechanic/*.html",
    "./admin/*.php",
    "./includes/*.php",
    "./assets/js/*.js"
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif']
      },
      colors: {
        brand: {
          DEFAULT: '#0F766E',
          dark: '#0B5A54',
          light: '#E6F4F2'
        },
        accent: {
          DEFAULT: '#F97316',
          dark: '#EA6A0B'
        }
      }
    }
  },
  plugins: []
};

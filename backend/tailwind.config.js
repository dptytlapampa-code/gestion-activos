import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    darkMode: 'class',
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                surface: {
                    50: '#f8fafc',
                    100: '#f3f7fc',
                    200: '#d9e2ec',
                    300: '#c5d2df',
                    400: '#9aa9b7',
                    500: '#71808f',
                    600: '#556472',
                    700: '#404d59',
                    800: '#2c3742',
                    900: '#1b2430'
                },
                primary: {
                    50: '#eef6ff',
                    100: '#d9e9ff',
                    200: '#b8d4ff',
                    300: '#92b8ff',
                    400: '#6b96ff',
                    500: '#4a74f5',
                    600: '#3759d1',
                    700: '#2b45a5',
                    800: '#22367d',
                    900: '#1a2857'
                }
            }
        },
    },
    plugins: [],
};

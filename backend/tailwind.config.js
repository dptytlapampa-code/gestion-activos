import defaultTheme from 'tailwindcss/defaultTheme';

export default {
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
                    50: '#f7f8fb',
                    100: '#eef0f6',
                    200: '#dde1ee',
                    300: '#c3c9df',
                    400: '#a6adc9',
                    500: '#8b93b3',
                    600: '#70789a',
                    700: '#596079',
                    800: '#3f455a',
                    900: '#2a2f40'
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

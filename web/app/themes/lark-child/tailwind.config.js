const plugin = require('tailwindcss/plugin');
const _ = require('lodash');

module.exports = {
    important: '#app',
    theme: {
        screens: {
            'sm': '576px',
            'md': '768px',
            'lg': '992px',
            'xl': '1185px',
        },
        borderWidth: {
            DEFAULT: '1px',
            0: '0',
            1: '1px',
            2: '2px',
            3: '3px',
        },
        fontFamily: {
            'bitter': ['Bitter', 'serif'],
        },
        wordSpacing: {
            // '-0.4': '-0.4px',
        },
        fontSize: {
            base: ['20px', '30px'],
            36: ['36px', {
                lineHeight: '48px',
                letterSpacing: '-0.6px',
            }],
            20: ['20px', {
                lineHeight: '27px',
                letterSpacing: '1px',
            }],
            18: ['18px', {
                lineHeight: '18px',
            }],
            16: ['16px', {
                lineHeight: '16px',
            }],
            14: ['14px', {
                lineHeight: '14px',
            }],
        },
        divideWidth: {
          0: '0px',
          1: '1px',
        },
        colors: {
            current: 'currentColor',
            transparent: 'transparent',
            white: '#FFFFFF',
            black: '#000000',
        },
        spacing: {
            'full': '100%',
            0: '0px',
            5: '5px',
            10: '10px',
            12: '12px',
            15: '15px',
            18: '18px',
            20: '20px',
            25: '25px',
            30: '30px',
            50: '50px',
            '50%': '50%',
            '100%': '100%',
        },
        extend: {
            inset: {
                '1': '1px',
            },
            zIndex: {
                '1': '1',
                '2': '2',
                '3': '3',
                '4': '4',
                '5': '5',
                '999': '999',
            },
            translate: {
                // ...
            },
            // borderColor: {
            // },
            letterSpacing: {
                // ...
            },
            lineHeight: {
                // ...
            },
            opacity: {
                // ...
            },
            width: {
                // ...
            },
            height: {
                // ...
            },
            minHeight: {
                // ...
            },
            maxHeight: {
                unset: 'unset',
            },
            minWidth: {
                // ...
            },
            maxWidth: {
                unset: 'unset',
            },
            margin: {
                // ...
            },
            rotate: {
                // ...
            },
            padding: {
                // ...
            },
        },
        container: {
            center: true,
            padding: '20px',
        },
        gap: {
            10: '10px',
            15: '15px',
            20: '20px',
        },
    },

    variants: {
        cursor: ['responsive', 'hover', 'focus'],
        extend: {
            opacity: ['responsive', 'hover', 'focus', 'disabled'],
            borderWidth: ['last'],
            textColor: ['focus', 'active'],
            backgroundColor: ['focus', 'active'],
        },
    },

    plugins: [
        require('postcss-import'),
        require('@tailwindcss/ui'),
        require('@tailwindcss/forms'),
        require('@tailwindcss/custom-forms'),
        require('tailwindcss'),
        require('autoprefixer'),
        plugin(function({ addUtilities, theme, variants, e }) {
          var prefix = 'word-spacing';

          const wordSpacing = _.map(theme('wordSpacing', {}), (value, key) => {
            if (key.includes('-')) {
                prefix = `-${prefix}`;
                key = key.substring(1);
            }

            return {
              [`.${e(`${prefix}-${key}`)}`]: {
                wordSpacing: value,
              },
            }
          })

          addUtilities(wordSpacing, variants('wordSpacing'));
        }),
    ],
};

const { colors } = require('@tailwindcss/ui/colors');

module.exports = {
    theme: {
        screens: {
            'sm': '576px',
            'md': '768px',
            'lg': '992px',
            'xl': '1200px',
            // '2xl': '1210px',
        },
        borderWidth: {
            DEFAULT: '1px',
            '0': '0',
            '1': '1px',
            '2': '2px',
            '3': '3px',
        },
        fontFamily: {
        },
        fontSize: {
          // base: ['18px', '30px'],
          // '44': ['44px', {
          //     lineHeight: '43px',
          // }],
        },
        colors: {
          'transparent': 'transparent',
          'white': '#FFFFFF',
        },
        spacing: {
            'full': '100%',
            '0': '0px',
            '5': '5px',
            '10': '10px',
            '15': '15px',
            // '30%': '30%',
        },
        extend: {
            zIndex: {
                '1': '1',
                '2': '2',
                '3': '3',
                '4': '4',
                '5': '5',
                '999': '999',
            },
            // borderColor: {
            // },
            // letterSpacing: {
            // },
            // opacity: {
            // },
            // width: {
            // },
            // height: {
            // },
            // minHeight: {
            // },
            // maxHeight: {
            // },
            // minWidth: {
            // },
            // maxWidth: {
            // },
            // lineHeight: {
            // },
            // padding: {
            // },
        },
        container: {
            center: true,
            padding: '20px',
        },
        gap: {
            // '10': '10px',
            // '15': '15px',
            // '20': '20px',
        },
    },

    variants: {
        cursor: ['responsive', 'hover', 'focus'],
        extend: {
            opacity: ['responsive', 'hover', 'focus', 'disabled'],
            borderWidth: ['last'],
            textColor: ['focus', 'active'],
            backgroundColor: ['focus', 'active'],
        }
    },

    plugins: [
        require('postcss-import'),
        require('@tailwindcss/ui'),
        require('@tailwindcss/forms'),
        require('@tailwindcss/custom-forms'),
        require('tailwindcss'),
        require('autoprefixer'),
    ],
};

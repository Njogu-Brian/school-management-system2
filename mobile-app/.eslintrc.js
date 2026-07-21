/** @type {import('eslint').Linter.Config} */
module.exports = {
    root: true,
    extends: ['@react-native/eslint-config'],
    ignorePatterns: ['node_modules/', 'babel.config.js', 'metro.config.js', '.eslintrc.js', '.expo/', 'dist/', 'coverage/'],
    overrides: [
        {
            files: ['apps/admin/src/features/**/*.{ts,tsx}'],
            rules: {
                'no-restricted-syntax': [
                    'warn',
                    {
                        selector: "MemberExpression[object.name='fontSizes']",
                        message: 'V3: use typography.* from useTheme() instead of fontSizes.',
                    },
                    {
                        selector: "Literal[value=/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/]",
                        message: 'V3: use theme palette/semantic tokens instead of hardcoded hex colors.',
                    },
                ],
            },
        },
    ],
    rules: {
        // Prettier 3 removed resolveConfig.sync; eslint-plugin-prettier@4 crashes until upgraded together.
        'prettier/prettier': 'off',
        // RN codebases commonly use dynamic inline styles; exhaustive-deps is noisy on legacy screens.
        'react-native/no-inline-styles': 'off',
        'react-hooks/exhaustive-deps': 'off',
        curly: 'off',
        'react/no-unstable-nested-components': 'off',
        'comma-dangle': 'off',
        radix: 'warn',
        'no-void': 'off',
    },
};

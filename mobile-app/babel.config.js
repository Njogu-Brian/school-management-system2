module.exports = {
    presets: ['babel-preset-expo'],
    plugins
        [
            'module-resolver',
            {
                root: ['./src'],
                extensions: ['.ios.js', '.android.js', '.js', '.ts', '.tsx', '.json'],
                alias: {
                    'react-native-vector-icons/MaterialIcons': './src/components/Icon',
                    '@components': './src/components',
                    '@screens': './src/screens',
                    '@navigation': './src/navigation',
                    '@contexts': './src/contexts',
                    '@hooks': './src/hooks',
                    '@api': './src/api',
                    '@types': './src/types',
                    '@utils': './src/utils',
                    '@constants': './src/constants',
                    '@styles': './src/styles',
                    '@services': './src/services',
                },
            },
        ],
    ],
};

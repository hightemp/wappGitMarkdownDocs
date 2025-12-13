
const path = require('path')

const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
    // режим задаем через CLI: webpack --mode=development|production
    devtool: 'eval-source-map',

    entry: ['./js/main.js'],

    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: 'main.js'
    },

    module: {
        rules: [
            // словари typo-js (aff/dic) – импортируем как строку
            {
                test: /\.(aff|dic)$/i,
                type: 'asset/source'
            },

            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    loaders: {
                        js: 'babel-loader'
                    }
                }
            },

            {
                test: /\.js$/,
                use: {
                    loader: 'babel-loader'
                }
            },

            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader']
            },

            // картинки/шрифты: инлайним как data: URL (поведение как у base64-inline-loader)
            {
                test: /\.(jpe?g|png|ttf|eot|svg|woff2?)$/i,
                type: 'asset/inline'
            }
        ]
    },

    plugins: [new VueLoaderPlugin()]
}

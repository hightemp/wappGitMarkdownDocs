
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
    mode: 'production',
    entry: [
        './js/main.js'
    ],
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    extractCSS: true
                }
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader'],
            },
            {
                test: /\.(jpe?g|png|ttf|eot|svg|woff(2)?)(\?[a-z0-9=&.]+)?$/,
                use: 'base64-inline-loader'
            }
        ],
    },
    plugins: [
        new VueLoaderPlugin()
    ]
}

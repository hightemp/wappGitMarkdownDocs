
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
        ],
    },
    plugins: [
        new VueLoaderPlugin()
    ]
}

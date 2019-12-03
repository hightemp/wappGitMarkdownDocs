
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
    mode: 'development', //'production',
    devtool: 'eval-source-map',
    entry: [
        './js/main.js'
    ],
    module: {
        rules: [
            {
                test: /\.(aff|dic)$/i,
                use: 'raw-loader',
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    extractCSS: true,
                    loaders: {
                        js: 'babel-loader!eslint-loader'
                    }
                }
            },
            {
                test: /\.js$/,
                //exclude: /node_modules\/(?!vue-resource|vue|vue-snotify|simplemde|bootstrap-vue)/,
                use: {
                    loader: 'babel-loader',
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

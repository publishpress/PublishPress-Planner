const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
    mode: NODE_ENV,
    entry: {
        'modules/custom-status/lib/custom-status-block': './modules/custom-status/lib/custom-status-block.jsx',
        'modules/calendar/lib/async-calendar/js/index': './modules/calendar/lib/async-calendar/js/index.jsx'
    },
    output: {
        path: __dirname,
        filename: '[name].min.js'
    },
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                loader: 'babel-loader'
            },
            {
                test: /\.js$/,
                enforce: 'pre',
                use: ['source-map-loader'],
            },
        ]
    },
    resolve: {
        extensions: ['.js', '.jsx']
    }
};

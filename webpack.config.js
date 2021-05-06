const NODE_ENV = process.env.NODE_ENV || 'development';

var path = require('path');

module.exports = {
    mode: NODE_ENV,
    entry: {
        'modules/custom-status/lib/custom-status-block': './modules/custom-status/lib/custom-status-block.jsx',
        'modules/calendar/lib/async-calendar/async-calendar-component': './modules/calendar/lib/async-calendar/async-calendar-component.jsx'
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
            }
        ]
    },
    resolve: {
        extensions: ['.js', '.jsx']
    }
};

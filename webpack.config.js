const NODE_ENV = process.env.NODE_ENV || 'development';

var path = require('path');

module.exports = {
    mode: NODE_ENV,
    entry: './modules/custom-status/lib/custom-status-block.jsx',
    output: {
        path: path.join(__dirname, 'modules/custom-status/lib'),
        filename: 'custom-status-block.min.js'
    },
    module: {
        rules: [
            {
                test: /.jsx$/,
                exclude: /node_modules/,
                loader: 'babel-loader'
            }
        ]
    }
};

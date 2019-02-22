// sets mode webpack runs under
const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
    mode: NODE_ENV,

    // entry is the source script
    entry: './modules/custom-status/lib/custom-status-block.jsx',

    // output is where to write the built file
    output: {
        path: __dirname + '/modules/custom-status/lib',
        filename: 'custom-status-block.min.js'
    },
    module: {
        // the list of rules used to process files
        // this looks for .js files, exclude files
        // in node_modules directory, and uses the
        // babel-loader to process
        rules: [
            {
                test: /.jsx$/,
                exclude: /node_modules/,
                loader: 'babel-loader'
            }
        ]
    }
};

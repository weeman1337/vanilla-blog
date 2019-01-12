const path = require('path');


module.exports = {
    context: path.resolve(__dirname, 'src/js'),
    entry: {
        home: './home.js',
    },
    output: {
        filename: '[name].js',
        path: __dirname + '/public'
    },
    module: {
        rules: [{
            test: /\.scss$/,
            use: [
                "style-loader",
                "css-loader",
                "sass-loader"
            ]
        }]
    }
};

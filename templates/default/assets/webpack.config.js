const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const TerserPlugin = require('terser-webpack-plugin');
const autoprefixer = require('autoprefixer');
const path = require('path');

const paths = {
  src: {
    scss: './src/scss',
    js: './src/js',
    fonts: './src/fonts',
    img: './src/img',
  },
  dist: {
    css: './css',
    js: './js',
    fonts: './fonts',
    img: './img',
  },
};

module.exports = {
  devtool: 'source-map',
  entry: {
    libs: [paths.src.scss + '/libs.scss'],
    theme: [paths.src.js + '/theme.js', paths.src.scss + '/theme.scss'],
  },
  mode: 'development',
  module: {
    rules: [
      {
        test: /\.(sass|scss)$/,
        include: path.resolve(__dirname, paths.src.scss.slice(2)),
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
          },
          {
            loader: 'css-loader',
            options: {
              url: false,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [['autoprefixer']],
              },
            },
          },
          {
            loader: 'sass-loader',
          },
        ],
      },
    ],
  },
  optimization: {
    splitChunks: {
      cacheGroups: {
        vendor: {
          test: /[\\/](node_modules)[\\/].+\.js$/,
          name: 'vendor',
          chunks: 'all',
        },
      },
    },
    minimizer: [
      new CssMinimizerPlugin(),
      new TerserPlugin({
        extractComments: false,
        terserOptions: {
          output: {
            comments: false,
          },
        },
      }),
    ],
  },
  output: {
    filename: paths.dist.js + '/[name].bundle.js',
  },
  plugins: [
    new CopyPlugin({
      patterns: [
        {
          from: paths.src.fonts,
          to: paths.dist.fonts,
        },
        {
          from: paths.src.img,
          to: paths.dist.img,
        }
      ],
    }),
    new RemoveEmptyScriptsPlugin(),
    new MiniCssExtractPlugin({
      filename: paths.dist.css + '/[name].bundle.css',
    }),
  ],
  target: 'web',
};

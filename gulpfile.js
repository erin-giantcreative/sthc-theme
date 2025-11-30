const { src, dest, watch, series, parallel } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');
//const plumber = require('gulp-plumber');

const outDir = './assets/css';

function build(file, outName) {
  return function compile() {
    return src(file)
      //.pipe(plumber())                // keeps the stream alive on errors
      .pipe(sass().on('error', sass.logError))
      .pipe(cleanCSS({ compatibility: 'ie11' }))
      .pipe(rename(outName))
      .pipe(dest(outDir));
  };
}

// individual tasks
const cssMain = build('./assets/scss/main.scss', 'style.min.css');
const cssHome = build('./assets/scss/pages/home.scss', 'home.min.css');
const cssThankYou = build('./assets/scss/pages/thank-you.scss', 'thank-you.min.css');

// group task
const styles = parallel(
  cssMain,
  cssHome,
  cssThankYou
);

// watcher
function watcher() {
  watch('./assets/scss/**/*.scss', styles);
}

exports.styles = styles;
exports.default = series(styles, watcher);

'use strict'

const gulp = require("gulp")
const { parallel } = require("gulp")
const sass = require("gulp-sass")
const cleanCss = require("gulp-clean-css")
const concat = require('gulp-concat')
const terser = require('gulp-terser');
const strip = require('gulp-strip-comments');
const removeEmptyLines = require('gulp-remove-empty-lines');
const sourcemaps = require('gulp-sourcemaps');
const rename = require('gulp-rename');
const gulpif = require('gulp-if');

const useSourceMaps = false;
const useMaximumCompress = false;

function css() {
    return gulp.src('./lib/wgo.scss')
        .pipe(gulpif(useSourceMaps, sourcemaps.init()))
        .pipe(sass())
        .pipe(cleanCss({ format: 'keep-breaks' }))
        .pipe(rename('wgo.min.css'))
        .pipe(gulpif(useSourceMaps, sourcemaps.write()))
        .pipe(gulp.dest('./lib'))
}

function watchCss() {
    gulp.watch('./lib/wgo.scss', parallel('css'))
}

function js() {
    return gulp.src('./lib/wgo.js')
        .pipe(gulpif(useSourceMaps, sourcemaps.init()))
        .pipe(strip())
        .pipe(removeEmptyLines())
        .pipe(gulpif(useMaximumCompress, terser()))
        .pipe(concat('wgo.min.js'))
        .pipe(gulpif(useSourceMaps, sourcemaps.write()))
        .pipe(gulp.dest('./lib'))
}

function watchJs() {
    gulp.watch('./lib/wgo.js', parallel('js'))
}

exports.css = css
exports.js = js
exports.build = parallel(css, js)
exports.watch = parallel(watchCss, watchJs)
exports.default = this.watch

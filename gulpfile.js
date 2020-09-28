'use strict'

const gulp = require("gulp")
const { parallel } = require("gulp")
const sass = require("gulp-sass")
const cleanCss = require("gulp-clean-css")
const concat = require('gulp-concat')
const uglify = require('gulp-uglify-es').default
const sourcemaps = require('gulp-sourcemaps')
const rename = require('gulp-rename');

function css() {
    return gulp.src('./lib/wgo.scss')
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(cleanCss())
        .pipe(rename('wgo.min.css'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('./lib'))
}

function watchCss() {
    gulp.watch('./lib/wgo.scss', parallel('css'))
}

function js() {
    return gulp.src('./lib/wgo.js')
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('wgo.min.js'))
        .pipe(sourcemaps.write())
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

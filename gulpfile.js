/**
 * Simple Squash It! Gulp project builder
 */
var gulp = require('gulp'),
	concat = require('gulp-concat'),
	uglify = require('gulp-uglify'),
	sass = require('gulp-sass'),
	flags =
	{
		production: false
	};

gulp.task
	(
		'compile.plugin',
		function (doneCallBack) {
			gulp.src(['client/js/*.js'])
				.pipe(concat('wbfy-squash-it.min.js'))
				.pipe(uglify())
				.pipe(gulp.dest('resources/js'));

			gulp.src(['client/scss/main.scss'])
				.pipe(concat('wbfy-squash-it.min.css'))
				.pipe(sass({ outputStyle: 'compressed', includePaths: ['client/scss'] }))
				.pipe(gulp.dest('resources/css'));
			doneCallBack();
		}
	);

gulp.task('production', function (doneCallback) { flags.production = true; doneCallback(); });

gulp.task('compile', gulp.parallel('compile.plugin'));
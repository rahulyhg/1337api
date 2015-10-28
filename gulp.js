// start gulp file
var gulp = require('gulp');

// define plug-ins
var flatten 	= require('gulp-flatten');
var concat 		= require('gulp-concat');
var gulpFilter 	= require('gulp-filter');
var uglify 		= require('gulp-uglify');
var minifycss 	= require('gulp-minify-css');
var rename 		= require('gulp-rename');
var bowerFiles 	= require('main-bower-files');

// Define paths variables
var dest_path =  'assets/test';

// grab libraries files from `bower_components-vendor` folder, minify and publish
gulp.task('bower_components', function() {

		var jsFilter = gulpFilter('*.js');
		var cssFilter = gulpFilter('*.css');
		var fontFilter = gulpFilter(['*.eot', '*.woff', '*.svg', '*.ttf']);

		return gulp.src(bowerFiles())

		// grab vendor js files from bower_components, minify and push in /public
		.pipe(jsFilter)
		.pipe(gulp.dest(dest_path + '/js/'))
		.pipe(uglify())
		.pipe(concat('vendor.min.js'))
		.pipe(gulp.dest(dest_path + '/js/'))

		// grab vendor css files from bower_components, minify and push in /public
		.pipe(cssFilter)
//        .pipe(gulp.dest(dest_path + '/css'))
		.pipe(minifycss())
		.pipe(rename({
			suffix: ".min"
		}))
		.pipe(gulp.dest(dest_path + '/css'))

		// grab vendor font files from bower_components and push in /public
		.pipe(fontFilter)
		.pipe(flatten())
		.pipe(gulp.dest(dest_path + '/fonts'));
});
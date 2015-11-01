// start gulp
var gulp = require('gulp');

// define plug-ins
var flatten 	= require('gulp-flatten');
var concat 		= require('gulp-concat');
var gulpFilter 	= require('gulp-filter');
var uglify 		= require('gulp-uglify');
var minifycss 	= require('gulp-minify-css');
var rename 		= require('gulp-rename');
var bowerFiles 	= require('main-bower-files');
var addsrc 		= require('gulp-add-src');
var gutil 		= require('gulp-util');
var edit        = require('gulp-edit');
var wrap 		= require('gulp-wrap');
var less 		= require('gulp-less');

// grab libraries files from `assets/vendor` folder, minify and publish
gulp.task
	('vendor-js', function() {

		// define gulp task vendor vars
		var dest_path 	=  'assets/vendor';
		var jsFilter 	= gulpFilter('*.js', {restore: true});

		return gulp
			.src(bowerFiles())
			.pipe(jsFilter)
			.pipe(addsrc.append(dest_path + '/other_components/**/*.js'))
			.pipe(uglify())
			.pipe(wrap('//<%= file.relative %>\n<%= contents %>'))
			.pipe(concat('vendor.min.js', { newLine: '\r\n' }))
			.pipe(edit(function(src, cb) {src = '// Last modified: ' + new Date().toLocaleString() + '\n\n' + src; cb(null, src);}))
			.pipe(gulp.dest(dest_path))
			.pipe(jsFilter.restore)
		;
});

gulp.task
	('vendor-css', function() {

		// define gulp task vendor vars
		var dest_path 	=  'assets/vendor';
		var cssFilter 	= gulpFilter('*.css', {restore: true});
		var lessFilter 	= gulpFilter('*.less', {restore: true});

		return gulp
			.src(bowerFiles())
			.pipe(lessFilter)
			.pipe(less())
			.pipe(lessFilter.restore)
			.pipe(cssFilter)
			.pipe(addsrc.append(dest_path + '/other_components/**/*.css'))
			.pipe(minifycss({keepSpecialComments: 0}))
			.pipe(wrap('/* <%= file.relative %> */\n<%= contents %>'))
			.pipe(concat('vendor.min.css', { newLine: '\r\n' }))
			.pipe(edit(function(src, cb) {src = '/* Last modified: ' + new Date().toLocaleString() + ' */\n\n' + src; cb(null, src);}))
			.pipe(gulp.dest(dest_path))
			.pipe(cssFilter.restore)
		;
});



		;


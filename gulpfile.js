// start gulp
var gulp = require('gulp');

// define plug-ins
var flatten 		= require('gulp-flatten');
var concat 			= require('gulp-concat');
var filter 			= require('gulp-filter');
var uglify 			= require('gulp-uglify');
var minifyCss 		= require('gulp-minify-css');
var rename 			= require('gulp-rename');
var mainBowerFiles 	= require('main-bower-files');
var addSrc 			= require('gulp-add-src');
var util 			= require('gulp-util');
var edit 			= require('gulp-edit');
var wrap 			= require('gulp-wrap');
var less 			= require('gulp-less');
var del 			= require('del');

gulp.task
	('vendor-js', function() {

		// define gulp task vendor vars
		var dest_path 	=  'assets/vendor';
		var jsFilter 	= filter('*.js', {restore: true});
		
		// cleaning up
		del([dest_path + '/vendor.min.js']);

		// gulp js
		return gulp
			.src(mainBowerFiles())
			.pipe(jsFilter)
			.pipe(addSrc.append(dest_path + '/other_components/**/*.js'))
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
		var cssFilter 	= filter('*.css', {restore: true});
		var lessFilter 	= filter('*.less', {restore: true});

		// cleaning up
		del([dest_path + '/vendor.min.css']);

		// gulp css
		return gulp
			.src(mainBowerFiles())
			.pipe(lessFilter)
			.pipe(less())
			.pipe(lessFilter.restore)
			.pipe(cssFilter)
			.pipe(addSrc.append(dest_path + '/other_components/**/*.css'))
			.pipe(minifyCss({keepSpecialComments: 0}))
			.pipe(wrap('/* <%= file.relative %> */\n<%= contents %>'))
			.pipe(concat('vendor.min.css', { newLine: '\r\n' }))
			.pipe(edit(function(src, cb) {src = '/* Last modified: ' + new Date().toLocaleString() + ' */\n\n' + src; cb(null, src);}))
			.pipe(gulp.dest(dest_path))
			.pipe(cssFilter.restore)
		;
});

gulp.task
	('vendor-fonts', function() {

		// define gulp task vendor vars
		var dest_path 	=  'assets/vendor';
		var fontFilter 	= filter(['*.eot', '*.woff', '*.woff2', '*.svg', '*.ttf']);

		return gulp
			.src(mainBowerFiles())
			.pipe(fontFilter)
			.pipe(flatten())
			.pipe(gulp.dest('assets/fonts'))
		;
});	

gulp.task('default', ['vendor-js', 'vendor-css', 'vendor-fonts']);

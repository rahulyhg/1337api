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

// grab libraries files from `assets/vendor` folder, minify and publish
gulp.task('vendor', function() {

		// define gulp task vendor vars
		var dest_path 	=  'assets/vendor';
		var jsFilter 	= gulpFilter('*.js');
		var cssFilter 	= gulpFilter('*.css');
		var fontFilter 	= gulpFilter(['*.eot', '*.woff', '*.svg', '*.ttf']);
		
		return gulp
			.src(bowerFiles())

			// javascript
			.pipe(jsFilter)
			.pipe(addsrc(dest_path + '/other_components/**/*.js'))
			.pipe(uglify())
			.pipe(concat('vendor.min.js', { newLine: '\r\n\r\n' }))
			.pipe(gulp.dest(dest_path))

			// css
			.pipe(cssFilter)
			.pipe(addsrc(dest_path + '/other_components/**/*.css'))
			.pipe(minifycss())
			.pipe(rename({suffix: ".min"}))
			.pipe(gulp.dest(dest_path))

			// fonts
			.pipe(fontFilter)
			.pipe(flatten())
			.pipe(gulp.dest(dest_path + '/fonts'))
		;
});

/*
var gulp        = require('gulp');
var concat      = require('gulp-concat');
var uglify      = require('gulp-uglify');
var rename      = require('gulp-rename');
var del         = require('del');
var beautify    = require('gulp-beautify');
var edit        = require('gulp-edit');
var minifyCss   = require('gulp-minify-css');
var cssbeautify = require('gulp-cssbeautify');
var imagemin    = require('gulp-imagemin');
var pngquant    = require('imagemin-pngquant');
var jsonminify  = require('gulp-jsonminify');
var htmlreplace = require('gulp-html-replace');

gulp.task // Source JS Files
(
	'sourceJsFiles',
	function()
	{
		var dist = 'dist/js';
		var beautify_options = { 'indent_with_tabs': true, 'brace-style': 'expand' };
		gulp
			.src('www/js/*.js')
			.pipe(beautify(beautify_options))
			.pipe(uglify())
			.pipe(concat('scripts.min.js'))
			.pipe
			(
				edit
				(
					function(src, cb)
					{
						src = '// Last modified: ' + new Date().toLocaleString() + '\n' + src;
						cb(null, src);
					}
				)
			)
			.pipe(gulp.dest(dist))
		;
	}
);

gulp.task // Source CSS Files
(
	'sourceCssFiles',
	function()
	{
		var dist = 'dist/css';
		gulp
			.src('www/css/*.css')
			.pipe(cssbeautify())
			.pipe(minifyCss())
			.pipe(concat('styles.min.css'))
			.pipe
			(
				edit
				(
					function(src, cb)
					{
						src = '// Last modified: ' + new Date().toLocaleString() + '\n' + src;
						cb(null, src);
					}
				)
			)
			.pipe(gulp.dest(dist))
		;
	}
);

gulp.task // Source Image Files
(
	'sourceImageFiles',
	function()
	{
		var dist = 'dist/images';
		gulp
			.src('www/images/*')

			.pipe
			(
				imagemin
				(
					{
						progressive : true,
						svgoPlugins : [{removeViewBox: false}],
						use         : [pngquant()]
					}
				)
			)
			.pipe(gulp.dest(dist))
		;
	}
);

gulp.task // Source Json Files
(
	'sourceJsonFiles',
	function()
	{
		var dist = 'dist/data';
		gulp
			.src('www/data/*')
			.pipe(jsonminify())
			.pipe(gulp.dest(dist))
		;
	}
);

gulp.task // Source HTML Replace Files
(
	'sourceHtmlFiles',
	function()
	{
		gulp
			.src('www/index.html')
			.pipe
			(
				htmlreplace
				(
					{
						'css': 'css/styles.min.css',
						'js': 'js/scripts.min.js'
					}
				)
			)
			.pipe(gulp.dest('dist/')
		);
	}
);

gulp.task // watch
(
	'watch',
	function()
	{
		var wSourceJsFiles = ['www/js/*.js'];
		gulp.watch(wSourceJsFiles, ['sourceJsFiles']);

		var wSourceCssFiles = ['www/css/*.css'];
		gulp.watch(wSourceCssFiles, ['sourceCssFiles']);

		var wSourceImageFiles = ['www/images/*.png','www/images/*.jpg','www/images/*.jpeg','www/images/*.gif'];
		gulp.watch(wSourceImageFiles, ['sourceImageFiles']);

		var wSourceJsonFiles = ['www/data/*.json'];
		gulp.watch(wSourceJsonFiles, ['sourceJsonFiles']);

		var wSourceHtmlFiles = ['www/index.html'];
		gulp.watch(wSourceHtmlFiles, ['sourceHtmlFiles']);
	}
);

gulp.task('_default', ['watch']);
*/
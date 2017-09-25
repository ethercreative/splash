var gulp = require('gulp'),
	sourcemaps = require('gulp-sourcemaps'),
	
	// Less
	less = require("gulp-less"),
	LessPluginAutoPrefix = require('less-plugin-autoprefix'),
	cleanCss = require('gulp-clean-css'),
	autoprefixer = new LessPluginAutoPrefix({ browsers: ["last 3 versions"] }),
	
	// JS
	rollup = require('rollup').rollup,
	eslint = require('rollup-plugin-eslint'),
	babel  = require('rollup-plugin-babel'),
	uglify = require('rollup-plugin-uglify'),
	nodeResolve = require('rollup-plugin-node-resolve'),
	commonjs = require('rollup-plugin-commonjs'),
	minify = require('uglify-js').minify;

// Less
gulp.task('less', function () {
	gulp.src('less/splash.less')
	    .pipe(sourcemaps.init())
	    .pipe(less({
		    plugins: [autoprefixer]
	    }).on('error', function(err){ console.log(err.message); }))
	    .pipe(cleanCss())
	    .pipe(sourcemaps.write('.'))
	    .pipe(gulp.dest('../splash/resources/css'));
});

// JS
gulp.task('js', function () {
	rollup({
		input: 'js/splash.js',
		plugins: [
			eslint({
				useEslintrc: false,
				baseConfig: {
					parserOptions: {
						ecmaVersion: 7,
						sourceType: "module"
					},
					extends: "eslint:recommended",
				},
				parser: "babel-eslint",
				rules: {
					eqeqeq: [1, "smart"],
					semi: [1, "always"],
					"no-loop-func": [2],
					"no-console": [1],
					"no-mixed-spaces-and-tabs": [0],
				},
				envs: ["browser", "es6"]
			}),
			nodeResolve({
				module: true,
				jsnext: true,
				main: true,
				browser: true
			}),
			commonjs(),
			babel(),
			uglify({}, minify)
		],
		sourcemap: true
	}).then(function (bundle) {
		bundle.write({
			format: 'es',
			sourcemap: true,
			file: '../splash/resources/js/splash.min.js'
		});
	}).catch(function(err) { console.error(err); });
});


// Watcher
gulp.task('watch', function () {
	gulp.watch(['js/**/*.js', '!js/**/*.min.js'], ['js']);
	gulp.watch(['less/**/*'], ['less']);
});

gulp.task('default', ['watch']);

module.exports = {
	// The filename of the manifest file. If set to null, not manifest will
	// generate.
	manifest: null,

	less: {
		// If set to false, Less compilation will not run
		run: true,

		// An array of entry Less file paths. Must be strings.
		entry: [
			'less/splash.less',
		],

		// An array of output CSS file paths. Must match the entry paths.
		// Output names can contain: "[hash:20]": a random hash (with a given
		// length)
		output: [
			'../src/resources/css/splash.less',
		],
	},

	js: {
		// If set to false, JS compilation will not run
		run: true,

		// An array of entry JS file paths
		// See https://webpack.js.org/configuration/entry-context/#entry for
		// supported entries.
		// JS Supports Flow
		// Also supports Typescript (.ts) files automatically!
		entry: {
			splash: './js/splash.js',
		},

		// An array of output JS file paths. Must match input paths.
		// See https://webpack.js.org/configuration/output/
		// for supported output configs
		output: {
			path: process.cwd() + '/../src/resources/js',
			filename: 'splash.min.js',
		},

		// If set to true, JSX will be supported
		jsx: false,

		// Will be merged with the webpack config, allowing you to add, remove,
		// or override any webpack config options.
		config: webpack => ({}),
	},

	critical: {},

	browserSync: {},

	copy: {},
};

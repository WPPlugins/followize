module.exports = {
	options : {
		separator : ';'
	},
	admin : {
		src : [
			'<%= paths.js %>/admin/libs/*.js',
			'<%= paths.js %>/admin/templates/*.js',
			'<%= paths.js %>/admin/vendor/**/*.js',
			'<%= paths.js %>/admin/app/*.js',
			'<%= paths.js %>/admin/boot.js'
		],
		dest : '<%= paths.js %>/admin/built.js',
	},
	front : {
		src : [
			'<%= paths.js %>/front/libs/*.js',
			'<%= paths.js %>/front/templates/*.js',
			'<%= paths.js %>/front/vendor/component/*.js',
			'<%= paths.js %>/front/vendor/additional/*.js',
			'<%= paths.js %>/front/vendor/localization/*.js',
			'<%= paths.js %>/front/app/*.js',
			'<%= paths.js %>/front/boot.js'
		],
		dest : '<%= paths.js %>/front/built.js',
	}
};
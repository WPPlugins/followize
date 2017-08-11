module.exports =  {
	scriptsAdmin : {
		files : ['<%= concat.admin.src %>'],
		tasks : ['jshint', 'concat:admin']
	},
	scriptsFront : {
		files : ['<%= concat.front.src %>'],
		tasks : ['jshint', 'concat:front']
	}
};
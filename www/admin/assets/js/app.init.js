/* ************************************************************
ANGULAR INIT
************************************************************ */
var AdminApp = angular.module('AdminApp', ['ngRoute', 'angular-json-editor', 'ui.bootstrap']);

/* ************************************************************
ANGULAR ROUTES
************************************************************ */			

// NG Routes Provider

AdminApp.config([
	'$routeProvider',
	function($routeProvider) {

		// DASHBOARD
		$routeProvider.when(
			'/', 
			{
				templateUrl: 	'assets/tpl/dashboard.html', 
				controller: 	'DashboardController',
				controllerAs: 	'dashboard',
				resolve: {
					hi: 	function(apiService){ return apiService.getHi(); 		},
					edges: 	function(apiService){ return apiService.getEdges(); 	},				
				}
			}	
		);

		// CRUD		
		$routeProvider.when( 
			'/list/:edge/p/:page', 
			{
				templateUrl: 	'assets/tpl/list.html', 
				controller: 	'ListController',
				controllerAs: 	'list',
				resolve: {
					valid: 	function(apiService){ return apiService.validateParams(); 	},					
					schema: function(apiService){ return apiService.getSchema(); 		},
					list: 	function(apiService){ return apiService.getList(); 			},
					count: 	function(apiService){ return apiService.getCount(); 		},
				}
			}		
		);

		$routeProvider.when( 
			'/create/:edge', 
			{
				templateUrl: 	'assets/tpl/create.html', 
				controller: 	'CreateController',
				controllerAs: 	'create',
				resolve: {
					valid: 	function(apiService){ return apiService.validateParams(); 	},
					schema: function(apiService){ return apiService.getSchema(); 		},
				}
			}	
		);

		$routeProvider.when( 
			'/read/:edge/:id', 
			{
				templateUrl: 	'assets/tpl/read.html', 
				controller: 	'ReadController',
				controllerAs: 	'read',
				resolve: {	
					valid: 	function(apiService){ return apiService.validateParams(); 	},					
					schema: function(apiService){ return apiService.getSchema(); 		},
					read: 	function(apiService){ return apiService.getRead(); 			},
				},
			}
		);

		$routeProvider.when(
			'/update/:edge/:id', 
			{
				templateUrl: 	'assets/tpl/update.html', 
				controller: 	'UpdateController',
				controllerAs: 	'update',
				resolve: {	
					valid: 	function(apiService){ return apiService.validateParams(); 	},					
					schema: function(apiService){ return apiService.getSchema(); 		},
					read: 	function(apiService){ return apiService.getRead(); 			},
				},				
			}	
		);

		// REDIRECTS
		$routeProvider.when( 
			'/list/:edge', 
			{
				redirectTo: function (routeParams, path, search) { return path+'/p/1'; }
			}
		);

		// OTHERWISE
		$routeProvider.otherwise(
			{redirectTo: '/'}
		);

	}
]);

/* ************************************************************
ANGULAR JSON EDITOR
************************************************************ */			

// GLOBAL UPLOAD AJAX FUNCTION
// **************************** */			

var jeUploadFunction = function(type, file, cbs) {
							
						if (file) {
							var reader = new FileReader();

							reader.onloadend = function(evt){
								var b = evt.target.result;
								var uploadData = '{"filename":"'+file.name+'", "filesize":"'+file.size+'", "blob":"'+b+'"}';
								var percentComplete = 0;

									$.ajax({
										xhr: function () {
											var xhr = new window.XMLHttpRequest();
											xhr.upload.addEventListener("progress", function (evt) {
												if (evt.lengthComputable) {
													var percentComplete = Math.round((evt.loaded / evt.total)*100);
													if(percentComplete <=100) {
														cbs.updateProgress(percentComplete);
													}
												}
											}, false);
											return xhr;
										},
										type: 'POST',
										// TODO: need to pass "edge" at upload url.
										url: 'api/upload/page',
										contentType: "application/json; charset=utf-8",
										dataType: "json",
										data: uploadData,
									
										success: function( data, textStatus, jQxhr ){
											cbs.success(''+data.id+'');
										},
										error: function( jqXhr, textStatus, errorThrown ){
											console.log( errorThrown );
										}
									});

							};
							reader.readAsDataURL(file);
						};
					};

// GLOBAL JSON EDITOR CONFIG
// **************************** */			

var jePluginsConfig = {
						sceditor: {
							style: 			'assets/css/libs/jquery.sceditor.default.min.css',
							toolbar: 		'bold,italic,underline|strike,subscript,superscript|link,unlink|removeformat|bulletlist,orderedlist|source',
							locale: 		'pt-BR',
							emoticonsEnabled: 	false,
							width: 				'98%',
							resizeEnabled: 		false,
						}
					};

var jeOptionsConfig = {
						iconlib: 			'fontawesome4',
						theme: 				'bootstrap3',
						ajax: 				true,
						disable_collapse: 	true,
						disable_edit_json: 	true,
						disable_properties: true,
						upload: 			jeUploadFunction,
					};

// JSON Editor Provider
// **************************** */			

AdminApp.config( function(JSONEditorProvider) {
	JSONEditorProvider.configure({
		plugins: jePluginsConfig,
		defaults: { options: jeOptionsConfig }
	});
});

/* ************************************************************
NPROGRESS CONFIG
************************************************************ */	
NProgress.configure({ parent: '#page-wrapper' });

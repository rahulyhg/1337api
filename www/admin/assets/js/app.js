/* ************************************************************
INIT
************************************************************ */
var AdminApp = angular.module('AdminApp', ['ngRoute', 'angular-json-editor', 'ui.bootstrap']);

/* ************************************************************
ANGULAR MODULES CONFIG
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

// JSON Editor Provider
AdminApp.config( function(JSONEditorProvider) {
	JSONEditorProvider.configure({
		plugins: {
			sceditor: {
				plugins: 			'',
				style: 				'assets/js/sceditor/jquery.sceditor.default.min.css',
				toolbar: 			'bold,italic,underline|strike,subscript,superscript|link,unlink|removeformat|bulletlist,orderedlist|source',
				locale: 			'pt-BR',
				emoticonsEnabled: 	false,
				width: 				'98%',
				resizeEnabled: 		false,
			}
		},
		defaults: {
			options: {
				iconlib: 			'fontawesome4',
				theme: 				'bootstrap3',
				ajax: 				true,
				disable_collapse: 	true,
				disable_edit_json: 	true,
				disable_properties: true,
				upload: 			function(type, file, cbs) 
									{
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
									}

				}
			}
	});
});

/* ************************************************************
ANGULAR SERVICES
************************************************************ */		

AdminApp.factory("apiService", function($q, $http, $location, $route){

	var apiService = {

		getHi: function() {
			var deferred = $q.defer();
			
			hi = $http.get('api/hi').then(function(response) {
				deferred.resolve(response.data);
			});

			return deferred.promise;
		},

		validateParams: function() {

			// define variables
			var deferred = $q.defer();
			var edge 	= $route.current.params.edge;
			var page 	= $route.current.params.page;
			var id 		= $route.current.params.id;

			edges = apiService.getEdges().then(function(edges) {

				// validate if edge exist in beans
				if (edges[edge]){

					// validate if id param is required
					if(id !== undefined){
						
						idCheck = $http.get('api/exists/'+edge+'/'+id).then(function(response) {

							// validate if ID exist in database					
							if(response.data.exists === true){
								deferred.resolve();
							}
							else{
								deferred.reject('ID dos not exist');
								console.log('ID does not exist.');
								$location.url('/');
							}

						});
					}
					else{
						deferred.resolve();
					}

				}
				else{
					deferred.reject('Edge dos not exist');
					console.log('Edge dos not exist.');
					$location.url('/');
				}
			});

			return deferred.promise;
		},

		getEdges: function() {
			var deferred = $q.defer();

			edges = $http.get('api/edges').then(function(response) {
				deferred.resolve(response.data.beans);
			});
			
			return deferred.promise;
		},

		getSchema: function() {
			var deferred = $q.defer();
			var edge = $route.current.params.edge;

			schema = $http.get('api/schema/'+edge).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getList: function() {
			var deferred = $q.defer();
			var edge = $route.current.params.edge;
			var page = $route.current.params.page;

			list = $http.get('api/list/'+edge+'/'+page).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getCount: function() {
			var deferred = $q.defer();
			var edge = $route.current.params.edge;

			count = $http.get('api/count/'+edge).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getRead: function() {
			var deferred = $q.defer();
			var edge 	= $route.current.params.edge;
			var id 		= $route.current.params.id;

			read = $http.get('api/read/'+edge+'/'+id).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

	};

	return apiService;
});

/* ************************************************************
ANGULAR CONTROLLERS
************************************************************ */

// Main Controller
AdminApp.controller('MainController', function ($scope, apiService) {
	
	$scope.$on("$routeChangeStart", function() {
		NProgress.start();
	});

	$scope.$on("$routeChangeSuccess", function() {
		// fired on success of routeChange.
	});

	$scope.$on('$viewContentLoaded', function(){
		NProgress.done();
	});

	// get service function to be used async
	apiService.getEdges().then(function(edges) {
		$scope.edges = edges;
	});

});

// Dashboard Controller
AdminApp.controller('DashboardController', function ($scope, hi, edges) {
	$scope.hi = hi;
	$scope.edges = edges;
});

// Menu Controller
AdminApp.controller('MenuController', function ($scope, $location) {

	$scope.isActive = function(edge) {
		if ( ($location.path().split('/')[2] === edge) || ($location.path() === '/' && edge === 'dashboard') ) {
			return 'active';
		} else {
			return '';
		}
	};

});

// LIST Controller
AdminApp.controller('ListController', function ($scope, $location, $http, $routeParams, schema, list, count) {
	
	$scope.alert 		= {};
	$scope.schema 		= schema;
	$scope.items 		= list;
	$scope.totalItems 	= count.sum;
	$scope.itemsPerPage = 5;
	$scope.maxSize 		= 10;

	$scope.setPage = function (page) {
		$scope.currentPage = page;
	};

	$scope.setPage($routeParams.page);

	$scope.pageChanged = function() {
		$location.url('/list/'+ $routeParams.edge +'/p/'+$scope.currentPage);
	};

	$scope.onDestroy = function(id) {
		var destroyItem = confirm('Tem certeza que deseja excluir?');

		if (destroyItem) {
			$http.delete('api/destroy/'+ $routeParams.edge +'/'+id).then(function(response) {
				$scope.$broadcast('sendAlert', response);
				delete $scope.items[id];
			});
		}
	}

});

// CREATE Controller
AdminApp.controller('CreateController', function ($scope, schema) {

	$scope.schema = schema;
	$scope.schemaData = {};

	$scope.onChange = function(data) {
		// fired onChange of form data. 
		console.dir(data);
	};

});

// READ Controller
AdminApp.controller('ReadController', function ($scope, schema, read) {
	
	$scope.item = read;
	$scope.schema = schema;
	$scope.schemaData = read;

	$scope.onLoad = function() {
		$scope.$broadcast('disableForm', {});
	};

});

// UPDATE Controller
AdminApp.controller('UpdateController', function ($scope, schema, read) {
	
	$scope.item = read;
	$scope.schema = schema;
	$scope.schemaData = read;

	$scope.onChange = function(data) {
		// fired onChange of form data. 
		console.dir(data);
	};

});

// Forms Controller
AdminApp.controller('FormController', function ($scope, $http, $location, $routeParams) {
	
	var edge 	= $routeParams.edge;
	var id 		= $routeParams.id;

	$scope.schema = $scope.$parent.schema;
	$scope.itemId = id;

	if($scope.$parent.read !== undefined){
		$scope.$on('disableForm', function(event, obj) {

			$scope.editor.disable();

			// TODO: when using sceditor WYSIWYG, need to fire function to "readOnly = true"
			// Examples that doesn't work: 
			// instance.readOnly(1);
			// $scope.editor.plugins.sceditor.readOnly(true);
			
		});
	};

	$scope.onCreate = function() {

		var item = $scope.editor.getValue();
		$http.post('api/create/'+edge, item).success(function(){
			$location.path('/list/'+edge);
		});

	};

	$scope.onUpdate = function(){
	
		var item = $scope.editor.getValue();
		$http.put('api/update/'+edge+'/'+id, item).success(function() {
			$location.path('/list/'+edge);
		});
	};

	$scope.onDestroy = function() {
		var destroyItem = confirm('Tem certeza que deseja excluir?');

		if (destroyItem) {
			$http.delete('api/destroy/'+edge+'/'+id).then(function(response) {
				$location.path('/list/'+edge);
			});
		}
	}

});

AdminApp.controller('AlertController', function ($scope) {
	$scope.alerts = [];

	$scope.$on('sendAlert', function(event, obj) {
		$scope.alerts.push({type: 'danger', msg: obj.data.message});		
	});

	$scope.closeAlert = function(index) {
		$scope.alerts.splice(index, 1);
	};

});

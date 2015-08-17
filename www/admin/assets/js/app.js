/* ************************************************************
INIT
************************************************************ */
NProgress.start();

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
					schema: function(apiService){ return apiService.getSchema(); 	},
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
					schema: function(apiService){ return apiService.getSchema(); 	},
					read: 	function(apiService){ return apiService.getRead(); 		},
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
					schema: function(apiService){ return apiService.getSchema(); 	},
					read: 	function(apiService){ return apiService.getRead(); 		},
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
AdminApp.config(
	function(JSONEditorProvider) {
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

													$.ajax({
														xhr: function () {
															var xhr = new window.XMLHttpRequest();
															xhr.upload.addEventListener("progress", function (evt) {
																if (evt.lengthComputable) {
																	var percentComplete = evt.loaded / evt.total;
																	console.log(percentComplete);
																}
															}, false);
															xhr.addEventListener("progress", function (evt) {
																if (evt.lengthComputable) {
																	var percentComplete = evt.loaded / evt.total;
																	console.log(percentComplete);
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

											if (type === 'root.upload_fail') cbs.failure('Upload failed');
											else {
												var tick = 0;

												var tickFunction = function() {
													tick += 1;
													// console.log('progress: ' + tick);

												if (tick < 100) {
													cbs.updateProgress(tick);
													window.setTimeout(tickFunction, 50)
												} else if (tick == 100) {
													cbs.updateProgress();
													window.setTimeout(tickFunction, 500)
												} else {
													//cbs.success('http:www.//example.com/images/' + file.name);
												}
											};

											  window.setTimeout(tickFunction)
											}

										},
				}
			}
		});
	}
);

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
AdminApp.controller('MainController', 
	function ($scope) {
	
		$scope.$on('$viewContentLoaded', function(){
			NProgress.done();
		});

	}
);

// Dashboard Controller
AdminApp.controller('DashboardController', 
	function ($scope, hi, edges) {
		$scope.hi = hi;
		$scope.edges = edges;
	}
);

// Menu Controller
AdminApp.controller('MenuController', 
	function ($scope, $http, $location, apiService) {

		// get service function to be used async
		apiService.getEdges().then(function(edges) {
			$scope.edges = edges;
		});

		$scope.isActive = function(edge) {
			if ($location.path().split('/')[2] === edge) {
				return 'active';
			} 
			if ($location.path() === '/' && edge === 'dashboard') {
				return 'active';
			} 
			else 
			{
				return '';
			}
		};

	}
);

// List Controller
AdminApp.controller('ListController', 
	function ($scope, $location, $routeParams, schema, list, count) {
		NProgress.start();

		var edge = $routeParams.edge;
		var page = $routeParams.page;

		$scope.schema 		= schema;
		$scope.items 		= list;
		$scope.totalItems 	= count.sum;
		$scope.itemsPerPage = 5;
		$scope.maxSize 		= 10;

		$scope.setPage = function (page) {
			$scope.currentPage = page;
		};

		$scope.setPage(page);

		$scope.pageChanged = function() {
			$location.url('/list/'+edge+'/p/'+$scope.currentPage);
		};

		$scope.onDestroy = function(id) {
			var destroyItem = confirm('Tem certeza que deseja excluir?');

			if (destroyItem) {
				$http.delete('api/destroy/'+edge+'/'+id);
			}
		}

	}
);

// Create Controller
AdminApp.controller('CreateController', 
	function ($scope, schema) {
		NProgress.start();

		$scope.schema = schema;
		$scope.schemaData = {};

		$scope.onChange = function(data) {
			console.log('onChange: ');
			console.dir(data);
		};

	}
);

// Read Controller
AdminApp.controller('ReadController', 
	function ($scope, schema, read) {
		NProgress.start();

		$scope.item = read;
		$scope.schema = schema;
		$scope.schemaData = read;

		$scope.onLoad = function() {
			$scope.$broadcast('disableForm', {});
		};

	}
);

// Update Controller
AdminApp.controller('UpdateController', 
	function ($scope, schema, read) {
		NProgress.start();

		$scope.item = read;

		$scope.schema = schema;
		$scope.schemaData = read;

		$scope.onChange = function(data) {
			console.log('onChange: ');
			console.dir(data);
		};

	}
);

// Forms Controller
AdminApp.controller('FormController', 
	function ($scope, $http, $location, $routeParams) {
		NProgress.start();

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
				$scope.reset();
				$scope.activePath = $location.path('/list/'+edge);
			});

			$scope.reset = function() {
				$scope.item = angular.copy($scope.master);
			};

			$scope.reset();
		};

		$scope.onUpdate = function(){
			var item = $scope.editor.getValue();

			$http.put('api/update/'+edge+'/'+id, item).success(function() {
				$scope.activePath = $location.path('/list/'+edge);
			});
		};

		$scope.onDestroy = function() {
			var destroyItem = confirm('Tem certeza que deseja excluir?');

			if (destroyItem) {
				$http.delete('api/destroy/'+edge+'/'+id);
				$scope.activePath = $location.path('/list/'+edge);
			}
		}

	}
);

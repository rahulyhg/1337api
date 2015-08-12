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
				templateUrl: 'assets/tpl/dashboard.html', 
				controller: 'DashboardController',
				controllerAs: 'dashboard',
				resolve: {
					hi: 	function(apiService){ return apiService.getHi(); },
					edges: 	function(apiService){ return apiService.getEdges(); },				
				}
			}	
		);

		// CRUD		
		$routeProvider.when( 
			'/list/:edge/p/:page', 
			{
				templateUrl: 'assets/tpl/list.html', 
				controller: 'ListController',
				controllerAs: 'list',
				resolve: {
					schema: function(apiService){ return apiService.getSchema(); },
					list: 	function(apiService){ return apiService.getList(); },
					count: 	function(apiService){ return apiService.getCount(); },
				}
			}		
		);

		$routeProvider.when( 
			'/create/:edge', 
			{
				templateUrl: 'assets/tpl/create.html', 
				controller: 'CreateController',
				controllerAs: 'create'				
			}	
		);

		$routeProvider.when( 
			'/read/:edge/:id', 
			{
				templateUrl: 'assets/tpl/read.html', 
				controller: 'ReadController',
				controllerAs: 'read',
				resolve: {	
				},
			}
		);

		$routeProvider.when(
			'/update/:edge/:id', 
			{
				templateUrl: 'assets/tpl/update.html', 
				controller: 'UpdateController',
				controllerAs: 'update'
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
				}
			}
		});
	}
);

/* ************************************************************
ANGULAR SERVICES
************************************************************ */		

AdminApp.factory("apiService", function($q, $http, $route){

	var hi;
	var edges;
	var schema;
	var list;

	var apiService = {

		getHi: function() {
			if ( !hi ) {
				hi = $http.get('api/hi').then(function(response) {
					return response.data;
				});
			}
			return hi;
		},

		getEdges: function() {
			if (!edges) {
				edges = $http.get('api/edges').then(function(response) {
					return response.data.beans;
				});
			}
			return edges;
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

	};

	return apiService;
});

/*AdminApp.factory('existsService', function($http) {

	var promise;	
	var existsService = {
		async: function(edge, id) {
			if ( !promise ) {
				promise = $http.get('api/exists/'+edge+'/'+id).then(function (response) {
					return response.data.exists;
				});
			}
	
			// Return the promise to the controller
			return promise;
		}
	};
	return existsService;
});
*/

/* ************************************************************
ANGULAR CONTROLLERS
************************************************************ */

// Main Controller
AdminApp.controller('MainController', 
	function ($scope, $routeParams, $location) {
	
		$scope.$on('$viewContentLoaded', function(){
			NProgress.done();
		});

//		$http.get('api/edges').success(function(data) {
//			$scope.edges = data.beans;
//
//			if(data.beans[edge] === undefined){
//				$location.url('/');
//			};
//
//		});

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
	function ($scope, $http, apiService) {
		
		// ASYNC GET SERVICE
		apiService.getEdges().then(function(edges) {
			$scope.edges = edges;
		});

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
	function ($scope, $http, $location, $routeParams) {
		NProgress.start();

		var edge = $routeParams.edge;

		$scope.schema = $http.get('api/schema/'+edge);
		$scope.schemaData = {};

		$scope.onChange = function(data) {
			console.log('onChange: ');
			console.dir(data);
		};

	}
);

// Read Controller
AdminApp.controller('ReadController', 
	function ($scope, $http, $location, $routeParams, testResolve) {
		NProgress.start();

		console.dir(testResolve);


		var edge = $routeParams.edge;
		var id = $routeParams.id;

		$http.get('api/read/'+edge+'/'+id).success(function(data) {
			$scope.item = data;
		});

		$scope.schema = $http.get('api/schema/'+edge);
		$scope.schemaData = $http.get('api/read/'+edge+'/'+id);

		$scope.onLoad = function() {
			$scope.$broadcast('disableForm', {});
		};

	}
);

// Update Controller
AdminApp.controller('UpdateController', 
	function ($scope, $http, $location, $routeParams) {
		NProgress.start();

		var edge 	= $routeParams.edge;
		var id 		= $routeParams.id;

		$http.get('api/read/'+edge+'/'+id).success(function(data) {
			$scope.item = data;
		});

		$scope.schema = $http.get('api/schema/'+edge);
		$scope.schemaData = $http.get('api/read/'+edge+'/'+id);

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

		$http.get('api/schema/'+edge).success(function(data) {
			data.itemId = id;			
			$scope.schema = data;
		});

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
				$scope.activePath = $location.path('/'+edge);
			});

			$scope.reset = function() {
				$scope.item = angular.copy($scope.master);
			};

			$scope.reset();
		};

		$scope.onUpdate = function(){
			var item = $scope.editor.getValue();

			$http.put('api/update/'+edge+'/'+id, item).success(function() {
				$scope.activePath = $location.path('/'+edge);
			});
		};

		$scope.onDestroy = function() {
			var destroyItem = confirm('Tem certeza que deseja excluir?');

			if (destroyItem) {
				$http.delete('api/destroy/'+edge+'/'+id);
				$scope.activePath = $location.path('/'+edge);
			}
		}

	}
);

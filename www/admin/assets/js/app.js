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
				controllerAs: 'dashboard'
			}
		);

		// CRUD		
		$routeProvider.when( 
			'/list/:edge/p/:page', 
			{
				templateUrl: 'assets/tpl/list.html', 
				controller: 'ListController',
				controllerAs: 'list'				
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

					testResolve: function($http) {
						return $http.get('api/exists/item/1');
					},

/*					id: function ($q, $route, $location, existsService) {

						var deferred = $q.defer(),
						edge = $route.current.params.edge;
						id = $route.current.params.id;

						// ASYNC GET & BROADCAST BEANS LIST
						existsService.async(edge, id).then(function(data) {
							var exists = data;
							console.log(data);

							if(data == true){
								deferred.resolve();
							}

							else{
								console.log('redirect');
								deferred.reject('invalid id');
								$location.url('/');
							}

						});

						return deferred.promise;
					}*/

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

AdminApp.factory('edgesService', function($http) {

	var promise;	
	var edgesService = {
		async: function() {
			if ( !promise ) {
				// $http returns a promise, which has a then function, which also returns a promise
				promise = $http.get('api/edges').then(function (response) {
	
					// The then function here is an opportunity to modify the response
					// console.log(response);
				
					// The return value gets picked up by the then in the controller.
					return response.data;
				});
			}
	
			// Return the promise to the controller
			return promise;
		}
	};
	return edgesService;
});

AdminApp.factory('existsService', function($http) {

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


/* ************************************************************
ANGULAR CONTROLLERS
************************************************************ */

// Main Controller
AdminApp.controller('MainController', 
	function ($scope, $routeParams, $location, edgesService) {

		// ASYNC GET & BROADCAST BEANS LIST
		edgesService.async().then(function(data) {
			var edges = data.beans;
			$scope.$broadcast('edges', {edges});
		});
	
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
	function ($scope, $http) {
		NProgress.start();

		$http.get('api/hi').success(function(data){
			$scope.hi  = data;
		});

		// TODO: Quando saio dessa tela e volto, o $scope do broadcast "sumiu".

		$scope.$on('edges', function(event, data) {
			$scope.edges = data.edges;
		});

	}
);

// Menu Controller
AdminApp.controller('MenuController', 
	function ($scope, $http) {
		NProgress.start();

		$scope.$on('edges', function(event, data) {
			$scope.edges = data.edges;
		});

	}
);

// List Controller
AdminApp.controller('ListController', 
	function ($scope, $http, $location, $routeParams) {
		NProgress.start();

		var edge = $routeParams.edge;
		var page = $routeParams.page;

		$http.get('api/schema/'+edge).success(function(data) {
			$scope.schema = data;
		});

		$http.get('api/list/'+edge+'/'+page).success(function(data) {
			$scope.items = data;
		});			

		$http.get('api/count/'+edge).success(function(data) {
			$scope.totalItems = data.sum;
			$scope.setPage(page);
		});

		$scope.itemsPerPage = 5;
		$scope.maxSize = 10;

		$scope.setPage = function (page) {
			$scope.currentPage = page;
		};

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

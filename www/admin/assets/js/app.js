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
				controller:'DashboardController'
			}
		);

		// CRUD
		$routeProvider.when( 
			'/list/:bean', 
			{
				templateUrl: 'assets/tpl/list.html', 
				controller: 'ListController', 
				redirectTo: function (routeParams, path, search) { 
					return path+'p/1'; 
				} 
			}
		);
		
		$routeProvider.when( 
			'/list/:bean/p/:page', 
			{
				templateUrl: 'assets/tpl/list.html', 
				controller: 'ListController'
			}		
		);

		$routeProvider.when( 
			'/create/:bean', 
			{
				templateUrl: 'assets/tpl/create.html', 
				controller: 'CreateController'
			}	
		);

		$routeProvider.when( 
			'/read/:bean/:id', 
			{
				templateUrl: 'assets/tpl/read.html', 
				controller: 'ReadController'
			}		
		);

		$routeProvider.when(
			'/update/:bean/:id', 
			{
				templateUrl: 'assets/tpl/update.html', 
				controller: 'UpdateController'
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
// nothing here.

/* ************************************************************
ANGULAR CONTROLLERS
************************************************************ */

// Main Controller
AdminApp.controller('MainController', 
	function ($scope, $http, $routeParams, $location) {

		$scope.$on('$viewContentLoaded', function(){
			NProgress.done();
		});

		var edge = $routeParams.bean;

		$http.get('api/edges').success(function(data) {
			$scope.edges = data.beans;

			if(data.beans[edge] === undefined){
				$location.url('/');
			};

		});

	}
);

// Dashboard Controller
AdminApp.controller('DashboardController', 
	function ($scope, $http) {
		NProgress.start();

		$http.get('api/hi').success(function(data){
			$scope.hi  = data;
		});

		$http.get('api/edges').success(function(data){
			$scope.beans  = data.beans;
		});
	}
);

// Menu Controller
AdminApp.controller('MenuController', 
	function ($scope, $http) {
		NProgress.start();
		
		$http.get('api/edges').success(function(data){
			$scope.beans  = data.beans;
		});


	}
);

// List Controller
AdminApp.controller('ListController', 
	function ($scope, $http, $location, $routeParams) {
		NProgress.start();
		var path = $location.path().split('/');
		var edge = path[2];
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
	function ($scope, $http, $location) {
		NProgress.start();
		// $scope.master = {};
		// $scope.activePath = null;

		var path = $location.path().split('/');
		var edge = path[2];

		$scope.schema = $http.get('api/schema/'+edge)
		$scope.schemaData = {};

		$scope.onChange = function(data) {
			console.log('onChange: ');
			console.dir(data);
		};

	}
);

// Read Controller
AdminApp.controller('ReadController', 
	function ($scope, $http, $location, $routeParams) {
		NProgress.start();
		// $scope.master = {};
		// $scope.activePath = null;

		var path = $location.path().split('/');
		var edge = $routeParams.bean;

		$http.get('api/edges').success(function(data) {
			$scope.edges = data.beans;

			if(data.beans[edge] === undefined){
				$location.url('/');
			};

		});

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
		// $scope.master = {};
		// $scope.activePath = null;

		var path 	= $location.path().split('/');
		var edge 	= path[2];
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
	function ($scope, $http, $location) {
		NProgress.start();

		var path 	= $location.path().split('/');
		var action 	= path[1];
		var edge 	= path[2];
		var id 		= path[3];

		$http.get('api/schema/'+edge).success(function(data) {
			data.itemId = id;			
			$scope.schema = data;
		});

		if(action == 'read'){
			$scope.$on('disableForm', function(event, obj) {

				console.dir($scope.editor);
				// TODO: when using sceditor WYSIWYG, need to fire function to "readOnly = true"
				//instance.readOnly(1);

				$scope.editor.disable();
				$scope.editor.plugins.sceditor.readOnly(true);
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

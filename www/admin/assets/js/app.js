
/* ************************************************************
INIT
************************************************************ */
var AdminApp = angular.module('AdminApp', ['ngRoute', 'angular-json-editor']);
NProgress.start();

/* ************************************************************
ANGULAR MODULES CONFIG
************************************************************ */			

// NG Routes Provider
var edgeObj = {};
var edgeRoutes = [];
var edgeRoutesArr = [];

for(bean in beans){	
	edgeObj = 
		[
			{route: '/'			+beans[bean]		,obj: {templateUrl: 'assets/tpl/list.html'	, controller: 'ListController'}		},
			{route: '/create/'	+beans[bean]		,obj: {templateUrl: 'assets/tpl/create.html', controller: 'CreateController'}	},
			{route: '/read/'	+beans[bean]+'/:id'	,obj: {templateUrl: 'assets/tpl/read.html'	, controller: 'ReadController'}		},
			{route: '/update/'	+beans[bean]+'/:id'	,obj: {templateUrl: 'assets/tpl/update.html', controller: 'UpdateController'}	}
		];
	edgeRoutes.push(edgeObj);
}

AdminApp.config([
	'$routeProvider',
	function($routeProvider) {
		$routeProvider.when('/', {templateUrl: 'assets/tpl/dashboard.html', controller:'DashboardController'});

		for (var k in edgeRoutes){
			edgeRoutesArr = edgeRoutes[k];
			$routeProvider.when(edgeRoutesArr[0].route, edgeRoutesArr[0].obj);
			$routeProvider.when(edgeRoutesArr[1].route, edgeRoutesArr[1].obj);
			$routeProvider.when(edgeRoutesArr[2].route, edgeRoutesArr[2].obj);
			$routeProvider.when(edgeRoutesArr[3].route, edgeRoutesArr[3].obj);
		}

		$routeProvider.otherwise({redirectTo: '/'});
	}
]);

// JSON Editor Provider
AdminApp.config(
	function(JSONEditorProvider) {
		JSONEditorProvider.configure({
			defaults: {
				options: {
					iconlib: 			'fontawesome4',
					theme: 				'bootstrap3',
					disable_collapse: 	true,
					disable_edit_json: 	true,
					disable_properties: true
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
	function ($scope) {

		$scope.$on('$viewContentLoaded', function(){
			NProgress.done();
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
			$scope.menus  = data.beans;
		});
	}
);

// List Controller
AdminApp.controller('ListController', 
	function ($scope, $http, $location) {
		NProgress.start();

		var path = $location.$$path.split('/');
		var edge = path[1];

		$http.get('api/schema/'+edge).success(function(data) {
			$scope.schema = data;
		});
		$http.get('api/read/'+edge).success(function(data) {
			$scope.items = data;
		});
		$http.get('api/count/'+edge).success(function(data) {
			$scope.count = data;
		});

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

		var path = $location.$$path.split('/');
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

		var path = $location.$$path.split('/');
		var edge = path[2];
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

		var path = $location.$$path.split('/');
		var edge = path[2];
		var id = $routeParams.id;

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

		var path 	= $location.$$path.split('/');
		var action 	= path[1];
		var edge 	= path[2];
		var id 		= path[3];

		$http.get('api/schema/'+edge).success(function(data) {
			data.itemId = id;			
			$scope.schema = data;
		});

		if(action == 'read'){
			$scope.$on('disableForm', function(event, obj) {
				$scope.editor.disable();
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

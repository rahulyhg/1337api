/* ************************************************************
ANGULAR INIT
************************************************************ */
var AdminApp = angular.module('AdminApp', ['ngRoute', 'angular-json-editor']);

/* ************************************************************
ANGULAR MODULES CONFIG
************************************************************ */			

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
ANGULAR CONTROLLERS
************************************************************ */

// Dashboard Controller
AdminApp.controller('DashboardController', 
	function ($scope, $http) {
		$http.get('api/hi').success(function(data){
			$scope.hi  = data;
		});
	}
);

// Menu Controller
AdminApp.controller('MenuController', 
	function ($scope, $http) {
		$http.get('api/edges').success(function(data){
			$scope.menus  = data.beans;
		});
	}
);

// List Controller
AdminApp.controller('ListController', 
	function ($scope, $http, $location) {
		var edge = $location.$$path;
		$http.get('api/schema'+edge).success(function(data) {
			$scope.schema = data;
		});
		$http.get('api/read'+edge).success(function(data) {
			$scope.items = data;
		});
		$http.get('api/count'+edge).success(function(data) {
			$scope.count = data;
		});
	}
);

// Create Controller
AdminApp.controller('CreateController', 
	function ($scope, $http, $location) {
		$scope.master = {};
		$scope.activePath = null;
		var edge = $location.$$path.split('/');

		$http.get('api/schema/'+edge[2]).success(function(data) {
			$scope.schema = data;
		});

		$scope.add_new = function(item, AddNewForm) {

	/*		$http.post('api/create/'+edge[2], item).success(function(){
				$scope.reset();
				$scope.activePath = $location.path('/'+edge[2]);
			});

			$scope.reset = function() {
				$scope.item = angular.copy($scope.master);
			};

			$scope.reset();
	*/	};

	    $scope.mySchema = {
	        type: 'object',
	        properties: {
	            title: {
	                type: 'string',
	                title: 'Item Name',
	                required: true,
	                minLength: 1
	            }
	        }
	    };

	    $scope.myStartVal = {

	    };

	    $scope.onChange = function (data) {
	        console.log('Form changed!');
	        console.dir(data);
	    };
	}
);

// Update Controller
AdminApp.controller('UpdateController', 
	function ($scope, $http, $location, $routeParams) {
		var id = $routeParams.id;
		$scope.activePath = null;
		var edge = $location.$$path.split('/');

		$http.get('api/schema/'+edge[2]).success(function(data) {
			$scope.schema = data;
		});

		$http.get('api/read/'+edge[2]+'/'+id).success(function(data) {
			$scope.item = data;
		});

		$scope.update = function(item){
			$http.put('api/update/'+edge[2]+'/'+id, item).success(function(data) {
				$scope.item = data;
				$scope.activePath = $location.path('/'+edge[2]);
			});
		};

		$scope.delete = function(item) {
			var deleteitem = confirm('Tem certeza que deseja excluir?');

			if (deleteitem) {
				$http.delete('api/destroy/'+edge[2]+'/'+item.id);
				$scope.activePath = $location.path('/'+edge[2]);
			}
		}
	}
);

// Forms Controller
	AdminApp.controller('JSONEditorFormButtonsController', function ($scope, $http) {

    $scope.onSubmit = function () {
        console.log('onSubmit data in sync controller', $scope.editor.getValue());
        var item = $scope.editor.getValue();

		$http.post('api/create/items', item).success(function(){
			$scope.reset();
		});

		$scope.reset = function() {
			$scope.item = angular.copy($scope.master);
		};

		$scope.reset();
    };

    $scope.onAction2 = function () {
        console.log('onAction2');
    };


});














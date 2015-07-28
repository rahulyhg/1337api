/* ************************************************************
ANGULAR CONTROLLER
************************************************************ */

function MenuController($scope, $http) {
	$http.get('api/edges').success(function(data){
		$scope.menus  = data.beans;
	});
}

function ListController($scope, $http, $location) {
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

function AddCtrl($scope, $http, $location) {
	$scope.master = {};
	$scope.activePath = null;

	$scope.add_new = function(item, AddNewForm) {

		$http.post('api/add_item', item).success(function(){
			$scope.reset();
			$scope.activePath = $location.path('/');
		});

		$scope.reset = function() {
			$scope.item = angular.copy($scope.master);
		};

		$scope.reset();

	};
}

function EditCtrl($scope, $http, $location, $routeParams) {
	var id = $routeParams.id;
	$scope.activePath = null;

	$http.get('api/read/items/'+id).success(function(data) {
		$scope.item = data;
	});

	$scope.update = function(item){
		
		$http.put('api/update/items/'+id, item).success(function(data) {
			$scope.item = data;
			$scope.activePath = $location.path('/');
		});
	};

	$scope.delete = function(item) {
		console.log(item);

		var deleteitem = confirm('Are you absolutely sure you want to delete?');
		if (deleteitem) {
			$http.delete('api/destroy/items/'+item.id);
			$scope.activePath = $location.path('/');
		}
	};
}
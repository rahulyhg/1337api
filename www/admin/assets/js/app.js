/* ************************************************************
ANGULAR CONTROLLER
************************************************************ */

function DashboardController($scope, $http) {
	$http.get('api/hi').success(function(data){
		$scope.hi  = data;
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

function CreateController($scope, $http, $location) {
	$scope.master = {};
	$scope.activePath = null;
	var edge = $location.$$path.split('/');

	$http.get('api/schema/'+edge[2]).success(function(data) {
		$scope.schema = data;
	});

	$scope.add_new = function(item, AddNewForm) {

		$http.post('api/create/'+edge[2], item).success(function(){
			$scope.reset();
			$scope.activePath = $location.path('/'+edge[2]);
		});

		$scope.reset = function() {
			$scope.item = angular.copy($scope.master);
		};

		$scope.reset();

	};
}

function UpdateController($scope, $http, $location, $routeParams) {
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

	};
}
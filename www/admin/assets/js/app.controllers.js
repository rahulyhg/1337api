/* ************************************************************
ANGULAR ADMIN APP CONTROLLERS
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

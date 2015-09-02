/* ************************************************************
ANGULAR ADMIN APP CONTROLLERS
************************************************************ */

// Main Controller
AdminApp.controller('MainController', function ($rootScope, $scope, $location, $localStorage, authService, apiService) {

	$scope.$on("$routeChangeStart", function() {
		// fired on success of viewContent load.
	});

	$scope.$on("$routeChangeSuccess", function() {
		// fired on success of routeChange.
	});

	$scope.$on('$viewContentLoaded', function(){
		// fired on success of viewContent load.
	});

	function successAuth(res) {
		$localStorage.token = res.token;
			console.log('authentication: success');
			setTimeout(function(){ window.location = "/admin/"; }, 1000);
	}

	$scope.login = function () {
		var formData = {
			email: $scope.user.email,
			password: $scope.user.password
		};

		authService.login(formData, successAuth, function () {
			var message = {'data': {'message': 'Login/Senha incorreta, tente novamente.'}};
			$scope.$broadcast('sendAlert', message);
		})
	};

	$scope.logout = function () {
		authService.logout(function () {
			console.log('redirect logout');
			setTimeout(function(){ window.location = "/admin/"; }, 1000);

		});
	};

	$scope.token = $localStorage.token;
	$scope.tokenClaims = authService.getTokenClaims();
	$scope.user = $scope.tokenClaims.data;

	$scope.isAuth = function() {
		if ( !empty($scope.tokenClaims.data) ) {
			return true;
		} else {
			return false;
		}
	};

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
AdminApp.controller('ListController', function ($scope, $location, $http, $routeParams, schema, list, count, config) {
	
	$scope.alert 			= {};
	$scope.schema 			= schema;
	$scope.items 			= list;
	$scope.itemsThisPage 	= Object.keys(list).length; 
	$scope.totalItems 		= count.sum;
	$scope.itemsPerPage 	= 5;
	$scope.maxSize 			= 10;

	$scope.setPage = function (page) {
		$scope.currentPage = page;
	};

	$scope.setPage($routeParams.page);

	$scope.pageChanged = function() {
		$location.url('/list/'+ $routeParams.edge +'/p/'+$scope.currentPage);
	};

	$scope.onReload = function() {
		window.location.reload();
	};

	$scope.onExport = function() {
		window.open(config.API_BASE_URL + '/export/'+ $routeParams.edge);
	};

	$scope.onDestroy = function(id) {
		var destroyItem = confirm('Tem certeza que deseja excluir?');

		if (destroyItem) {
			$http.delete(config.API_BASE_URL + '/destroy/'+ $routeParams.edge +'/'+id).then(function(response) {
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

// Form Controller
AdminApp.controller('FormController', function ($scope, $http, $location, $routeParams, config) {
	
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
		$http.post(config.API_BASE_URL + '/create/'+edge, item).success(function(){
			$location.path('/list/'+edge);
		});

	};

	$scope.onUpdate = function(){
	
		var item = $scope.editor.getValue();
		$http.put(config.API_BASE_URL + '/update/'+edge+'/'+id, item).success(function() {
			$location.path('/list/'+edge);
		});
	};

	$scope.onDestroy = function() {
		var destroyItem = confirm('Tem certeza que deseja excluir?');

		if (destroyItem) {
			$http.delete(config.API_BASE_URL + '/destroy/'+edge+'/'+id).then(function(response) {
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

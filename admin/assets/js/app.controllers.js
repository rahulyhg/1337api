/* ************************************************************
ANGULAR ADMIN APP CONTROLLERS
************************************************************ */

// Main Controller
AdminApp.controller('MainController', function($rootScope, $scope, $location, $localStorage, $log, authService, apiService) {

	$scope.$on('$routeChangeStart', function() {
		// fired on success of viewContent load.
	});

	$scope.$on('$routeChangeSuccess', function() {
		// fired on success of routeChange.
	});

	$scope.$on('$viewContentLoaded', function() {
		// fired on success of viewContent load.
	});

	function successAuth(res) {
		$localStorage.token = res.data.token;
			$log.debug('login: success');
			window.location.href = window.location.pathname;
	}

	function errorAuth(res) {
		$log.debug('login: failed.');
		$scope.$broadcast('sendAlert', res);
	}

	$scope.login = function() {
		var formData = {
			email: $scope.user.email,
			password: $scope.user.password
		};
		authService.login(formData, successAuth, errorAuth);
	};

	$scope.logout = function() {
		authService.logout(function() {
			$log.debug('logout: success');
			window.location.href = window.location.pathname;
		});
	};

	$scope.token = $localStorage.token;
	$scope.tokenClaims = authService.getTokenClaims();
	$scope.user = $scope.tokenClaims.data;

	$scope.isAuth = function() {
		if (!empty($scope.tokenClaims.data)) {
			return true;
		} else {
			return false;
		}
	};

	// get service function to be used async
	if (!empty($scope.tokenClaims.data)) {
		apiService.getEdges().then(function(edges) {
			$scope.edges = edges;
		});
	}

});

// Dashboard Controller
AdminApp.controller('DashboardController', function($scope, hi, edges) {
	$scope.hi = hi;
	$scope.edges = edges;
});

// Menu Controller
AdminApp.controller('MenuController', function($scope, $location) {

	$scope.isActive = function(edge) {
		if (($location.path().split('/')[2] === edge) || ($location.path() === '/' && edge === 'dashboard')) {
			return 'active';
		} else {
			return '';
		}
	};

});

// LIST Controller
AdminApp.controller('ListController', function($scope, $location, $http, $routeParams, schema, list, count, config) {

	$scope.alert = {};
	$scope.schema = schema;
	$scope.items = list;
	$scope.itemsThisPage = Object.keys(list).length;
	$scope.totalItems = count.sum;
	$scope.itemsPerPage = 5;
	$scope.maxSize = 10;

	$scope.setPage = function(page) {
		$scope.currentPage = page;
	};

	$scope.setPage($routeParams.page);

	$scope.pageChanged = function() {
		$location.url('/list/' + $routeParams.edge + '/p/' + $scope.currentPage);
	};

	$scope.onReload = function() {
		window.location.reload();
	};

	$scope.onExport = function() {

		$http.get(config.API_BASE_URL + '/export/' + $routeParams.edge, { responseType: 'arraybuffer' })
			.success(function(data) {
				var file = new Blob([data], { type: 'application/csv' });
				var expTimestamp = Date.now();
				saveAs(file, 'export-' + $routeParams.edge + '-' + expTimestamp + '.csv');
		});

	};

	$scope.onDestroy = function(id) {

		swal(
			{title: "Tem certeza que deseja excluir?", 
			 text: "Esse registro será permanentemente excluído do banco de dados.",
			 type: "warning",
			 showCancelButton: true,
			 confirmButtonColor: "#DD6B55",
			 confirmButtonText: "Sim, excluir",
			 closeOnConfirm: false 
			}, 
			function(){
				$http.post(config.API_BASE_URL + '/destroy/' + $routeParams.edge + '/' + id).then(function(response) {
					swal("Sucesso", response.data.message, "success"); 
				});
				delete $scope.items[id];
			});
	};

});

// CREATE NG Controller
AdminApp.controller('CreateController', ['$scope', '$log', 'schema', function($scope, $log, schema) {

	$scope.schema = schema;
	$scope.schemaData = {};

	$scope.onChange = function(data) {
		// fired onChange of form json data.
		$log.debug(data);
	};

}]);

// READ Controller
AdminApp.controller('ReadController', function($scope, schema, read) {

	$scope.item = read;
	$scope.schema = schema;
	$scope.schemaData = read;

	$scope.onLoad = function() {
		$scope.$broadcast('disableForm', {});
	};

});

// UPDATE Controller
AdminApp.controller('UpdateController', function($scope, schema, read) {

	$scope.item = read;
	$scope.schema = schema;
	$scope.schemaData = read;

	$scope.onChange = function(data) {
		// fired onChange of form data.
		console.dir(data);
	};

});

// Update Password Controller
AdminApp.controller('UpdatePasswordController', function($scope) {

	// TODO: would be nice to validate if new_password and confirm_new_password are equal values thru JSON Schema frontend.
	var schema =
		{ 'type': 'object',
			'required': true,
			'properties': {
				'password': {'type': 'string', 'format': 'password', 'title': 'Senha Atual', 'required': true, 'minLength': 1, 'maxLength': 191 },
				'new_password': {'type': 'string', 'format': 'password', 'title': 'Nova Senha', 'required': true, 'minLength': 1, 'maxLength': 191 },
				'confirm_new_password': {'type': 'string', 'format': 'password', 'title': 'Confirme Nova Senha', 'required': true, 'minLength': 1, 'maxLength': 191 },
			},
		};

	$scope.schema = schema;
	$scope.schemaData = {};

	$scope.onChange = function(data) {
		// fired onChange of form data.
		console.dir(data);
	};

});

// Form Controller
AdminApp.controller('FormController', ['$scope', '$http', '$location', '$routeParams', '$q', 'config', function($scope, $http, $location, $routeParams, $q, config) {
	var edge = $routeParams.edge;
	var id = $routeParams.id;

	$scope.schema = $scope.$parent.schema;
	$scope.itemId = id;

	if ($scope.$parent.read !== undefined) {
		$scope.$on('disableForm', function(event, obj) {

			$scope.editor.disable();

			// TODO: when using sceditor WYSIWYG, need to fire function to "readOnly = true"
			// Examples that doesn't work:
			// instance.readOnly(1);
			// $scope.editor.plugins.sceditor.readOnly(true);

		});
	}

	$scope.onCreate = function() {
		var item = $scope.editor.getValue();
		var deferred = $q.defer();

		create = $http.post(config.API_BASE_URL + '/create/' + edge, item).then(function(res) {
			$location.path('/list/' + edge);
			deferred.resolve(res.data);
		});
		return deferred.promise;
	};

	$scope.onUpdate = function() {
		var item = $scope.editor.getValue();
		$http.post(config.API_BASE_URL + '/update/' + edge + '/' + id, item).success(function() {
			$location.path('/list/' + edge);
		});
	};

	$scope.onUpdatePassword = function() {
		var item = $scope.editor.getValue();
		var id = $scope.$parent.user.id;

		$http.post(config.API_BASE_URL + '/updatePassword/user/' + id, item).success(function() {
			console.log('PUT!');
			//$location.path('/list/'+edge);
		});
	};

	$scope.onDestroy = function() {

		swal(
			{title: "Tem certeza que deseja excluir?", 
			 text: "Esse registro será permanentemente excluído do banco de dados.",
			 type: "warning",
			 showCancelButton: true,
			 confirmButtonColor: "#DD6B55",
			 confirmButtonText: "Sim, excluir",
			 closeOnConfirm: false 
			}, 
			function(){
				$http.post(config.API_BASE_URL + '/destroy/' + $routeParams.edge + '/' + id).then(function(response) {
					$location.path('/list/' + edge);
					swal("Sucesso", response.data.message, "success"); 
				});
				delete $scope.items[id];
			});
	};

}]);

AdminApp.controller('AlertController', function($scope) {
	$scope.alerts = [];

	$scope.$on('sendAlert', function(event, obj) {
		$scope.alerts.push({type: 'danger', msg: obj.data.message});
	});

	$scope.closeAlert = function(index) {
		$scope.alerts.splice(index, 1);
	};

});

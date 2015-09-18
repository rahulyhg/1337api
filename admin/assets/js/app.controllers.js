(function(){
"use strict";

/* ************************************************************
ANGULAR ADMIN APP CONTROLLERS
************************************************************ */

// Main Controller
AdminApp.controller('MainController', 
	['$scope', '$location', '$localStorage', '$log', 'authService', 'apiService',
	function($scope, $location, $localStorage, $log, authService, apiService) {

		$scope.$on('$routeChangeStart', function() {
			$log.debug('routeChangeStart');
		});

		$scope.$on('$routeChangeSuccess', function() {
			$log.debug('routeChangeSuccess');
		});

		$scope.$on('$viewContentLoaded', function() {
			$log.debug('viewContentLoaded');
		});

		function successAuth(res) {
			$localStorage.token = res.data.token;
			$log.debug('login: success');
			window.location.href = window.location.pathname;
		}

		$scope.login = function() {
			var formData = {
				email: $scope.user.email,
				password: $scope.user.password
			};
			authService.login(formData, successAuth);
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

	}]
);

// Dashboard Controller
AdminApp.controller('DashboardController', 
	['$scope', 'hi', 'edges', 
	function($scope, hi, edges) {
		$scope.hi = hi;
		$scope.edges = edges;
	}]
);

// Menu Controller
AdminApp.controller('MenuController', 
	['$scope', '$location', 
	function($scope, $location) {

		$scope.isActive = function(edge) {
			if (($location.path().split('/')[2] === edge) || ($location.path() === '/' && edge === 'dashboard')) {
				return 'active';
			} else {
				return '';
			}
		};

	}]
);

// LIST Controller
AdminApp.controller('ListController', 
	['$scope', '$location', '$http', '$routeParams', '$q', 'schema', 'list', 'count', 'config',
	function($scope, $location, $http, $routeParams, $q, schema, list, count, config) {

		$scope.schema = schema;
		$scope.items = $.map(list, function(el) { return el; });
		$scope.itemsThisPage = Object.keys(list).length;
		$scope.totalItems = count.sum;
		$scope.itemsPerPage = count.itemsPerPage;
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
			var deferred = $q.defer();

			var onExport = $http.get(config.API_BASE_URL + '/export/' + $routeParams.edge)
				.then(function(res) {
					var file = new Blob([res.data], { type: 'application/csv' });
					var expTimestamp = Date.now();
					saveAs(file, 'export-' + $routeParams.edge + '-' + expTimestamp + '.csv');
					deferred.resolve('export-' + $routeParams.edge + '-' + expTimestamp + '.csv');
				});
			return deferred.promise;
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

	}]
);

// CREATE NG Controller
AdminApp.controller('CreateController', 
	['$scope', '$log', 'schema', 
	function($scope, $log, schema) {

		$scope.schema = schema;
		$scope.schemaData = {};

		$scope.onChange = function(data) {
			// fired onChange of form json data.
			$log.debug(data);
		};
	}]
);

// READ NG Controller
AdminApp.controller('ReadController', 
	['$scope', 'schema', 'read',
	function($scope, schema, read) {

		$scope.item = read;
		$scope.schema = schema;
		$scope.schemaData = read;

		$scope.onLoad = function() {
			$scope.$broadcast('disableForm', {});
		};

	}]
);

// UPDATE Controller
AdminApp.controller('UpdateController', 
	['$scope', '$log', 'schema', 'read',
	function($scope, $log, schema, read) {

		$scope.item = read;
		$scope.schema = schema;
		$scope.schemaData = read;

		$scope.onChange = function(data) {
			// fired onChange of form data.
			$log.debug(data);
		};

	}]
);

// Update Password Controller
AdminApp.controller('UpdatePasswordController', 
	['$scope', '$log',
	function($scope, $log) {

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
			$log.debug(data);
		};

	}]
);

// Form Controller
AdminApp.controller('FormController', 
	['$scope', '$http', '$location', '$routeParams', '$log', '$q', 'config', 
	function($scope, $http, $location, $routeParams, $log, $q, config) {
		var edge = $routeParams.edge;
		var id = $routeParams.id;

		$scope.schema = $scope.$parent.schema;
		$scope.itemId = id;

		if ($scope.$parent.read !== undefined) {
			$scope.$on('disableForm', function(event, obj) {

				// disable json editor
				$scope.editor.disable();

				// check for sceditor plugin instances and disable it
				for (var key in $scope.editor.root.editors) {
					if($scope.editor.getEditor("root."+key).sceditor_instance !== undefined){
						$scope.editor.getEditor("root."+key).sceditor_instance.readOnly(true);
					}
				}

			});
		}

		$scope.onCreate = function() {
			var item = $scope.editor.getValue();
			var deferred = $q.defer();

			var create = $http.post(config.API_BASE_URL + '/create/' + edge, item).then(function(res) {
				$location.path('/list/' + edge);
				deferred.resolve(res.data);
			});
			return deferred.promise;
		};

		$scope.onUpdate = function() {
			var item = $scope.editor.getValue();
			var deferred = $q.defer();

			var update = $http.post(config.API_BASE_URL + '/update/' + edge + '/' + id, item).then(function(res) {
				$location.path('/list/' + edge);
				deferred.resolve(res.data);
			});
			return deferred.promise;
		};

		$scope.onUpdatePassword = function() {
			var item = $scope.editor.getValue();
			var id = $scope.$parent.user.id;

			$http.post(config.API_BASE_URL + '/updatePassword/user/' + id, item).success(function() {
				//TODO: define what to do after updatePassword success. Should user be logged out?
				//$location.path('/list/'+edge);
			});
		};

		$scope.onDestroy = function() {

			swal(
				{title: "Tem certeza que deseja excluir?", 
				 text: "Esse registro será permanentemente excluído do banco de dados.",
				 type: "warning",
				 showCancelButton: true,
				 confirmButtonColor: "#c9302c",
				 confirmButtonText: "Sim, excluir",
				 closeOnConfirm: false 
				}, 
				function(){
					$http.post(config.API_BASE_URL + '/destroy/' + $routeParams.edge + '/' + id).then(function(response) {
						$location.path('/list/' + edge);
						$log.debug(response.data.message);
						swal("Sucesso", response.data.message, "success"); 
					});
					delete $scope.itemId;
				});
		};

	}]
);

/* ************************************************************
./end ANGULAR ADMIN APP CONTROLLERS
************************************************************ */

})();
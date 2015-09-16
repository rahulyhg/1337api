/* ************************************************************
ANGULAR ADMIN APP SERVICES
************************************************************ */

// authService Factory
AdminApp.factory('authService',
	['$q', '$http', '$localStorage', '$location', 'config',
	function($q, $http, $localStorage, $location, config) {

		function urlBase64Decode(str) {
			var output = str.replace('-', '+').replace('_', '/');
			switch (output.length % 4) {
				case 0:
					break;
				case 2:
					output += '==';
					break;
				case 3:
					output += '=';
					break;
				default:
					throw 'Illegal base64url string!';
			}
			return window.atob(output);
		}

		function getClaimsFromToken() {
			var token = $localStorage.token;
			var user = {};
			if (typeof token !== 'undefined') {
				var encoded = token.split('.')[1];
				user = JSON.parse(urlBase64Decode(encoded));
			}
			return user;
		}

		var tokenClaims = getClaimsFromToken();

		return {
			isAuth: function() {

				if (empty(tokenClaims)) {
					$location.url('login');
				}

				if (!empty(tokenClaims) && $location.path() == '/') {
					$location.url('dashboard');
				}

			},
			login: function(data, successAuth, errorAuth) {

				// define variables
				var deferred = $q.defer();

				loginSubmit = $http.post(config.API_SIGNIN_URL, data).then(function(res) {
					if (res.data.error) {
						errorAuth(res);
					} else {
						successAuth(res);
					}
				});
				
				return deferred.promise;

			},
			logout: function(success) {
				tokenClaims = {};
				delete $localStorage.token;
				success();
			},
			getTokenClaims: function() {
				return tokenClaims;
			}
		};
	}]
);

// apiService Factory
AdminApp.factory('apiService', 
	['$q', '$http', '$location', '$route', 'config',
	function($q, $http, $location, $route, config) {

		var apiService = {

			getHi: function() {
				var deferred = $q.defer();

				hi = $http.get(config.API_BASE_URL + '/hi').then(function(res) {
					deferred.resolve(res.data);
				});
				return deferred.promise;
			},

			validateParams: function() {

				// define variables
				var deferred = $q.defer();
				var edge = $route.current.params.edge;
				var page = $route.current.params.page;
				var id = $route.current.params.id;

				edges = apiService.getEdges().then(function(edges) {

					// validate if edge exist in beans
					if (edges[edge]) {

						// validate if id param is required
						if (id !== undefined) {
							idCheck = $http.get(config.API_BASE_URL + '/exists/' + edge + '/' + id).then(function(response) {

								// validate if ID exist in database
								if (response.data.exists === true) {
									deferred.resolve();
								}
								else {
									deferred.reject('ID dos not exist');
									console.log('ID does not exist.');
									$location.url('/');
								}

							});
						}
						else {
							deferred.resolve();
						}

					}
					else {
						deferred.reject('Edge dos not exist');
						console.log('Edge dos not exist.');
						$location.url('/');
					}
				});

				return deferred.promise;
			},

			getEdges: function() {
				var deferred = $q.defer();

				edges = $http.get(config.API_BASE_URL + '/edges').then(function(response) {
					deferred.resolve(response.data.beans);
				});

				return deferred.promise;
			},

			getSchema: function() {
				var deferred = $q.defer();
				var edge = $route.current.params.edge;

				schema = $http.get(config.API_BASE_URL + '/schema/' + edge).then(function(response) {
					deferred.resolve(response.data);
				});

				return deferred.promise;
			},

			getList: function() {
				var deferred = $q.defer();
				var edge = $route.current.params.edge;
				var page = $route.current.params.page;

				list = $http.get(config.API_BASE_URL + '/list/' + edge + '/' + page).then(function(response) {
					deferred.resolve(response.data);
				});

				return deferred.promise;
			},

			getCount: function() {
				var deferred = $q.defer();
				var edge = $route.current.params.edge;

				count = $http.get(config.API_BASE_URL + '/count/' + edge).then(function(response) {
					deferred.resolve(response.data);
				});

				return deferred.promise;
			},

			getRead: function() {
				var deferred = $q.defer();
				var edge = $route.current.params.edge;
				var id = $route.current.params.id;

				read = $http.get(config.API_BASE_URL + '/read/' + edge + '/' + id).then(function(response) {
					deferred.resolve(response.data);
				});

				return deferred.promise;
			},

		};

		return apiService;
	}]
);

/* ************************************************************
ANGULAR ADMIN APP INTERCEPTORS
************************************************************ */

// apiInterceptor Factory
AdminApp.factory('apiInterceptor', 
	['$q', '$location', '$localStorage', '$log', 
	function($q, $location, $localStorage, $log) {

		var apiInterceptor = {
			'request': function(config) {
				config.headers = config.headers || {};
				if ($localStorage.token) {
					config.headers.Authorization = 'Bearer ' + $localStorage.token;
				}
				return config;
			},
			'response': function(res){

				$log.debug(res.data);

				if (res.data.error) {
					$log.error(res.data.message);
					swal("ERRO", res.data.message, "error");
					return $q.reject(res);
				}

				return res;
			},
			'responseError': function(res) {

				if (res.status === 400) {
					$log.error(res.data.message);
					swal("ERRO", res.data.message, "error");
				}

				if (res.status === 401 || res.status === 403) {
					$log.error(res.data.message);
					
					if ($location.path() !== '/login' && typeof reloadLock === 'undefined') {
						reloadLock = true;
						delete $localStorage.token;
						tokenClaims = {};
						window.location.href = window.location.pathname;
					}
				}
				return $q.reject(res);
			}
		};

		return apiInterceptor;
	}]
);
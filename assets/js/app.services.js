/* ************************************************************
HELPER FUNCTIONS
************************************************************ */

// PHP.JS EMPTY
// http://phpjs.org/functions/empty/ 
// **************************** */
function empty(mixed_var) {
  var undef, key, i, len;
  var emptyValues = [undef, null, false, 0, '', '0'];

  for (i = 0, len = emptyValues.length; i < len; i++) {
    if (mixed_var === emptyValues[i]) {
      return true;
    }
  }

  if (typeof mixed_var === 'object') {
    for (key in mixed_var) {
      return false;
    }
    return true;
  }

  return false;
}

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
			login: function(data, successAuth) {
				var deferred = $q.defer();

				loginSubmit = $http.post(config.API_SIGNIN_URL, data).then(function(res) {
					successAuth(res);
					deferred.resolve(res);
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
	['$q', '$http', '$location', '$route', '$log', 'config',
	function($q, $http, $location, $route, $log, config) {

		var apiService = {

			getHi: function() {
				var deferred = $q.defer();

				hi = $http.get(config.API_BASE_URL + '/hi', {cache: true}).then(function(res) {
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
							idCheck = $http.get(config.API_BASE_URL + '/' + edge + '/' + id + '/exists').then(function(response) {

								// validate if ID exist in database
								if (response.data.exists === true) {
									deferred.resolve();
								}
								else {
									$log.warn('ID does not exist.');
									deferred.reject('ID dos not exist');
									$location.url('/');
								}

							});
						}
						else {
							deferred.resolve();
						}

					}
					else {
						$log.warn('Edge dos not exist.');
						deferred.reject('Edge dos not exist');
						$location.url('/');
					}
				});

				return deferred.promise;
			},

			getEdges: function() {
				var deferred = $q.defer();

				edges = $http.get(config.API_BASE_URL + '/edges',{cache: true}).then(function(response) {
					deferred.resolve(response.data.edges);
				});

				return deferred.promise;
			},

			getSchema: function() {
				var deferred = $q.defer();
				var edge = $route.current.params.edge;

				schema = $http.get(config.API_BASE_URL + '/' + edge + '/schema',{cache: true}).then(function(response) {
					deferred.resolve(response.data);
				});

				return deferred.promise;
			},

			getList: function() {
				var deferred = $q.defer();
				var edge = $route.current.params.edge;
				var page = $route.current.params.page;

				list = $http.get(config.API_BASE_URL + '/' + edge + '?page=' + page).then(function(response) {
					deferred.resolve(response.data);
				});

				return deferred.promise;
			},

			getCount: function() {
				var deferred = $q.defer();
				var edge = $route.current.params.edge;

				count = $http.get(config.API_BASE_URL + '/' + edge + '/count' ).then(function(response) {
					deferred.resolve(response.data);
				});

				return deferred.promise;
			},

			getRead: function() {
				var deferred = $q.defer();
				var edge = $route.current.params.edge;
				var id = $route.current.params.id;

				read = $http.get(config.API_BASE_URL + '/' + edge + '/' + id).then(function(response) {
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
					
					if(res.data.debug){
						$log.error(res.data.debug);
					}
					
					return $q.reject(res);
				}

				return res;
			},
			'responseError': function(res) {

				if (res.status === 401 || res.status === 403) {
					$log.error(res.data.message);
					
					if ($location.path() !== '/login' && typeof reloadLock === 'undefined') {
						reloadLock = true;
						delete $localStorage.token;
						tokenClaims = {};
						window.location.href = window.location.pathname;
					}
				}
				else {

					if(res.data.message){
						$log.error(res.data.message);
						swal("ERRO", res.data.message, "error");						
					}
					else {
						swal("ERRO", "Não foi possível processar sua requisição.", "error");						
						$log.error('Não foi possível processar sua requisição.');
					}

				}

				return $q.reject(res);
			}
		};

		return apiInterceptor;
	}]
);

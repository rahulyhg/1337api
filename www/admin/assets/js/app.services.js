/* ************************************************************
ANGULAR ADMIN APP SERVICES
************************************************************ */		

// authService Factory
AdminApp.factory('authService', function ($http, $localStorage, $location, config) {

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

			if(empty(tokenClaims)){
				$location.url('/login');
			};

			if(!empty(tokenClaims) && $location.path() == '/'){
				$location.url('/dashboard');				
			};

		},
		login: function (data, success, error) {
			$http.post(config.API_SIGNIN_URL, data).success(success).error(error)
		},
		logout: function (success) {
			tokenClaims = {};
			delete $localStorage.token;
			success();
		},
		getTokenClaims: function () {
			return tokenClaims;
		}
	};
});

// apiService Factory
AdminApp.factory("apiService", function ($q, $http, $location, $route, config) {

	var apiService = {

		getHi: function() {
			var deferred = $q.defer();
			
			hi = $http.get(config.API_BASE_URL + '/hi').then(function(response) {
				deferred.resolve(response.data);
			});

			return deferred.promise;
		},

		validateParams: function() {

			// define variables
			var deferred = $q.defer();
			var edge 	= $route.current.params.edge;
			var page 	= $route.current.params.page;
			var id 		= $route.current.params.id;

			edges = apiService.getEdges().then(function(edges) {

				// validate if edge exist in beans
				if (edges[edge]){

					// validate if id param is required
					if(id !== undefined){
						
						idCheck = $http.get(config.API_BASE_URL + '/exists/'+edge+'/'+id).then(function(response) {

							// validate if ID exist in database					
							if(response.data.exists === true){
								deferred.resolve();
							}
							else{
								deferred.reject('ID dos not exist');
								console.log('ID does not exist.');
								$location.url('/');
							}

						});
					}
					else{
						deferred.resolve();
					}

				}
				else{
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

			schema = $http.get(config.API_BASE_URL + '/schema/'+edge).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getList: function() {
			var deferred = $q.defer();
			var edge = $route.current.params.edge;
			var page = $route.current.params.page;

			list = $http.get(config.API_BASE_URL + '/list/'+edge+'/'+page).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getCount: function() {
			var deferred = $q.defer();
			var edge = $route.current.params.edge;

			count = $http.get(config.API_BASE_URL + '/count/'+edge).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getRead: function() {
			var deferred = $q.defer();
			var edge 	= $route.current.params.edge;
			var id 		= $route.current.params.id;

			read = $http.get(config.API_BASE_URL + '/read/'+edge+'/'+id).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

	};

	return apiService;
});

/* ************************************************************
ANGULAR ADMIN APP INTERCEPTORS
************************************************************ */		

// apiInterceptor Factory
AdminApp.factory('apiInterceptor', ['$q', '$location', '$localStorage', function($q, $location, $localStorage) {  

	var apiInterceptor = {
		'request': function (config) {
			config.headers = config.headers || {};
			if ($localStorage.token) {
				config.headers.Authorization = 'Bearer ' + $localStorage.token;
			}
			return config;
		},
		'responseError': function (response) {
			if (response.status === 400 || response.status === 401 || response.status === 403) {

				if( $location.path() !== '/login' && typeof reloadLock === 'undefined' ){
					reloadLock = true;
					delete $localStorage.token;
					tokenClaims = {};
					window.location.href = window.location.pathname;
				};
			}
			return $q.reject(response);
		}
	};

    return apiInterceptor;
}]);

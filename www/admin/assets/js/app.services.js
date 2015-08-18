/* ************************************************************
ANGULAR ADMIN APP SERVICES
************************************************************ */		

AdminApp.factory("apiService", function($q, $http, $location, $route){

	var apiService = {

		getHi: function() {
			var deferred = $q.defer();
			
			hi = $http.get('api/hi').then(function(response) {
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
						
						idCheck = $http.get('api/exists/'+edge+'/'+id).then(function(response) {

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

			edges = $http.get('api/edges').then(function(response) {
				deferred.resolve(response.data.beans);
			});
			
			return deferred.promise;
		},

		getSchema: function() {
			var deferred = $q.defer();
			var edge = $route.current.params.edge;

			schema = $http.get('api/schema/'+edge).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getList: function() {
			var deferred = $q.defer();
			var edge = $route.current.params.edge;
			var page = $route.current.params.page;

			list = $http.get('api/list/'+edge+'/'+page).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getCount: function() {
			var deferred = $q.defer();
			var edge = $route.current.params.edge;

			count = $http.get('api/count/'+edge).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

		getRead: function() {
			var deferred = $q.defer();
			var edge 	= $route.current.params.edge;
			var id 		= $route.current.params.id;

			read = $http.get('api/read/'+edge+'/'+id).then(function(response) {
				deferred.resolve(response.data);
			});
			
			return deferred.promise;
		},

	};

	return apiService;
});

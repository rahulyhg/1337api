(function () {
	'use strict';

	angular.module('app')
		.controller('HomeController', ['$rootScope', '$scope', '$location', '$localStorage', 'Auth',
			function ($rootScope, $scope, $location, $localStorage, Auth) {
				function successAuth(res) {
					$localStorage.token = res.token;
						console.log('redirect success');
						setTimeout(function(){ window.location = "/auth-demo/"; }, 1000);
				}

				$scope.signin = function () {
					var formData = {
						email: $scope.email,
						password: $scope.password
					};

					Auth.signin(formData, successAuth, function () {
						$rootScope.error = 'Invalid credentials.';
					})
				};

				$scope.logout = function () {
					Auth.logout(function () {
						console.log('redirect logout');
						setTimeout(function(){ window.location = "/auth-demo/"; }, 1000);

					});
				};
				$scope.token = $localStorage.token;
				$scope.tokenClaims = Auth.getTokenClaims();
			}])

		.controller('RestrictedController', ['$rootScope', '$scope', 'Data', function ($rootScope, $scope, Data) {
			Data.getRestrictedData(function (res) {
				$scope.data = res;
			}, function () {
				$rootScope.error = 'Failed to fetch restricted content.';
			});
		}]);
})();
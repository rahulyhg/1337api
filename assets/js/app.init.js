/* ************************************************************
ANGULAR INIT
************************************************************ */
var AdminApp = angular.module('AdminApp', 
	['ngRoute', 'ngStorage', 'angular-loading-bar', 'angular-json-editor', 'ui.bootstrap']
);

(function(){
"use strict";

/* ************************************************************
ANGULAR CONSTANTS
************************************************************ */
AdminApp.constant(
	'config',
		{
			API_BASE_URL: 'api/v1/private',
			API_SIGNIN_URL: 'api/v1/auth/signin',
		}
);

/* ************************************************************
ANGULAR CONFIG - LOG PROVIDER
************************************************************ */
AdminApp.config([
	'$logProvider',
	function($logProvider) {
		$logProvider.debugEnabled(false);
}]);

/* ************************************************************
ANGULAR CONFIG - NG ROUTES PROVIDER
************************************************************ */

AdminApp.config([
	'$routeProvider',
	function($routeProvider) {

		// DASHBOARD
		$routeProvider.when(
			'/',
			{
				controller: 'MainController',
				controllerAs: 'main',
				resolve: {
					auth: function(authService) { return authService.isAuth(); },
				}
			}
		);

		// DASHBOARD
		$routeProvider.when(
			'/dashboard',
			{
				templateUrl: 'assets/tpl/dashboard.html',
				controller: 'DashboardController',
				controllerAs: 'dashboard',
				resolve: {
					auth: function(authService) { return authService.isAuth(); },
					hi: function(apiService) { return apiService.getHi(); },
					edges: function(apiService) { return apiService.getEdges(); },
				}
			}
		);

		// LOGIN
		$routeProvider.when(
			'/login',
			{
				templateUrl: 'assets/tpl/login.html',
				controller: 'MainController',
				controllerAs: 'main',
			}
		);

		// UPDATE PASSWORD
		$routeProvider.when(
			'/userAccount/updatePassword',
			{
				templateUrl: 'assets/tpl/updatePassword.html',
				controller: 'UpdatePasswordController',
				controllerAs: 'updatePassword',
				resolve: {
					auth: function(authService) { return authService.isAuth(); },
				},
			}
		);

		// CRUD
		$routeProvider.when(
			'/list/:edge/p/:page',
			{
				templateUrl: 'assets/tpl/list.html',
				controller: 'ListController',
				controllerAs: 'list',
				resolve: {
					auth: function(authService) { return authService.isAuth(); },
					valid: function(apiService) { return apiService.validateParams(); },
					schema: function(apiService) { return apiService.getSchema(); },
					list: function(apiService) { return apiService.getList(); },
					count: function(apiService) { return apiService.getCount(); },
				}
			}
		);

		$routeProvider.when(
			'/create/:edge',
			{
				templateUrl: 'assets/tpl/create.html',
				controller: 'CreateController',
				controllerAs: 'create',
				resolve: {
					auth: function(authService) { return authService.isAuth(); },
					valid: function(apiService) { return apiService.validateParams(); },
					schema: function(apiService) { return apiService.getSchema(); },
				}
			}
		);

		$routeProvider.when(
			'/read/:edge/:id',
			{
				templateUrl: 'assets/tpl/read.html',
				controller: 'ReadController',
				controllerAs: 'read',
				resolve: {
					auth: function(authService) { return authService.isAuth(); },
					valid: function(apiService) { return apiService.validateParams(); },
					schema: function(apiService) { return apiService.getSchema(); },
					read: function(apiService) { return apiService.getRead(); },
				},
			}
		);

		$routeProvider.when(
			'/update/:edge/:id',
			{
				templateUrl: 'assets/tpl/update.html',
				controller: 'UpdateController',
				controllerAs: 'update',
				resolve: {
					auth: function(authService) { return authService.isAuth(); },
					valid: function(apiService) { return apiService.validateParams(); },
					schema: function(apiService) { return apiService.getSchema(); },
					read: function(apiService) { return apiService.getRead(); },
				},
			}
		);

		// REDIRECTS
		$routeProvider.when(
			'/list/:edge',
			{
				redirectTo: function(routeParams, path, search) { return path + '/p/1'; }
			}
		);

		// OTHERWISE
		$routeProvider.otherwise(
			{redirectTo: '/'}
		);
	}
]);

/* ************************************************************
ANGULAR CONFIG - HTTP PROVIDER
************************************************************ */

AdminApp.config([
	'$httpProvider',
	function($httpProvider) {
		$httpProvider.interceptors.push('apiInterceptor');
}]);

/* ************************************************************
ANGULAR JSON EDITOR
************************************************************ */

// GLOBAL UPLOAD AJAX FUNCTION
// **************************** */

var jeUploadFunction = function(type, file, cbs) {

						if (file) {
							// TODO: token is being recovered with jQuery, not Angular. future study to roadmap.
							var token = JSON.parse(localStorage.getItem('ngStorage-token'));
							var reader = new FileReader();

							reader.onloadend = function(evt) {
								var b = evt.target.result;
								var uploadData = '{"filename":"' + file.name + '", "filesize":"' + file.size + '", "blob":"' + b + '"}';
								var percentComplete = 0;

									// TODO: POST request is being done with jQuery, not Angular. future study to roadmap.
									$.ajax({
										xhr: function() {
											var xhr = new window.XMLHttpRequest();
											xhr.upload.addEventListener('progress', function(evt) {
												if (evt.lengthComputable) {
													var percentComplete = Math.round((evt.loaded / evt.total) * 100);
													if (percentComplete <= 100) {
														cbs.updateProgress(percentComplete);
													}
												}
											}, false);
											return xhr;
										},										
										type: 'POST',
										url: 'api/v1/private/upload',
										headers: {'Authorization':'Bearer ' + token},
										contentType: 'application/json; charset=utf-8',
										dataType: 'json',
										data: uploadData,
										success: function(data, textStatus, jQxhr) {

											if (data.error) {
												// TODO: If this function was being done via Angular, we could use $log to debug errors.
												console.dir(data.message);
												swal("ERRO", data.message, "error");

												if(data.debug){
													console.dir(data.debug);
												}
												cbs.success('');
											}
											else {
												cbs.success('' + data.href + '');
											}
										},
										error: function(jqXhr, textStatus, errorThrown) {
											console.log(errorThrown);
										}
									});

							};
							reader.readAsDataURL(file);
						}
					};

// GLOBAL JSON EDITOR CONFIG
// **************************** */

var jePluginsConfig = {
						sceditor: {
							style: 'assets/css/jquery.sceditor.default.min.css',
							toolbar: 'bold,italic,underline|strike,subscript,superscript|link,unlink|removeformat|bulletlist,orderedlist|source',
							locale: 'pt-BR',
							emoticonsEnabled: false,
							width: '98%',
							resizeEnabled: false,
						}
					};

var jeOptionsConfig = {
						iconlib: 'fontawesome4',
						theme: 'bootstrap3',
						ajax: true,
						disable_collapse: true,
						disable_edit_json: true,
						disable_properties: true,
						upload: jeUploadFunction,
					};

// JSON Editor Provider
// **************************** */

AdminApp.config(function(JSONEditorProvider) {
	JSONEditorProvider.configure({
		plugins: jePluginsConfig,
		defaults: { options: jeOptionsConfig }
	});
});

/* ************************************************************
./end ANGULAR INIT
************************************************************ */

})();
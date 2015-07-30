<?php 
	$edges = json_decode(file_get_contents('http://'.$_SERVER[HTTP_HOST].'/admin/api/edges'), true);
	foreach ($edges['beans'] as $k => $v) {
		$beans[$k] = $v['name'];
	}	
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>Admin Dashboard</title>
	<meta name="description" content="">
	<meta name="author" content="de elijah">

	<!-- CSS -->
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/sb-admin.css" rel="stylesheet" type="text/css">
	<link href="assets/css/font-awesome.min.css" rel="stylesheet" type="text/css">

	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>

<body ng-app="AdminApp">

<div id="wrapper">

	<!-- NAVIGATION -->
	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<!-- Brand and toggle get grouped for better mobile display -->
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="/admin">umstudio.com</a>
		</div>
		<!-- Top Menu Items -->
		<ul class="nav navbar-right top-nav">
			<li class="dropdown">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-user"></i> Elijah Hatem <b class="caret"></b></a>
				<ul class="dropdown-menu">
					<li>
						<a href="#"><i class="fa fa-fw fa-user"></i> Minha Conta</a>
					</li>
					<li>
						<a href="#"><i class="fa fa-fw fa-gear"></i> Configurações</a>
					</li>
					<li class="divider"></li>
					<li>
						<a href="#"><i class="fa fa-fw fa-power-off"></i> Log Out</a>
					</li>
				</ul>
			</li>
		</ul>
		<!-- Sidebar Menu Items - These collapse to the responsive navigation menu on small screens -->
		<div class="collapse navbar-collapse navbar-ex1-collapse">
			<ul class="nav navbar-nav side-nav" ng-controller="MenuController">
				<li class="active"><a href="/admin	"><i class="fa fa-fw fa-dashboard"></i> Dashboard</a></li>
				<li ng-repeat="menu in menus"><a href="#/{{menu.name}}"><i class="fa fa-fw fa-th-list"></i> {{menu.title}}</a></li>
			</ul>
		</div>
		<!-- /.navbar-collapse -->
	</nav>
	<!-- END NAVIGATION -->

	<!-- CONTAINER -->	
	<div id="page-wrapper">

		<div class="container-fluid">
			<div ng-view></div>
		</div>
		<!-- /.container-fluid -->

	</div>
	<!-- END CONTAINER -->	

</div>

<script type="text/javascript" src="assets/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/js/jsoneditor.min.js"></script>
<script type="text/javascript" src="assets/js/angular.min.js"></script>
<script type="text/javascript" src="assets/js/angular-route.min.js"></script>
<script type="text/javascript" src="assets/js/angular-json-editor.min.js"></script>
<script type="text/javascript" src="assets/js/bootstrap.min.js"></script>

<script type="text/javascript">

	angular
	.module(	
			'AdminApp', 
			[	'ngRoute', 
				'angular-json-editor'
			]
	)
	.config(
		[
			'$routeProvider', 
			function($routeProvider) {
				$routeProvider.
				when('/', {templateUrl: 'assets/tpl/dashboard.html', controller:DashboardController}).

				<?php
					foreach ($beans as $k => $v) {
						echo 'when(\'/'.$v.'\', {templateUrl: \'assets/tpl/list.html\', controller: ListController}).';
						echo 'when(\'/update/'.$v.'/:id\', {templateUrl: \'assets/tpl/update.html\', controller: UpdateController}).';
						echo 'when(\'/create/'.$v.'\', {templateUrl: \'assets/tpl/create.html\', controller: CreateController}).';
					};
				?>
				otherwise({redirectTo: '/'});
			}
		]	
	)

	.controller('JSONEditorFormButtonsController', function ($scope, $http) {

    $scope.onSubmit = function () {
        console.log('onSubmit data in sync controller', $scope.editor.getValue());
        var item = $scope.editor.getValue();

		$http.post('api/create/items', item).success(function(){
			$scope.reset();
		});

		$scope.reset = function() {
			$scope.item = angular.copy($scope.master);
		};

		$scope.reset();
    };

    $scope.onAction2 = function () {
        console.log('onAction2');
    };


})


	.controller('MenuController', ['$scope','$http', function ($scope, $http) {
		$http.get('api/edges').success(function(data){
			$scope.menus  = data.beans;
		})
	}]);
</script>

<script type="text/javascript" src ="assets/js/app.js"></script>

</body>
</html>
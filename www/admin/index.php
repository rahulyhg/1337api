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
	<title>AngularJs</title>
	<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet">
</head>

<body ng-app="AdminApp">

	<div class="container-fluid">
		<!-- NAVIGATION -->
		<nav class="text-center">
			<a href="/admin"><h1>Admin Sandbox</h1></a>
			<ul class="list-inline" ng-controller="MenuController">
				<li ng-repeat="menu in menus"><a href="#/{{menu.name}}">{{menu.name}}</a></li>
			</ul>
		</nav>
		<!-- END NAVIGATION -->
	</div>

	<div class="container">
		<div ng-view></div>
	</div>

<script type="text/javascript" src="assets/js/angular.min.js"></script>

<script type="text/javascript">
	angular.module('AdminApp', []).
	config(['$routeProvider', function($routeProvider) {
		$routeProvider.
		when('/', {templateUrl: 'assets/tpl/dashboard.html'}).

		<?php
			foreach ($beans as $k => $v) {
				echo 'when(\'/'.$v.'\', {templateUrl: \'assets/tpl/list.html\', controller: ListController}).';
			};
		?>

		when('/create', {templateUrl: 'assets/tpl/create.html', controller: AddCtrl}).
		when('/update/:id', {templateUrl: 'assets/tpl/update.html', controller: EditCtrl}).
		otherwise({redirectTo: '/'});
	}]);
</script>

<script type="text/javascript" src ="assets/js/app.js"></script>

</body>
</html>
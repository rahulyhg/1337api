<!DOCTYPE html>
<html>
<head>

	<!-- META -->
	<meta charset='utf-8'>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" type="image/gif" href="assets/images/favicon.gif"/>

	<title>Admin Dashboard</title>
	<meta name="description" content="">
	<meta name="author" content="de elijah">

	<!-- CSS -->
	<link type="text/css" rel="stylesheet" href="assets/css/app.style.css" />

	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
	<![endif]-->

</head>

<body ng-app="AdminApp">

<div id="wrapper" ng-controller="MainController">

	<!-- NAVIGATION -->
	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation" ng-controller="MenuController">

		<!-- NAVIGATION - HEADER MENU -->
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="/admin">umstudio.com</a>
		</div>
		<!-- END NAVIGATION - HEADER MENU -->

		<!-- NAVIGATION - TOP MENU -->
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
		<!-- END NAVIGATION - TOP MENU -->

		<!-- NAVIGATION - MAIN MENU -->
		<div class="collapse navbar-collapse navbar-ex1-collapse">
			<ul class="nav navbar-nav side-nav">
				<li ng-class="isActive('dashboard')"><a href="#/"><i class="fa fa-fw fa-dashboard"></i> Dashboard</a></li>
				<li ng-if=" !edge.child && !edge.parent " ng-class="isActive(edge.name)" ng-repeat="edge in edges">

					<!-- if no relationship -->
					<a href="#/list/{{edge.name}}">
						<i class="fa fa-fw fa-{{edge.icon}}"></i> {{edge.title}}
					</a>
					<!-- endif -->
				</li>

				<li ng-if="edge.parent" ng-repeat="edge in edges">

					<!-- if one-to-many relationship -->
					<a href="javascript:;" data-target="#menu-{{edge.name}}" data-toggle="collapse">
						<i class="fa fa-fw fa-arrow-circle-o-right"></i> {{edge.title}} <i class="fa fa-fw fa-caret-down"></i>
					</a>

					<ul id="menu-{{edge.name}}" class="nav navbar-nav side-nav-sub collapse in">
						<li ng-class="isActive(edge.parent.name)"><a href="#/list/{{edge.parent.name}}"><i class="fa fa-fw fa-{{edge.parent.icon}}"></i> {{edge.parent.title}}</a></li>
						<li ng-class="isActive(edge.name)"><a href="#/list/{{edge.name}}"><i class="fa fa-fw fa-{{edge.icon}}"></i> {{edge.title}}</a></li>
					</ul>	
					<!-- end if -->
					
				</li>

			</ul>
		</div>
		<!-- END NAVIGATION - MAIN MENU -->

	</nav>
	<!-- END NAVIGATION -->

	<!-- CONTAINER -->	
	<div id="page-wrapper">
		<div ng-view class="container-fluid"></div>
	</div>
	<!-- END CONTAINER -->	
	
</div>

<!-- JAVASCRIPT -->
<script type="text/javascript" src="assets/js/libs/jquery.min.js"></script>
<script type="text/javascript" src="assets/js/libs/bootstrap.min.js"></script>
<script type="text/javascript" src="assets/js/libs/jsoneditor.min.js"></script>
<script type="text/javascript" src="assets/js/libs/nprogress.min.js"></script>

<script type="text/javascript" src="assets/js/libs/angular.min.js"></script>
<script type="text/javascript" src="assets/js/libs/angular-route.min.js"></script>
<script type="text/javascript" src="assets/js/libs/angular-json-editor.min.js"></script>
<script type="text/javascript" src="assets/js/libs/angular-ui-bootstrap/ui-bootstrap.min.js"></script>
<script type="text/javascript" src="assets/js/libs/angular-ui-bootstrap/ui-bootstrap-tpls.min.js"></script>
<script type="text/javascript" src="assets/js/libs/sceditor/jquery.sceditor.min.js"></script>
<script type="text/javascript" src="assets/js/libs/sceditor/jquery.sceditor.pt-BR.js"></script>

<script type="text/javascript" src="assets/js/app.init.js"></script>
<script type="text/javascript" src="assets/js/app.services.js"></script>
<script type="text/javascript" src="assets/js/app.controllers.js"></script>
<!-- END JAVASCRIPT -->

</body>
</html>
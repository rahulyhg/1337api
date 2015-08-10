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
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/sb-admin.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/nprogress.css" rel="stylesheet" type="text/css"/>
	<link href="assets/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/js/sceditor/themes/default.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/custom.css" rel="stylesheet" type="text/css" />

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
			<ul class="nav navbar-nav side-nav" ng-controller="MenuController">
				<li><a href="#/"><i class="fa fa-fw fa-dashboard"></i> Dashboard</a></li>
				<li ng-repeat="bean in beans">

					<!-- if no relationship -->
					<a ng-if=" !bean.child && !bean.parent " href="#/{{bean.name}}">
						<i class="fa fa-fw fa-{{bean.icon}}"></i> {{bean.title}}
					</a>
					<!-- endif -->

					<!-- if one-to-many relationship -->
					<a ng-if="bean.parent" href="javascript:;" data-target="#menu-{{bean.name}}" data-toggle="collapse">
						<i class="fa fa-fw fa-arrow-circle-o-right"></i> {{bean.title}} <i class="fa fa-fw fa-caret-down"></i>
					</a>
					
					<ul ng-if="bean.parent" id="menu-{{bean.name}}" class="collapse in">
						<li><a href="#/{{bean.parent.name}}"><i class="fa fa-fw fa-{{bean.icon}}"></i> {{bean.parent.title}}</a></li>
						<li><a href="#/{{bean.name}}"><i class="fa fa-fw fa-{{bean.icon}}"></i> {{bean.title}}</a></li>
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
		<div class="container-fluid" ng-controller="MainController">
			<div ng-view></div>
		</div>
	</div>
	<!-- END CONTAINER -->	
	
</div>

<!-- JAVASCRIPT -->
<script type="text/javascript">
<?php 
	$edges = json_decode(file_get_contents('http://'.$_SERVER[HTTP_HOST].'/admin/api/edges'), true);
	echo 'var beans = [];'. "\n";
	foreach ($edges['beans'] as $k => $v) {
		$beans[$k] = $v['name'];
		echo 'beans.push(\''.$v['name'].'\');'. "\n";
	};
?>
</script>

<script type="text/javascript" src="assets/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/js/nprogress.js"></script>
<script type="text/javascript" src="assets/js/jsoneditor.min.js"></script>
<script type="text/javascript" src="assets/js/angular.min.js"></script>
<script type="text/javascript" src="assets/js/angular-route.min.js"></script>
<script type="text/javascript" src="assets/js/angular-json-editor.min.js"></script>
<script type="text/javascript" src="assets/js/sceditor/jquery.sceditor.min.js"></script>
<script type="text/javascript" src="assets/js/sceditor/jquery.sceditor.pt-BR.js"></script>
<script type="text/javascript" src="assets/js/bootstrap.min.js"></script>
<script type="text/javascript" src="assets/js/app.js"></script>
<!-- END JAVASCRIPT -->

</body>
</html>
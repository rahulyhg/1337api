
function LoadingMenu($scope, $http)
{
  $http.get('api/edges').success(function(data){
    console.log(data.edges);
    $scope.menus  = data.edges;
  });
}

function ListCtrl($scope, $http) {
  $http.get('api/read/items').success(function(data) {
    $scope.items = data;
  });
}

function AddCtrl($scope, $http, $location) {
  $scope.master = {};
  $scope.activePath = null;

  $scope.add_new = function(item, AddNewForm) {

    $http.post('api/add_item', item).success(function(){
      $scope.reset();
      $scope.activePath = $location.path('/');
    });

    $scope.reset = function() {
      $scope.item = angular.copy($scope.master);
    };

    $scope.reset();

  };
}

function EditCtrl($scope, $http, $location, $routeParams) {
  var id = $routeParams.id;
  $scope.activePath = null;

  $http.get('api/read/items/'+id).success(function(data) {
    $scope.items = data;
  });

  $scope.update = function(item){
    
    $http.put('api/update/items/'+id, item).success(function(data) {
      $scope.items = data;
      $scope.activePath = $location.path('/');
    });
  };

  $scope.delete = function(item) {
    console.log(item);

    var deleteitem = confirm('Are you absolutely sure you want to delete?');
    if (deleteitem) {
      $http.delete('api/items/'+item.id);
      $scope.activePath = $location.path('/');
    }
  };
}
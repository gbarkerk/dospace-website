
// Initialize your app
var myApp = new Framework7({
    animateNavBackIcon:true
});

// Export selectors engine
var $$ = Dom7;

// Add main View
var mainView = myApp.addView('.view-main', {
    // Enable dynamic Navbar
    dynamicNavbar: true,
    // Enable Dom Cache so we can use all inline pages
    domCache: true
});


var app = angular.module('DoSpaceApp',[]);

	app.controller('loginCtrl', function($scope, $http, $window) {
        //get parse user stuff...
    //$scope.memberName;
    $scope.openText = "Open";

    $scope.setLock = function(lock)
    {
    	$scope.selectedLock = lock;
    }

    $scope.logout = function()
    {
    	//myApp.goBack();
    	localStorage.removeItem('userid');
    	localStorage.removeItem('password');
    	$scope.username = "";
    	$scope.password = "";
    	myApp.loginScreen();
    }

    $scope.open = function()
    {
    	lockid = $scope.selectedLock.id;
    	myApp.showPreloader('Acquiring location...');

    	//$scope.username 
    	//$scope.password
    	if (navigator.geolocation) {
        	navigator.geolocation.getCurrentPosition(function(position){
        	console.log(position);

        	var params = {
		        userid: $scope.username,
		        password: $scope.password,
		        lat: position.coords.latitude,
		        lon: position.coords.longitude,
		        lock: lockid
		      };

		     var config = {
		        params: params,
		        timeout: 5000
		      };
		   
		myApp.hidePreloader();
    	myApp.showPreloader('Opening Door...');
    	$http.get('//dospace.ie/api/v1/index.php/open', config).
		  then(function(response) {
		   	console.log(response); 
		   	if(response.data.code != 0)
		   	{
		   		myApp.alert("Error "+ response.data.code + ": " + response.data.message, "DoSpace");
		   	} else {
		   		myApp.alert("The door has been opened.", "DoSpace");
		   	}
		   	//$scope.openText = "Open";
		   	myApp.hidePreloader();
		}, function(response) {
			myApp.alert("The request failed: Code "+response.status,"DoSpace");
			console.log(response);
			myApp.hidePreloader();
    // called asynchronously if an error occurs
    // or server returns response with an error status.
  			});
        		

        		

        });
        }
    }


    $scope.login = function()
    {
    	myApp.showPreloader('Logging in...');
    	//myApp.closeModal();
    	//alert($scope.username+$scope.password);

		var params = {
		        userid: $scope.username,
		        password: $scope.password
		      };

		      var config = {
		        params: params
		      };

    	$http.get('//dospace.ie/api/v1/index.php/authenticate', config).
		  then(function(response) {
		    // this callback will be called asynchronously
		    // when the response is available
		    $scope.memberDetails = response.data['member_details'][0];
		    $scope.lockDetails = response.data['lock_details']
		    
		    if($scope.memberDetails){
		    	$scope.memberName = $scope.memberDetails['first_name'] + " " + $scope.memberDetails['surname'];
		    	myApp.closeModal();
		    	localStorage['userid'] = $scope.username;
		    	localStorage['password'] = $scope.password;
		    	//get the default lock description...
		    	for(var lock in $scope.lockDetails)
		    	{
		    		if($scope.lockDetails[lock].id == $scope.memberDetails.default_space)
		    		{
		    			$scope.selectedLock = $scope.lockDetails[lock];
		    			$scope.memberDetails.default_space = $scope.lockDetails[lock].description;

		    		}
		    	}
		    	
			} else {
				myApp.alert('Incorrect username and/or password.','DoSpace');
				$scope.password = "";
				//localStorage['userid'] = $scope.username;
		    	localStorage.removeItem('password');
			}
			myApp.hidePreloader();
		    console.log(response);

		  }, function(response) {
		    // called asynchronously if an error occurs
		    // or server returns response with an error status.
		    alert(response);
		  });
    	//alert($scope.username + " " + $scope.password);
    }


    if(localStorage['userid'] && localStorage['password'])
    {
    	//alert("Have user details!");
    	$scope.username = localStorage['userid'];
    	$scope.password = localStorage['password'];
    	$scope.login();
    } 

    });
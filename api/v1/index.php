<?php

require '../vendor/autoload.php'; //php composer include
include 'utilities.php';

define("OPENING",0);
define("AUTH_ERROR", 100);
define("DISTANCE_ERROR", 200);
define("MEMBERSHIP_ERROR", 300);


date_default_timezone_set('Europe/Dublin');

header('Content-Type: application/json');

//	Create new Slim App
$app = new \Slim\Slim(array('debug' => true));

//	Test route /hello/
$app->get('/hello/:name', function ($name) use ($app) {
    echo "Hello, $name";
$paramValue = $app->request->get('test');
    echo $paramValue;
});

// Open Lock Route
$app->get('/open',function() use ($app) {

	$userid = $app->request->get('userid');
	$password = $app->request->get('password');
	$lock = $app->request->get('lock');
	$userlat = $app->request->get('lat');
	$userlon = $app->request->get('lon');

	//authenticate user details
	$results = array();
	$return = array();

	$conn = getDatabaseConnection();

	$can_open_door = false;

	if($stmt = $conn->prepare("SELECT contract_renewal,active_member, default_space FROM members WHERE userid=? AND password=?"))
	{
		$stmt->bind_param("ss",$userid,$password);	
		$stmt->execute(); 

		$rows = fetch($stmt);
		
		$results['member_details'] = $rows;

		if($results['member_details'])
		{
			if(time() < strtotime($results['member_details'][0]['contract_renewal']))
			{
				$can_open_door=true;
			}
		}
	}

	
	if($results['member_details'])
	{ // user is authenticated
		if($stmt = $conn->prepare("SELECT * FROM locks WHERE id=?"))
		{
			$stmt->bind_param("s", $lock);
			$stmt->execute(); 

			$rows = fetch($stmt);
			
			$results['lock_details'] = $rows;
			//echo json_encode($results);	
			if($results['lock_details'])
			{ // the lock was found
				//check to see if the user is close enough to the 
				$distance = distance($userlat, $userlon, $results['lock_details'][0]['latitude'], $results['lock_details'][0]['longitude'], "K");
				if($distance < 200) //change distance variable
				{	
					if($can_open_door)
					{
						/* Create new queue object */
						$queue = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_REQ);

						/* Connect to an endpoint */
						$queue->connect("tcp://localhost:5556");

						/* send and receive */
						$queue->send($results['lock_details'][0]['id']);

						$return['message'] = $queue->recv();
						$return['code'] = OPENING;

						//$queue->disconnect("tcp://localhost:5556");

					} else {
						$return['message'] = "Your membership has expired.";
						$return['code'] = MEMBERSHIP_ERROR;
					}

				} else
				{
					$return['message'] = "You are too far away from the lock.";
					$return['code'] = DISTANCE_ERROR;

				}
			} else {
				
				$return['message'] = "Authentication Error.";
				$return['code'] = AUTH_ERROR;

			}


		}
	} else {
		$return['message'] = "Authentication Error.";
		$return['code'] = AUTH_ERROR;
	}

	echo json_encode($return);
});

//	Authentication Route
$app->get('/authenticate',function() use ($app) {
	$userid = $app->request->get('userid');
	$password = $app->request->get('password');

	//connect to database server


	$result = array();

	//mysqli_report(MYSQLI_REPORT_ALL);
	$conn = getDatabaseConnection();

	if($stmt = $conn->prepare("SELECT first_name, surname, userid, company, email_address, membership_type, contract_renewal,active_member, default_space, slackid  FROM members WHERE userid=? AND password=?"))
	{
		$stmt->bind_param("ss",$userid,$password);	
		$stmt->execute(); 

		$rows = fetch($stmt);
		
		$results['member_details'] = $rows;
	}

	if($results['member_details'])
	{

		if($stmt = $conn->prepare("SELECT * FROM locks"))
		{
		$stmt->execute(); 

		$rows = fetch($stmt);
		
		$results['lock_details'] = $rows;
		//echo json_encode($rows);	

		}
	}
	$results['request_uri'] = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo json_encode($results);
	//now fetch all the locks in the database...

	$conn->close();
});


$app->run();

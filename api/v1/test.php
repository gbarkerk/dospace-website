<?php

						$queue = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_REQ);

						/* Connect to an endpoint */
						$queue->connect("tcp://localhost:5556");

						/* send and receive */
						$queue->send("lock1");

						echo $queue->recv();
						
						//$queue->disconnect("tcp://localhost:5556");

?>
<?php

/**
*
* PHP script to Manage Updates on the Cachet Status Page by Hammed Olalekan Osanyinpeju aka Successtar
*
* Updated Version and documentation of this available at https:\/\/github.com/successtar/cachet-manager
*
* Suggestions, corrections and critics are highly welcome.
*
* For further enquiries, you can reach me on 2347061855688 or osanyinpejuhammed35@gmail.com.
*
* Thanks... 
*
**/


/**
*
************************************************************
************************************************************
*
* CACHET UPDATE MANAGER CLASS
*
************************************************************
************************************************************
*
**/

class UpdateManager{
	
	/* Variables needed for most transactions in this class */
	
	private $access_token = "TYPE YOUR ACCESS TOKEN HERE";		//set access token
	
	private $myhost = "https://myStatusPageDomain.com/";	//status page base url, replace myStatusPageDomain.com with your page domain name
	
	public function compoList(){
		
		/*Load all components data from the status page via the curl req function in this class */
		
		$req = $this -> curl_req("GET", "api/v1/components"); 
		
		/* Check if the transaction was successfull and pass the data else return false */
		
		return ($req[1]['http_code'] === 200) ? $req[0] : false;		
	}


	public function metricList(){
		
		/* Load all Metrics data from the status page via the curl req function in this class */
		
		$req = $this -> curl_req("GET", "api/v1/metrics"); 
		
		/*  Check if the transaction was successfull and pass the data else return false */
		
		return ($req[1]['http_code'] === 200) ? $req[0] : false;
	}

	public function getMetricId($metric_name, $metrics, $suffix, $desc){
		
		/* Initialize Id as empty */
		
		$id = "";
		
		/* Loop through all the metrics passed into this function */
		
		foreach ($metrics as $metric) {
			
			/* If metric Name match the expected, get the Id of the metric and break the loop */
			
			if ($metric['name'] === $metric_name){
			
				$id = $metric['id'];
	
				break;
			}
		}

		/* Check if Id value is set from the previous block. If not enter here to create the metric */
		
		if ($id == ""){
			
			/* Set default value for Uptime as 100 while for Response as 0 */
			
			$default_value = ($suffix === "%") ? 100 : 0;
			
			/* Create a new metric with all the information passed into this function */
			
			$new_metric = $this -> curl_req("POST", "api/v1/metrics", array('name' => $metric_name, 'suffix' => $suffix, 'description' => $desc, 'default_value' => $default_value, 'display_chart' => 1 ));
			
			/*If Metric was created successfully enter here to obtain the new metric id */
			
			if ($new_metric[0]['data']['name'] == $metric_name & $new_metric[1]['http_code'] === 200){
				
				/* Load updated Metrics */
				
				$updated_metrics = $this -> metricList();
				
				/* If metrics was loaded successfully get the new metric id here */
				
				if ($updated_metrics != false){
					
					$arr_id = count($updated_metrics['data']) - 1;
					
					$id = $updated_metrics['data'][$arr_id]['id'];
				}
			}
		}

		return $id;
	}

	public function pingUrl($url){
		
		/* Filter url leaving the host name for pinging */

		$url = str_replace("https://", "", str_replace("http://", "", str_replace("www.", "", $url)));

		$url_host = explode("/", $url);

		/* Ping the host name and set the time before and after pinging to measure load time */

		$startTime = microtime(true) * 1000;

		exec("ping -c 1  ".$url_host[0], $out);

		$stopTime = microtime(true) * 1000;

		/* If pinging is successful return the time taken for the transaction else return false */

		if ($out && count($out) >= 6){

			return ($stopTime - $startTime);
		}
		else{
			return false;
		}

	}

	public function curl_req($method, $path, $data=null){
		
		/* Full Url for the request */
		
		$url = $this -> myhost.$path;
		
		/* Curl options for request sent from from this class */
		
		$options = array(

					CURLOPT_FOLLOWLOCATION => true,   // follow redirects
					
					CURLOPT_RETURNTRANSFER => 1,	  //Return data
					
					CURLOPT_MAXREDIRS      => 2,     // stop after 2 redirects
					
					CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
					
					CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
				
					CURLOPT_TIMEOUT        => 120,    // time-out on response
				); 
		
		$ch = curl_init($url);
		
		curl_setopt_array($ch, $options);
    	
   		/* More curl options base on the Method used for the request with GET method being the default */
		
		if ($method == "POST") {
		
			curl_setopt($ch, CURLOPT_POST, 1 ); 	//post request
		
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));	//data to be sent to server in JSON format
		
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Cachet-Token: '.$this -> access_token));	//Access token and content type sent via the http header
		}
	    	elseif ($method == "PUT") {
    		
			curl_setopt($ch, CURLOPT_POST, 1 ); 
	   		
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));	//data to be sent to server in JSON format
   			
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Cachet-Token: '.$this -> access_token));  	//Access token and content type sent via the http header  	
   			
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");				//Custom request set to PUT
   		}
	    	elseif ($method == "DELETE") {
    	
			curl_setopt($ch, CURLOPT_POST, 1 );  	//post request
	   		
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));	//data to be sent to server in JSON format
        		
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Cachet-Token: '.$this -> access_token));  	//Access token and content type sent via the http header
	        	
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");			//Custom request set to DELETE
    		}
    		
		/* Execute request and return the response with transaction informations */
		
    		$content  = json_decode(curl_exec($ch), true);
   		
		$info = curl_getinfo($ch);
   		
   		curl_close($ch);

		return array($content, $info);
	}
}


/**
*
************************************************************
************************************************************
*
* CACHET UPDATE MANAGER EXECUTION STARTS HERE
*
************************************************************
************************************************************
*
**/


	/* New Instance of the Update Manager class */

	$task = new UpdateManager();

	/* Load all components and metrics */
	
	$loadCompo = $task -> compoList();
	
	$loadMetrics = $task -> metricList();
	
	$timer = strtotime(date("Y-m-d H:i:00")); 

	if ($loadCompo === false | $loadMetrics === false){
		
		/* No connection to Status Page */
		
		die("Unable to connect to Status Page..".PHP_EOL);
	}
	else{
		foreach ($loadCompo['data'] as $compo) {
			
			/* Validate component Url */
			
			if (filter_var($compo['link'], FILTER_VALIDATE_URL)){
				
				/* Ping Url */
				
				$checkServer = $task -> pingUrl($compo['link']);
				
				/* If false is return the component server is down, update status page if not updated yet */
				
				if ($checkServer === false){
				
					if ($compo['status'] != 4){
						
						/* Update component status if it is not at Major outage */
						
						$task -> curl_req("PUT", "api/v1/components/".$compo['id'], array('status' => 4));		
					}
					
					/* Update Metrics for App and Services group */
					
					if ($compo['group_id'] === 2 | $compo['group_id'] === 3){				
						
						/* Get component Response Time metric Id */
						
						$resp_id = $task -> getMetricId($compo['name']." Response Time", $loadMetrics['data'], "ms", "This Server Response time in millisecond");
						
						if ($resp_id > 0){
							
							/* Update Response Time metric data for the component */
							
							$task -> curl_req("POST", "api/v1/metrics/".$resp_id."/points", array("value" => 0, "timestamp" => $timer ));
						}
						
						/* Get component Uptime metric Id */
						
						$uptime_id = $task -> getMetricId($compo['name']." Uptime", $loadMetrics['data'], "%", "Percentage of this server availability");

						if ($uptime_id > 0){
							
							/* Update Uptime metric data for the component */
							
							$task -> curl_req("POST", "api/v1/metrics/".$uptime_id."/points", array("value" => 0, "timestamp" => $timer ));
						}
					}
				}
				else {
					if ($compo['status'] != 1){
						
						/* Update component status if it is not at operational */
						
						$task -> curl_req("PUT", "api/v1/components/".$compo['id'], array('status' => 1));
					}
					
					/* Update Metrics for App, Services and other groups with group Id of 2 and above */
					
					if ($compo['group_id'] >= 2){
						
						/* Get component Response Time metric Id */
						
						$resp_id = $task -> getMetricId($compo['name']." Response Time", $loadMetrics['data'], "ms", "This Server Response time in millisecond");
						
						if ($resp_id > 0){
							
							/* Update Response Time metric data for the component */
							
							$task -> curl_req("POST", "api/v1/metrics/".$resp_id."/points", array("value" => $checkServer, "timestamp" => $timer ));
						}
						
						/* Get component Uptime metric Id */
						
						$uptime_id = $task -> getMetricId($compo['name']." Uptime", $loadMetrics['data'], "%", "Percentage of this server availability");

						if ($uptime_id > 0){
							
							/* Update Uptime metric data for the component */
							
							$task -> curl_req("POST", "api/v1/metrics/".$uptime_id."/points", array("value" => 100, "timestamp" => $timer ));
						}
					}
				}

			} 

		
		}

	} 


?>

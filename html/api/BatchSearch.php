<?php
	header('Access-Control-Allow-Origin: *');
	header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
	$inData = getRequestInfo();
	
	$searchResults = "";
	$searchCount = 0;

	$conn = new mysqli("localhost", "groupseventeen", "Group17Grapefruit", "CONTACTS"); 	
	if ($conn->connect_error) 
	{
		returnWithError( $conn->connect_error );
	} 
	else
	{
		$escaped_name = "%" . trim($conn->real_escape_string($inData["name"])) . "%";
		$escaped_userId = trim($conn->real_escape_string($inData["userId"]));
		$escaped_batchSize = trim($conn->real_escape_string($inData["batchSize"]));
		$escaped_batchStart = trim($conn->real_escape_string($inData["batchStart"]));

		$stmt = $conn->prepare("SELECT * FROM Information WHERE CONCAT(FirstName,  ' ', LastName) LIKE ? AND UserId = ? LIMIT ? OFFSET ?");
		$stmt->bind_param("ssss", $escaped_name, $escaped_userId, $escaped_batchSize, $escaped_batchStart);
		$stmt->execute();
		
		$result = $stmt->get_result();
		$rowCount = $result->num_rows;

		while($row = $result->fetch_assoc())
		{
			if( $searchCount > 0 )
			{
				$searchResults .= ",";
			}
			$searchCount++;
			$searchResults .= '"' . $row["ID"] . '":{"firstName":"' . $row["FirstName"] .'","lastName":"' . $row["LastName"] . '","email":"' . $row["Email"] . '","phone":"' . $row["Phone"] . '","profilePic":"'. $row["ProfilePicture"].'","dateCreated":"' . $row["DateCreated"] . '"}';
		}
		
		if( $searchCount == 0 )
		{
			returnWithError( "No Records Found" );
		}
		else
		{
			returnWithInfo( $searchResults, $rowCount);
		}
		
		$stmt->close();
		$conn->close();
	}

	function getRequestInfo()
	{
		return json_decode(file_get_contents('php://input'), true);
	}

	function sendResultInfoAsJson( $obj )
	{
		header('Content-type: application/json');
		echo $obj;
	}
	
	function returnWithError( $err )
	{
		$retValue = '{"error":"' . $err . '", "count":"0"}';
		sendResultInfoAsJson( $retValue );
	}
	
	function returnWithInfo( $searchResults, $rowCount)
	{
		$retValue = '{"results":{' . $searchResults . '},"error":"", "count":"'.$rowCount.'"}';
		sendResultInfoAsJson( $retValue );
	}
	
?>
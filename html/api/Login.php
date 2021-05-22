<?php
# Request json should have 'login' and 'password'. 
# 'password' is assumed to have been hashed on the client side with md5. 

# Response json example: 
# {"id":0, "firstName":"", "lastName":"", "error":"", "lastLogin":"0000-00-00 00:00:00"}

# Returns an associative array from the incoming json (sourced from the php input file stream)
function getRequestInfo()
{
    return json_decode(file_get_contents('php://input'), true);
}

$inData = getRequestInfo();

# The inData array has the wrong number of elements, or the required 'login' and 'password' are missing
if ((count($inData) != 2) || (!isset($inData["login"]) || !isset($inData["password"])))
{
    http_response_code(400);
    returnWithError("Bad Login Request");
}

$id = 0;
$firstName = "";
$lastName = "";
$lastLogin = "";
$err = "";

$conn = new mysqli("localhost", "groupseventeen", "Group17Grapefruit", "CONTACTS"); 	
if( $conn->connect_error )
{
    returnWithError( $conn->connect_error );
}
else
{
    # Prevent SQL injection, also trim whitespace.
    $escaped_login = trim($conn->real_escape_string($inData["login"]));
    $escaped_password = trim($conn->real_escape_string($inData["password"]));

    $stmt = $conn->prepare("SELECT ID, FirstName, LastName, DateLastLoggedIn, Password FROM Users WHERE Login=?");
    $stmt->bind_param("s", $escaped_login);
    $stmt->execute();
    $result = $stmt->get_result();

    # Did a User exist with that login?
    if($row = $result->fetch_assoc())
    {
        $stmt->close();
        $conn->close();

        # Register will use an additional hash (php's built in bcrypt hash) and this compares
        # the hashed password from the DB, to the incoming hash. Passwords are hashed
        # with md5 client side, and then bcrypt on the server side.
        if (password_verify($escaped_password, $row['Password']))
        {
            returnWithInfo( $row['FirstName'], $row['LastName'], $row['ID'], $row['DateLastLoggedIn'] );
        }
        else
        {
            returnWithError("Invalid Password");
        }
    }
    else
    {
        $stmt->close();
        $conn->close();
        returnWithError("Invalid Username");
    }

    
}

function sendResultInfoAsJson( $obj )
{
    header('Content-type: application/json');
    echo $obj;
    http_response_code(200);
    die();
}

function returnWithError( $err )
{
    $retValue = '{"id":0,"firstName":"","lastName":"","error":"' . $err . '","lastLogin":"0000-00-00 00:00:00"}';
    sendResultInfoAsJson( $retValue );
    
}

function returnWithInfo( $firstName, $lastName, $id, $lastLogin)
{
    $retValue = '{"id":' . $id . ',"firstName":"' . $firstName . '","lastName":"' . $lastName . '","error":"","lastLogin":"'.$lastLogin.'"}';
    sendResultInfoAsJson( $retValue );
}

?>

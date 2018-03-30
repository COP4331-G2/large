<?php

// Add file with connection-related functions
require 'Connection.php';

// Crete a session for the username
session_start();

// Receive decoded JSON payload from client
$jsonPayload = getJSONPayload();

// Establish a connection to the database
$dbConnection = establishConnection();

// Call the client-requested function
callVariableFunction($dbConnection, $jsonPayload);

// Folder were all images will be save.
$destinationFolder = "uploads/";

/* *************** */
/* Functions Below */
/* *************** */

/**
 * Call a variable function passed as a string from the client-side
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function callVariableFunction($dbConnection, $jsonPayload)
{
    // Get function name (as string) from the JSON payload
    $function = $jsonPayload['function'];

    // Ensure that the function exists and is callable
    if (is_callable($function)) {
        // Use the JSON payload 'function' string field to call a PHP function
        $function($dbConnection, $jsonPayload);
    } else {
        // If the function is not callable, return a JSON error response
        returnError('JSON payload tried to call undefined PHP function ' . $function . '()');
    }
}

/**
 * Verify username/password information and (perhaps) login to a user's account
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function loginAttempt($dbConnection, $jsonPayload)
{
    // Get the username and password from the JSON payload
    $username = trim($jsonPayload['username']);
    $password = $jsonPayload['password'];

    // This block uses prepared statements and parameterized queries to protect against SQL injection
    // MySQL query to check if the username exists in the database
    $query = $dbConnection->prepare("SELECT * FROM Users WHERE username = ?");
    $query->bind_param('s', $username);
    $query->execute();

    // Result from the query
    $result = $query->get_result();

    // Verify if the username exists
    if ($result->num_rows > 0) {
        // If the username exists...
        // Get the other coloumn information for the user account
        $row = $result->fetch_assoc();

        // Verify if the password is correct
        if (password_verify($password, $row['password'])) {
            // If the password is correct...
            // Return the JSON success response (including user's id)
            $_SESSION['id'] = $row['id'];
            returnSuccess('Login successful.', $_SESSION['id']);
        } else {
            // If the password isn't correct...
            // Return a JSON error response
            returnError('Password incorrect.');
        }
    } else {
        // If the username doesn't exist...
        // Return a JSON error response
        returnError('Username not found.');
    }
}

/**
 * Create a new user account
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function createUser($dbConnection, $jsonPayload)
{
    // Get the username and password from the JSON payload
    $username = trim($jsonPayload['username']);
    $password = $jsonPayload['password'];
    $firstName = trim($jsonPayload['firstName']);
    $lastName = trim($jsonPayload['lastName']);
    $emailAddress = trim($jsonPayload['emailAddress']);
    $isGroup = 0;
    // Check for various error-inducing situations
    if (strlen($username) > 60) {
        returnError('Username cannot exceed 60 characters.');
    } else if (strlen($username) <= 0) {
        returnError('Username cannot be empty.');
    } else if (strlen($password) <= 0) {
        returnError('Password cannot be empty.');
    } else if (strlen($firstName) > 60) {
        returnError('First name cannot exceed 60 characters.');
    } else if (strlen($firstName) <= 0) {
            returnError('First name cannot be empty.');
    } else if (strlen($lastName) > 60) {
      returnError('Last name cannot exceed 60 characters.');
    } else if (strlen($lastName) <= 0) {
          returnError('Last name cannot be empty.');
    } else if (strlen($emailAddress) > 60) {
          returnError('Email address cannot exceed 60 characters.');
    } else if (strlen($emailAddress) <= 0) {
              returnError('Email address cannot be empty.');
    }else {
        // This block uses prepared statements and parameterized queries to protect against SQL injection
        // MySQL query to check if a username already exists in the database
        $query = $dbConnection->prepare("SELECT * FROM Users WHERE username=?");
        $query->bind_param('s', $username);
        $query->execute();

        // Result from the query
        $result = $query->get_result();

        // If a username already exists...
        // Return a JSON error response
        if ($result->num_rows > 0) {
            returnError('Username already exists.');
        }

        $query->close();

        // Encrypt the password (using PHP defaults)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // This block uses prepared statements and parameterized queries to protect against SQL injection
        // MySQL query to add the username and password into the database
        $query = $dbConnection->prepare("INSERT INTO Users (username, password, firstName, lastName, emailAddress, isGroup) VALUES (?, ?, ?, ?, ?, ?)");
        $query->bind_param('sssssi', $username, $hashedPassword, $firstName, $lastName, $emailAddress, $isGroup);
        $query->execute();

        // Result from the query
        $result = mysqli_affected_rows($dbConnection);

        // Check to see if the insertion was successful...
        if ($result) {
          $_SESSION['id'] = $row['id'];
            // If successful, return JSON success response
            $query->close();
            returnSuccess('User created.');
        } else {
            // If not successful, return JSON error response
            $query->close();
            returnError('User not created: ' . $dbConnection->error);
        }
    }
}

/**
 * Delete a user account (and all associated contacts)
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function deleteUser($dbConnection, $jsonPayload)
{
    /* Not yet implemented */

    // Will need to get the user's id
    // Then iterate through all contacts and delete them (via deleteContact())
    // Then delete the user itself

}

/*
  Logout function
*/
function logout()
{
  session_destroy();
  if($_SESSION==null)
  returnSuccess('User has logout');
}

/** Verify if passwords match
  *Checks if unsername exist
  *
 */

function authentication()
{
  //database call to check if the username exists
 // checks paasword input from both fields (could this be done on the frontend?)
}

/** Machine Learning function
  *
 */
function autoTag($dbConnection, $jsonPayload)
{
  // implement machine learning algorithm
  // separate machine learning from create post? as a stand alone funct?
  // image to text
  //text to tags



}

/*
  function utilized to create postings in feed
*/

function createPost($dbConnection, $jsonPayload)
{
  //call autoTag function
  //implement posting capabilities
  $file = $_FILES['file'];

  $image = uploadImageHelper($file);

  // get tag array. Insert in the database
  // the image name and the tags associate to the image.
  // after run the line below to save the image.

  // Get from JSON: userID, body text,  image URL
  $userID = $_SESSION['id'];
  $bodyText = trim($jsonPayload['bodyText']);
  $imageName = trim($image);

  // Add post to the database
  $query = $dbConnection->prepare("INSERT INTO Posts (userID, bodyText, imageName) VALUES (?, ?, ?)");
  $query->bind_param('iss', $userID, $bodyText, $imageName);
  $query->execute();

  // Result from the query
  $result = $query->get_result();

  // Check to see if the insertion was successful...
  if ($result) {
    $postTags = $dbConnection->insert_id;
    move_uploaded_file($_FILES['file']['tmp_name'], $destinationFolder.$image);
    createPostTags($dbConnection, $jsonPayload, $postID);
  // If successful, return JSON success response
  returnSuccess('Post created.');
  
  } else {
  // If not successful, return JSON error response
  returnError($dbConnection->error);
  }


  // We don't need a relational table for posts and users


  // Call image tagger and populate relational table: Posts_Tags

}
function createPostTags($dbConnection, $jsonPayload, $postID){

  $postTags = $jsonPayload['tag'];
  $len = count($postTags);
  $i = 0;

    do{
      $id = getTagIDfromTagsTable($dbConnection, $postTags[i]);
      if($id !== -1){
        $query = $dbConnection->prepare("INSERT INTO Posts_Tags (postID, tagID) VALUES (?, ?)");
        $query->bind_param('ii', $postID, $id);
        $query->execute();
        $result = $query->get_result();
        if(!$result){
          returnError('There was an error in creating the post tag(s).');
        }
      }
      // Tag doesnt exist. Insert to tags table
      else{
        $query = $dbConnection->prepare("INSERT INTO Tags (name) VALUES (?)");
        $query->bind_param('s', $postTags[i]);
        $query->execute();
        $result = $query->get_result();
        if($result){
          $tagID = $dbConnection->insert_id;

          $query = $dbConnection->prepare("INSERT INTO Posts_Tags (postID, tagID) VALUES (?, ?)");
          $query->bind_param('ii', $postID, $id);
          $query->execute();
          $result = $query->get_result();

          if(!$result){
            returnError('There was an error in creating the post tag(s).');
          }

        }
      }

    }while($i<$len);

    returnSuccess('Post tag(s) were successfuly created.');

}


 /** Search by tags function
   *
  */
  function tagSearchTable($dbConnection, $tag)
  {
    //implement search logic
    /*
      4 different searches (similar calls, all within same function?):
        personalized (Favorites)
        latest
        groups
        my post
    */


    // Check to see if the tag name pass exists in the Tags table. return 1 = true or 0 =f alse
    $query = $dbConnection->prepare("SELECT name FROM Tags WHERE name = ?");
    $query->bind_param('s', $tag);
    $query->execute();
    $result =  $query->get_result();

    return $result->num_rows > 0 ? 1 : 0;
  }

  // Returns the Tag id for a Tag in the Tags table.
  function getTagIDfromTagsTable($dbConnection, $tag)
  {

    if(tagSearchTable($dbConnection, $tag))
    {
      $query = $dbConnection->prepare("SELECT id FROM Tags WHERE name = ?");
      $query->bind_param('s', $tag);
      $query->execute();
      $result =  $query->get_result();

      $row = $result->fetch_assoc();

      return $row['id'];
    }
    // no id exists for that tag.
    else{
      return -1;
    }
  }

/*
 *  Settings function
*/
function settings($dbConnection, $jsonPayload)
{
  // implement connections for settings


}

/*
 *   Upload image helper function
 */
function uploadImageHelper($image)
{
  // Gets the image, make sure the upload was successful,
  // checks the extension and rename it with an unique name.
  // Then it returns the new name.
  $extAllow = array('jpg', 'jpeg', 'png', 'gif');
  $ext = strtolower(end(explode('.', $image['file']['name'])));

  if(in_array($ext, $extAllow))
  {
    if($image['file']['error'] === 0)
    {
      return $imageNewName = uniqid('', true).".".$ext;
    }
    else
    {
      returnError('An error occur while uploading the image.');
    }
  }
  else
  {
    returnError('Image extension is not allow.');
  }

}

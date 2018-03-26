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
        $result = $query->get_result();

        // Check to see if the insertion was successful...
        if ($result) {
            // If successful, return JSON success response
            $query->close();
            returnSuccess('User created.');
        } else {
            // If not successful, return JSON error response
            $query->close();
            returnError($dbConnection->error);
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
{}

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
  // Get from JSON: userID, body text,  image URL
  $userID = $jsonPayload['userID'];
  $bodyText = trim($jsonPayload['bodyText']);
  $imageURL = trim($jsonPayload['imageURL']);

  // Add post to the database
  $query = $dbConnection->prepare("INSERT INTO Posts (userID, bodyText, imageName) VALUES (?, ?, ?)");
  $query->bind_param('iss', $userID, $bodyText, $imageURL);
  $query->execute();

  // Result from the query
  $result = $query->get_result();

  // Check to see if the insertion was successful...
  if ($result) {
  // If successful, return JSON success response
  returnSuccess('Post created.');
  } else {
  // If not successful, return JSON error response
  returnError($dbConnection->error);
  }


  // We don't need a relational table for posts and users


  // Call image tagger and populate relational table: Posts_Tags

}

 /** Search by tags function
   *
  */
function tagSearch($dbConnection, $jsonPayload)
{
  //implement search logic
  /*
    4 different searches (similar calls, all within same function?):
      personalized (Favorites)
      latest
      groups
      my post
  */
}

/*
 *  Settings function
*/
function settings($dbConnection, $jsonPayload)
{
  // implement connections for settings


}

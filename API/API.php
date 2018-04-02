<?php

// Add file with connection-related functions
require 'Connection.php';

// Crete a session for the username
session_start();

// Receive decoded JSON payload from client
$jsonPayload = getJSONPayload();

// Establish a connection to the database
$dbConnection = establishConnection();

// White list of API callable functions
$functionWhiteList = [
    'loginAttempt',
    'createUser',
    'getPostsLatest',

    // REMOVE BEFORE DEPLOY
    'testUpload',
];

// Call the client-requested function
callVariableFunction($dbConnection, $jsonPayload, $functionWhiteList);

/* *************** */
/* Functions Below */
/* *************** */

/**
 * Call a variable function passed as a string from the client-side
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function callVariableFunction($dbConnection, $jsonPayload, $functionWhiteList)
{
    // Get function name (as string) from the JSON payload
    $function = $jsonPayload['function'];

    // Ensure that the function is in the white list (and use strict)
    $funcIndex = array_search($function, $functionWhiteList, true);

    // Use the functionWhiteList version, not the user-supplied version
    // This is for security reasons
    if ($funcIndex !== false && $funcIndex !== null) {
        $function = $functionWhiteList[$funcIndex];
    } else {
        // If the function is not part of the white list, return a JSON error response
        returnError('JSON payload tried to call non-white list PHP function ' . $function . '()');
    }

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
    $username = strtolower(trim($jsonPayload['username']));
    $password = trim($jsonPayload['password']);

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
    // Get the new account information from the JSON payload
    $username     = strtolower(trim($jsonPayload['username']));
    $password     = trim($jsonPayload['password']);
    $firstName    = trim($jsonPayload['firstName']);
    $lastName     = trim($jsonPayload['lastName']);
    $emailAddress = trim($jsonPayload['emailAddress']);
    $isGroup      = 0;

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
    }

    // This block uses prepared statements and parameterized queries to protect against SQL injection
    // MySQL query to check if a username already exists in the database
    $query = $dbConnection->prepare("SELECT * FROM Users WHERE username = ?");
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
        $query->close();
        // If successful, return JSON success response
        returnSuccess('User created.');
    } else {
        $query->close();
        // If not successful, return JSON error response
        returnError('User not created: ' . $dbConnection->error);
    }
}

/**
 * Create a post from a user
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function createPost($dbConnection, $jsonPayload)
{
    $userID   = $jsonPayload['userID'];
    $bodyText = trim($jsonPayload['bodyText']);
    $imageURL = trim($jsonPayload['imageURL']);

    // TODO: This should call a function to create tags

    // Add post to the database
    $query = $dbConnection->prepare("INSERT INTO Posts (userID, bodyText, imageURL) VALUES (?, ?, ?)");
    $query->bind_param('iss', $userID, $bodyText, $imageURL);
    $query->execute();

    $result = mysqli_affected_rows($dbConnection);

    // Check to see if the insertion was successful...
    if ($result) {
        // If successful, return JSON success response
        returnSuccess('Post created.');
    } else {
        // If not successful, return JSON error response
        returnError('Post not created: ' . $dbConnection->error);
    }
}

/**
 * Create a post from a user
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function getPostsLatest($dbConnection, $jsonPayload)
{
    $numberOfPosts = $jsonPayload['numberOfPosts'];

    $query = $dbConnection->prepare("SELECT * FROM Posts ORDER BY id DESC LIMIT ?;");
    $query->bind_param('i', $numberOfPosts);
    $query->execute();

    $result = $query->get_result();

    // Verify posts were found
    if ($result->num_rows <= 0) {
        returnError('No posts found: ' . $dbConnection->error);
    }

    $postResults = [];

    while ($row = $result->fetch_assoc()) {
        $postInformation = [
            'postID'   => $row['id'],
            'userID'   => $row['userID'],
            'bodyText' => $row['bodyText'],
            'imageURL' => $row['imageURL'],
        ];

        $postResults[] = $postInformation;
    }

    returnSuccess('Posts found.', $postResults);
}

// DANGEROUS: ONLY FOR TESTING PURPOSES
// REMOVE BEFORE DEPLOY
// Make sure to add "return;" in returnSuccess()
// And remove first "die;" statement in this function
function testUpload($dbConnection, $jsonPayload)
{
    die;

    for ($i = 1; $i <= 545; $i++) {
        $imageURL = "http://res.cloudinary.com/cop4331g2/image/upload/v1522708347/image" . $i . ".jpg";

        $jsonPayload = [
            'imageURL' => $imageURL,
            'userID' => 3,
            'bodyText' => "Testing...1...2...3...",
        ];

        createPost($dbConnection, $jsonPayload);
    }

    die;
}

// TODO: getPostsPersonal()
// TODO: getPostsGroups()
// TODO: likePost()
// TODO: getPostByID()
// TODO: suggestTags()

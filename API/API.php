<?php

// Add file with connection-related functions
require 'Connection.php';

// Establish a connection to the database
$dbConnection = establishConnection();

// Receive decoded JSON payload from client
$jsonPayload = getJSONPayload();

// White list of API-callable functions
$functionWhiteList = [
    'loginAttempt',
    'createUser',
    'getPostsLatest',
    'createPost',
    'likePost',
    'unlikePost',
    'getPost',

    // TODO: REMOVE BEFORE DEPLOY
    'testUpload',
];

// Call the client-requested function
callVariableFunction($dbConnection, $jsonPayload, $functionWhiteList);

/* *************** */
/* Endpoints Below */
/* *************** */

/**
 * Verify username/password information and (perhaps) login to a user's account
 *
 * @json Payload : function, username, password
 * @json Response: userID, username
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function loginAttempt($dbConnection, $jsonPayload)
{
    // Get the username and password from the JSON payload
    // Always store usernames in lowercase
    $username = strtolower(trim($jsonPayload['username']));
    $password = trim($jsonPayload['password']);

    checkForEmptyProperties([$username, $password]);

    // This block uses prepared statements and parameterized queries to protect against SQL injection
    // MySQL query to check if the username exists in the database
    $statement = "SELECT * FROM Users WHERE username = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('s', $username);
    $query->execute();

    // Result from the query
    $result = $query->get_result();

    $query->close();

    // Verify if the username exists
    if ($result->num_rows > 0) {
        // If the username exists...
        // Get the other coloumn information for the user account
        $row = $result->fetch_assoc();

        // Verify if the password is correct
        if (password_verify($password, $row['password'])) {
            $userInfo = [];
            $userInfo['userID'] = $row['id'];
            $userInfo['username'] = $row['username'];

            // If the password is correct...
            // Return the JSON success response (including user's id)
            returnSuccess('Login successful.', $userInfo);
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

    // This purposefully doesn't include $isGroup because 0 will evaluate to false in empty()
    // Instead, the database itself will ensure a default value of 0
    checkForEmptyProperties([$username, $password, $firstName, $lastName, $emailAddress]);

    // Check for various error-inducing situations
    if (strlen($username) > 60) {
        returnError('Username cannot exceed 60 characters.');
    } else if (strlen($firstName) > 60) {
        returnError('First name cannot exceed 60 characters.');
    } else if (strlen($lastName) > 60) {
        returnError('Last name cannot exceed 60 characters.');
    } else if (strlen($emailAddress) > 60) {
        returnError('Email address cannot exceed 60 characters.');
    }

    // This block uses prepared statements and parameterized queries to protect against SQL injection
    // MySQL query to check if a username already exists in the database
    $statement = "SELECT * FROM Users WHERE username = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('s', $username);
    $query->execute();

    // Result from the query
    $result = $query->get_result();

    $query->close();

    // If a username already exists...
    // Return a JSON error response
    if ($result->num_rows > 0) {
        returnError('Username already exists.');
    }

    // Encrypt the password (using PHP defaults)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // This block uses prepared statements and parameterized queries to protect against SQL injection
    // MySQL query to add the username and password into the database
    $statement = "INSERT INTO Users (username, password, firstName, lastName, emailAddress, isGroup) VALUES (?, ?, ?, ?, ?, ?)";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('sssssi', $username, $hashedPassword, $firstName, $lastName, $emailAddress, $isGroup);
    $query->execute();

    // Result from the query
    $result = $query->affected_rows;

    if ($result) {
        $userID = $query->insert_id;
    }

    $query->close();

    // Check to see if the insertion was successful...
    if ($result) {
        $userInfo = [];
        $userInfo['userID'] = $userID;
        $userInfo['username'] = getUsernameFromUserID($dbConnection, $userID);

        // If successful, return JSON success response
        returnSuccess('User created.', $userInfo);
    } else {
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
    $tags     = $jsonPayload['tags'];

    // Posts are not required to have $bodyText, $imageURL, or $tags
    checkForEmptyProperties([$userID]);

    // Add post to the database
    $statement = "INSERT INTO Posts (userID, bodyText, imageURL) VALUES (?, ?, ?)";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('iss', $userID, $bodyText, $imageURL);
    $query->execute();

    $result = $query->affected_rows;

    if ($result) {
        $postID = $query->insert_id;
    }

    $query->close();

    // Check to see if the insertion was successful...
    if ($result) {
        createPostsTagsRow($dbConnection, $postID, $tags);

        // If successful, return JSON success response
        returnSuccess('Post created.');
    } else {
        // If not successful, return JSON error response
        returnError('Post not created: ' . $dbConnection->error);
    }
}

/**
 * Get a single post by ID
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function getPost($dbConnection, $jsonPayload)
{
    $postID = $jsonPayload['postID'];

    checkForEmptyProperties([$postID]);

    $post = getPostByID($dbConnection, $postID);

    returnSuccess('Posts found.', $post);
}

/**
 * Get the latest specified-amount of posts
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function getPostsLatest($dbConnection, $jsonPayload)
{
    $userID        = $jsonPayload['userID'];
    $numberOfPosts = $jsonPayload['numberOfPosts'];

    checkForEmptyProperties([$userID, $numberOfPosts]);

    $statement = "SELECT * FROM Posts ORDER BY id DESC LIMIT ?;";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('i', $numberOfPosts);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    // Verify posts were found
    if ($result->num_rows <= 0) {
        returnError('No posts found: ' . $dbConnection->error);
    }

    $postResults = [];

    // NOTE: $userID is the ID of the actual user fetching the posts
    //       $row['userID'] is the ID of each user that created the post(s) being fetched
    while ($row = $result->fetch_assoc()) {
        $postID = $row['id'];

        $postInformation = [
            'postID'   => $postID,
            'userID'   => $row['userID'],
            'username' => getUsernameFromUserID($dbConnection, $row['userID']),
            'bodyText' => $row['bodyText'],
            'imageURL' => $row['imageURL'],
            'tags'     => getPostTags($dbConnection, $postID),
            'isLiked'  => isPostLiked($dbConnection, $userID, $postID),
        ];

        $postResults[] = $postInformation;
    }

    returnSuccess('Post(s) found.', $postResults);
}

function likePost($dbConnection, $jsonPayload)
{
    $userID = $jsonPayload['userID'];
    $postID = $jsonPayload['postID'];

    checkForEmptyProperties([$userID, $postID]);

    $statement = "INSERT IGNORE INTO Users_Posts_Likes (userID, postID) VALUES (?, ?);";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $postID);
    $query->execute();

    $result = $query->affected_rows;

    $query->close();

    if ($result <= 0) {
        returnError('Previously liked post.');
    }

    $statement = "SELECT tagID FROM Posts_Tags WHERE postID = ?;";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('i', $postID);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    $tags = [];

    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['tagID'];
    }

    foreach ($tags as $tagID) {
        $statement = "INSERT INTO Users_Tags_Likes (userID, tagID) VALUES (?, ?) ON DUPLICATE KEY UPDATE strength = strength + 1";
        $query = $dbConnection->prepare($statement);
        $query->bind_param('ii', $userID, $tagID);
        $query->execute();

        $query->close();
    }

    returnSuccess('Post liked.');
}

function unlikePost($dbConnection, $jsonPayload)
{
    $userID = $jsonPayload['userID'];
    $postID = $jsonPayload['postID'];

    checkForEmptyProperties([$userID, $postID]);

    $statement = "DELETE FROM Users_Posts_Likes WHERE userID = ? AND postID = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $postID);
    $query->execute();

    $result = $dbConnection->affected_rows;

    $query->close();

    if ($result <= 0) {
        returnError('Post was not previously liked.');
    }

    $statement = "UPDATE Users_Tags_Likes SET strength = strength - 1 WHERE userID = ? AND tagID IN (SELECT tagID FROM Posts_Tags WHERE postID = ?)";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $postID);
    $query->execute();

    $query->close();

    returnSuccess('Post unliked.');
}

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

function checkForEmptyProperties($properties)
{
    foreach ($properties as $property) {
        if (empty($property)) {
            returnError('Not all JSON properties are set.');
        }
    }
}

function getPostTags($dbConnection, $postID)
{
    $statement = "SELECT t.name FROM Tags AS t, Posts_Tags AS pt WHERE pt.postID = ? AND t.id = pt.tagID";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('i', $postID);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    $tagResults = [];

    while ($row = $result->fetch_assoc()) {
        $tagResults[] = $row['name'];
    }

    return $tagResults;
}

function createPostsTagsRow($dbConnection, $postID, $tags)
{
    foreach ($tags as $tag) {
        $statement = "SELECT * FROM Tags WHERE name = ?";
        $query = $dbConnection->prepare($statement);
        $query->bind_param('s', $tag);
        $query->execute();

        $result = $query->get_result();

        $query->close();

        if ($result->num_rows > 0) {
            $tagID = $result->fetch_assoc()['id'];
        } else {
            $statement = "INSERT INTO Tags (name) VALUES (?)";
            $query = $dbConnection->prepare($statement);
            $query->bind_param('s', $tag);
            $query->execute();

            $tagID = $query->insert_id;

            $query->close();
        }

        $statement = "INSERT INTO Posts_Tags (postID, tagID) VALUES (?, ?)";
        $query = $dbConnection->prepare($statement);
        $query->bind_param('ii', $postID, $tagID);
        $query->execute();

        $query->close();
    }
}

function getPostByID($dbConnection, $postID)
{
    $statement = "SELECT * FROM Posts WHERE id = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('i', $postID);
    $query->execute();

    $row = $query->get_result()->fetch_assoc();

    $query->close();

    $post = [
        'postID'   => $row['id'],
        'userID'   => $row['userID'],
        'bodyText' => $row['bodyText'],
        'imageURL' => $row['imageURL'],
        'tags'     => getPostTags($dbConnection, $row['id']),
    ];

    return $post;
}

function getUsernameFromUserID($dbConnection, $userID)
{
    $statement = "SELECT username FROM Users WHERE id = ?;";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('i', $userID);
    $query->execute();

    $username = $query->get_result()->fetch_assoc()['username'];

    $query->close();

    return $username;
}

function isPostLiked($dbConnection, $userID, $postID)
{
    $statement = "SELECT * FROM Users_Posts_Likes WHERE userID = ? AND postID = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $postID);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    // Return true if a row was found (meaning the post is liked by the user)
    // Return false otherwise (meaning the post is not liked by the user)
    return ($result->num_rows > 0);
}

/* ******************** */
/* TEST Functions Below */
/* ******************** */

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
// TODO: suggestTags()

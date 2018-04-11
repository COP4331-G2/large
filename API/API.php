<?php

// For mlTest
include 'AWS/AWS.php';

// Included connection-related functions
require 'Connection.php';

// Established connection to the database
$dbConnection = establishConnection();

// Received decoded JSON payload from client
$jsonPayload = getJSONPayload();

// White list of API-callable functions
$functionWhiteList = [
    'createPost',
    'createUser',
    'getPost',
    'getPostsPersonal',
    'getPostsLatest',
    'getPostsGroups',
    'likePost',
    'loginAttempt',
    'unlikePost',
    'updateUser',

    // For mlTest
    'mlTest',
];

// Call the client-requested function
callVariableFunction($dbConnection, $jsonPayload, $functionWhiteList);

/* ************************************************************* */
/*                     Endpoints Below                           */
/* ************************************************************* */

/**
 * Verify username/password information and (perhaps) login to a user's account
 *
 * @json Payload : function, username, password
 * @json Response: userID, username
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function loginAttempt($dbConnection, $jsonPayload)
{
    // Always store username in lowercase
    $username = strtolower(trim($jsonPayload['username']));
    $password = trim($jsonPayload['password']);

    checkForEmptyProperties([$username, $password]);

    // MySQL query to check if the username exists in the database
    $statement = "SELECT * FROM Users WHERE username = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('s', $username);
    $query->execute();

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
            $userInfo['userID']   = $row['id'];
            $userInfo['username'] = $row['username'];

            // If the password is correct...
            returnSuccess('Login successful.', $userInfo);
        } else {
            // If the password isn't correct...
            returnError('Password incorrect.');
        }
    } else {
        // If the username doesn't exist...
        returnError('Username not found.');
    }
}

/**
 * Create a new user account
 *
 * @json Payload : function, username, password, firstName, lastName, emailAddress, [isGroup]
 * @json Response: userID, username
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function createUser($dbConnection, $jsonPayload)
{
    $username     = strtolower(trim($jsonPayload['username']));
    $password     = trim($jsonPayload['password']);
    $firstName    = trim($jsonPayload['firstName']);
    $lastName     = trim($jsonPayload['lastName']);
    $emailAddress = trim($jsonPayload['emailAddress']);
    $isGroup      = 0;

    // This purposefully doesn't include $isGroup because 0 will evaluate to false in empty()
    // Instead, the database itself will ensure a default value of 0
    checkForEmptyProperties([$username, $password, $firstName, $lastName, $emailAddress]);

    // Check for various error-inducing inputs
    if (strlen($username) > 60) {
        returnError('Username cannot exceed 60 characters.');
    } else if (strlen($firstName) > 60) {
        returnError('First name cannot exceed 60 characters.');
    } else if (strlen($lastName) > 60) {
        returnError('Last name cannot exceed 60 characters.');
    } else if (strlen($emailAddress) > 60) {
        returnError('Email address cannot exceed 60 characters.');
    }

    // MySQL query to check if a username already exists in the database
    $statement = "SELECT * FROM Users WHERE username = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('s', $username);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    if ($result->num_rows > 0) {
        // If a username already exists...
        returnError('Username already exists.');
    }

    // Encrypt the password (using PHP defaults)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // MySQL query to add the new user information into the database
    $statement = "INSERT INTO Users (username, password, firstName, lastName, emailAddress, isGroup) VALUES (?, ?, ?, ?, ?, ?)";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('sssssi', $username, $hashedPassword, $firstName, $lastName, $emailAddress, $isGroup);
    $query->execute();

    $result = $query->affected_rows;

    if ($result) {
        // Get the newly created user's database ID
        $userID = $query->insert_id;
    }

    $query->close();

    // Check to see if the insertion was successful...
    if ($result) {
        $userInfo = [];
        $userInfo['userID']   = $userID;
        $userInfo['username'] = getUsernameFromUserID($dbConnection, $userID);

        // If successful...
        returnSuccess('User created.', $userInfo);
    } else {
        // If not successful...
        returnError('User not created: ' . $dbConnection->error);
    }
}

/**
 * Create a user post
 *
 * @json Payload : function, userID, [bodyText, imageURL, tags]
 * @json Response: postID
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function createPost($dbConnection, $jsonPayload)
{
    $userID   = $jsonPayload['userID'];
    $bodyText = trim($jsonPayload['bodyText']);
    $imageURL = trim($jsonPayload['imageURL']);
    $tags     = $jsonPayload['tags'];

    // Trim whitespace from all tags
    // The strange syntax allows the updated values to escape the scope of the foreach loop
    foreach ($tags as $key => $field) {
        $tags[$key] = trim($tags[$key]);
    }

    // Posts are not actually required to have $bodyText, $imageURL, or $tags
    checkForEmptyProperties([$userID]);

    // Add newly created post to the database
    $statement = "INSERT INTO Posts (userID, bodyText, imageURL) VALUES (?, ?, ?)";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('iss', $userID, $bodyText, $imageURL);
    $query->execute();

    $result = $query->affected_rows;

    if ($result) {
        // Get the newly created post's database ID
        $postID = $query->insert_id;
    }

    $query->close();

    // Check to see if the insertion was successful...
    if ($result) {
        // Create the relational rows for each post/tag
        createPostsTagsRows($dbConnection, $postID, $tags);

        // Append the newly created post's database ID to the JSON response
        $postInfo = ['postID' => $postID];

        // If successful...
        returnSuccess('Post created.', $postInfo);
    } else {
        // If not successful...
        returnError('Post not created: ' . $dbConnection->error);
    }
}

/**
 * Get database information for a single post by its ID
 *
 * @json Payload : function, userID, postID
 * @json Response: postID, userID, username, [bodyText, imageURL, tags], isLiked
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function getPost($dbConnection, $jsonPayload)
{
    $userID = $jsonPayload['userID'];
    $postID = $jsonPayload['postID'];

    checkForEmptyProperties([$userID, $postID]);

    $post = getPostByID($dbConnection, $postID);

    // Check to see if the post was found...
    if ($post) {
        // Append whether or not the post is liked by the user to the JSON response
        $post['isLiked'] = isPostLiked($dbConnection, $userID, $postID);

        // Append the post creator's username
        $post['username'] = getUsernameFromUserID($dbConnection, $post['userID']);

        // If the post was found...
        returnSuccess('Posts found.', $post);
    } else {
        // If the post was not found...
        returnError("Post not found.");
    }
}

/**
 * Get the most relevant posts for a particular user (based on tag likes)
 *
 * @json Payload : function, userID, numberOfPosts
 * @json Response: (multiple) postID, userID, username, [bodyText, imageURL, tags], isLiked
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function getPostsPersonal($dbConnection, $jsonPayload)
{
    $userID        = $jsonPayload['userID'];
    $numberOfPosts = $jsonPayload['numberOfPosts'];

    checkForEmptyProperties([$userID, $numberOfPosts]);

    $statement =
        "SELECT id, userID, bodyText, imageURL,
        (SELECT GROUP_CONCAT(t.name) FROM Tags AS t, Posts_Tags AS pt WHERE pt.postID = Posts.id AND t.id = pt.tagID) AS tags,
        (SELECT username FROM Users WHERE id = Posts.userID) AS username,
        IFNULL((SELECT SUM(strength) FROM Users_Tags_Likes WHERE userID = ? AND tagID IN (SELECT tagID FROM Posts_Tags WHERE postID = Posts.id)), 0) AS strength,
        CASE WHEN (SELECT id FROM Users_Posts_Likes WHERE userID = ? AND postID = Posts.id) IS NOT NULL THEN 1 ELSE 0 END AS isLiked
        FROM Posts ORDER BY id DESC LIMIT ?";

    $query = $dbConnection->prepare($statement);
    $query->bind_param('iii', $userID, $userID, $numberOfPosts);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    // Verify post(s) were found
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
            'username' => $row['username'],
            'bodyText' => $row['bodyText'],
            'imageURL' => $row['imageURL'],
            'tags'     => preg_split('/,/', $row['tags'], null, PREG_SPLIT_NO_EMPTY),
            'isLiked'  => ($row['isLiked'] == TRUE),
            'strength' => (int) $row['strength'],
        ];

        $postResults[] = $postInformation;
    }

    returnSuccess('Post(s) found.', $postResults);
}

/**
 * Get the specified amount of latest posts
 *
 * @json Payload : function, userID, numberOfPosts
 * @json Response: (multiple) postID, userID, username, [bodyText, imageURL, tags], isLiked
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function getPostsLatest($dbConnection, $jsonPayload)
{
    $userID        = $jsonPayload['userID'];
    $numberOfPosts = $jsonPayload['numberOfPosts'];

    checkForEmptyProperties([$userID, $numberOfPosts]);

    $statement =
        "SELECT id, userID, bodyText, imageURL,
        (SELECT GROUP_CONCAT(t.name) FROM Tags AS t, Posts_Tags AS pt WHERE pt.postID = Posts.id AND t.id = pt.tagID) AS tags,
        (SELECT username FROM Users WHERE id = Posts.userID) AS username,
        CASE WHEN (SELECT id FROM Users_Posts_Likes WHERE userID = ? AND postID = Posts.id) IS NOT NULL THEN 1 ELSE 0 END AS isLiked
        FROM Posts ORDER BY id DESC LIMIT ?";

    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $numberOfPosts);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    // Verify post(s) were found
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
            'username' => $row['username'],
            'bodyText' => $row['bodyText'],
            'imageURL' => $row['imageURL'],
            'tags'     => preg_split('/,/', $row['tags'], null, PREG_SPLIT_NO_EMPTY),
            'isLiked'  => ($row['isLiked'] == TRUE),
        ];

        $postResults[] = $postInformation;
    }

    returnSuccess('Post(s) found.', $postResults);
}

/**
 * Get the specified amount of latest posts created by groups
 *
 * @json Payload : function, userID, numberOfPosts
 * @json Response: (multiple) postID, userID, username, [bodyText, imageURL, tags], isLiked
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function getPostsGroups($dbConnection, $jsonPayload)
{
    $userID        = $jsonPayload['userID'];
    $numberOfPosts = $jsonPayload['numberOfPosts'];

    checkForEmptyProperties([$userID, $numberOfPosts]);

    $statement =
        "SELECT p.id, p.userID, p.bodyText, p.imageURL,
        (SELECT GROUP_CONCAT(t.name) FROM Tags AS t, Posts_Tags AS pt WHERE pt.postID = p.id AND t.id = pt.tagID) AS tags,
        (SELECT username FROM Users WHERE id = p.userID) AS username,
        CASE WHEN (SELECT id FROM Users_Posts_Likes WHERE userID = ? AND postID = p.id) IS NOT NULL THEN 1 ELSE 0 END AS isLiked
        FROM Posts AS p, Users AS u WHERE p.userID = u.id AND u.isGroup = 1 ORDER BY id DESC LIMIT ?";

    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $numberOfPosts);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    // Verify post(s) were found
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
            'username' => $row['username'],
            'bodyText' => $row['bodyText'],
            'imageURL' => $row['imageURL'],
            'tags'     => preg_split('/,/', $row['tags'], null, PREG_SPLIT_NO_EMPTY),
            'isLiked'  => ($row['isLiked'] == TRUE),
        ];

        $postResults[] = $postInformation;
    }

    returnSuccess('Post(s) found.', $postResults);
}

/**
 * Like a post
 *
 * @json Payload : function, userID, postID
 * @json Response: tagsLikedCount
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function likePost($dbConnection, $jsonPayload)
{
    $userID = $jsonPayload['userID'];
    $postID = $jsonPayload['postID'];

    checkForEmptyProperties([$userID, $postID]);

    // Create the relationship row for the userID and the postID
    $statement = "INSERT IGNORE INTO Users_Posts_Likes (userID, postID) VALUES (?, ?)";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $postID);
    $query->execute();

    $result = $query->affected_rows;

    $query->close();

    // If the post had already been liked...
    if ($result <= 0) {
        returnError('Previously liked post.');
    }

    // Get the IDs of each tag for this post
    $statement = "SELECT tagID FROM Posts_Tags WHERE postID = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('i', $postID);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    // Track how many tags were liked as a result of this post being liked
    $tagsLikedCount = $result->num_rows;

    $likeInfo = ['tagsLikedCount' => $tagsLikedCount];

    // Store all of the IDs for each tag related to this post
    $tags = [];

    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['tagID'];
    }

    // Create the relationship row(s) for each tag liked as a result of this post being liked
    // Or increase the strength count if the tag(s) has/have already been liked by this user
    foreach ($tags as $tagID) {
        $statement = "INSERT INTO Users_Tags_Likes (userID, tagID) VALUES (?, ?) ON DUPLICATE KEY UPDATE strength = strength + 1";
        $query = $dbConnection->prepare($statement);
        $query->bind_param('ii', $userID, $tagID);
        $query->execute();

        $query->close();
    }

    increaseStrengthCount($dbConnection, $userID, $tagsLikedCount);

    returnSuccess('Post liked.', $likeInfo);
}

/**
 * Unlike a post
 *
 * @json Payload : function, userID, postID
 * @json Response: tagsUnlikedCount
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function unlikePost($dbConnection, $jsonPayload)
{
    $userID = $jsonPayload['userID'];
    $postID = $jsonPayload['postID'];

    checkForEmptyProperties([$userID, $postID]);

    // Delete the relationship row for the userID and the postID
    $statement = "DELETE FROM Users_Posts_Likes WHERE userID = ? AND postID = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $postID);
    $query->execute();

    $result = $dbConnection->affected_rows;

    $query->close();

    // If the post had not already been liked...
    if ($result <= 0) {
        returnError('Post was not previously liked.');
    }

    // Delete the relationship row(s) for each tag unliked as a result of this post being unliked
    // Or decrease the strength count if the tag(s) has/have already been liked by this user
    $statement = "UPDATE Users_Tags_Likes SET strength = strength - 1 WHERE userID = ? AND tagID IN (SELECT tagID FROM Posts_Tags WHERE postID = ?)";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $postID);
    $query->execute();

    // Track how many tags were unliked as a result of this post being unliked
    $tagsUnlikedCount = $dbConnection->affected_rows;

    $query->close();

    $unlikeInfo = ['tagsUnlikedCount' => $tagsUnlikedCount];

    decreaseStrengthCount($dbConnection, $userID, $tagsUnlikedCount);

    returnSuccess('Post unliked.', $unlikeInfo);
}

/** Machine Learning function
  * SHOULD HAVE (Impressive feature to present to the class)
  * @json Payload : function, tagID, postID, imageURL
  * @json Response: autoTag
  *
  * @param mysqli $dbConnection MySQL connection instance
  * @param object $jsonPayload Decoded JSON stdClass object
  *
 */
function suggestTags($dbConnection, $jsonPayload)
{
  // implement machine learning algorithm
  // separate machine learning from create post? as a stand alone funct?
  // image to text
  //text to tags
}

/**
 * Update a user's personal account information
 *
 * @json Payload : function, userID, [username, password, firstName, lastName, emailAddress]
 * @json Response: [none]
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 *
*/
function updateUser($dbConnection, $jsonPayload)
{
    $userID       = $jsonPayload['userID'];
    $username     = strtolower(trim($jsonPayload['username']));
    $password     = trim($jsonPayload['password']);
    $firstName    = trim($jsonPayload['firstName']);
    $lastName     = trim($jsonPayload['lastName']);
    $emailAddress = trim($jsonPayload['emailAddress']);

    checkForEmptyProperties([$userID]);

    // Check for various error-inducing inputs
    if (strlen($username) > 60) {
        returnError('Username cannot exceed 60 characters.');
    } else if (strlen($firstName) > 60) {
        returnError('First name cannot exceed 60 characters.');
    } else if (strlen($lastName) > 60) {
        returnError('Last name cannot exceed 60 characters.');
    } else if (strlen($emailAddress) > 60) {
        returnError('Email address cannot exceed 60 characters.');
    }

    // If a new password is provided...
    if (!empty($password)) {
        // Encrypt the password (using PHP defaults)
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    // This MySQL statement will only update each column if the new value is not an empty string
    $statement =
        "UPDATE Users SET
            username     = CASE WHEN ? = '' THEN username     ELSE ? END,
            password     = CASE WHEN ? = '' THEN password     ELSE ? END,
            firstName    = CASE WHEN ? = '' THEN firstName    ELSE ? END,
            lastName     = CASE WHEN ? = '' THEN lastName     ELSE ? END,
            emailAddress = CASE WHEN ? = '' THEN emailAddress ELSE ? END
        WHERE id = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ssssssssssi', $username, $username, $password, $password, $firstName, $firstName, $lastName, $lastName, $emailAddress, $emailAddress, $userID);
    $query->execute();

    $result = $dbConnection->affected_rows;

    $query->close();

    // Check to see if the information was updated...
    if ($result > 0) {
        returnSuccess('User information updated.');
    } else {
        returnError('User information not updated.');
    }
}

/** Authentication function
  * MAY HAVE
  * @json Payload : function, userID, password
  * @json Response: userID, password
  *
  * @param mysqli $dbConnection MySQL connection instance
  * @param object $jsonPayload Decoded JSON stdClass object
  *
 */
function authenticateUser()
{
  //database call to check if the username exists
 // checks paasword input from both fields (could this be done on the frontend?)
}

/* **************************************************** */
/*                  Functions Below                     */
/* **************************************************** */

/**
 * Call a variable function passed as a string from the client
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 * @param array $functionWhiteList An array of white-listed API functions
 */
function callVariableFunction($dbConnection, $jsonPayload, $functionWhiteList)
{
    // Get function name (as a string) from the JSON payload
    $function = $jsonPayload['function'];

    // Ensure that the function is in the white list (using strict type comparison)
    $funcIndex = array_search($function, $functionWhiteList, true);

    // Use the functionWhiteList version, not the user-supplied version (for security reasons)
    if ($funcIndex !== false && $funcIndex !== null) {
        $function = $functionWhiteList[$funcIndex];
    } else {
        // If the function is not part of the white list, return a JSON error response
        returnError('JSON payload tried to call non-white list PHP function ' . $function . '()');
    }

    // Ensure that the function exists and is callable
    if (is_callable($function)) {
        // Use the 'function' string field to call a PHP function (this uses the magic of unicorn blood)
        $function($dbConnection, $jsonPayload);
    } else {
        // If the function is not callable, return a JSON error response
        returnError('JSON payload tried to call undefined PHP function ' . $function . '()');
    }
}

/**
 * Ensure that all of the array properties are not empty
 *
 * @param array $properties An array of properties
 */
function checkForEmptyProperties($properties)
{
    foreach ($properties as $property) {
        if (empty($property)) {
            returnError('Not all JSON properties are set for this endpoint call.');
        }
    }
}

/**
 * Get all of the tag names from a post
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param integer $postID The database ID of a post
 */
function getPostTags($dbConnection, $postID)
{
    // Use a post ID to get all associated tag names
    $statement = "SELECT t.name FROM Tags AS t, Posts_Tags AS pt WHERE pt.postID = ? AND t.id = pt.tagID";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('i', $postID);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    $tagResults = [];

    // Build an array of tag names
    while ($row = $result->fetch_assoc()) {
        $tagResults[] = $row['name'];
    }

    return $tagResults;
}

/**
 * Create relationship row(s) for a post and tag(s)
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param integer $postID The database ID of a post
 * @param array $tags A string array of tag names
 */
function createPostsTagsRows($dbConnection, $postID, $tags)
{
    // Do this for each tag given
    foreach ($tags as $tag) {
        // Select (if exists) information for a tag by its name
        $statement = "SELECT * FROM Tags WHERE name = ?";
        $query = $dbConnection->prepare($statement);
        $query->bind_param('s', $tag);
        $query->execute();

        $result = $query->get_result();

        $query->close();

        // Check to see if the tag already exists...
        if ($result->num_rows > 0) {
            // If it does, then fetch the tagID
            $tagID = $result->fetch_assoc()['id'];
        } else {
            // If it doesn't, then create the new tag in the database
            $statement = "INSERT INTO Tags (name) VALUES (?)";
            $query = $dbConnection->prepare($statement);
            $query->bind_param('s', $tag);
            $query->execute();

            // Save the newly created tag's ID
            $tagID = $query->insert_id;

            $query->close();
        }

        // Create the relationship row for the postID and the tagID
        $statement = "INSERT INTO Posts_Tags (postID, tagID) VALUES (?, ?)";
        $query = $dbConnection->prepare($statement);
        $query->bind_param('ii', $postID, $tagID);
        $query->execute();

        $query->close();
    }
}

/**
 * Get a post's information using its ID
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param integer $postID The database ID of a post
 */
function getPostByID($dbConnection, $postID)
{
    // Get the post's information using its ID
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

        // This will be an array of strings containing tag names
        'tags'     => getPostTags($dbConnection, $row['id']),
    ];

    return $post;
}

/**
 * Get a user's username using its ID
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param integer $userID The database ID of a user
 */
function getUsernameFromUserID($dbConnection, $userID)
{
    // Get the user's username using its ID
    $statement = "SELECT username FROM Users WHERE id = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('i', $userID);
    $query->execute();

    $username = $query->get_result()->fetch_assoc()['username'];

    $query->close();

    return $username;
}

/**
 * Determine if a post is liked by a user
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param integer $userID The database ID of a user
 * @param integer $postID The database ID of a post
 */
function isPostLiked($dbConnection, $userID, $postID)
{
    $statement = "SELECT * FROM Users_Posts_Likes WHERE userID = ? AND postID = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $userID, $postID);
    $query->execute();

    $result = $query->get_result();

    $query->close();

    // True if a row was found (meaning the post is liked by the user)
    // False otherwise (meaning the post is not liked by the user)
    $isLiked = ($result->num_rows > 0);

    return $isLiked;
}

/**
 * Increase the total strength count for a user
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param integer $userID The database ID of a user
 * @param integer $strengthIncrease The amount to add to the user's total strength count
 */
function increaseStrengthCount($dbConnection, $userID, $strengthIncrease)
{
    $statement = "UPDATE Users SET strengthCount = strengthCount + ? WHERE id = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $strengthIncrease, $userID);
    $query->execute();

    $query->close();
}

/**
 * Decrease the total strength count for a user
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param integer $userID The database ID of a user
 * @param integer $strengthDecrease The amount to subtract from the user's total strength count
 */
function decreaseStrengthCount($dbConnection, $userID, $strengthDecrease)
{
    $statement = "UPDATE Users SET strengthCount = strengthCount - ? WHERE id = ?";
    $query = $dbConnection->prepare($statement);
    $query->bind_param('ii', $strengthDecrease, $userID);
    $query->execute();

    $query->close();
}

/** Authentication helper  function
  * MAY HAVE
  * @param mysqli $dbConnection MySQL connection instance
  * @param object $jsonPayload Decoded JSON stdClass object
  *
 */
function generateAuthCode()
{
  //helper function to authenticate users

}

/** Authentication helper function
  * MAY HAVE
  * @param mysqli $dbConnection MySQL connection instance
  * @param object $jsonPayload Decoded JSON stdClass object
  *
 */
function verifyAuthCode()
{
  //verifies if username and password is valid
}

function mlTest($dbConnection, $jsonPayload)
{
    $text = $jsonPayload['text'];

    $tagArray = comprehend($text);

    returnSuccess('mlTest', $tagArray);
}

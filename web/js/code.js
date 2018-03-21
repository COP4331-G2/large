// Constant value for API path (for ease of use)
//const API = "API/API.php";
//const API = "http://34.205.31.49/small/web/API/API.php";
const API = "http://api.jsonbin.io/b/5ab2a48a2efae41465eb947d";

var currentUserID = "Julian";
var posts;

var failwhale = `
<pre>
                   .
                  ":"
                ___:____      |"\\/"|
              ,'        \\'.    \\  /
              |  o        \\\\___/  |
            ~^~^~^~^~^~^~^~^~^~^~^~^~
                   (fail whale)
</pre>
`;


function login()
{
    // Get the username and password from the HTML fields
    var username = document.getElementById("loginName").value;
    var password = document.getElementById("loginPassword").value;

    // Ensure that the HTML login result message is blank
    document.getElementById("loginResult").innerHTML = "";

    // Fail Whale (easter egg)
    if (username === "failwhale") {
        document.getElementById("loginResult").innerHTML = failwhale;
        return false;
    }

    // Setup the JSON payload to send to the API
    var jsonPayload = {
        function: "loginAttempt",
        username: username,
        password: password
    };
    jsonPayload = JSON.stringify(jsonPayload);
    console.log("JSON Payload: " + jsonPayload);

    // Setup the HMLHttpRequest
    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, false);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    // Attempt to login and catch any error message
    try {
        // Send the XMLHttpRequest
        xhr.send(jsonPayload);
        console.log("JSON Response: " + xhr.responseText);

        // Parse the JSON returned from the request
        var jsonObject = JSON.parse(xhr.responseText);

        // If the returned JSON contains an error then set the HTML login result message
        if (jsonObject.error || !jsonObject.success) {
            document.getElementById("loginResult").innerHTML = jsonObject.error;
            return false;
        }

        // Set current user data
        currentUserID = jsonObject.results.id;
        console.log("Current UserID: " + currentUserID);

        // Reset the HTML fields to blank
        document.getElementById("loginName").value = "";
        document.getElementById("loginPassword").value = "";

        // Hide the login HTML elements
        hideOrShow("loginDiv", false);

        // Hide the landing page
        hideOrShow("landingPageDiv", false);

        // Show the post-login HTML elements
        hideOrShow("loggedinDiv", true);
        hideOrShow("accessUIDiv", true);
        
    } catch (e) {
        // If there is an error parsing the JSON, attempt to set the HTML login result message
        document.getElementById("loginResult").innerHTML = e.message;
    }

    document.getElementById("currentUserName").innerHTML = jsonObject.results.username;

    return true;
}

function hideOrShow(elementId, showState) {
    var componentToChange = document.getElementById(elementId);

    // Set the visibility based on showState
    if (!componentToChange) {
        console.log("Element (" + elementId + ") is either not currently available or is not a valid id name");
        return;
    }

    // Set the visibility based on showState
    componentToChange.style.visibility = showState ? "visible" : "hidden";

    // Set the display based on showState
    componentToChange.style.display = showState ? "block" : "none";
}

function hideOrShowByClass(elementClass, showState) {
    var nodeList = document.getElementsByClassName(elementClass);

    if (!nodeList) {
        console.log("Element (" + elementClass + ") is either not currently available or is not a valid id name");
        return;
    }

    for (var i = 0; i < nodeList.length; i++) {
        var node = nodeList[i];
        node.style.visibility = showState ? "visible" : "hidden";
        node.style.display = showState ? "block" : "none";
    }
}

function CallServerSide(jsonPayload) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, true);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");
    try {
        xhr.onreadystatechange = function() {

            if (this.readyState === 4 && this.status === 200) {
                var jsonObject = JSON.parse(xhr.responseText);
                populatePosts();
            }
        };
        xhr.send(jsonPayload);
    } catch (err) {
        console.log(err);
    }
}

function createAccount() {
    var username = document.getElementById("createUser").value;
    var password = document.getElementById("createPassword").value;
    var confirm = document.getElementById("confirmPassword").value;
    var firstName = document.getElementById("createFirstName").value;
    var lastName = document.getElementById("createLastName").value;
    var email = document.getElementById("creatEmail").value;


    document.getElementById("createResult").innerHTML = "";
    if (username.length > 60)
    {
        document.getElementById("createResult").innerHTML = "Username must not exceed 60 characters.";
        return;
    }
    if (username.length === 0) {
        document.getElementById("createResult").innerHTML = "Username must not be empty.";
        return;
    }
    if (password.length > 60)
    {
        document.getElementById("createResult").innerHTML = "Password must not exceed 60 characters.";
        return;
    }
    if (password.length < 6) {
        document.getElementById("createResult").innerHTML = "Password must be longer than 6 characters.";
        return;
    }
    if (password !== confirm) {
        document.getElementById("createResult").innerHTML = "Passwords don't match.";
        return;
    }
    if (firstName.length > 60)
    {
        document.getElementById("createResult").innerHTML = "That is a very long first name, can we call you another name?";
        return;
    }
    if (lastName.length > 60)
    {
        document.getElementById("createResult").innerHTML = "That is a very long last name, can we call you another name?";
        return;
    }
    if (email.length > 60 || !stringContains(email, "@") || email.length < 4)
    {
        document.getElementById("createResult").innerHTML = "Please enter a valid email";
        return;
    } 

    var jsonPayload =
        {
            function: "createUser",
            username: username,
            password: password,
            firstName: firstName,
            lastName: lastName,
            emailAddress: email
        };

    jsonPayload = JSON.stringify(jsonPayload);
    console.log("JSON Payload: " + jsonPayload);

    //setup
    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, false);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    try {
        //send the xml request
        xhr.send(jsonPayload);

        var jsonObject = JSON.parse(xhr.responseText);

        if (jsonObject.error) {
            document.getElementById("createResult").innerHTML = jsonObject.error;
            return false;
        }

        //make forms blank
        document.getElementById("createUser").innerHTML = "";
        document.getElementById("createPassword").innerHTML = "";
        document.getElementById("confirmPassword").innerHTML = "";
        document.getElementById("createFirstName").innerHTML = "";
        document.getElementById("creatEmail").innerHTML = "";
        document.getElementById("createLastName").innerHTML = "";
        //hide sign up
        // hideOrShow("signupDiv", false);

        //go back to login page
        // hideOrShow("homepageWelcomeDiv",true);

    } catch (e) {
        // If there is an error parsing the JSON, attempt to set the HTML login result message
        document.getElementById("loginResult").innerHTML = e.message;
    }

    return true;
}

function stringContains(stringToCheck, substring) {
    return stringToCheck.toLowerCase().indexOf(substring.toLowerCase()) !== -1;
}

function populatePosts()
{
    if (!currentUserID) {
        return;
    }

    var jsonPayload = {
        function: "getContacts",
        userID: currentUserID
    };
    jsonPayload = JSON.stringify(jsonPayload);

    var xhr = new XMLHttpRequest();
    xhr.open("GET", API, true);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    try {
        xhr.onreadystatechange = function () {

            if (this.readyState === 4 && this.status === 200) {
                var jsonObject = JSON.parse(xhr.responseText);
                buildPostData(jsonObject.posts);
                posts = jsonObject.results;
            }
        };

        xhr.send(jsonPayload);
    } catch (err) {
        console.log(err);
    }
}

function buildPostData(data) 
{
    var tud = document.getElementById("postScroll");
    var i;
    if(!data)
    {
      console.log("data is not available");
      return;
    }
    for (i = 0; i < data.length; i++) 
    {
        var post = document.createElement('div');
        var image = document.createElement('img');
        image.src = data[i].imageAddress;
        image.className = "image";
        post.appendChild(image);
        tud.appendChild(post);
    }
}


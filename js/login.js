
const API = "http://www.musuapp.com/API/API.php";


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


function login() {
    // Get the username and password from the HTML fields
    var username = document.getElementById("loginName").value;
    var password = document.getElementById("loginPassword").value;

    // Ensure that the HTML login result message is blank
    document.getElementById("loginResult").innerHTML = "";

    // Fail Whale (easter egg)
    if (username === "failwhale") {
        document.getElementById("loginResult").innerHTML = failwhale;
        return false;
    } if (username === "tetris") {
        return "tetris";
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

        // Parse the JSON returned from the request
        var jsonObject = JSON.parse(xhr.responseText);

        if (jsonObject.success) {
            window.location = 'http://www.musuapp.com/posts.html?currentUserID=' + jsonObject.results.userID + '&username=' + jsonObject.results.username;
        }

        document.getElementById("loginResult").innerHTML = jsonObject.error;

        return false;

    } catch (e) {
        // If there is an error parsing the JSON, attempt to set the HTML login result message
        document.getElementById("loginResult").innerHTML = e.message;
    }

    return true;
}

function createAccount() {
    var username = document.getElementById("createUser").value;
    var password = document.getElementById("createPassword").value;
    var confirm = document.getElementById("confirmPassword").value;
    var firstName = document.getElementById("createFirstName").value;
    var lastName = document.getElementById("createLastName").value;
    var email = document.getElementById("creatEmail").value;


    document.getElementById("createResult").innerHTML = "";
    if (username.length > 60) {
        document.getElementById("createResult").innerHTML = "Username must not exceed 60 characters.";
        return;
    }
    if (username.length === 0) {
        document.getElementById("createResult").innerHTML = "Username must not be empty.";
        return;
    }
    if (password.length > 60) {
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
    if (firstName.length > 60) {
        document.getElementById("createResult").innerHTML = "That is a very long first name, can we call you another name?";
        return;
    }
    if (lastName.length > 60) {
        document.getElementById("createResult").innerHTML = "That is a very long last name, can we call you another name?";
        return;
    }
    if (email.length > 60 || !stringContains(email, "@") || email.length < 4) {
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

    //setup
    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, false);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    try {
        //send the xml request
        xhr.send(jsonPayload);

        var jsonObject = JSON.parse(xhr.responseText);
        
        if (jsonObject.success) {
            window.location = 'http://www.musuapp.com/posts.html?currentUserID=' + jsonObject.results.userID + '&username=' + jsonObject.results.username;
        }

        //make forms blank and add error to form
        document.getElementById("createResult").innerHTML = jsonObject.error;
        document.getElementById("createUser").innerHTML = "";
        document.getElementById("createPassword").innerHTML = "";
        document.getElementById("confirmPassword").innerHTML = "";
        document.getElementById("createFirstName").innerHTML = "";
        document.getElementById("creatEmail").innerHTML = "";
        document.getElementById("createLastName").innerHTML = "";

        return false;

    } catch (e) {
        // If there is an error parsing the JSON, attempt to set the HTML login result message
        document.getElementById("createResult").innerHTML = e.message;
    }

    return true;
}

function stringContains(stringToCheck, substring) {
    return stringToCheck.toString().toLowerCase().indexOf(substring.toLowerCase()) !== -1;
}

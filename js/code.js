// Constant value for API path (for ease of use)
//const API = "API/API.php";
const API = "http://www.musuapp.com/API/API.php";

var currentUserID;
var postList;                       //List provided by backend, usually really big
var filteredPostList;               //List filtered by search bar, can be really big. If not filtered, is the same as post list.
var indexLoaded;                    //Last index loaded by page on filtered post list

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

        // Parse the JSON returned from the request
        var jsonObject = JSON.parse(xhr.responseText);

        // If the returned JSON contains an error then set the HTML login result message
        if (jsonObject.error || !jsonObject.success) {
            document.getElementById("loginResult").innerHTML = jsonObject.error;
            return false;
        }
        else if (jsonObject.success)
        {
            window.location = 'http://www.musuapp.com/posts.html?currentUserID='+jsonObject.results.userID+'&username='+jsonObject.results.username;
        }

    } catch (e) {
        // If there is an error parsing the JSON, attempt to set the HTML login result message
        document.getElementById("loginResult").innerHTML = e.message;
    }

    return true;
}

function createAccount()
{
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

    //setup
    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, false);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    try {
        //send the xml request
        xhr.send(jsonPayload);

        var jsonObject = JSON.parse(xhr.responseText);

        if (jsonObject.error || !jsonObject.success) {
            document.getElementById("createResult").innerHTML = jsonObject.error;
            return false;
        }
        else if (jsonObject.success)
        {
            window.location = 'http://www.musuapp.com/posts.html?currentUserID=' + jsonObject.results.userID + '&username=' + jsonObject.results.username;
        }

        //make forms blank
        document.getElementById("createUser").innerHTML = "";
        document.getElementById("createPassword").innerHTML = "";
        document.getElementById("confirmPassword").innerHTML = "";
        document.getElementById("createFirstName").innerHTML = "";
        document.getElementById("creatEmail").innerHTML = "";
        document.getElementById("createLastName").innerHTML = "";

    } catch (e) {
        // If there is an error parsing the JSON, attempt to set the HTML login result message
        document.getElementById("createResult").innerHTML = e.message;
    }

    return true;
}

function stringContains(stringToCheck, substring) {
    return stringToCheck.toString().toLowerCase().indexOf(substring.toLowerCase()) !== -1;
}

function populatePosts(number)
{
    var jsonPayload =
    {
            function: "getPostsLatest",
            numberOfPosts: number,
            userID: currentUserID
    };    

    jsonPayload = JSON.stringify(jsonPayload);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, true);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    try
    {
        xhr.onreadystatechange = function ()
        {

            if (this.readyState === 4 && this.status === 200)
            {
                var jsonObject = JSON.parse(xhr.responseText);
                if (jsonObject.success)
                {
                    postList = jsonObject.results;
                    filteredPostList = jsonObject.results;
                    indexLoaded = 0;
                    var tud = document.getElementById("postScroll");
                    tud.innerHTML = "";
                    buildPostData(filteredPostList.slice(0, 10));
                }
                else
                {
                    console.log(jsonObject.message);
                    alert("Error when reading posts, please try again later");
                }
            }
        };

        xhr.send(jsonPayload);
    } catch (err)
    {
        console.log(err);
    }
}

function buildPostData(posts)
{
    var tud = document.getElementById("postScroll");
    var i;
    if(!posts)
    {
      console.log("data is not available");
      return;
    }

    for (i = 0; i < posts.length; i++) {
        var post = document.createElement('div');
        var text = document.createElement('div');
        var tags = document.createElement('div');
        var username = document.createElement('div');
        var verticalLine = document.createElement('div');
        var tumbsupdiv = document.createElement('div');
        tumbsupdiv.className = "buttsup";
        var tumbsup = document.createElement('button');
        if (posts[i].isLiked) 
        {
            tumbsup.className = "btn btn-secondary mr-2 my-2 my-sm-0 unlikeButton";
            tumbsup.innerHTML = "Unlike";
        }
        else
        {
            tumbsup.className = "btn btn-primary mr-2 my-2 my-sm-0 likeButton";
            tumbsup.innerHTML = "Like";
        }
        tumbsup.id = posts[i].postID;
        tumbsup.onclick = function ()
        {
            if (this.innerHTML === "Like")
            {
                this.className = "btn btn-secondary mr-2 my-2 my-sm-0 unlikeButton";
                this.innerHTML = "Unlike";
                var jsonPayload =
                    {
                        function: "likePost",
                        userID: currentUserID,
                        postID: this.id
                    };


                jsonPayload = JSON.stringify(jsonPayload);

                //setup
                var xhr = new XMLHttpRequest();
                xhr.open("POST", API, true);
                xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

                try {
                    //send the xml request
                    xhr.send(jsonPayload);
                }
                catch(e)
                {
                    console.log(e.message);
                }
         
            }
            else
            {
                this.className = "btn btn-primary mr-2 my-2 my-sm-0 likeButton";
                this.innerHTML = "Like";

                var jsonPayload =
                    {
                        function: "unlikePost",
                        userID: currentUserID,
                        postID: this.id
                    };


                jsonPayload = JSON.stringify(jsonPayload);

                //setup
                var xhr = new XMLHttpRequest();
                xhr.open("POST", API, true);
                xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

                try {
                    //send the xml request
                    xhr.send(jsonPayload);
                }
                catch (e) {
                    console.log(e.message);
                }

            }
        };
        verticalLine.className = "line-separator";
        text.innerHTML = posts[i].bodyText;
        tags.innerHTML = posts[i].tags;
        username.innerHTML = posts[i].username;
        var image = document.createElement('img');
        image.src = posts[i].imageURL;
        image.className = "image";
        text.className = "postBodyText";
        tags.className = "tagsText";
        username.className = "usernamePostText";
        tumbsupdiv.appendChild(tumbsup);
        post.appendChild(username);
        post.appendChild(tags);
        post.appendChild(text);
        post.appendChild(image);
        post.appendChild(tumbsupdiv);
        post.appendChild(verticalLine);
        tud.appendChild(post);
    }

    indexLoaded += posts.length;
}

function searchPosts()
{
    var typedSearch = document.getElementById("searchText").value;
    filteredPostList = postList.filter(function (item) {
        return (stringContains(item.tags, typedSearch) || stringContains(item.username, typedSearch) || stringContains(item.bodyText, typedSearch));
    });
    indexLoaded = 0;
    var tud = document.getElementById("postScroll");
    tud.innerHTML = "";
    buildPostData(filteredPostList.slice(0, 10));
}

function loadNext()
{
    buildPostData(filteredPostList.slice(indexLoaded, indexLoaded + 10));
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function startPosts()
{
    currentUserID = getParameterByName('currentUserID');
    currentUserID = 2;
    document.getElementById("currentUserName").innerHTML = getParameterByName('username');
    populatePosts(1000);

}

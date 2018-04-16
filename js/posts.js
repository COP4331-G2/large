
const API = "http://www.musuapp.com/API/API.php";

var currentUserID;
var postList;                       //List provided by backend, usually really big
var filteredPostList;               //List filtered by search bar, can be really big. If not filtered, is the same as post list.
var indexLoaded;                    //Last index loaded by page on filtered post list

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

                jsonPayload =
                    {
                        function: "unlikePost",
                        userID: currentUserID,
                        postID: this.id
                    };


                jsonPayload = JSON.stringify(jsonPayload);

                //setup
                xhr = new XMLHttpRequest();
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
        return stringContains(item.tags, typedSearch) || stringContains(item.username, typedSearch) || stringContains(item.bodyText, typedSearch);
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
    document.getElementById("currentUserName").innerHTML = getParameterByName('username');
    populatePosts(1000);

}
function settings()
{
    //@json Payload : function, userID, [username, password, firstName, lastName, emailAddress]
    // Get the username and password from the HTML fields
    currentUserID = getParameterByName('currentUserID');
    var newusername = document.getElementById("username").value;
    var newfirstname = document.getElementById("firstname").value;
    var newlastname = document.getElementById("lastname").value;
    var newpassword = document.getElementById("password").value;
    var newemail = document.getElementById("emailAdress").value;


    // Setup the JSON payload to send to the API
    var jsonPayload = {
        function: "updateUser",
        userID: currentUserID,
        username: newusername,
        password: newpassword,
        firstName: newfirstname,
        lastName: newlastname,
        emailAddress: newemail
    };
    jsonPayload = JSON.stringify(jsonPayload);
    console.log("JSON Payload: " + jsonPayload);

    // Setup the HMLHttpRequest
    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, false);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    // Attempt to send info and catch any error message
    try {
        // Send the XMLHttpRequest
        xhr.send(jsonPayload);

        // Parse the JSON returned from the request
        var jsonObject = JSON.parse(xhr.responseText);

        if (jsonObject.success) {

        }
        else
        {

        document.getElementById("loginResult").innerHTML = jsonObject.error;

        return false;
        }

    

    } catch (e) {

    }

    return true;
}





}

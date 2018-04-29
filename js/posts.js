const unsignedUploadPreset = "gppxllz4";
const API = "API/API.php";
var cloudinaryURL = "https://api.cloudinary.com/v1_1/cop4331g2/upload";

var currentUserID;
var postList;                       //List provided by backend, usually really big
var filteredPostList;               //List filtered by search bar, can be really big. If not filtered, is the same as post list.
var indexLoaded;                    //Last index loaded by page on filtered post list

function stringContains(stringToCheck, substring)
{
    return stringToCheck.toString().toLowerCase().indexOf(substring.toLowerCase()) !== -1;
}

function getPosts(functionName, numberOfPosts)
{
    var jsonPayload =
    {
            function: functionName,
            numberOfPosts: numberOfPosts,
            userID: currentUserID,
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

                console.log(jsonObject);

                if (jsonObject.success)
                {
                    postList = jsonObject.results;
                    filteredPostList = jsonObject.results;
                    indexLoaded = 0;
                    var tud = document.getElementById("postScroll");
                    tud.innerHTML = "";
                    buildPostData(filteredPostList.slice(0, 10));
                }
            }
        };
        xhr.send(jsonPayload);

    } catch (err)
    {
        console.log(err);
        alert("Error when reading posts, please try again later");
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
        var post         = document.createElement('div');
        var text         = document.createElement('div');
        var tags         = document.createElement('div');
        var username     = document.createElement('div');
        var verticalLine = document.createElement('div');
        var tumbsupdiv   = document.createElement('div');
        var deletediv    = document.createElement('div');
        var tumbsup      = document.createElement('button');

        if (posts[i].isLiked) {
            tumbsup.className = "btn btn-secondary mr-2 my-2 my-sm-0 unlikeButton";
            tumbsup.innerHTML = "Unlike";
        } else {
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
        tags.innerHTML = posts[i].tags.join(', ');
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
    buildPostData(filteredPostList.slice(indexLoaded, indexLoaded + 20));
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
    getPosts("getPostsPersonal", 1000);
}

function settings()
{
    //@json Payload : function, userID, [username, password, firstName, lastName, emailAddress]
    // Get the username and password from the HTML fields
    currentUserID           = getParameterByName('currentUserID');
    var newusername         = document.getElementById("username").value;
    var newfirstname        = document.getElementById("firstName").value;
    var newlastname         = document.getElementById("lastName").value;
    var newpassword         = document.getElementById("password").value;
    var newpassword_confirm = document.getElementById("password_confirm").value;
    var newemail            = document.getElementById("emailAddress").value;

    if (newpassword !== newpassword_confirm) {
        alert("Passwords don't match!");
        return;
    }

    // Setup the JSON payload to send to the API
    var jsonPayload = {
        function: "updateUser",
        userID: currentUserID,
        username: newusername,
        firstName: newfirstname,
        lastName: newlastname,
        emailAddress: newemail,
        password: newpassword
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

        console.log(jsonObject);

        if (jsonObject.success) {
            document.getElementById("username").value = "";
            document.getElementById("firstName").value = "";
            document.getElementById("lastName").value = "";
            document.getElementById("emailAddress").value = "";
            document.getElementById("password").value = "";
            document.getElementById("password_confirm").value = "";
            $("#SettingsModal").modal('hide');
        }
        else
        {
            return false;
        }
    } catch (e)
    {
        console.log(e.message);
    }

    return true;
}

function suggestTags()
{
    var _bodyText = document.getElementById("postText").value;
    var _picFile = document.getElementById("postImage").files[0];
    var _imageURL;

    if (_picFile === null || _picFile === 'undefined') {
        alert("You must select an image!");
        return;
    }

    if (_picFile !== null) {
        var xhr1 = new XMLHttpRequest();
        var fd = new FormData();
        xhr1.open('POST', cloudinaryURL, false);
        xhr1.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr1.onreadystatechange = function (e) {
            if (xhr1.readyState === 4 && xhr1.status === 200) {
                var response = JSON.parse(xhr1.responseText);
                _imageURL = response.secure_url;
            }
        };

        fd.append('upload_preset', unsignedUploadPreset);
        fd.append('file', _picFile);
        xhr1.send(fd);
    }


    var jsonPayload =
        {
            function: "suggestTags",
            bodyText: _bodyText,
            imageURL: _imageURL
        };

    console.log(jsonPayload);

    jsonPayload = JSON.stringify(jsonPayload);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, false);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    try
    {
        xhr.onreadystatechange = function () {

            if (this.readyState === 4 && this.status === 200)
            {
                var jsonObject = JSON.parse(xhr.responseText);

               if (jsonObject.success)
                {
                   var tags = jsonObject.results;

                   var _tags = document.getElementById("postTags").value.replace(" ,", ",").replace(", ", ",").split(",");

                    for (var i = 0; i < tags.length; i++)
                    {
                        if (!_tags.includes(tags[i]))
                        {
                            _tags.push(tags[i]);
                        }
                   }

                    _tags.clean("");

                    _tags.clean(undefined);

                    document.getElementById("postTags").value = _tags.join(', ');
                }
            }
        };

        xhr.send(jsonPayload);
    } catch (err)
    {
        console.log(err.message);
        alert("Error when suggesting tags, please try again later");
    }
}

function createPost()
{
    var _bodyText = document.getElementById("postText").value;
    var _tags = document.getElementById("postTags").value.replace(" ,",",").replace(", ", ",").split(",");
    var _picFile = document.getElementById("postImage").files[0];
    var _imageURL;

    if (_picFile === null || _picFile === 'undefined')
    {
        alert("You must upload an image!");
        return;
    }

    if (_picFile !== null)
    {
        var xhr1 = new XMLHttpRequest();
        var fd = new FormData();
        xhr1.open('POST', cloudinaryURL, false);
        xhr1.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr1.onreadystatechange = function (e) {
            if (xhr1.readyState === 4 && xhr1.status === 200) {
                var response = JSON.parse(xhr1.responseText);
                _imageURL = response.secure_url;
            }
        };

        fd.append('upload_preset', unsignedUploadPreset);
        fd.append('file', _picFile);
        xhr1.send(fd);
    }
    else
    {
        return;
    }

    var jsonPayload =
        {
            function: "createPost",
            userID: currentUserID,
            bodyText: _bodyText,
            imageURL: _imageURL,
            tags: _tags
        };

    jsonPayload = JSON.stringify(jsonPayload);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, false);
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
                    document.getElementById("postText").value = "";
                    document.getElementById("postTags").value = "";
                    document.getElementById("postImage").files = null;
                    $("#createPostModal").modal('hide');
                    startPosts();
                }

                console.log(jsonObject.message);
            }
        };

        xhr.send(jsonPayload);
    }
    catch (err)
    {
        console.log(err);
        alert("Error when creating post, please try again later");
    }
}

function logout() {
    // Setup the JSON payload to send to the API
    var jsonPayload = {
        function: "logout",
        userID: currentUserID,
    };
    jsonPayload = JSON.stringify(jsonPayload);
    console.log("JSON Payload: " + jsonPayload);

    // Setup the HMLHttpRequest
    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, false);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    // Attempt to loutout and catch any error message
    try {
        // Send the XMLHttpRequest
        xhr.send(jsonPayload);

        // Parse the JSON returned from the request
        var jsonObject = JSON.parse(xhr.responseText);

        if (jsonObject.success) {
            window.location = 'index.html';
        }

        return true;
    } catch (e) {
        // Do something?
    }
}

Array.prototype.clean = function (deleteValue) {
    for (var i = 0; i < this.length; i++) {
        if (this[i] === deleteValue) {
            this.splice(i, 1);
            i--;
        }
    }
    return this;
};

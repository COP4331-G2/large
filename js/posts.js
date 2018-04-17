const cloudinaryAPI = "";
const cloudinaryAPIKEY = "";
const API = "http://www.musuapp.com/API/API.php";

var currentUserID;
var postList;                       //List provided by backend, usually really big
var filteredPostList;               //List filtered by search bar, can be really big. If not filtered, is the same as post list.
var indexLoaded;                    //Last index loaded by page on filtered post list

function stringContains(stringToCheck, substring)
{
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

            //do something

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
    var _bodyText = document.getElementById("postText").innerText;
    var _imageURL = uploadImage();

    var jsonPayload =
        {
            function: "suggestTags",
            bodyText: _bodyText,
            imageURL: _imageURL
        };

    jsonPayload = JSON.stringify(jsonPayload);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", API, true);
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

                    //populate tags onto text label
                }
                else
                {
                    console.log(jsonObject.message);
                    alert("Error when suggesting tags, please try again later");
                }
            }
        };

        xhr.send(jsonPayload);
    } catch (err)
    {
        console.log(err);
        alert("Error when suggesting tags, please try again later");
    }
}

function createPost()
{
    var _bodyText = document.getElementById("postText").value;
    var _tags = document.getElementById("postTags").value.split(",");

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
    xhr.open("POST", API, true);
    xhr.setRequestHeader("Content-type", "application/json; charset=UTF-8");

    try {
        xhr.onreadystatechange = function () {

            if (this.readyState === 4 && this.status === 200) {
                var jsonObject = JSON.parse(xhr.responseText);
                if (jsonObject.success)
                {
                    var tags = jsonObject.results;

                    //do stuff show success 
                }
                else {
                    console.log(jsonObject.message);
                    alert("Error when suggesting tags, please try again later");
                }
            }
            else
            {
                alert("Error when suggesting tags, please try again later");
            }
        };

        xhr.send(jsonPayload);
    } catch (err) {
        console.log(err);
        alert("Error when suggesting tags, please try again later");
    }
}

function logout() {
    window.location = 'http://www.musuapp.com';
}

const cloudName = 'cop4331g2';
const unsignedUploadPreset = 'gppxllz4';

function openModal()
{
    var fileSelect = document.getElementById("fileSelect"),
        fileElem = document.getElementById("fileElem");

    fileSelect.addEventListener("click", function (e) {
        if (fileElem) {
            fileElem.click();
        }
        e.preventDefault(); // prevent navigation to "#"
    }, false);

    dropbox = document.getElementById("dropbox");
    dropbox.addEventListener("dragenter", dragenter, false);
    dropbox.addEventListener("dragover", dragover, false);
    dropbox.addEventListener("drop", drop, false);
}

function dragenter(e) {
    e.stopPropagation();
    e.preventDefault();
}

function dragover(e) {
    e.stopPropagation();
    e.preventDefault();
}

function drop(e) {
    e.stopPropagation();
    e.preventDefault();

    var dt = e.dataTransfer;
    var files = dt.files;

    handleFiles(files);
}

function uploadFile(file) {
    var url = `https://api.cloudinary.com/v1_1/${cloudName}/upload`;
    var xhr = new XMLHttpRequest();
    var fd = new FormData();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    // Reset the upload progress bar
    document.getElementById('progress').style.width = 0;

    // Update progress (can be used to show progress indicator)
    xhr.upload.addEventListener("progress", function (e)
    {
        var progress = Math.round((e.loaded * 100.0) / e.total);
        document.getElementById('progress').style.width = progress + "%";

        console.log(`fileuploadprogress data.loaded: ${e.loaded},
        data.total: ${e.total}`);
    });

    xhr.onreadystatechange = function (e) {
        if (xhr.readyState == 4 && xhr.status == 200) {
            // File uploaded successfully
            var response = JSON.parse(xhr.responseText);
            var url = response.secure_url;
            // Create a thumbnail of the uploaded image, with 150px width
            var tokens = url.split('/');
            tokens.splice(-2, 0, 'w_150,c_scale');
            var img = new Image(); // HTML5 Constructor
            img.src = tokens.join('/');
            img.alt = response.public_id;
            document.getElementById('gallery').appendChild(img);
        }
    };

    fd.append('upload_preset', unsignedUploadPreset);
    fd.append('tags', 'browser_upload'); // Optional - add tag for image admin in Cloudinary
    fd.append('file', file);
    xhr.send(fd);
}

var handleFiles = function (files) {
    for (var i = 0; i < files.length; i++) {
        uploadFile(files[i]); // call the function to upload the file
    }
};
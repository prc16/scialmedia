document.addEventListener('DOMContentLoaded', () => {
    const createPostTextArea = document.getElementById("createPostTextArea");
    const createPostMediaUpload = document.getElementById("createPostMediaUpload");
    const createPostMediaPreview = document.getElementById("createPostMediaPreview");
    const createPostPostButton = document.getElementById("createPostPostButton");
    const postsFeedContainer = document.getElementById("postsFeedContainer");
    const createPostClearButton = document.getElementById("createPostClearButton");
    const createPostErrorMessage = document.getElementById('createPostErrorMessage');

    // Function to handle resizing of textarea
    createPostTextArea.addEventListener("input", function (event) {
        this.style.height = "auto";
        this.style.height = this.scrollHeight + "px";
        createPostErrorMessage.innerText = "";
    });

    createPostClearButton.addEventListener('click', function () {
        createPostMediaUpload.value = "";
        createPostMediaPreview.innerHTML = "";
        createPostClearButton.style.display = 'none';
        createPostErrorMessage.innerText = "";
    });

    // Function to handle file upload
createPostMediaUpload.addEventListener("change", function () {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const mediaType = file.type.split("/")[0];
            if (mediaType === "image") {
                createPostErrorMessage.innerText = "";
                createPostClearButton.style.display = 'block'; // Display the remove button
                createPostMediaPreview.innerHTML = `<img src="${e.target.result}" alt="Image Preview" class="image-preview">`;
            } else  {
                createPostMediaUpload.value = ""; // This line will clear the file input
                createPostMediaPreview.innerHTML = ""; // Clear any existing preview
                createPostErrorMessage.innerText = "Only images are allowed";
            }

        };
        reader.readAsDataURL(file);
    }
});


    // Function to handle post submission
    createPostPostButton.addEventListener("click", function () {
        const postText = createPostTextArea.value;
        const file = createPostMediaUpload.files[0];

        // if ((postText.trim() === "") && !file) {
        //     createPostErrorMessage.innerText = "Empty post not allowed.";
        //     return;
        // }

        const formData = new FormData();
        formData.append("post_text", postText.trim());
        formData.append("media_file", file);

        fetch('../create_post/server.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (response.ok) {
                    createPostTextArea.value = "";
                    createPostMediaUpload.value = "";
                    createPostMediaPreview.innerHTML = "";
                    createPostTextArea.rows = 1;
                    createPostTextArea.style.height = "auto";
                    createPostClearButton.style.display = 'none';
                    createPostErrorMessage.innerText = "";

                    // Trigger update event on displayPosts div
                    const updateEvent = new Event('updateNeeded');
                    postsFeedContainer.dispatchEvent(updateEvent);
                } else {
                    // Parse JSON response
                    return response.json().then(data => {
                        // Server returned an error, display the error message
                        createPostErrorMessage.innerText = data.message;
                        console.log(data.message);
                    });
                }
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    });

    //createPostTextArea.focus();
});

<?php
include("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['image_title'];
    $description = $_POST['image_description'];
    $category_id = $_POST['category_id'];

    $file = $_FILES['image_file'];

    $upload_dir = "uploads/";

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $original_name = basename($file["name"]);
    $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    $new_name = time() . "_" . $original_name;
    $file_name = $upload_dir . $new_name;

    if (move_uploaded_file($file["tmp_name"], $file_name)) {

        $sql = "INSERT INTO image
                (image_title, image_description, file_name, file_type, upload_date, category_id)
                VALUES
                ('$title', '$description', '$file_name', '$file_type', NOW(), '$category_id')";

        mysqli_query($conn, $sql);

        header("Location: index.php");
        exit();
    } else {
        echo "Upload failed!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Photo</title>
    <style>
    body{
        background:#111;
        color:white;
        font-family:'Segoe UI',sans-serif;
        margin:0;
    }

    .container{
        max-width:1000px;
        margin:40px auto;
        background:#181818;
        border-radius:20px;
        padding:35px;
        box-shadow:0 0 20px rgba(0,0,0,.4);
    }

    h1{
        margin-bottom:30px;
        color:#22d3ee;
    }

    .form-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:25px;
    }

    .form-group{
        display:flex;
        flex-direction:column;
    }

    .form-group label{
        color:#22d3ee;
        font-weight:bold;
        margin-bottom:8px;
    }

    input[type=text],
    textarea,
    select{
        background:#e5e5e5;
        border:none;
        border-radius:12px;
        padding:14px;
        font-size:15px;
    }

    textarea{
        min-height:120px;
        resize:none;
    }

    .full-width{
        grid-column:1/-1;
    }

    .upload-box{
        background:#0f2027;
        padding:25px;
        border-radius:16px;
        margin-top:15px;
    }

    input[type=file]{
        width:100%;
        background:#e5e5e5;
        padding:12px;
        border-radius:10px;
    }

    .btn-row{
        display:flex;
        gap:15px;
        margin-top:30px;
    }

    .btn{
        padding:14px 25px;
        border:none;
        border-radius:12px;
        font-weight:bold;
        cursor:pointer;
        text-decoration:none;
        text-align:center;
    }

    .btn-upload{
        background:#06b6d4;
        color:white;
    }

    .btn-back{
        background:#333;
        color:white;
    }

    .preview{
        width:250px;
        height:250px;
        border-radius:15px;
        border:3px solid #22d3ee;
        object-fit:cover;
        display:block;
        margin-bottom:20px;
    }
</style>
</head>
<body>

    <div class="container">

    <h1>📸 Upload New Photo</h1>

    <form method="POST" enctype="multipart/form-data">

    <div class="form-grid">

    <div>
        <img id="preview" class="preview"
            src="https://via.placeholder.com/250x250?text=Preview">
    </div>

    <div>

    <div class="form-group">
    <label>Image Title</label>
    <input type="text" name="image_title" required>
    </div>

    <br>

    <div class="form-group">
    <label>Indoor / Outdoor</label>
    <select name="category_id">
        <option value="1">Indoor</option>
        <option value="2">Outdoor</option>
    </select>
    </div>

    </div>

    <div class="form-group full-width">
    <label>Description</label>
    <textarea name="image_description" required></textarea>
    </div>

    <div class="upload-box full-width">

    <div class="form-group">
    <label>Choose Image</label>
    <input type="file"
        name="image_file"
        accept="image/*"
        onchange="previewImage(event)"
        required>
    </div>

    </div>

    </div>

    <div class="btn-row">
        <button type="submit" class="btn btn-upload">
            Upload Photo
        </button>

        <a href="index.php" class="btn btn-back">
            Back to Dashboard
        </a>
    </div>

    </form>

    </div>

    <script>
    function previewImage(event){
        const reader = new FileReader();

        reader.onload = function(){
            document.getElementById('preview').src = reader.result;
        }

        reader.readAsDataURL(event.target.files[0]);
    }
    </script>

</body>
</html>
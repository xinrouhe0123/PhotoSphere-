<?php
include("config.php");

function detect_background_category($image_src)
{
    if (!function_exists('imagecreatefromstring')) {
        return 2;
    }

    $content = @file_get_contents($image_src);
    if (!$content) {
        return 2;
    }

    $original = @imagecreatefromstring($content);
    if (!$original) {
        return 2;
    }

    $newW = 120;
    $newH = 120;

    $img = imagecreatetruecolor($newW, $newH);

    imagecopyresampled(
        $img,
        $original,
        0, 0, 0, 0,
        $newW, $newH,
        imagesx($original),
        imagesy($original)
    );

    imagedestroy($original);

    $histogram = [];
    $total = 0;

    $borderSize = 20;

    for ($x = 0; $x < $newW; $x++) {
        for ($y = 0; $y < $newH; $y++) {

            // Only read border background area
            $isBackground =
                ($x < $borderSize) ||
                ($x >= $newW - $borderSize) ||
                ($y < $borderSize) ||
                ($y >= $newH - $borderSize);

            if (!$isBackground) {
                continue;
            }

            $rgb = imagecolorat($img, $x, $y);

            $r = ($rgb >> 16) & 255;
            $g = ($rgb >> 8) & 255;
            $b = $rgb & 255;

            // Group similar colours
            $r = floor($r / 32) * 32;
            $g = floor($g / 32) * 32;
            $b = floor($b / 32) * 32;

            $key = "$r-$g-$b";

            $histogram[$key] = ($histogram[$key] ?? 0) + 1;
            $total++;
        }
    }

    imagedestroy($img);

    if ($total == 0) {
        return 2;
    }

    arsort($histogram);

    $top1 = reset($histogram);
    $top3 = array_sum(array_slice($histogram, 0, 3));

    $top1Ratio = $top1 / $total;
    $top3Ratio = $top3 / $total;

    // Plain background = Formal
    if ($top1Ratio >= 0.45 || $top3Ratio >= 0.70) {
        return 1;
    }

    return 2;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = mysqli_real_escape_string($conn, $_POST['image_title']);
    $description = mysqli_real_escape_string($conn, $_POST['image_description']);

    $file = $_FILES['image_file'];
    $upload_dir = "uploads/";

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $original_name = basename($file["name"]);
    $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($file_type, $allowed)) {
        die("Only JPG, JPEG and PNG files are allowed.");
    }

    $tmp_file = $file["tmp_name"];

    // IMPORTANT: detect using temp image before moving
    $category_id = detect_background_category($tmp_file);

    $new_name = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $original_name);
    $file_name = $upload_dir . $new_name;

    if (move_uploaded_file($tmp_file, $file_name)) {

        $sql = "INSERT INTO image
                (image_title, image_description, file_name, file_type, upload_date, category_id)
                VALUES
                ('$title', '$description', '$file_name', '$file_type', NOW(), '$category_id')";

        if (mysqli_query($conn, $sql)) {
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Database Error: " . mysqli_error($conn);
        }

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
        body {
            background:#111827;
            color:white;
            font-family:Arial, sans-serif;
            margin:0;
        }

        .container {
            max-width:1000px;
            margin:40px auto;
            background:#020617;
            border-radius:20px;
            padding:35px;
            border:1px solid #1e293b;
        }

        h1 {
            margin-bottom:30px;
            color:#818cf8;
        }

        .form-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:25px;
        }

        .form-group {
            display:flex;
            flex-direction:column;
        }

        .form-group label {
            color:#cbd5e1;
            font-weight:bold;
            margin-bottom:8px;
        }

        input[type=text],
        textarea {
            background:#e5e7eb;
            border:none;
            border-radius:12px;
            padding:14px;
            font-size:15px;
        }

        textarea {
            min-height:120px;
            resize:none;
        }

        .full-width {
            grid-column:1/-1;
        }

        .upload-box {
            background:#111827;
            padding:25px;
            border-radius:16px;
            margin-top:15px;
            border:1px solid #1e293b;
        }

        input[type=file] {
            width:100%;
            background:#e5e7eb;
            padding:12px;
            border-radius:10px;
            color:black;
        }

        .btn-row {
            display:flex;
            gap:15px;
            margin-top:30px;
        }

        .btn {
            padding:14px 25px;
            border:none;
            border-radius:12px;
            font-weight:bold;
            cursor:pointer;
            text-decoration:none;
            text-align:center;
        }

        .btn-upload {
            background:#4f46e5;
            color:white;
        }

        .btn-back {
            background:#374151;
            color:white;
        }

        .preview {
            width:250px;
            height:250px;
            border-radius:15px;
            border:3px solid #4f46e5;
            object-fit:cover;
            display:block;
            margin-bottom:20px;
            background:white;
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
            <label>CBR Result</label>
            <input type="text" value="Auto detect: Formal / Informal" disabled>
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
    <button type="submit" class="btn btn-upload">Upload Photo</button>
    <a href="dashboard.php" class="btn btn-back">Back to Dashboard</a>
</div>

</form>

</div>

<script>
function previewImage(event) {
    const reader = new FileReader();

    reader.onload = function() {
        document.getElementById('preview').src = reader.result;
    }

    reader.readAsDataURL(event.target.files[0]);
}
</script>

</body>
</html>
<?php
include("config.php");

$base_url = "https://bitp3353.utem.edu.my/2026/all/";

$where = "WHERE 1=1";

if (!empty($_GET['file_type'])) {
    $file_type = mysqli_real_escape_string($conn, $_GET['file_type']);
    $where .= " AND image.file_type = '$file_type'";
}

if (!empty($_GET['description'])) {
    $description = mysqli_real_escape_string($conn, $_GET['description']);
    $where .= " AND image.image_description LIKE '%$description%'";
}

if (!empty($_GET['category'])) {
    $category = mysqli_real_escape_string($conn, $_GET['category']);

    if ($category == "Formal") {
        $where .= " AND image.category_id = 1";
    } elseif ($category == "Informal") {
        $where .= " AND image.category_id = 2";
    }
}

$sql = "SELECT 
            image.*, 
            category.category_name,
            photographer.photographer_name
        FROM image
        LEFT JOIN category 
            ON image.category_id = category.category_id
        LEFT JOIN photographer
            ON image.photographer_id = photographer.photographer_id
        $where
        ORDER BY image.upload_date DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PhotoSphere</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background:#111827;
            color:white;
            margin:0;
        }

        header {
            background:#020617;
            padding:25px 35px;
            border-bottom:1px solid #1e293b;
        }

        header h1 {
            margin:0;
            font-size:30px;
            letter-spacing:2px;
        }

        .upload-banner {
            background:#020617;
            border:1px solid #1e293b;
            border-radius:16px;
            padding:25px 30px;
            margin:30px;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .upload-banner h3 {
            margin:0;
            font-size:22px;
        }

        .upload-banner p {
            color:#94a3b8;
            margin:8px 0 0;
        }

        .upload-btn {
            background:#4f46e5;
            color:white;
            padding:14px 24px;
            border-radius:10px;
            font-weight:bold;
            text-decoration:none;
        }

        .container {
            padding:0 30px 30px;
        }

        .filter-box {
            background:#020617;
            padding:28px;
            border-radius:16px;
            border:1px solid #1e293b;
            margin-bottom:28px;
        }

        .filter-form {
            display:flex;
            align-items:end;
            gap:18px;
            flex-wrap:wrap;
        }

        .filter-group {
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .filter-group label {
            font-size:13px;
            color:#9ca3af;
            font-weight:bold;
        }

        .filter-group input,
        .filter-group select {
            min-width:190px;
            padding:12px;
            border-radius:8px;
            border:none;
        }

        .search-btn {
            background:#4f46e5;
            color:white;
            padding:12px 24px;
            border-radius:8px;
            border:none;
            cursor:pointer;
            font-weight:bold;
        }

        .reset-btn {
            color:#818cf8;
            padding:12px 0;
            text-decoration:none;
        }

        .grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));
            gap:22px;
        }

        .card {
            background:#020617;
            border:1px solid #1e293b;
            padding:18px;
            border-radius:16px;
            transition:0.3s;
        }

        .card:hover {
            border-color:#4f46e5;
            transform:translateY(-3px);
        }

        .image-box {
            width:100%;
            height:280px;
            background:#111827;
            border-radius:14px;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
            margin-bottom:15px;
        }

        .image-box img {
            width:100%;
            height:100%;
            object-fit:contain;
        }

        .card h3 {
            margin:10px 0;
            font-size:20px;
        }

        .card p {
            color:#cbd5e1;
            font-size:14px;
        }

        .badge {
            display:inline-block;
            padding:5px 12px;
            background:#1e3a8a;
            border-radius:20px;
            font-size:12px;
            color:#bfdbfe;
        }

        .empty {
            color:#9ca3af;
            text-align:center;
            grid-column:1/-1;
            padding:40px;
            background:#020617;
            border-radius:16px;
            border:1px solid #1e293b;
        }
    </style>
</head>

<body>

<header>
    <h1>PHOTOSPHERE</h1>
</header>

<div class="upload-banner">
    <div>
        <h3>📤 Upload New Photo</h3>
        <p>Add new images for ABR, TBR and CBR retrieval.</p>
    </div>

    <a href="upload.php" class="upload-btn">Upload Photo</a>
</div>

<div class="container">

    <div class="filter-box">
        <h2>Retrieval Search</h2>

        <form method="GET" class="filter-form">

            <div class="filter-group">
                <label>ABR - File Type</label>
                <select name="file_type">
                    <option value="">All</option>
                    <option value="jpg">JPG</option>
                    <option value="jpeg">JPEG</option>
                    <option value="png">PNG</option>
                </select>
            </div>

            <div class="filter-group">
                <label>TBR - Description</label>
                <input type="text" name="description" placeholder="sunset, wedding, beach">
            </div>

            <div class="filter-group">
                <label>CBR - Formal / Informal</label>
                <select name="category">
                    <option value="">All</option>
                    <option value="Formal">Formal</option>
                    <option value="Informal">Informal</option>
                </select>
            </div>

            <button type="submit" class="search-btn">Search</button>
            <a href="dashboard.php" class="reset-btn">Reset</a>

        </form>
    </div>

    <div class="grid">

        <?php if (mysqli_num_rows($result) == 0) { ?>
            <div class="empty">No images found.</div>
        <?php } ?>

        <?php while($row = mysqli_fetch_assoc($result)) { ?>

            <?php
            $img = $row['file_name'] ?? '';
            $img_src = '';

            if (!empty($img)) {
                if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                    $img_src = $img;
                } elseif (file_exists(__DIR__ . "/" . $img)) {
                    $img_src = $img;
                } elseif (strpos($img, 'uploads/') === 0) {
                    $img_src = $base_url . $img;
                } else {
                    $img_src = $img;
                }
            }

            $title = !empty($row['image_title'])
                ? $row['image_title']
                : (!empty($row['photographer_name'])
                    ? $row['photographer_name']
                    : 'Untitled Photo');

            $desc = !empty($row['image_description']) 
                ? $row['image_description'] 
                : 'No description';

            $type = !empty($row['file_type']) 
                ? $row['file_type'] 
                : '-';

            if ($row['category_id'] == 1) {
                $category = "Formal";
            } elseif ($row['category_id'] == 2) {
                $category = "Informal";
            } else {
                $category = !empty($row['category_name']) 
                    ? $row['category_name'] 
                    : 'Uncategorized';
            }

            $date = !empty($row['upload_date']) 
                ? $row['upload_date'] 
                : '-';
            ?>

            <div class="card">

                <div class="image-box">
                    <?php if (!empty($img_src)) { ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>"
                             alt="Photo"
                             onerror="this.src='https://via.placeholder.com/300x200?text=No+Image';">
                    <?php } else { ?>
                        <img src="https://via.placeholder.com/300x200?text=No+Image">
                    <?php } ?>
                </div>

                <h3><?php echo htmlspecialchars($title); ?></h3>
                <p><?php echo htmlspecialchars($desc); ?></p>
                <p><b>Type:</b> <?php echo htmlspecialchars($type); ?></p>
                <p><b>Category:</b> 
                    <span class="badge">
                        <?php echo htmlspecialchars($category); ?>
                    </span>
                </p>
                <p><b>Date:</b> <?php echo htmlspecialchars($date); ?></p>

            </div>

        <?php } ?>

    </div>

</div>

</body>
</html>
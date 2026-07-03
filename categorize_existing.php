<?php
include("config.php");

$base_url = "https://bitp3353.utem.edu.my/2026/all/";

function make_safe_url($base_url, $file_name)
{
    if (strpos($file_name, 'http://') === 0 || strpos($file_name, 'https://') === 0) {
        return str_replace(" ", "%20", $file_name);
    }

    $parts = explode("/", $file_name);
    $encoded_parts = array_map("rawurlencode", $parts);

    return $base_url . implode("/", $encoded_parts);
}

function get_image_content($image_url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $image_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200 || !$data) {
        return false;
    }

    return $data;
}

function detect_background_category($image_url, &$top1RatioOutput, &$top3RatioOutput, &$statusOutput)
{
    $top1RatioOutput = 0;
    $top3RatioOutput = 0;
    $statusOutput = "";

    if (!function_exists('imagecreatefromstring')) {
        $statusOutput = "GD not enabled";
        return 2;
    }

    $content = get_image_content($image_url);

    if (!$content) {
        $statusOutput = "Cannot read image URL";
        return 2;
    }

    $original = @imagecreatefromstring($content);

    if (!$original) {
        $statusOutput = "Invalid image content";
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

    $borderSize = 25;

    for ($x = 0; $x < $newW; $x++) {
        for ($y = 0; $y < $newH; $y++) {

            // Read background border only
            $isBorder =
                ($x < $borderSize) ||
                ($x >= $newW - $borderSize) ||
                ($y < $borderSize) ||
                ($y >= $newH - $borderSize);

            if (!$isBorder) {
                continue;
            }

            $rgb = imagecolorat($img, $x, $y);

            $r = ($rgb >> 16) & 255;
            $g = ($rgb >> 8) & 255;
            $b = $rgb & 255;

            // Histogram bucket
            $r = floor($r / 32) * 32;
            $g = floor($g / 32) * 32;
            $b = floor($b / 32) * 32;

            $key = "$r-$g-$b";

            $histogram[$key] = ($histogram[$key] ?? 0) + 1;
            $total++;
        }
    }

    imagedestroy($img);

    if ($total == 0 || empty($histogram)) {
        $statusOutput = "No pixels sampled";
        return 2;
    }

    arsort($histogram);

    $top1 = reset($histogram);
    $top3 = array_sum(array_slice($histogram, 0, 3));

    $top1Ratio = $top1 / $total;
    $top3Ratio = $top3 / $total;

    $top1RatioOutput = round($top1Ratio, 2);
    $top3RatioOutput = round($top3Ratio, 2);

    $statusOutput = "OK";

    // Plain background = Formal
    if ($top1Ratio >= 0.35 || $top3Ratio >= 0.65) {
        return 1;
    }

    return 2;
}

$sql = "SELECT image_id, file_name FROM image";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}

echo "<h2>CBR Categorization Existing Images</h2>";

while ($row = mysqli_fetch_assoc($result)) {

    $image_id = mysqli_real_escape_string($conn, $row['image_id']);
    $file_name = $row['file_name'];

    $image_url = make_safe_url($base_url, $file_name);

    $top1Ratio = 0;
    $top3Ratio = 0;
    $status = "";

    $category_id = detect_background_category(
        $image_url,
        $top1Ratio,
        $top3Ratio,
        $status
    );

    $update = mysqli_query($conn, "
        UPDATE image
        SET category_id = '$category_id'
        WHERE image_id = '$image_id'
    ");

    echo "<div style='margin-bottom:15px; border-bottom:1px solid #ccc; padding-bottom:10px;'>";

    echo "<img src='" . htmlspecialchars($image_url) . "' width='80' style='vertical-align:middle; margin-right:10px;'>";

    echo "ID: " . htmlspecialchars($row['image_id']);
    echo " | Top1 Ratio: " . $top1Ratio;
    echo " | Top3 Ratio: " . $top3Ratio;
    echo " | Status: " . htmlspecialchars($status);
    echo " | Category: ";

    if ($category_id == 1) {
        echo "<b style='color:green;'>Formal</b>";
    } else {
        echo "<b style='color:red;'>Informal</b>";
    }

    if (!$update) {
        echo " | Update Error: " . mysqli_error($conn);
    }

    echo "</div>";
}

echo "<br><a href='dashboard.php'>Back to Dashboard</a>";
?>
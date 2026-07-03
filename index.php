<?php
session_start();

// 1. Get group name from URL parameter or current folder name
if (!isset($_GET['group'])) {
    $group = basename(dirname(__FILE__));
} else {
    $group = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['group']);
}

// 2. Include database connection
include("config.php");

// 3. Retrieve full_name and matric_no using INNER JOIN
$members = [];
$sql = "SELECT S.full_name, S.matric_no
        FROM stu S
        INNER JOIN groupdb G
            ON S.group_no = G.groupID
        WHERE G.groupID = ?";

if ($stmt = $conn->prepare($sql)) {

    $stmt->bind_param("s", $group);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<title>Group Members | <?php echo htmlspecialchars($group); ?></title>

<style>

body{
    background:#0f0f0f;
    color:white;
    font-family:Arial,Helvetica,sans-serif;
    padding:40px;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:2px solid #333;
    padding-bottom:20px;
    margin-bottom:40px;
}

.table-container{
    border:1px solid #444;
    border-radius:12px;
    overflow:hidden;
    background:rgba(255,255,255,.02);
}

table{
    width:100%;
    border-collapse:collapse;
    text-align:left;
}

th,
td{
    padding:22px 30px;
    border-bottom:1px solid #333;
    font-size:1.3rem;
}

th{
    background:#161616;
    color:#00d2ff;
    text-transform:uppercase;
    letter-spacing:1px;
    font-size:1.2rem;
}

tr:last-child td{
    border-bottom:none;
}

tr:hover{
    background:rgba(255,255,255,.05);
    transition:.2s;
}

.text-break{
    word-break:break-word;
    line-height:1.5;
    font-size:1.35rem;
    font-weight:500;
}

.matrix-code{
    color:#00d2ff;
    font-family:monospace;
    font-weight:bold;
    font-size:1.4rem;
}

.bil-col{
    font-size:1.3rem;
    font-weight:bold;
}

.button-group{
    display:flex;
    gap:15px;
    margin-top:40px;
}

.btn-back,
.btn-project{

    display:inline-block;
    padding:14px 30px;
    border-radius:8px;
    text-decoration:none;
    font-weight:bold;
    font-size:1.1rem;
    transition:.3s;

}

.btn-back{

    background:#555;
    color:white;

}

.btn-back:hover{

    background:#666;

}

.btn-project{

    background:#00d2ff;
    color:#111827;

}

.btn-project:hover{

    background:#00b8e6;

}

</style>

</head>

<body>

<div class="header">

    <h1>SENARAI AHLI KUMPULAN</h1>

    <div style="
        border:1px solid #00d2ff;
        padding:10px 25px;
        font-size:1.6rem;
        border-radius:5px;
        font-weight:bold;
    ">
        GROUP: <?php echo htmlspecialchars($group); ?>
    </div>

</div>

<div class="table-container">

<table>

<thead>

<tr>

<th style="width:100px;">BIL</th>

<th>NAMA PENUH</th>

<th style="width:350px;">NO. MATRIK</th>

</tr>

</thead>

<tbody>

<?php if(empty($members)){ ?>

<tr>

<td colspan="3"
    style="
        text-align:center;
        color:#ff4444;
        padding:40px;
        font-size:1.4rem;
    ">

    Tiada data ahli kumpulan ditemui untuk kod group
    "<?php echo htmlspecialchars($group); ?>".

</td>

</tr>

<?php } else { ?>

<?php foreach($members as $index=>$row){ ?>

<tr>

<td class="bil-col">

<?php echo $index+1; ?>

</td>

<td class="text-break" style="text-transform:uppercase;">

<?php echo htmlspecialchars($row['full_name']); ?>

</td>

<td class="matrix-code">

<?php echo htmlspecialchars($row['matric_no']); ?>

</td>

</tr>

<?php } ?>

<?php } ?>

</tbody>

</table>

</div>

<div class="button-group">

    <!-- Back to Dashboard (Current Group) -->
    <a href="../../dashboard.php?group=<?php echo urlencode($group); ?>"
       class="btn-back">

        BACK TO DASHBOARD

    </a>

    <!-- Go to Main Project Dashboard -->
    <a href="../../dashboard.php"
       class="btn-project">

        GO TO PROJECT

    </a>

</div>

</body>
</html>
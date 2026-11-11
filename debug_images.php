<?php
/**
 * debug_images.php
 * TEMPORARY debug page — run this to see exactly why images aren't showing.
 * DELETE this file after you've fixed the images.
 *
 * RUN: http://localhost/wede2/debug_images.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/DBConn.php';

$conn   = getDBConnection();
$result = $conn->query("SELECT ClothesID, ItemName, ImageFile FROM tblClothes");
$conn->close();

echo "<h2 style='font-family:sans-serif;padding:1rem;'>Image Debug Report</h2>";
echo "<table border='1' cellpadding='8' style='font-family:monospace;border-collapse:collapse;margin:1rem;'>";
echo "<tr style='background:#eee;'>
        <th>ID</th>
        <th>Item</th>
        <th>DB ImageFile value</th>
        <th>Full server path</th>
        <th>File exists?</th>
        <th>Preview</th>
      </tr>";

while ($row = $result->fetch_assoc()) {
    $imgFile    = $row['ImageFile'];
    $serverPath = __DIR__ . '/images/' . $imgFile;
    $exists     = file_exists($serverPath);
    $urlPath    = 'images/' . htmlspecialchars($imgFile);

    echo "<tr>";
    echo "<td>" . (int)$row['ClothesID'] . "</td>";
    echo "<td>" . htmlspecialchars($row['ItemName']) . "</td>";
    echo "<td style='color:" . ($imgFile ? 'green' : 'red') . ";'>"
         . ($imgFile ? htmlspecialchars($imgFile) : 'NULL / EMPTY') . "</td>";
    echo "<td style='font-size:.8em;color:#555;'>" . htmlspecialchars($serverPath) . "</td>";
    echo "<td style='color:" . ($exists ? 'green' : 'red') . ";font-weight:bold;'>"
         . ($exists ? '✔ YES' : '✖ NO') . "</td>";
    echo "<td>" . ($imgFile ? "<img src='$urlPath' style='height:60px;border:1px solid #ccc;'
                               onerror=\"this.outerHTML='<span style=color:red>BROKEN</span>'\">" : '—') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3 style='font-family:sans-serif;padding:1rem;'>Files actually in /images/ folder:</h3>";
echo "<ul style='font-family:monospace;padding:1rem;'>";
$files = scandir(__DIR__ . '/images/');
foreach ($files as $f) {
    if ($f !== '.' && $f !== '..') {
        echo "<li>" . htmlspecialchars($f) . "</li>";
    }
}
echo "</ul>";
?>
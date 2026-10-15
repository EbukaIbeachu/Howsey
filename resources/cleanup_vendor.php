<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<h1>Misplaced Vendor Cleaner</h1>';
echo '<p>Current Directory: '.__DIR__.'</p>';

// List of folders to target relative to htdocs (where this script runs)
$targets = [
    'routes/vendor',
    'database/vendor',
    'resources/vendor',
    'storage/vendor', // Adding this just in case
];

// Recursive delete function
function deleteDirectory($dir)
{
    if (! file_exists($dir)) {
        return true;
    }

    if (! is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (! deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) {
            return false;
        }
    }

    return rmdir($dir);
}

// Process targets
foreach ($targets as $target) {
    $fullPath = __DIR__.'/'.$target;

    echo "<hr><strong>Checking:</strong> $target <br>";

    if (file_exists($fullPath) && is_dir($fullPath)) {
        echo "<span style='color:orange'>FOUND:</span> $fullPath <br>";

        if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
            if (deleteDirectory($fullPath)) {
                echo "<span style='color:green'>[DELETED]</span> Successfully removed $target.<br>";
            } else {
                echo "<span style='color:red'>[ERROR]</span> Could not delete $target. Check permissions.<br>";
            }
        } else {
            echo "<span style='color:blue'>[PENDING]</span> Click link below to delete.<br>";
        }
    } else {
        echo "<span style='color:gray'>[CLEAN]</span> Not found.<br>";
    }
}

echo '<hr>';
if (! isset($_GET['confirm'])) {
    echo "<h2><a href='?confirm=yes' style='color:red; font-size: 20px;'>⚠️ Click Here to DELETE Found Folders</a></h2>";
} else {
    echo "<h2><a href='cleanup_vendor.php'>Refresh Scan</a></h2>";
}

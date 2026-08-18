<?php
/**
 * Background Generator Trigger - The Final Chapter
 * Access: http://localhost:7788/gen_bg_trigger.php
 * This will generate the battlefield background image using Python/ffmpeg
 */

$out = __DIR__ . '/assets/img/bg-battlefield2.jpg';

if (file_exists($out)) {
    $mtime = date('Y-m-d H:i:s', filemtime($out));
    $size = filesize($out);
    header('Content-Type: text/html');
    echo "<h2>Image already exists!</h2>";
    echo "<p>Path: $out</p>";
    echo "<p>Size: " . number_format($size) . " bytes</p>";
    echo "<p>Modified: $mtime</p>";
    echo "<p><a href='assets/img/bg-battlefield2.jpg'>View Image</a></p>";
    echo "<p><a href='?regen=1'>Regenerate</a></p>";
    if (!isset($_GET['regen'])) exit;
}

echo "<h2>Generating battlefield background...</h2>\n";
echo "<pre>\n";
flush();

$script = __DIR__ . '/gen_bg.py';
$ffmpeg_script = __DIR__ . '/gen_bg_ffmpeg.sh';

// Try Python first
if (file_exists($script)) {
    echo "Running Python generator...\n";
    $output = [];
    $retval = 0;
    exec("cd " . escapeshellarg(__DIR__) . " && python3 " . escapeshellarg($script) . " 2>&1", $output, $retval);
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
        flush();
    }
    echo "Exit code: $retval\n";
}

// If that didn't work, try ffmpeg script
if (!file_exists($out) && file_exists($ffmpeg_script)) {
    echo "\nRunning ffmpeg generator...\n";
    $output = [];
    $retval = 0;
    exec("bash " . escapeshellarg($ffmpeg_script) . " 2>&1", $output, $retval);
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
        flush();
    }
    echo "Exit code: $retval\n";
}

echo "</pre>\n";

if (file_exists($out)) {
    $size = filesize($out);
    echo "<h2 style='color:green'>✓ Success!</h2>";
    echo "<p>Generated: $out ($size bytes)</p>";
    echo "<p><a href='assets/img/bg-battlefield2.jpg'>View Image</a></p>";
} else {
    echo "<h2 style='color:red'>✗ Failed to generate image</h2>";
    echo "<p>Check the output above for errors.</p>";
}

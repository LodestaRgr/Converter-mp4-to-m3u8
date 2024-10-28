<?php
session_start();

function generateUniqueFolderName($prefix = 'media_') {
    return uniqid($prefix, true);
}

$target_dir = "/var/www/public/uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if file is a actual video or fake video
if(isset($_POST["submit"])) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE); // Получение MIME-типа файла
    $mime = finfo_file($finfo, $_FILES["fileToUpload"]["tmp_name"]);
    finfo_close($finfo);

    if($mime == "video/mp4") {
        $uploadOk = 1;
    } else {
        echo "Sorry, only MP4 files are allowed.<br>";
        $uploadOk = 0;
    }
}

// Check file size
if ($_FILES["fileToUpload"]["size"] > 500000000) {
    echo "Sorry, your file is too large.<br>";
    $uploadOk = 0;
}

// Allow certain file formats
if($videoFileType != "mp4") {
    echo "Sorry, only MP4 files are allowed.<br>";
    $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.<br>";
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "The file ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " has been uploaded.<br>";

        // Generate a unique folder name
        $folder_name = generateUniqueFolderName();
        $output_dir = "/var/www/public/media/$folder_name";
        if (!mkdir($output_dir, 0777, true)) {
            echo "Failed to create directories...<br>";
        } else {
            echo "Directories created successfully.<br>";
        }

        $commands = [
            "ffmpeg -i " . escapeshellarg($target_file) . " -ss 20.00 -vframes 1 " . escapeshellarg("$output_dir/cover_h1.jpg"),
            "ffmpeg -i " . escapeshellarg($target_file) . " -ss 20.00 -vframes 1 -s 760x428 " . escapeshellarg("$output_dir/cover_l1.jpg"),
            "ffmpeg -ss 00:00:10 -t 3 -i " . escapeshellarg($target_file) . " -s 640x360 -r 2 " . escapeshellarg("$output_dir/preview.gif"),
            "ffmpeg -i " . escapeshellarg($target_file) . " -profile:v baseline -level 3.0 -s 800x480 -start_number 0 -hls_time 2 -force_key_frames 'expr:gte(t,n_forced*2)' -hls_list_size 0 -threads 5 -preset ultrafast -f hls " . escapeshellarg("$output_dir/480_out.m3u8"),
            "ffmpeg -i " . escapeshellarg($target_file) . " -profile:v baseline -level 3.0 -s 1280x720 -start_number 0 -hls_time 2 -force_key_frames 'expr:gte(t,n_forced*2)' -hls_list_size 0 -threads 5 -preset ultrafast -f hls " . escapeshellarg("$output_dir/720_out.m3u8"),
        ];

        $all_commands_executed = true;
        foreach ($commands as $command) {
            $output = shell_exec($command . " 2>&1");
            if (strpos($output, 'error') !== false) {
                $all_commands_executed = false;
            }
        }

        // Create master playlist only if all commands executed successfully
        if ($all_commands_executed) {
            $master_playlist = "#EXTM3U\n";
            $master_playlist .= "#EXT-X-STREAM-INF:BANDWIDTH=750000,RESOLUTION=854x480\n";
            $master_playlist .= "480_out.m3u8\n";
            $master_playlist .= "#EXT-X-STREAM-INF:BANDWIDTH=2000000,RESOLUTION=1280x720\n";
            $master_playlist .= "720_out.m3u8\n";

            if (file_put_contents("$output_dir/main.m3u8", $master_playlist) === false) {
                echo "Failed to create master playlist.<br>";
            } else {
                echo "Master playlist created successfully.<br>";
            }

            // Delete the original uploaded file
            if (unlink($target_file)) {
                echo "Original file deleted successfully.<br>";
            } else {
                echo "Failed to delete the original file.<br>";
            }

            // Save information about the uploaded file
            $metadata_file = "$output_dir/metadata.json";
            $metadata = [
                'original_filename' => basename($_FILES["fileToUpload"]["name"]),
                'folder_name' => $folder_name
            ];
            file_put_contents($metadata_file, json_encode($metadata));

            echo "Conversion completed. <a href='media/$folder_name/main.m3u8'>Play</a><br>";
        } else {
            echo "Failed to execute one or more commands.<br>";
        }
        echo "<a href='index.php'>Go back</a>";
    } else {
        echo "Sorry, there was an error uploading your file.<br>";
        echo "Error code: " . $_FILES["fileToUpload"]["error"] . "<br>";
    }
}
?>

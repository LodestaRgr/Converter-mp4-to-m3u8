<!DOCTYPE html>
<html>
<head>
    <title>HLS Portal</title>
    <script>
        function confirmDeletion(folder) {
            if (confirm("Вы точно уверены, что хотите удалить этот плейлист?")) {
                window.location.href = 'delete.php?folder=' + folder;
            }
        }
    </script>
</head>
<body>
    <h1>Converter mp4 to m3u8</h1>

    <h2>Upload Video</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        Select video to upload:
        <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="Upload Video" name="submit">
    </form>

    <h2>Available Playlists</h2>
    <ul>
    <?php
    $dir = 'media';
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($folder = readdir($dh)) !== false) {
                if ($folder != "." && $folder != "..") {
                    $metadata_file = "$dir/$folder/metadata.json";
                    if (file_exists($metadata_file)) {
                        $metadata = json_decode(file_get_contents($metadata_file), true);
                        $original_filename = $metadata['original_filename'];
                        echo "<li><a href='media/$folder/main.m3u8'>$folder</a> | $original_filename | <a href='#' onclick=\"confirmDeletion('$folder')\">Delete</a></li>";
                    }
                }
            }
            closedir($dh);
        }
    }
    ?>
    </ul>

    <h2>for windows tool <a href='_mp4_to_m3u8.zip'>download</a></h2>
</body>
</html>

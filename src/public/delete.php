<?php
if (isset($_GET['folder'])) {
    $folder = $_GET['folder'];
    $dir = "media/$folder";
    
    function rrmdir($dir) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file)) rrmdir($file); else unlink($file);
        }
        rmdir($dir);
    }
    
    rrmdir($dir);
    
    echo "Folder $folder deleted. <a href='index.php'>Go back</a>";
}
?>

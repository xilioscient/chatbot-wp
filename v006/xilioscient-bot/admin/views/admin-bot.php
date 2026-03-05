<?php
// admin-bot.php
$config_file = './../../xsbot-assets/config.json';
$upload_dir = './../../xsbot-assets/';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : ['banner' => '', 'profile' => ''];

    foreach (['banner', 'profile'] as $key) {
        if (!empty($_FILES[$key]['name'])) {
            $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
            $filename = $key . '.' . $ext;
            if (move_uploaded_file($_FILES[$key]['tmp_name'], $upload_dir . $filename)) {
                $config[$key] = $upload_dir . $filename . '?t=' . time(); // Cache busting
            }
        }
    }
    file_put_contents($config_file, json_encode($config));
    echo "Immagini salvate con successo!";
}
?>

<form method="post" enctype="multipart/form-data">
    <label>Immagine Banner Header:</label><br>
    <input type="file" name="banner"><br><br>
    <label>Immagine Profilo Bot:</label><br>
    <input type="file" name="profile"><br><br>
    <button type="submit">Salva Configurazione</button>
</form>
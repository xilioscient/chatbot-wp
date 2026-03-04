<?php
// admin/views/admin-bot.php

// 1. Percorsi lato server (per il salvataggio dei file)
$plugin_root = plugin_dir_path(__FILE__) . '../../';
$config_file = $plugin_root . 'xsbot-assets/config.json';
$upload_dir = $plugin_root . 'xsbot-assets/';

$upload_url = plugins_url('xsbot-assets/', __FILE__ . '/../../');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : ['banner' => '', 'profile' => ''];

    foreach (['banner', 'profile'] as $key) {
        if (!empty($_FILES[$key]['name'])) {
            $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
            $filename = $key . '.' . $ext;
            
            if (move_uploaded_file($_FILES[$key]['tmp_name'], $upload_dir . $filename)) {
                $config[$key] = $upload_url . $filename . '?t=' . time(); 
            }
        }
    }
    
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
    
    file_put_contents($config_file, json_encode($config));
    echo "<p style='color:green;'>Configurazione salvata correttamente!</p>";
}
?>

<form method="post" enctype="multipart/form-data">
    <label>Immagine Banner Header:</label><br>
    <input type="file" name="banner"><br><br>
    <label>Immagine Profilo Bot:</label><br>
    <input type="file" name="profile"><br><br>
    <button type="submit">Salva Configurazione</button>
</form>
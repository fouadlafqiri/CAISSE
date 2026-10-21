<?php
require_once __DIR__ . '/auth.php';
require_login(['admin']);
require_once('db_connection.php');

function saveSettings(PDO $pdo, array $settings): void {
  $stmt = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
  foreach ($settings as $key => $value) $stmt->execute([':key'=>$key, ':value'=>$value]);
}
function setting(array $settings, string $key, string $default=''): string { return $settings[$key] ?? $default; }

if (isset($_GET['backup'])) {
  header('Content-Type: application/sql');
  header('Content-Disposition: attachment; filename="digital_7anout_' . date('Y-m-d_H-i-s') . '.sql"');
  echo "SET FOREIGN_KEY_CHECKS=0;\n";
  $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
  foreach ($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    echo "\nDROP TABLE IF EXISTS `$table`;\n$create;\n";
    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $columns = implode(',', array_map(fn($column)=>"`$column`", array_keys($row)));
      $values = implode(',', array_map(fn($value)=>$value === null ? 'NULL' : $pdo->quote((string)$value), array_values($row)));
      echo "INSERT INTO `$table` ($columns) VALUES ($values);\n";
    }
  }
  echo "SET FOREIGN_KEY_CHECKS=1;\n";
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (isset($_POST['save_store'])) {
      $logo = $_POST['current_logo'] ?? '';
      if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($extension, ['png','jpg','jpeg','gif','webp'], true)) {
          $logo = '../imgs/store-logo.' . $extension;
          move_uploaded_file($_FILES['logo']['tmp_name'], $logo);
        }
      }
      saveSettings($pdo, ['store_name'=>trim($_POST['store_name']??''), 'store_phone'=>trim($_POST['store_phone']??''), 'store_address'=>trim($_POST['store_address']??''), 'store_email'=>trim($_POST['store_email']??''), 'store_logo'=>$logo]);
    } elseif (isset($_POST['save_billing'])) {
      saveSettings($pdo, ['invoice_header'=>trim($_POST['invoice_header']??''), 'invoice_footer'=>trim($_POST['invoice_footer']??''), 'auto_print'=>(($_POST['auto_print']??'0')==='1'?'1':'0'), 'wifi_ssid'=>trim($_POST['wifi_ssid']??''), 'wifi_password'=>trim($_POST['wifi_password']??'')]);
    } elseif (isset($_POST['save_date_format'])) {
      $dateDisplayFormat = trim($_POST['date_display_format'] ?? 'full');
      $allowedDateFormats = ['full', 'short', 'day', 'day-time', 'iso'];
      if (!in_array($dateDisplayFormat, $allowedDateFormats, true)) {
        $dateDisplayFormat = 'full';
      }
      saveSettings($pdo, ['date_display_format'=>$dateDisplayFormat]);
    } elseif (isset($_POST['save_currency'])) {
      saveSettings($pdo, ['currency'=>trim($_POST['currency']??''), 'currency_symbol'=>trim($_POST['currency_symbol']??''), 'tax_rate'=>(string)max(0,min(100,(float)($_POST['tax_rate']??0)))]);
    } elseif (isset($_POST['save_payment_methods'])) {
      $cash=isset($_POST['payment_cash']); $card=isset($_POST['payment_card']); $transfer=isset($_POST['payment_transfer']);
      if (!$cash && !$card && !$transfer) $cash=true;
      saveSettings($pdo, ['payment_cash'=>$cash?'1':'0', 'payment_card'=>$card?'1':'0', 'payment_transfer'=>$transfer?'1':'0']);
    } elseif (isset($_POST['create_server'])) {
      $username=trim($_POST['new_username']??''); $password=$_POST['new_password']??'';
      if (strlen($username)<3 || strlen($username)>255 || strlen($password)<4) {
        throw new InvalidArgumentException('Le nom du serveur doit contenir au moins 3 caractères et le mot de passe au moins 4 caractères.');
      }
      $stmt=$pdo->prepare('INSERT INTO users (username,password,role) VALUES (:username,:password,:role)');
      $stmt->execute([':username'=>$username,':password'=>password_hash($password,PASSWORD_DEFAULT),':role'=>'server']);
    } elseif (isset($_POST['update_user'])) {
      $id=(int)($_POST['id']??0); $username=trim($_POST['username']??''); $role=$_POST['role']??'';
      if ($id>0 && $username!=='' && in_array($role,['admin','server'],true)) {
        if ($id===(int)($_SESSION['user_id']??0) && $role!=='admin') {
          throw new InvalidArgumentException('Vous ne pouvez pas retirer votre propre rôle administrateur.');
        }
        $roleStmt=$pdo->prepare('SELECT role FROM users WHERE id=:id');
        $roleStmt->execute([':id'=>$id]);
        $oldRole=$roleStmt->fetchColumn();
        if ($oldRole==='admin' && $role==='server' && (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()<=1) {
          throw new InvalidArgumentException('Il doit rester au moins un administrateur.');
        }
        $stmt=$pdo->prepare('UPDATE users SET username=:username,role=:role WHERE id=:id');
        $stmt->execute([':username'=>$username,':role'=>$role,':id'=>$id]);
        if($id===(int)($_SESSION['user_id']??0)) $_SESSION['username']=$username;
      }
    } elseif (isset($_POST['delete_user'])) {
      $id=(int)($_POST['id']??0);
      if ($id>0 && $id!==(int)($_SESSION['user_id']??0)) {
        $deleteRole=$pdo->prepare('SELECT role FROM users WHERE id=:id');
        $deleteRole->execute([':id'=>$id]);
        if ($deleteRole->fetchColumn()==='admin' && (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()<=1) {
          throw new InvalidArgumentException('Il doit rester au moins un administrateur.');
        }
        $stmt=$pdo->prepare('DELETE FROM users WHERE id=:id');
        $stmt->execute([':id'=>$id]);
      }
    } elseif (isset($_POST['restore_backup']) && isset($_FILES['backup_file']) && $_FILES['backup_file']['error']===UPLOAD_ERR_OK) {
      if (strtolower(pathinfo($_FILES['backup_file']['name'],PATHINFO_EXTENSION))==='sql') {
        $sql=file_get_contents($_FILES['backup_file']['tmp_name']);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); $pdo->exec($sql); $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
      }
    }
    $_SESSION['settings_message']='Modifications enregistrées.';
  } catch (Throwable $e) {
    $_SESSION['settings_message']='Opération impossible : '.$e->getMessage();
  }
  header('Location: parametres.php'); exit();
}

$settings=$pdo->query('SELECT setting_key,setting_value FROM app_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$users=$pdo->query('SELECT id,username,role FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$editUserId=(int)($_GET['edit_user']??0);
$message=$_SESSION['settings_message']??''; unset($_SESSION['settings_message']);
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Paramètres - Café / Restaurant</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/global.css"><link rel="stylesheet" href="../css/parametres.css"><?php render_app_date_script($pdo); ?></head><body>
<header class="header"><div class="header-left"><span><i class="fas fa-user"></i> Administrateur : <?=strtoupper(htmlspecialchars($_SESSION['username']??'ADMIN'))?></span></div><div class="header-center"><span id="current-time"></span></div><div class="header-right"><button class="close-btn" onclick="location.href='menu.php'">✕</button></div></header>
<div class="container my-4"><h2 class="mb-3"><i class="fas fa-sliders-h"></i> Paramètres du Système</h2><?php if($message): ?><div class="alert alert-info"><?=htmlspecialchars($message)?></div><?php endif; ?>

<div class="card"><div class="card-header-custom"><div class="section-title"><i class="fas fa-store"></i> Informations du magasin</div><div class="note">Ces informations apparaîtront sur les factures.</div></div><div class="card-body-custom"><form method="post" enctype="multipart/form-data"><input type="hidden" name="current_logo" value="<?=htmlspecialchars(setting($settings,'store_logo'))?>"><div class="row g-3">
<div class="col-md-6"><label class="form-label"><i class="fas fa-building"></i> Nom du café / restaurant</label><input name="store_name" class="form-control" type="text" value="<?=htmlspecialchars(setting($settings,'store_name','Café'))?>" required></div>
<div class="col-md-6"><label class="form-label"><i class="fas fa-phone"></i> Téléphone</label><input name="store_phone" class="form-control" type="text" value="<?=htmlspecialchars(setting($settings,'store_phone','+212661803100'))?>"></div>
<div class="col-md-12"><label class="form-label"><i class="fas fa-map-marker-alt"></i> Adresse</label><textarea name="store_address" class="form-control" rows="2"><?=htmlspecialchars(setting($settings,'store_address','Quartier Mohammadi, Casablanca'))?></textarea></div>
<div class="col-md-6"><label class="form-label"><i class="fas fa-envelope"></i> E-mail</label><input name="store_email" class="form-control" type="email" value="<?=htmlspecialchars(setting($settings,'store_email','contact@epicerie.ma'))?>"></div>
<div class="col-md-6"><label class="form-label"><i class="fas fa-image"></i> Logo (aperçu)</label><div class="d-flex gap-2 align-items-center"><?php if(setting($settings,'store_logo')): ?><img src="<?=htmlspecialchars(setting($settings,'store_logo'))?>" alt="logo" class="logo-preview rounded"><?php endif; ?><input name="logo" class="form-control" type="file" accept="image/*"></div></div>
<div class="col-12 text-end"><button name="save_store" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les informations</button></div></div></form></div></div>

<div class="card"><div class="card-header-custom"><div class="section-title"><i class="fas fa-file-invoice"></i> Facturation, Wi-Fi & Impression</div><div class="note">Les informations Wi-Fi sont imprimées en bas du ticket.</div></div><div class="card-body-custom"><form method="post"><div class="row g-3"><div class="col-md-6"><label class="form-label"><i class="fas fa-heading"></i> En-tête ticket</label><input name="invoice_header" class="form-control" value="<?=htmlspecialchars(setting($settings,'invoice_header','Café - Ticket'))?>"></div><div class="col-md-6"><label class="form-label"><i class="fas fa-shoe-prints"></i> Pied de page</label><input name="invoice_footer" class="form-control" value="<?=htmlspecialchars(setting($settings,'invoice_footer','Merci pour votre visite !'))?>"></div><div class="col-md-6"><label class="form-label"><i class="fas fa-wifi"></i> Nom du réseau Wi-Fi (SSID)</label><input name="wifi_ssid" class="form-control" value="<?=htmlspecialchars(setting($settings,'wifi_ssid'))?>"></div><div class="col-md-6"><label class="form-label"><i class="fas fa-key"></i> Mot de passe Wi-Fi</label><input name="wifi_password" class="form-control" value="<?=htmlspecialchars(setting($settings,'wifi_password'))?>"></div><div class="col-md-6"><label class="form-label"><i class="fas fa-print"></i> Impression automatique après vente</label><select name="auto_print" class="form-select"><option value="1" <?=setting($settings,'auto_print','0')==='1'?'selected':''?>>Oui</option><option value="0" <?=setting($settings,'auto_print','0')!=='1'?'selected':''?>>Non</option></select></div><div class="col-12 text-end"><button name="save_billing" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer facturation</button></div></div></form></div></div>

<div class="card"><div class="card-header-custom"><div class="section-title"><i class="fas fa-calendar-alt"></i> Affichage de la date</div><div class="note">Ce format est appliqué pour tous les utilisateurs du système.</div></div><div class="card-body-custom"><form method="post"><div class="row g-3"><div class="col-md-8"><label class="form-label"><i class="fas fa-clock"></i> Format d’affichage</label><select name="date_display_format" class="form-select"><option value="full" <?=setting($settings,'date_display_format','full')==='full'?'selected':''?>>Mercredi 21/10/2026 00:01:17</option><option value="short" <?=setting($settings,'date_display_format','full')==='short'?'selected':''?>>21/10/2026 00:01:17</option><option value="day" <?=setting($settings,'date_display_format','full')==='day'?'selected':''?>>Mercredi 21/10/2026</option><option value="day-time" <?=setting($settings,'date_display_format','full')==='day-time'?'selected':''?>>Mercredi 21/10/2026 00:01</option><option value="iso" <?=setting($settings,'date_display_format','full')==='iso'?'selected':''?>>2026-10-21 00:01:17</option></select></div><div class="col-12 text-end"><button name="save_date_format" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer le format</button></div></div></form></div></div>

<div class="card"><div class="card-header-custom"><div class="section-title"><i class="fas fa-credit-card"></i> Méthodes de paiement</div><div class="note">Activer / Désactiver les modes acceptés à la caisse.</div></div><div class="card-body-custom"><form method="post" class="row g-3"><div class="col-md-3"><div class="form-check"><input name="payment_cash" class="form-check-input" type="checkbox" id="cash" <?=setting($settings,'payment_cash','1')==='1'?'checked':''?>><label class="form-check-label" for="cash"><i class="fas fa-money-bill-wave"></i> Espèces</label></div></div><div class="col-md-3"><div class="form-check"><input name="payment_card" class="form-check-input" type="checkbox" id="card" <?=setting($settings,'payment_card','1')==='1'?'checked':''?>><label class="form-check-label" for="card"><i class="fas fa-credit-card"></i> Carte</label></div></div><div class="col-md-3"><div class="form-check"><input name="payment_transfer" class="form-check-input" type="checkbox" id="transfer" <?=setting($settings,'payment_transfer','0')==='1'?'checked':''?>><label class="form-check-label" for="transfer"><i class="fas fa-exchange-alt"></i> Virement</label></div></div><div class="col-12 text-end"><button name="save_payment_methods" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer méthodes</button></div></form></div></div>

<div class="card"><div class="card-header-custom"><div class="section-title"><i class="fas fa-users"></i> Utilisateurs & Rôles</div><div class="note">Les serveurs accèdent à la caisse, aux rapports jour/semaine, aux paiements et aux tickets.</div></div><div class="card-body-custom">
<form method="post" class="row g-3 align-items-end mb-4"><div class="col-md-5"><label class="form-label">Nom du nouveau serveur</label><input name="new_username" class="form-control" minlength="3" maxlength="255" autocomplete="off" required></div><div class="col-md-5"><label class="form-label">Mot de passe (4 caractères minimum)</label><input name="new_password" type="password" class="form-control" minlength="4" autocomplete="new-password" required></div><div class="col-md-2"><button name="create_server" class="btn btn-success w-100"><i class="fas fa-user-plus"></i> Ajouter</button></div></form>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>Nom</th><th>Rôle</th><th>Actions</th></tr></thead><tbody><?php foreach($users as $user): ?><tr><?php if($editUserId===(int)$user['id']): ?><form method="post"><td><?=$user['id']?><input type="hidden" name="id" value="<?=$user['id']?>"></td><td><input name="username" class="form-control" value="<?=htmlspecialchars($user['username'])?>" required></td><td><select name="role" class="form-select"><option value="admin" <?=$user['role']==='admin'?'selected':''?>>Administrateur</option><option value="server" <?=$user['role']==='server'?'selected':''?>>Serveur</option></select></td><td><button name="update_user" class="btn btn-sm btn-success"><i class="fas fa-save"></i> Enregistrer</button> <a href="parametres.php" class="btn btn-sm btn-secondary">Annuler</a></td></form><?php else: ?><td><?=$user['id']?></td><td><?=htmlspecialchars($user['username'])?></td><td><span class="badge bg-success"><?=$user['role']==='admin'?'Administrateur':'Serveur'?></span></td><td><a href="parametres.php?edit_user=<?=$user['id']?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i> Modifier</a> <form method="post" style="display:inline"><input type="hidden" name="id" value="<?=$user['id']?>"><button name="delete_user" class="btn btn-sm btn-outline-danger" <?=$user['id']===(int)($_SESSION['user_id']??0)?'disabled':''?> onclick="return confirm('Supprimer cet utilisateur ?')"><i class="fas fa-trash"></i> Supprimer</button></form></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div></div></div>

<div class="card"><div class="card-header-custom"><div class="section-title"><i class="fas fa-database"></i> Sauvegarde & Restauration</div><div class="note">Exporter/Importer la base de données (SQL).</div></div><div class="card-body-custom"><div class="d-flex gap-2 flex-wrap"><a href="parametres.php?backup=1" class="btn btn-outline-primary"><i class="fas fa-download"></i> Télécharger sauvegarde</a><form method="post" enctype="multipart/form-data" class="d-flex gap-2" onsubmit="return confirm('Restaurer cette sauvegarde et remplacer les données actuelles ?')"><input type="file" name="backup_file" class="form-control" accept=".sql" required><button name="restore_backup" class="btn btn-outline-danger"><i class="fas fa-upload"></i> Restaurer depuis fichier</button></form></div></div></div>
</div><script src="../js/global-time.js"></script></body></html>

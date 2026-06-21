<?php
session_start();
require_once('db_connection.php');
$currencySymbol=$pdo->query("SELECT COALESCE((SELECT setting_value FROM app_settings WHERE setting_key='currency_symbol'),'DH')")->fetchColumn();

if (isset($_POST['delete_payment'])) {
    $stmt = $pdo->prepare('DELETE FROM payments WHERE id=:id');
    $stmt->execute([':id'=>(int)$_POST['id']]);
    header('Location: reglement.php'); exit();
}

$clientId = (int)($_GET['client'] ?? 0);
$amount = trim($_GET['amount'] ?? '');
$method = trim($_GET['method'] ?? '');
$date = trim($_GET['date'] ?? '');
$where = [];
$params = [];
if ($clientId > 0) { $where[] = 's.client_id=:client'; $params[':client'] = $clientId; }
if ($amount !== '') { $where[] = 'pay.amount=:amount'; $params[':amount'] = $amount; }
if ($method !== '') { $where[] = 'pay.method=:method'; $params[':method'] = $method; }
if ($date !== '') { $where[] = 'DATE(pay.payment_date)=:date'; $params[':date'] = $date; }

$sql = "SELECT pay.*, s.id AS sale_id, COALESCE(c.name, 'Client libre') AS client_name FROM payments pay LEFT JOIN sales s ON s.id=pay.sale_id LEFT JOIN clients c ON c.id=s.client_id";
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY pay.payment_date DESC, pay.id DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$clients = $pdo->query('SELECT id,name FROM clients ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$stats = $pdo->query('SELECT COALESCE(SUM(amount),0) total, COUNT(*) count, COALESCE(AVG(amount),0) average FROM payments')->fetch(PDO::FETCH_ASSOC);
$methods = ['cash'=>'Espèces','card'=>'Carte bancaire','transfer'=>'Virement','check'=>'Chèque'];
?>
<!DOCTYPE html><html lang="fr"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Gestion des Paiements</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/global.css"><link rel="stylesheet" href="../css/reglement.css">
</head><body>
  <header class="header"><div class="header-left"><span><i class="fas fa-user"></i> User : <?=strtoupper(htmlspecialchars($_SESSION['username'] ?? 'ADMIN'))?></span></div><div class="header-center"><span id="current-time"></span></div><div class="header-right"><button class="close-btn" onclick="location.href='menu.php'">✕</button></div></header>
  <div class="container-fluid">
    <div class="page-title"><i class="fas fa-credit-card"></i><h2>Gestion des Paiements</h2></div>
    <div class="stats-section"><div class="row g-3">
      <div class="col-md-4"><div class="stat-card stat-card-primary"><div class="stat-card-icon"><i class="fas fa-money-bill-wave"></i></div><div class="stat-card-title">Montant Total</div><div class="stat-card-value"><?=number_format($stats['total'],2,'.',' ')?> <?=htmlspecialchars($currencySymbol)?></div></div></div>
      <div class="col-md-4"><div class="stat-card stat-card-success"><div class="stat-card-icon"><i class="fas fa-receipt"></i></div><div class="stat-card-title">Nombre de Paiements</div><div class="stat-card-value"><?=$stats['count']?></div></div></div>
      <div class="col-md-4"><div class="stat-card stat-card-info"><div class="stat-card-icon"><i class="fas fa-calculator"></i></div><div class="stat-card-title">Paiement Moyen</div><div class="stat-card-value"><?=number_format($stats['average'],2,'.',' ')?> <?=htmlspecialchars($currencySymbol)?></div><div class="stat-card-subtitle">Par transaction</div></div></div>
    </div></div>

    <div class="form-section"><h3><i class="fas fa-search"></i> Rechercher un Paiement</h3>
      <form method="get"><div class="row g-2">
        <div class="col-md-3"><label class="form-label"><i class="fas fa-user"></i> Nom du client</label><select name="client" class="form-select"><option value="">Tous les clients</option><?php foreach($clients as $client): ?><option value="<?=$client['id']?>" <?=$clientId===(int)$client['id']?'selected':''?>><?=htmlspecialchars($client['name'])?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label"><i class="fas fa-money-bill"></i> Montant payé</label><input type="number" name="amount" class="form-control" step="0.01" value="<?=htmlspecialchars($amount)?>" placeholder="Ex: 250.00"></div>
        <div class="col-md-2"><label class="form-label"><i class="fas fa-credit-card"></i> Méthode</label><select name="method" class="form-select"><option value="">Toutes</option><?php foreach($methods as $key=>$label): ?><option value="<?=$key?>" <?=$method===$key?'selected':''?>><?=$label?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label"><i class="fas fa-calendar"></i> Date du paiement</label><input type="date" name="date" class="form-control" value="<?=htmlspecialchars($date)?>"></div>
        <div class="col-md-1"><label class="form-label">&nbsp;</label><button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button></div>
      </div></form>
    </div>

    <div class="table-section"><div class="table-header"><h3><i class="fas fa-history"></i> Historique des Paiements</h3></div><div class="table-responsive"><table class="table table-hover">
      <thead><tr><th>#</th><th>Référence</th><th>Client</th><th>Montant</th><th>Méthode</th><th>Date</th><th>Heure</th><th>Actions</th></tr></thead><tbody>
      <?php foreach($payments as $index=>$payment): ?>
        <tr><td><?=$index+1?></td><td><strong>PAY-<?=date('Y',strtotime($payment['payment_date']))?>-<?=str_pad($payment['id'],3,'0',STR_PAD_LEFT)?></strong></td><td><?=htmlspecialchars($payment['client_name'])?></td><td><strong class="text-success"><?=number_format($payment['amount'],2,'.',' ')?> MAD</strong></td><td><span class="payment-badge payment-<?=htmlspecialchars($payment['method'])?>"><?=htmlspecialchars($methods[$payment['method']] ?? $payment['method'])?></span></td><td><?=date('d/m/Y',strtotime($payment['payment_date']))?></td><td><?=date('H:i',strtotime($payment['payment_date']))?></td><td><div class="action-btns"><button type="button" class="btn btn-view-table" data-info="Ticket TCK-<?=str_pad($payment['sale_id'],4,'0',STR_PAD_LEFT)?>"><i class="fas fa-eye"></i></button><button type="button" class="btn btn-print-table" onclick="window.print()"><i class="fas fa-print"></i></button><form method="post" style="display:inline"><input type="hidden" name="id" value="<?=$payment['id']?>"><button name="delete_payment" class="btn btn-delete-table" onclick="return confirm('Supprimer ce paiement ?')"><i class="fas fa-trash"></i></button></form></div></td></tr>
      <?php endforeach; ?>
      <?php if(!$payments): ?><tr><td colspan="8">Aucun paiement trouvé.</td></tr><?php endif; ?>
      </tbody></table></div><div class="pagination-section"><div class="pagination-info"><i class="fas fa-info-circle"></i> <?=count($payments)?> paiement(s) affiché(s)</div></div>
    </div>
  </div>
  <script src="../js/global-time.js"></script><script>document.querySelectorAll('.btn-view-table').forEach(button=>button.addEventListener('click',()=>alert(button.dataset.info)));</script>
</body></html>

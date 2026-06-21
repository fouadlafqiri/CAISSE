<?php
session_start();
require_once('db_connection.php');
$appSettings = $pdo->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$currencySymbol = $appSettings['currency_symbol'] ?? 'DH';
$invoiceHeader = $appSettings['invoice_header'] ?? 'Facture de Vente';
$invoiceFooter = $appSettings['invoice_footer'] ?? 'Merci pour votre visite !';

$ticketNumber = trim($_GET['ticket'] ?? '');
$ticketDate = trim($_GET['date'] ?? '');
$where = [];
$params = [];

if ($ticketNumber !== '') {
    $where[] = 's.id = :ticket_id';
    $params[':ticket_id'] = (int)preg_replace('/\D+/', '', $ticketNumber);
}
if ($ticketDate !== '') {
    $where[] = 'DATE(s.date) = :ticket_date';
    $params[':ticket_date'] = $ticketDate;
}

$sql = "SELECT s.id, s.date, s.total_amount, COALESCE(pay.method, s.payment_type) AS payment_method,
               COALESCE(c.name, 'Client libre') AS client_name,
               GROUP_CONCAT(CONCAT(p.name, ' x', si.quantity) ORDER BY si.id SEPARATOR '\n') AS details
        FROM sales s
        LEFT JOIN clients c ON c.id = s.client_id
        LEFT JOIN payments pay ON pay.sale_id = s.id
        LEFT JOIN sale_items si ON si.sale_id = s.id
        LEFT JOIN products p ON p.id = si.product_id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' GROUP BY s.id, s.date, s.total_amount, pay.method, s.payment_type, c.name ORDER BY s.date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
$methodLabels = ['cash' => 'Espèces', 'card' => 'Carte', 'transfer' => 'Virement'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tickets / Factures</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/tickets.css">
</head>
<body>
  <header class="header">
    <div class="header-left"><span><i class="fas fa-user"></i> User : <?=strtoupper(htmlspecialchars($_SESSION['username'] ?? 'ADMIN'))?></span></div>
    <div class="header-center"><span id="current-time"></span></div>
    <div class="header-right"><button class="close-btn" onclick="location.href='menu.php'">✕</button></div>
  </header>

  <div class="container mt-5">
    <h2 class="page-title"><i class="fas fa-receipt"></i> Gestion des Tickets</h2>
    <div class="card p-3 mb-4 search-bar">
      <form class="row g-3 align-items-center" method="get">
        <div class="col-md-4">
          <label class="form-label mb-1"><i class="fas fa-ticket-alt"></i> Numéro du ticket</label>
          <input type="text" name="ticket" class="form-control" placeholder="Ex: TCK-1024" value="<?=htmlspecialchars($ticketNumber)?>">
        </div>
        <div class="col-md-4">
          <label class="form-label mb-1"><i class="fas fa-calendar-alt"></i> Date</label>
          <input type="date" name="date" class="form-control" value="<?=htmlspecialchars($ticketDate)?>">
        </div>
        <div class="col-md-4 text-end mt-4">
          <button type="submit" class="btn btn-custom px-4"><i class="fas fa-search"></i> Rechercher</button>
        </div>
      </form>
    </div>

    <div class="card p-4">
      <h5><i class="fas fa-history"></i> Historique des Tickets</h5>
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
          <thead><tr><th>#</th><th>Numéro Ticket</th><th>Date</th><th>Montant Total</th><th>Méthode de Paiement</th><th>Client</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($tickets as $index => $ticket): $method = $methodLabels[$ticket['payment_method']] ?? $ticket['payment_method']; ?>
            <tr>
              <td><?=$index + 1?></td>
              <td><strong>TCK-<?=str_pad($ticket['id'], 4, '0', STR_PAD_LEFT)?></strong></td>
              <td><?=htmlspecialchars(date('Y-m-d H:i', strtotime($ticket['date'])))?></td>
              <td class="amount-cell"><?=number_format($ticket['total_amount'], 2, '.', ' ')?> <?=htmlspecialchars($currencySymbol)?></td>
              <td><span class="payment-badge"><?=htmlspecialchars($method)?></span></td>
              <td><?=htmlspecialchars($ticket['client_name'])?></td>
              <td>
                <button type="button" class="btn btn-sm btn-success btn-view-ticket" data-details="<?=htmlspecialchars($ticket['details'] ?? '')?>"><i class="fas fa-eye"></i> Voir</button>
                <button type="button" class="btn btn-sm btn-primary btn-print-ticket"><i class="fas fa-print"></i> Imprimer</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$tickets): ?><tr><td colspan="7">Aucun ticket trouvé.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="../js/global-time.js"></script>
  <script>
    const invoiceHeader = <?=json_encode($invoiceHeader, JSON_UNESCAPED_UNICODE)?>;
    const invoiceFooter = <?=json_encode($invoiceFooter, JSON_UNESCAPED_UNICODE)?>;
    document.querySelectorAll('.btn-view-ticket').forEach(button => {
      button.addEventListener('click', () => alert(button.dataset.details || 'Aucun article.'));
    });
    document.querySelectorAll('.btn-print-ticket').forEach(button => {
      button.addEventListener('click', () => {
        const row = button.closest('tr');
        const popup = window.open('', '_blank', 'width=700,height=500');
        popup.document.write(`<html><head><title>${row.children[1].textContent}</title></head><body><h2>${invoiceHeader}</h2><table border="1">${row.outerHTML}</table><p>${invoiceFooter}</p></body></html>`);
        popup.document.close();
        popup.print();
      });
    });
  </script>
</body>
</html>

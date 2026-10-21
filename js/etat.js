document.querySelectorAll('.btn-export').forEach(button => {
  button.addEventListener('click', () => {
    const table = button.closest('.table-card').querySelector('table');
    const csv = Array.from(table.rows).map(row =>
      Array.from(row.cells).map(cell => `"${cell.innerText.replaceAll('"', '""')}"`).join(';')
    ).join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob(['\uFEFF', csv], {type: 'text/csv;charset=utf-8'}));
    const period = new URLSearchParams(window.location.search).get('period') || 'daily';
    const type = button.closest('.table-card').dataset.report || 'rapport';
    link.download = `rapport-${period}-${type}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
  });
});

document.querySelectorAll('.btn-print').forEach(button => {
  button.addEventListener('click', () => {
    const report = button.closest('.report-table');
    if (report && report.dataset.report === 'sales') {
      document.body.classList.add('print-sales-report');
    }
    window.print();
  });
});

window.addEventListener('afterprint', () => {
  document.body.classList.remove('print-sales-report');
});

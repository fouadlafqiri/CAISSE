document.querySelectorAll('.btn-export').forEach(button => {
  button.addEventListener('click', () => {
    const table = button.closest('.table-card').querySelector('table');
    const csv = Array.from(table.rows).map(row =>
      Array.from(row.cells).map(cell => `"${cell.innerText.replaceAll('"', '""')}"`).join(';')
    ).join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], {type: 'text/csv;charset=utf-8'}));
    link.download = 'rapport.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  });
});

document.querySelectorAll('.btn-print').forEach(button => {
  button.addEventListener('click', () => window.print());
});

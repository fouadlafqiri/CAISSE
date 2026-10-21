function formatCurrentDate(date, format = window.appDateFormat || 'full') {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
  const time = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  const dateShort = date.toLocaleDateString('fr-FR');
  const iso = date.toISOString().slice(0, 19).replace('T', ' ');

  switch (format) {
    case 'short':
      return `${dateShort} ${time}`;
    case 'iso':
      return iso;
    case 'day':
      return `${days[date.getDay()]} ${dateShort}`;
    case 'day-time':
      return `${days[date.getDay()]} ${dateShort} ${time}`;
    case 'full':
    default:
      return `${days[date.getDay()]} ${dateShort} ${time}`;
  }
}

setInterval(() => {
  const current_time = document.getElementById('current-time');
  if (!current_time) return;
  const d = new Date();
  current_time.textContent = formatCurrentDate(d);
}, 100);
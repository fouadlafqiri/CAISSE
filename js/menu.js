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

function updateTime() {
    const now = new Date();
    const dateText = formatCurrentDate(now);
    const currentTimeNode = document.getElementById('current-time');
    if (!currentTimeNode) return;
    currentTimeNode.innerHTML = '<i class="fas fa-clock me-1"></i>' + dateText;
}

updateTime();
setInterval(updateTime, 1000);
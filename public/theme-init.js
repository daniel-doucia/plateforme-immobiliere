// Applique le thème choisi avant l'affichage de la page (évite un flash blanc)
(function () {
    try {
        var choix = localStorage.getItem('theme');
        var systemeSombre = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (choix === 'dark' || (choix !== 'light' && systemeSombre)) {
            document.documentElement.classList.add('dark');
        }
    } catch (e) {
        /* stockage indisponible : on reste en mode clair */
    }
})();

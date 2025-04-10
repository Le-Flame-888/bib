// Fonction pour initialiser les tooltips Bootstrap
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Fonction pour la recherche en temps réel
function initLiveSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        let timeout = null;
        searchInput.addEventListener('keyup', function(e) {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                const query = e.target.value;
                if (query.length >= 3) {
                    fetch(`${APP_URL}/search?q=${encodeURIComponent(query)}&ajax=1`)
                        .then(response => response.json())
                        .then(data => {
                            updateSearchResults(data.results);
                        })
                        .catch(error => console.error('Erreur:', error));
                }
            }, 500);
        });
    }
}

// Fonction pour mettre à jour les résultats de recherche
function updateSearchResults(results) {
    const resultsContainer = document.querySelector('.search-results');
    if (resultsContainer) {
        if (results.length > 0) {
            const html = results.map(book => `
                <div class="search-result-item">
                    <h5><a href="${APP_URL}/books/view/${book.id}">${book.title}</a></h5>
                    <p class="text-muted mb-1">ISBN: ${book.isbn}</p>
                    <p class="mb-0">Catégorie: ${book.category_name}</p>
                </div>
            `).join('');
            resultsContainer.innerHTML = html;
        } else {
            resultsContainer.innerHTML = '<p class="text-center text-muted">Aucun résultat trouvé</p>';
        }
    }
}

// Validation des formulaires
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Gestion des notifications
function initNotifications() {
    const notificationBell = document.querySelector('.notification-bell');
    if (notificationBell) {
        setInterval(() => {
            fetch(`${APP_URL}/notifications/check`)
                .then(response => response.json())
                .then(data => {
                    if (data.count > 0) {
                        notificationBell.querySelector('.badge').textContent = data.count;
                        notificationBell.classList.remove('d-none');
                    }
                })
                .catch(error => console.error('Erreur:', error));
        }, 60000); // Vérifier toutes les minutes
    }
}

// Gestion des emprunts
function initLoanActions() {
    // Prolongation d'emprunt
    document.querySelectorAll('.extend-loan').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const loanId = this.dataset.loanId;
            if (confirm('Voulez-vous prolonger cet emprunt ?')) {
                fetch(`${APP_URL}/loans/extend/${loanId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Erreur:', error));
            }
        });
    });

    // Retour de livre
    document.querySelectorAll('.return-book').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const loanId = this.dataset.loanId;
            if (confirm('Confirmer le retour de ce livre ?')) {
                fetch(`${APP_URL}/loans/return/${loanId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Erreur:', error));
            }
        });
    });
}

// Gestion du mode sombre
function initDarkMode() {
    const darkModeToggle = document.querySelector('.dark-mode-toggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
        });

        // Appliquer le mode sombre au chargement si nécessaire
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initLiveSearch();
    initFormValidation();
    initNotifications();
    initLoanActions();
    initDarkMode();
});
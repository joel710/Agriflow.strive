document.addEventListener('DOMContentLoaded', function () {
    const profileButton = document.getElementById('profile-menu-button');
    const profileDropdown = document.getElementById('profile-dropdown');
    const authButtons = document.getElementById('auth-buttons');
    const userProfile = document.getElementById('user-profile');
    const userEmail = document.getElementById('user-email');

    // Gestion du menu déroulant du profil
    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', function () {
            const expanded = profileButton.getAttribute('aria-expanded') === 'true';
            profileButton.setAttribute('aria-expanded', !expanded);
            profileDropdown.classList.toggle('hidden');
        });

        // Fermer le menu lors d'un clic à l'extérieur
        document.addEventListener('click', function (event) {
            if (!profileButton.contains(event.target) && !profileDropdown.contains(event.target)) {
                profileButton.setAttribute('aria-expanded', 'false');
                profileDropdown.classList.add('hidden');
            }
        });
    }

    // Vérifier l'état de connexion et mettre à jour l'interface
    function checkAuthState() {
        const user = JSON.parse(localStorage.getItem('user'));
        if (user && user.email) {
            authButtons.classList.add('hidden');
            userProfile.classList.remove('hidden');
            userEmail.textContent = user.email;

            // Afficher/masquer les éléments en fonction du rôle
            const producerElements = document.querySelectorAll('.producer-only');
            const clientElements = document.querySelectorAll('.client-only');

            if (user.role === 'producteur') {
                producerElements.forEach(el => el.style.display = 'block');
                clientElements.forEach(el => el.style.display = 'none');
            } else if (user.role === 'client') {
                producerElements.forEach(el => el.style.display = 'none');
                clientElements.forEach(el => el.style.display = 'block');
            }
        } else {
            authButtons.classList.remove('hidden');
            userProfile.classList.add('hidden');
        }
    }

    // Vérifier l'état de connexion au chargement
    checkAuthState();

    // Gérer la déconnexion
    window.logout = function () {
        localStorage.removeItem('user');
        localStorage.removeItem('token');
        checkAuthState();
        window.location.href = 'accueil.html';
    };
});
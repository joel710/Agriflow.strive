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

            // Gérer la redirection du tableau de bord en fonction du rôle
            const dashboardLink = document.getElementById('dashboard-link');
            if (dashboardLink) {
                dashboardLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    const user = JSON.parse(localStorage.getItem('user'));
                    if (user && user.role) {
                        const dashboardUrl = user.role === 'producteur' ? '/agriflow/tableau-producteur.html' : '/agriflow/tableau-client.html';
                        console.log('Redirection vers:', dashboardUrl);
                        window.location.replace(dashboardUrl);
                    } else {
                        window.location.replace('/agriflow/login.html');
                    }

                });
            }
        } else {
            authButtons.classList.remove('hidden');
            userProfile.classList.add('hidden');
        }
    }

    // Vérifier l'état de connexion au chargement
    checkAuthState();

    // Gérer la déconnexion
    window.logout = async function () {
        try {
            await fetch('/agriflow/auth/process.php?action=logout');
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            checkAuthState();
            window.location.replace('/agriflow/accueil.html');
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
            // On déconnecte quand même localement en cas d'erreur
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            checkAuthState();
            window.location.replace('/agriflow/accueil.html');
        }
    };
});
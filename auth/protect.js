// Protection des pages avec vérification du rôle
async function protectPage() {
    try {
        const response = await fetch('/agriflow/auth/process.php?action=check_auth');
        console.log('Réponse brute:', response);
        const responseText = await response.text();
        console.log('Texte de la réponse:', responseText);
        const data = JSON.parse(responseText);

        if (!data.authenticated) {
            window.location.href = '/agriflow/login.html';
            return;
        }

        // Vérification du rôle pour chaque page
        const currentPage = window.location.pathname;

        if (currentPage.includes('tableau-producteur.html') && data.role !== 'producteur') {
            window.location.href = '/agriflow/login.html';
            return;
        }

        if (currentPage.includes('tableau-client.html') && data.role !== 'client') {
            window.location.href = '/agriflow/login.html';
            return;
        }

        // Ajout du bouton de déconnexion dans la navigation
        const nav = document.querySelector('nav') || document.createElement('nav');
        const logoutButton = document.createElement('button');
        logoutButton.className = 'text-gray-700 hover:text-primary ml-4';
        logoutButton.textContent = 'Déconnexion';
        logoutButton.onclick = logout;
        nav.appendChild(logoutButton);

        // Affichage du nom de l'utilisateur si disponible
        if (data.email) {
            const userInfo = document.createElement('span');
            userInfo.className = 'text-gray-600 mr-4';
            userInfo.textContent = data.email;
            nav.insertBefore(userInfo, logoutButton);
        }

    } catch (error) {
        console.error('Erreur de vérification d\'authentification:', error);
        window.location.href = '/agriflow/login.html';
    }
}

// Fonction de déconnexion
async function logout() {
    try {
        await fetch('/agriflow/auth/process.php?action=logout');
        window.location.href = '/agriflow/index.html';
    } catch (error) {
        console.error('Erreur lors de la déconnexion:', error);
    }
}

// Exécution de la protection au chargement de la page
document.addEventListener('DOMContentLoaded', protectPage);
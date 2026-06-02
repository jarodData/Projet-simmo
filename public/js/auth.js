 // ============================================
// RÈGLES D'ACCÈS SIMMo
// ============================================
// Visiteur non connecté : peut VOIR les annonces
//                         mais NE PEUT PAS :
//                         - Rechercher (filtres avancés)
//                         - Contacter un agent
//                         - Accéder au dashboard
//                         - Voir les recommandations IA
//                         - Souscrire à un plan
// ============================================

// Pages accessibles sans connexion
const PAGES_PUBLIQUES = [
    'index.html',
    'annonces.html',
    'annonce-detail.html',
    'login.html',
    'register.html',
    'login-agent.html',
    'register-agent.html',
    
]

// Pages réservées aux utilisateurs connectés
const PAGES_UTILISATEUR = [
    'recommandations.html',
    'recherche-contextuelle.html',
    'recherche-description.html',
    'mes-favoris.html',
]

// Pages réservées aux agents
const PAGES_AGENT = [
    'dashboard.html',
    'mes-annonces.html',
    'creer-annonce.html',
    'modifier-annonce.html',
    'mes-contacts.html',   
    'mes-messages.html',  
    'plan.html',
    'paiement.html',
]


function estConnecte() {
    return !!localStorage.getItem('token')  // ← était 'simmo_token'
}

// function estAgent() {
//     const role = localStorage.getItem('role')  // ← était 'simmo_role'
//     if (role) return role === 'agent'
//     const user = getUser()
//     return user?.role === 'agent'
// }

// Vérifier l'accès à la page courante
function verifierAccesPage() {
    const pageCourante = window.location.pathname.split('/').pop() || 'index.html'

    // Page réservée agent
    if (PAGES_AGENT.includes(pageCourante)) {
        if (!estConnecte()) {
            sauvegarderRedirection()
            window.location.href = 'login-agent.html'
            return false
        }
        // ✅ Pas de vérification estAgent() pour chats.html
        // car utilisateurs ET agents peuvent accéder
        if (pageCourante !== 'chats.html' && pageCourante !== 'chat.html') {
            if (!estAgent()) {
                window.location.href = 'index.html'
                return false
            }
        }
    }

    // Page réservée utilisateur connecté
    if (PAGES_UTILISATEUR.includes(pageCourante)) {
        if (!estConnecte()) {
            sauvegarderRedirection()
            afficherModalConnexion()
            return false
        }
    }

    return true
}
// Sauvegarder la page pour redirection après connexion
function sauvegarderRedirection() {
    localStorage.setItem('redirect_after_login', window.location.href)
}

// Rediriger après connexion
function redirigerApresConnexion() {
    const redirect = localStorage.getItem('redirect_after_login')
    localStorage.removeItem('redirect_after_login')
    window.location.href = redirect || 'index.html'
}

// Modal connexion requis
function afficherModalConnexion(message = null) {
    const msg = message || 'Connectez-vous pour accéder à cette fonctionnalité.'

    // Créer le modal s'il n'existe pas
    if (!document.getElementById('modalConnexionRequis')) {
        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="modalConnexionRequis" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-body p-5 text-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-4">
                                <i class="bi bi-lock-fill text-primary fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Connexion requise</h5>
                            <p class="text-muted mb-4" id="msgModalConnexion">
                                ${msg}
                            </p>
                            <div class="d-flex gap-2 justify-content-center">
                                <button class="btn btn-outline-secondary px-4"
                                    data-bs-dismiss="modal">
                                    Annuler
                                </button>
                                <a href="login.html" class="btn btn-primary px-4">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Se connecter
                                </a>
                            </div>
                            <p class="mt-3 mb-0 text-muted small">
                                Pas encore de compte ?
                                <a href="register.html" class="text-primary fw-semibold">
                                    S'inscrire gratuitement
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `)
    } else {
        document.getElementById('msgModalConnexion').textContent = msg
    }

    new bootstrap.Modal(
        document.getElementById('modalConnexionRequis')
    ).show()
}

// Vérifier une action spécifique
function verifierActionConnectee(action, redirectUrl = null) {
    if (!estConnecte()) {
        if (redirectUrl) sauvegarderRedirection()
        afficherModalConnexion(
            `Connectez-vous pour ${action}.`
        )
        return false
    }
    return true
}

// Init au chargement
document.addEventListener('DOMContentLoaded', () => {
    verifierAccesPage()
})
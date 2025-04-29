/**
 * Pour avoir une variable globale userLogged et userId
 */
userLogged = null;
userId = 0;

function logout() {
	this.event.preventDefault();
	return confirm("Etes-vous sûr de vouloir vous déconnecter ?",
		"Déconnexion",
		function () {
			window.location.href = '/?section=auth&page=connexion&action=logout';
		}, function () {
			console.log("Annulé");
		}
	);
}

document.addEventListener('DOMContentLoaded', function (){
	let logoutLinks = document.querySelectorAll('[data-action=logout]');
	if (logoutLinks){
		logoutLinks.forEach(function(link){
			link.addEventListener('click', () => logout())
		})
	}
})
/**
 * Vérification des informations de connexion
 */
document.addEventListener('DOMContentLoaded', function () {
	let checkKeep = document.querySelector('#keepConnexion');
	if (checkKeep){
		checkKeep.addEventListener('click', function (eve) {
			let keepOption = eve.currentTarget;
			if (keepOption.checked) {
				let message = "Votre identifiant et mot de passe seront enregistrés sur cet ordinateur si vous cliquez sur le bouton \"Valider\".";
				confirm(message,
					"Confirmation",
					function () {
						keepOption.checked = true;
					}, function () {
						keepOption.checked = false;
					}
				)
			}
		})
	}

	let form = document.querySelector('#formAuth');
	if (form) {
		form.addEventListener('submit', async function (event) {
			event.preventDefault();

			let champIdentifiant = document.querySelector('input#identifiant');
			let champPassword = document.querySelector('input#mdp');

			if (champIdentifiant.value.trim() === '') {
				alert("Vous devez remplir votre identifiant !");
				champIdentifiant.scrollIntoView({block: "center"});
				champIdentifiant.focus();
				return false;
			}

			if (champPassword.value.trim() === '') {
				alert("Vous devez remplir votre mot de passe !");
				champPassword.scrollIntoView({block: "center"});
				champPassword.focus();
				return false;
			}

			if (champIdentifiant && champPassword) {
				let formData = new FormData();
				formData.append('identifiant', champIdentifiant.value);

				let reponse = await postData('api', 'loginapi', 'checkUserExists', formData);
				if (!reponse.userExists) {
					alert("Cet utilisateur n'existe pas !");
					champIdentifiant.scrollIntoView({block: "center"});
					champIdentifiant.focus();
					return false;
				}

				if (!checkPasswordFormat(champPassword.value)) {
					alert("Le format du mot de passe n'est pas valide !");
					champPassword.scrollIntoView({block: "center"});
					champPassword.focus();
					return false;
				}

				// formData = new FormData();
				// formData.append('identifiant', champIdentifiant.value);
				formData.append('password', champPassword.value);
				reponse = await postData('api', 'loginapi', 'checkActive', formData);
				if (!reponse.userActive) {
					alert("Cet utilisateur n'est pas actif !");
					champIdentifiant.scrollIntoView({block: "center"});
					champIdentifiant.focus();
					champPassword.value = "";
					return false;
				}

				reponse = await postData('api', 'loginapi', 'checkAuth', formData);
				if (!reponse.userAuthentified) {
					alert("Les informations d'identification sont incorrectes !");
					champIdentifiant.scrollIntoView({block: "center"});
					champIdentifiant.focus();
					champPassword.value = "";
					return false;
				}

				let checkKeep = document.querySelector('#keepConnexion');
				if (checkKeep.checked) {
					checkKeep.keepConnect(new Date(), champIdentifiant.value);
				} else {
					checkKeep.looseConnect();
				}
			} else {
				return false;
			}

			form.submit();
		})


	}

	/**
	 * Récupération des cookies si nécessaire
	 */
	let valRemember = parseInt(Cookies.get('remember', cookiesOptions));
	if (valRemember === 1) {
		document.querySelector('#keepConnexion').setAttribute('checked', '');
		if (Cookies.get('identifiantlog', cookiesOptions) !== undefined) {
			document.querySelector('input#identifiant').value = Cookies.get('identifiantlog', cookiesOptions)
		}
	} else {
		document.querySelector('#keepConnexion').removeAttribute('checked');
		document.querySelector('input#identifiant').value = "";
	}
});
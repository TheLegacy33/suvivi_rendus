let main = document.getElementById("connexion-inscription");
let message = document.getElementById("afficher-phone-permier");
let disparait = document.getElementById("disparaitre-phone-premier");
let deuxiemessage = document.getElementById("afficher-phone-deuxieme");
let deuxiemedisparait = document.getElementById("disparaitre-phone-deuxieme");

let premiereimage = document.getElementById("image-phone");
let deuxiemeimage = document.getElementById("image-phone-2");
let troisiemeimage = document.getElementById("image-phone-3");
let derniereimage = document.getElementById("image-phone-4");
if (premiereimage) {
	premiereimage.addEventListener('click', function () {
		message.style.display = "flex";
		disparait.style.display = "none";
		deuxiemeimage.style.display = "flex";
		premiereimage.style.display = "none";
	});
}

if (deuxiemeimage) {
	deuxiemeimage.addEventListener('click', function () {
		message.style.display = "none";
		disparait.style.display = "flex";
		deuxiemeimage.style.display = "none";
		premiereimage.style.display = "flex";
	});
}

if (troisiemeimage) {
	troisiemeimage.addEventListener('click', function () {
		deuxiemessage.style.display = "flex";
		deuxiemedisparait.style.display = "none";
		derniereimage.style.display = "flex";
		troisiemeimage.style.display = "none";
	});
}

if (derniereimage) {
	derniereimage.addEventListener('click', function () {
		deuxiemessage.style.display = "none";
		deuxiemedisparait.style.display = "flex";
		derniereimage.style.display = "none";
		troisiemeimage.style.display = "flex";
	});
}

/**
 * Vérification des informations de connexion
 */

document.addEventListener('DOMContentLoaded', function () {
	let form = document.querySelector('#formAuth');
	if (form) {
		form.addEventListener('submit', async function (event) {
			event.preventDefault();

			let champIdentifiant = document.querySelector('input#identifiant');
			let champPassword = document.querySelector('input#mdp');

			if (champIdentifiant.value.trim() === '') {
				fetchData('localeapi', 'traductionSearch', { 'code': '00615' }, 'json').then(function (reponse) {
					champIdentifiant.focus();
					alert(reponse.traductionLibelle);
				})
				return false;
			}

			if (champPassword.value.trim() === '') {
				fetchData('localeapi', 'traductionSearch', { 'code': '00617' }, 'json').then(function (reponse) {
					champPassword.focus();
					alert(reponse.traductionLibelle);
				})

				return false;
			}

			if (champIdentifiant && champPassword) {
				let formData = new FormData();
				formData.append('identifiant', champIdentifiant.value);

				let reponse = await postData('api', 'checkUserExists', formData);
				if (!reponse.userExists) {
					fetchData('localeapi', 'traductionSearch', { 'code': '00852' }, 'json').then(function (reponse) {
						champIdentifiant.focus();
						alert(reponse.traductionLibelle);
					})

					return false;
				}

				if (!checkPasswordFormat(champPassword.value)) {
					fetchData('localeapi', 'traductionSearch', { 'code': '00853' }, 'json').then(function (reponse) {
						champPassword.focus();
						alert(reponse.traductionLibelle);
					})

					return false;
				}

				formData = new FormData();
				formData.append('identifiant', champIdentifiant.value);
				formData.append('password', champPassword.value);
				reponse = await postData('api', 'checkActive', formData);
				if (!reponse.userActive) {
					fetchData('localeapi', 'traductionSearch', { 'code': '00854' }, 'json').then(function (reponse) {
						champIdentifiant.focus();
						champPassword.value = "";
						alert(reponse.traductionLibelle);
					})

					return false;
				}

				reponse = await postData('api', 'checkAuth', formData);
				if (!reponse.userAuthentified) {
					fetchData('localeapi', 'traductionSearch', { 'code': '00855' }, 'json').then(function (reponse) {
						champIdentifiant.focus();
						champPassword.value = "";
						alert(reponse.traductionLibelle);
					})

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

		let checkKeep = document.querySelector('#keepConnexion');
		checkKeep.addEventListener('click', function (eve) {
			let keepOption = eve.currentTarget;
			if (keepOption.checked) {
				fetchData('localeapi', 'traductionSearch', { 'code': '00903' }, 'json').then(function (reponse) {

					confirm(reponse.traductionLibelle,
						"Confirmation",
						function () {
							keepOption.checked = true;
						}, function () {
							keepOption.checked = false;
						}
					)
				})

			}
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
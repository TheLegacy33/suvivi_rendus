/**
 * Redéfinition de la fonction Alert
 */
window.alert = function (message, title = "Informations", btns = buttons.ok, viewLoadingImag = false, callbackValid) {
	const myMsgBox = new MessageBox();
	myMsgBox.setTitle(title);
	myMsgBox.setMessage(message);
	if (btns !== buttons.none) {
		myMsgBox.setButtons(btns, callbackValid);
	}
	if (viewLoadingImag) {
		myMsgBox.addLoadingImg();
	}
	myMsgBox.show();
}

/**
 * Redéfinition de la fonction Confirm
 */
window.confirm = function (message, title = 'Confirmation', callbackValid, callbackCancel, btns = buttons.okcancel) {
	const myMsgBox = new MessageBox();
	myMsgBox.setTitle(title);
	myMsgBox.setMessage(message);
	myMsgBox.setButtons(btns, callbackValid, callbackCancel);
	myMsgBox.show();
}

/**
 * Pour le bouton "Rester Connecté"
 */
const cookiesOptions = {
	expires: 365,
	path: '/',
	domain: '',
	samesite: 'Lax'
};

HTMLInputElement.prototype.keepConnect = function (dateLog, identifiantlog) {
	Cookies.set('remember', 1, cookiesOptions);
	Cookies.set('datelog', dateLog, cookiesOptions);
	Cookies.set('identifiantlog', identifiantlog, cookiesOptions);
}

HTMLInputElement.prototype.looseConnect = function () {
	Cookies.set('remember', 0, cookiesOptions);
	Cookies.remove('datelog', cookiesOptions);
	Cookies.remove('identifiantlog', cookiesOptions);
}

/**
 * fonction de vérification d'un format de password lettres minuscules + majuscules + chiffre + caractère spéciaux (-?!@#$%^&*_+=.~)
 */
function checkPasswordFormat(passToTest) {
	// const regexPassword = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\-?/!@#$%^&*_])(?=.{8,})");
	// const regexPassword = new RegExp("^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z])(?=.*[\-?/!@#$%^&*_]).{8,}$");
	const regexPassword = new RegExp('^(?=.*\\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z])(?=.*[\\-?\/!@#$%^&*_+=.~]).{8,}$');
	return regexPassword.test(passToTest);
}

/**
 * Fonction permettant de vérifier l'existence d'une valeur dans une datalist
 * @param valueSearched
 * @param dataListToUse
 */
function valueInList(valueSearched, dataListToUse) {
	if (dataListToUse.options.length === 0) {
		return false;
	} else {
		let valueFound = false;
		for (let valueFormList of dataListToUse.options) {
			if (valueFormList.value.toUpperCase() === valueSearched.toUpperCase()) {
				valueFound = true;
			}
		}
		return valueFound;
	}
}

/**
 * Vérification de la validité d'une date
 */

function dateValide(dateToTest, dayToCheck, monthToCheck, yearToCheck) {
	if (isNaN(dateToTest.getTime())) {
		return false;
	} else {
		if (dayToCheck !== dateToTest.getDate()
			|| monthToCheck !== dateToTest.getMonth() + 1
			|| yearToCheck !== dateToTest.getFullYear()) {
			return false;
		}
	}
	return true;
}

/**
 * crée un élément html img contenant une image de chargement
 */
function addLoadingImage(parentElement) {
	parentElement.setAttribute('disabled', 'disabled');
	parentElement.style.position = 'relative';
	let divLoader = document.createElement('div');
	divLoader.id = 'loader'; //'imgloading-' + parentElement.childNodes.length;
	divLoader.classList.add('loaderinbutton');
	parentElement.appendChild(divLoader);
}

/**
 * Enlève l'image de chargement
 */
function removeLoadingImage(parentElement) {
	parentElement.removeAttribute('disabled');
	parentElement.removeChild(parentElement.querySelector('div#loader'));
}

/**
 * Fonction XHR Pour post des données
 * @param section
 * @param page
 * @param action
 * @param data
 * @param type
 * @returns {Promise<any>}
 */
function postData(section = "api", page = "", action = "", data = {}, type = 'json') {
	let url = `?section=${section}&page=${page}&action=${action}`;
	// let url = `/${section}/${page}/${action}`;

	return fetch(url, {
		method: 'POST',
		headers: {
			// 'Content-type': 'multipart/form-data',
			// 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body: data
	}).then(function (data) {
		if (!data.ok) {
			throw new Error(`HTTP Erreur ! status: ${data.status}`);
		}
		if (type === 'json') {
			return data.json();
		} else {
			return data.text();
		}
	}).catch(function () {
		return false;
	});
}

/**
 * Fonction XHR Pour get des données
 * @param section
 * @param page
 * @param action
 * @param data
 * @param type
 * @returns {Promise<any>}
 */
function fetchData(section = "api", page="mainapi", action = "", data = {}, type = 'json') {
	let url = `?section=${section}&page=${page}&action=${action}`;
	// let url = `/${section}/${page}/${action}`;
	if (data !== null) {
		Object.entries(data).forEach(function (param) {
			url += `&${param[0]}=${param[1]}`;
		})
	}
	return fetch(url, {
		method: 'GET',
		headers: {
			// 'Content-type': 'multipart/form-data',
			// 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		}
	}).then(function (data) {
		if (!data.ok) {
			throw new Error(`HTTP Erreur ! status: ${data.status}`);
		}
		if (type === 'json') {
			return data.json();
		} else {
			return data.text();
		}
	}).catch(function () {
		return false;
	});
}


function numberFormat(numberToFormat, decimals = 2, currency = null, locale = null) {
	// Create our number formatter.
	let userLocaleStr = 'fr-FR', userCurrencyCode = 'EUR';
	if (currency !== null) {
		userCurrencyCode = currency.nom_devises_abrege;
	}
	if (locale !== null) {
		userLocaleStr = locale.libelle;
	}
	const formatter = new Intl.NumberFormat(userLocaleStr, {
		style: 'currency',
		currency: userCurrencyCode,
		minimumFractionDigits: decimals,
		maximumFractionDigits: decimals
	});
	return (formatter.format(numberToFormat));
}

/**
 * Fonction de tri aléatoire d'un tableau
 * Selon la méthode de Fisher Yates
 */
function shuffle(array) {
	// Test de la méthode de https://en.wikipedia.org/wiki/Fisher%E2%80%93Yates_shuffle
	for (let i = array.length - 1; i > 0; i--) {
		let j = Math.floor(Math.random() * (i + 1));
		[array[i], array[j]] = [array[j], array[i]];
	}
}

function updateRequirement(id, valid) {
	const requirement = document.querySelector(`#${id}`);
	if (requirement) {
		if (valid) {
			requirement.classList.add('valid');
		} else {
			requirement.classList.remove('valid');
		}
	}
}

/**
 * Fonction de vidage d'une liste
 */
function clearDataList(idList){
	if (idList && idList.options.length > 1){
		idList.innerHTML = '';
	}
}

/**
 * Fonction de vidage d'une liste
 */
function clearSelect(idSelect){
	if (idSelect && idSelect.options.length > 1){
		for (let idx = idSelect.options.length - 1; idx > 0; idx--){
			let option = idSelect.options[idx];
			if (parseInt(option.value) !== 0){
				idSelect.options.remove(idx);
			}
		}
	}
}

function checkEmail(email) {
	var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(email);
}

document.addEventListener('DOMContentLoaded', async function () {
	/**
	 * Pour tous les liens / boutons vers des pages non terminées ou non mises en ligne
	 */
	let linksToFinish = document.querySelectorAll('a[data-status=tofinish], button[data-status=tofinish]');
	if (linksToFinish) {
		linksToFinish.forEach((link) => {
			// link.addEventListener('click', function (eve) {
			// 	eve.preventDefault();
			// 	alert("Cette page est en cours d'évolution afin de vous proposer un contenu adapté et sera rapidement remise en ligne !");
			// });
			link.removeEventListener('click', null);
			link.onclick = function (eve) {
				eve.preventDefault();
				alert("Cette page est en cours d'évolution afin de vous proposer un contenu adapté et sera rapidement remise en ligne !");
			};
		});
	}

	/**
	 * Pour tous les champs password avec oeil ouvert / fermé
	 */
	const passwordsFields = document.querySelectorAll(`form input[type=password]`);
	passwordsFields.forEach(function (passField) {
		passField.title = "";

		const eye = passField.parentElement.querySelector('.oeil-ferme');
		const eyeoff = passField.parentElement.querySelector('.oeil-ouvert');

		if (eye) {
			eye.addEventListener('click', function () {
				eye.style.display = 'none';
				eyeoff.style.display = 'block';

				passField.type = 'text';
			});
		}

		if (eyeoff) {
			eyeoff.addEventListener('click', function () {
				eyeoff.style.display = 'none';
				eye.style.display = 'block';

				passField.type = 'password';
			});
		}

		passField.addEventListener('invalid', function () {
			this.setCustomValidity(this.title);
		})

		if (passField.getAttribute('name') === 'chMdp' || passField.getAttribute('name') === 'chPassword') {
			passField.addEventListener('input', function (event) {
				const value = event.target.value;

				updateRequirement('length', value.length >= 8);
				updateRequirement('lowercase', /[a-z]/.test(value));
				updateRequirement('uppercase', /[A-Z]/.test(value));
				updateRequirement('number', /\d/.test(value));
				updateRequirement('characters', /[-?/!@#$%^&*_+=.~]/.test(value));
			})

			const divPasswordRequirements = document.createElement('aside');
			divPasswordRequirements.classList.add('password-requirements');
			divPasswordRequirements.setAttribute('id', 'passRequirements');

			const pRequirementLength = document.createElement('label');
			pRequirementLength.classList.add('requirement');
			pRequirementLength.setAttribute('id', 'length');
			pRequirementLength.textContent = "Minimum 8 caractères";
			divPasswordRequirements.appendChild(pRequirementLength);

			const pRequirementMinuscule = document.createElement('label');
			pRequirementMinuscule.classList.add('requirement');
			pRequirementMinuscule.setAttribute('id', 'lowercase');
			pRequirementMinuscule.textContent = "Au moins 1 minuscule";
			divPasswordRequirements.appendChild(pRequirementMinuscule);

			const pRequirementMajuscule = document.createElement('label');
			pRequirementMajuscule.classList.add('requirement');
			pRequirementMajuscule.setAttribute('id', 'uppercase');
			pRequirementMajuscule.textContent = "Au moins 1 majuscule";
			divPasswordRequirements.appendChild(pRequirementMajuscule);

			const pRequirementNombre = document.createElement('label');
			pRequirementNombre.classList.add('requirement');
			pRequirementNombre.setAttribute('id', 'number');
			pRequirementNombre.textContent = "Au moins 1 chiffre";
			divPasswordRequirements.appendChild(pRequirementNombre);

			const pRequirementSpecialCar = document.createElement('label');
			pRequirementSpecialCar.classList.add('requirement');
			pRequirementSpecialCar.setAttribute('id', 'characters');
			pRequirementSpecialCar.setAttribute('style', 'white-space: pre;')
			pRequirementSpecialCar.textContent = "Au moins un caractère spécial :\n" +
				"\t-?/!@#$%^&amp;*_+=.£~";
			divPasswordRequirements.appendChild(pRequirementSpecialCar);

			let infoMdp = passField.parentElement.parentElement.querySelector('.legend-mdp button');
			if (infoMdp.classList.contains('infobulleMdp')) {
				infoMdp.addEventListener('click', function () {
					if (divPasswordRequirements.classList.contains('visible')) {
						divPasswordRequirements.classList.remove('visible');

					} else {
						this.parentElement.parentElement.prepend(divPasswordRequirements)
						divPasswordRequirements.classList.add('visible');
					}
				})
			}

			passField.addEventListener('focus', (event) => {
				const value = event.target.value;
				updateRequirement('length', value.length >= 8);
				updateRequirement('lowercase', /[a-z]/.test(value));
				updateRequirement('uppercase', /[A-Z]/.test(value));
				updateRequirement('number', /\d/.test(value));
				updateRequirement('characters', /[-?/!@#$%^&*_+=.~]/.test(value));
			});

			passField.addEventListener('blur', (event) => {
				const value = event.target.value;
				updateRequirement('length', value.length >= 8);
				updateRequirement('lowercase', /[a-z]/.test(value));
				updateRequirement('uppercase', /[A-Z]/.test(value));
				updateRequirement('number', /\d/.test(value));
				updateRequirement('characters', /[-?/!@#$%^&*_+=.~]/.test(value));
			})
		}
	})

	/**
	 * Formattage des champs en majuscule / capitalize
	 */
	const inputUpperFields = document.querySelectorAll('input.upper');
	inputUpperFields.forEach(function (inputElement) {
		inputElement.addEventListener('change', function () {
			this.value = this.value.toUpperCase().trim();
		})
	});

	const inputCapitalizeFields = document.querySelectorAll('input.capitalize');
	inputCapitalizeFields.forEach(function (inputElement) {
		inputElement.addEventListener('change', function () {
			this.value = this.value.substring(0, 1).toUpperCase() + this.value.substring(1).toLowerCase().trim();
		})
	});

	/**
	 * Gestion des champs de saisie numériques
	 */
	const inputTypeNumbers = document.querySelectorAll('input[data-subtype=number]');
	inputTypeNumbers.forEach(function (inputElement) {
		inputElement.addEventListener('keypress', function (event) {
			let chiffre = (event.charCode >= 48 && event.charCode <= 57);
			let point = (event.charCode === 46);
			let virgule = (event.charCode === 44);

			let max = this.getAttribute('max');
			let subtype = this.getAttribute('data-type');
			if (subtype === 'integer') {
				if (!chiffre) {
					event.preventDefault();
				}
			} else {
				if (!chiffre && !virgule && !point) {
					event.preventDefault();
				}

				let sepExiste = this.value.includes('.') || this.value.includes(',');
				if (sepExiste) {
					event.preventDefault();
				}
			}
			if ((this.value.toString() + String.fromCharCode(event.charCode)).length > max.length) {
				event.preventDefault();
			}
		})

		inputElement.addEventListener('change', function () {
			this.value = this.value.replaceAll('.', ',');
		})
	});

	/**
	 * Désactivation du clic droit sur toutes les images
	 */
	let images = document.querySelectorAll('img, svg');
	images.forEach(function (img) {
		img.addEventListener('contextmenu', function (eve) {
			eve.preventDefault();
			alert("Merci de respecter le travail de l'auteur en ne copiant pas le contenu sans autorisation !");
			return false;
		})
	});

	/**
	 * Application des infos bulle
	 */
	if (document.querySelector(".infobulle")) {
		document.querySelectorAll(".infobulle").forEach(function (click) {
			click.addEventListener('click', function () {
				this.classList.toggle("active")
			})
		})
	}

	/**
	 * Pour tous les liens / boutons disabled
	 */
	let linksDisabled = document.querySelectorAll('a[disabled], button[disabled]');
	if (linksDisabled) {
		linksDisabled.forEach((link) => {
			link.removeEventListener('click', null);
			link.onclick = function (eve) {
				eve.preventDefault();
			};
		});
	}

	/**
	 * Pour la gestion des datalist
	 */

	function getIdForValue(inputField, dataList){
		if (inputField.hasAttribute('data-idforvalue')) inputField.removeAttribute('data-idforvalue');
		if (valueInList(inputField.value.trim(), dataList)){
			for (let option of dataList.options){
				if (option.hasAttribute('data-idforvalue')){
					if (option.value.trim() === inputField.value.trim()){
						inputField.setAttribute('data-idforvalue', option.getAttribute('data-idforvalue'));
					}
				}
			}
		}
	}

	let dataLists = document.querySelectorAll('datalist[id]');
	if (dataLists){
		dataLists.forEach(function (dataList){
			let inputField = document.querySelector(`input[list=${dataList.id}]`);
			if (inputField){
				inputField.addEventListener('input', function (){
					getIdForValue(inputField, dataList);
				})

				inputField.addEventListener('blur', function (){
					getIdForValue(inputField, dataList);
				})

				inputField.addEventListener('change', function (){
					getIdForValue(inputField, dataList);
				})
			}
		})
	}
});
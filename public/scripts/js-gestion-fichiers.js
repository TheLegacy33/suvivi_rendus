/**
 * Gestion des actions de la page de visualisation des évaluations par un étudiant
 */
idTimer = 0;

document.addEventListener('DOMContentLoaded', function (){
	let champEcole = document.querySelector('#champEcole');
	let champClasse = document.querySelector('#champClasse');
	let champEtudiant = document.querySelector('#champEtudiant');
	let champEvaluation = document.querySelector('#champEvaluation');

	/**
	 * Récupération des classes en fonction de l'école
	 */
	if (champEcole){
		champEcole.addEventListener('change', function(){
			if (champClasse) clearSelect(champClasse);
			if (champEtudiant) clearSelect(champEtudiant);
			if (champEvaluation) clearSelect(champEvaluation);
			let tableauEnvoisFichiers = document.querySelector('#tableauEnvoisFichiers .cards');
			tableauEnvoisFichiers.classList.add('hidden');
			tableauEnvoisFichiers.innerHTML = '';

			if (idTimer > 0){
				clearInterval(idTimer);
				idTimer = 0;
			}
			fetchData('api', 'listapi', 'getclasses', {idecole: this.value})
				.then(function (response){
					for (let classe of response.content){
						champClasse.options.add(new Option(classe.nom, classe.id));
					}
				})
				.catch(function (error){
					console.log(error);
				})
		})
	}

	/**
	 * Récupération des étudiants et des évaluations en fonction de la classe
	 */
	if (champClasse){
		champClasse.addEventListener('input', function(){
			let idClasse = this.value;
			if (champEtudiant) clearSelect(champEtudiant);
			if (champEvaluation) clearSelect(champEvaluation);
			let tableauEnvoisFichiers = document.querySelector('#tableauEnvoisFichiers .cards');
			tableauEnvoisFichiers.classList.add('hidden');
			tableauEnvoisFichiers.innerHTML = '';

			if (idTimer > 0){
				clearInterval(idTimer);
				idTimer = 0;
			}
			fetchData('api', 'listapi', 'getetudiants', {idclasse: idClasse})
				.then(function (response){
					for (let etudiant of response.content){
						champEtudiant.options.add(new Option(`${etudiant.nom} ${etudiant.prenom}`, etudiant.id));
					}

					fetchData('api', 'listapi', 'getevaluations', {idclasse: idClasse})
						.then(function (response){
							for (let evaluation of response.content){
								champEvaluation.options.add(new Option(evaluation.nom, evaluation.id));
							}
						})
						.catch(function (error){
							console.log(error);
						})
				})
				.catch(function (error){
					console.log(error);
				})
		})
	}

	/**
	 * Gestion du formulaire
	 */
	let formInfos = document.querySelector('#formInfos');
	formInfos.addEventListener('submit', (eve) => {eve.preventDefault();})
	let btnSbmitForm = document.querySelector('#btnSbmitForm');
	if (btnSbmitForm){
		btnSbmitForm.addEventListener('click', async function(){
			if (idTimer > 0){
				clearInterval(idTimer);
				idTimer = 0;
			}
			if (champEcole.value === '0'){
				alert('Vous devez sélectionner une école !');
				champEcole.focus();
				champEcole.scrollIntoView({block: "center"});
				return false;
			}

			if (champClasse.value === '0'){
				alert('Vous devez sélectionner une classe !');
				champClasse.focus();
				champClasse.scrollIntoView({block: "center"});
				return false;
			}

			if (champEvaluation.value === '0'){
				alert('Vous devez sélectionner une évaluation !');
				champEvaluation.focus();
				champEvaluation.scrollIntoView({block: "center"});
				return false;
			}

			refreshme(champEcole.value, champClasse.value, champEtudiant.value, champEvaluation.value);

			idTimer = setInterval(function () {refreshme(champEcole.value, champClasse.value, champEtudiant.value, champEvaluation.value)}, 5000);
		})
	}
})

function refreshme(idecole, idclasse, idetudiant, idevaluation){
	fetchData('api', 'gestionapi', 'view-files', {idecole: idecole, idclasse: idclasse, idetudiant: idetudiant, idevaluation: idevaluation})
		.then(function (reponse){
			let envois = reponse.content;
			let tableauEnvoisFichiers = document.querySelector('#tableauEnvoisFichiers .cards');
			tableauEnvoisFichiers.classList.add('hidden');
			tableauEnvoisFichiers.innerHTML = '';
			let script = '';

			for (let etudiantId in envois){
				let envoi = envois[etudiantId];
				let etudiant = envoi.etudiant;
				let fichiers = envoi.fichiers;
				let nbFichiers = envoi.fichiers.length;
				let icon = envoi.icone;

				let couleur = (nbFichiers === 0 ? 'red' : 'green');

				script += `<div class="card bg-light-${couleur} ${couleur}">`;
				script += `<div class="left">`;
				script += `<div class="nom">${etudiant.nom} ${etudiant.prenom}</div>`;
				script += `<div class="email">${etudiant.email}</div>`;
				script += `</div>`;
				if (nbFichiers > 0){
					script += `<div class="right">`;
					script += `<a title="Télécharger le fichier" href="${fichiers[0].cheminPublic}" download="">${icon}</a>`;
					script += `</div>`;
				}else{
					script += `<div class="right">`;
					script += `${icon}`;
					script += `</div>`;
				}
				script += `</div>`;
			}
			tableauEnvoisFichiers.innerHTML = script;
			tableauEnvoisFichiers.classList.remove('hidden');
		})
		.catch((error) => {
			let tableauEnvoisFichiers = document.querySelector('#tableauEnvoisFichiers .cards');
			tableauEnvoisFichiers.classList.add('hidden');
			tableauEnvoisFichiers.innerHTML = '';
			console.log(error)
		})
}
/**
 * Gestion des actions de la page d'accueil - envoi d'un fichier
 */
document.addEventListener('DOMContentLoaded', function (){
	let champEcole = document.querySelector('#champEcole');
	let champClasse = document.querySelector('#champClasse');
	let champEtudiant = document.querySelector('#champEtudiant');
	let champEvaluation = document.querySelector('#champEvaluation');
	let btnAskForCode = document.querySelector('#btnAskForCode');
	let champCodeConnexion = document.querySelector('#champCodeConnexion');
	let champFichier = document.querySelector('#champFichier');
	let btnSubmitFile = document.querySelector('#btnSubmitFile');

	/**
	 * Récupération des classes en fonction de l'école
	 */
	if (champEcole){
		champEcole.addEventListener('change', function(){
			if (champClasse) clearSelect(champClasse);
			if (champEtudiant) clearSelect(champEtudiant);
			if (champEvaluation) clearSelect(champEvaluation);
			if (champCodeConnexion) {
				champCodeConnexion.setAttribute('disabled', '');
				champCodeConnexion.classList.remove('alert');
			}
			if (champFichier) champFichier.setAttribute('disabled', '');

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
			if (champCodeConnexion) {
				champCodeConnexion.setAttribute('disabled', '');
				champCodeConnexion.classList.remove('alert');
			}
			if (champFichier) champFichier.setAttribute('disabled', '');

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
	 * Gestion du bouton de demande de code
	 */
	if (btnAskForCode){
		btnAskForCode.addEventListener('click', function(){
			let champEcole = document.querySelector('#champEcole');
			let champClasse = document.querySelector('#champClasse');
			let champEtudiant = document.querySelector('#champEtudiant');
			let champEvaluation = document.querySelector('#champEvaluation');
			let idEcole = champEcole.value;
			let idClasse = champClasse.value;
			let idEtudiant = champEtudiant.value;
			let idEvaluation = champEvaluation.value;

			if (!(idEcole > 0 && idClasse > 0 && idEtudiant > 0 && idEvaluation > 0)){
				alert(`Vous devez sélectionner votre école, votre classe, votre nom et l'évaluation pour laquelle vous souhaitez demander l'accès à la fonction de rendu !`);
				champEcole.focus();
				champEcole.scrollIntoView({block: "center"});
				return false;
			}else{
				let formData = new FormData();
				formData.append('idecole', idEcole);
				formData.append('idclasse', idClasse);
				formData.append('idetudiant', idEtudiant);
				formData.append('idevaluation', idEvaluation);
				postData('api', 'tokenapi', 'getconnexioncode', formData)
					.then(function (response){
						let dateHeureValidation = response.content;
						if (response.status === 208){
							alert(`Un email vient de vous être envoyé contenant le code de connexion <br>à utiliser avant le ${dateHeureValidation}`, 'Information', buttons.ok, false, function(){
								champCodeConnexion.removeAttribute('disabled');
								champCodeConnexion.classList.add('alert');
								champFichier.removeAttribute('disabled');
								champCodeConnexion.focus();
							});
						}else{
							alert(`Un email vient de vous être envoyé contenant un nouveau code de connexion <br>à utiliser avant le ${dateHeureValidation}`, 'Information', buttons.ok, false, function(){
								champCodeConnexion.removeAttribute('disabled');
								champCodeConnexion.classList.add('alert');
								champFichier.removeAttribute('disabled');
								champCodeConnexion.focus();
							});
						}
					})
					.catch((error) => {console.log(error)})
			}
		})
	}

	/**
	 * Gestion du bouton d'envoi
	 */
	let formSendFile = document.querySelector('#formSendFile');
	// if (btnSubmitFile){
	// 	btnSubmitFile.addEventListener('click', async function(){
	if (formSendFile){
		formSendFile.addEventListener('submit', async function(eve){
			eve.preventDefault();
			if (champEcole.value === '0'){
				alert('Vous devez sélectionner votre école !');
				champEcole.focus();
				champEcole.scrollIntoView({block: "center"});
				return false;
			}

			if (champClasse.value === '0'){
				alert('Vous devez sélectionner votre classe !');
				champClasse.focus();
				champClasse.scrollIntoView({block: "center"});
				return false;
			}

			if (champEtudiant.value === '0'){
				alert('Vous devez sélectionner votre nom !');
				champEtudiant.focus();
				champEtudiant.scrollIntoView({block: "center"});
				return false;
			}

			if (champEvaluation.value === '0'){
				alert('Vous devez sélectionner une évaluation !');
				champEvaluation.focus();
				champEvaluation.scrollIntoView({block: "center"});
				return false;
			}

			if (champCodeConnexion.value.trim() === ''){
				alert('Vous devez saisir votre code de connexion reçu par mail !');
				champCodeConnexion.focus();
				champCodeConnexion.scrollIntoView({block: "center"});
				return false;
			}else{
				let reponse = await fetchData('api', 'tokenapi', 'verif-code', {idetudiant: champEtudiant.value, code: champCodeConnexion.value.trim()});
				console.log(reponse);
				if (reponse.status !== 200 || reponse.valid !== true){
					alert("Le code saisi n'est pas correct ou n'est plus valide !");
					champCodeConnexion.focus();
					champCodeConnexion.scrollIntoView({block: "center"});
					return false;
				}
			}

			if (champFichier.files.length === 0){
				alert('Vous devez sélectionner le fichier à transmettre !');
				champFichier.focus();
				champFichier.scrollIntoView({block: "center"});
				return false;
			}
			formSendFile.submit();
		})
	}
})
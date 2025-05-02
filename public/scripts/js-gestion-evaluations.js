/**
 * Gestion des actions de la page de visualisation des évaluations par un gestionnaire
 */
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

			fetchData('api', 'studentapi', 'view-evaluations', {idetudiant: champEtudiant.value, idevaluation: champEvaluation.value})
				.then(function (reponse){
					let evaluations = reponse.content;
					let tableauEvaluations = document.querySelector('#tableauEvaluations');
					tableauEvaluations.classList.add('hidden');
					tableauEvaluations.innerHTML = '';
					let script = '';
					for (let evaluationId in evaluations){
						let evaluation = evaluations[evaluationId];
						script += `<table><thead><tr><th>${evaluation.nomevaluation}</th></tr></thead><tbody><tr><td><table>`;
						if (evaluation.fichiers && evaluation.fichiers.length > 0){
							let numFichier = 1;
							for (let fichier of evaluation.fichiers){
								if (fichier.note === null) fichier.note = '-';
								if (fichier.commentaire === null) fichier.commentaire = '-';
								if (numFichier === 1){
									fichier.note += ' / 20';
									numFichier++;
								}else{
									fichier.note = '';
									fichier.commentaire = '';
								}
								script += `
									<tr>
									<td>${formatDate(fichier.dateEnvoi.date)}</td>
									<td><a href="${fichier.cheminPublic}" target="_blank">Télécharger</a></td>
									<td>${fichier.note}</td>
									<td>${fichier.commentaire}</td>
									</tr>`;
							}
							}else{
								script += `<tr><td>Aucun fichier envoyé</td></tr>`;
							}
						script += `</table></td></tr></tbody></table>`;
					}
					tableauEvaluations.innerHTML = script;
					tableauEvaluations.classList.remove('hidden');

				})
				.catch((error) => {console.log(error)})
		})
	}
})
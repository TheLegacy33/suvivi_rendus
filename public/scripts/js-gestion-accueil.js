/**
 * Gestion des actions de la page d'accueil - envoi d'un fichier
 */
document.addEventListener('DOMContentLoaded', function (){
	let champEcole = document.querySelector('#champEcole');
	let champClasse = document.querySelector('#champClasse');
	let champEtudiant = document.querySelector('#champEtudiant');

	let dataListEcoles = document.querySelector('#lstEcoles');
	let dataListClasses = document.querySelector('#lstClasses');
	let dataListEtudiants = document.querySelector('#lstEtudiants');

	if (champEcole){
		champEcole.addEventListener('input', function(){
			if (dataListClasses) clearList(dataListClasses);
			if (dataListEtudiants) clearList(dataListEtudiants);
			if (valueInList(this.value.trim(), dataListEcoles)){
				fetchData('api', 'listapi', 'getclasses', {idecole: this.getAttribute('data-idforvalue')})
					.then(function (response){
						let script = '';
						for (let classe of response.content){
							script += `<option data-idforvalue="${classe.id}" value="${classe.nom}">`;
						}
						dataListClasses.innerHTML = script;
					})
					.catch(function (error){
						console.log(error);
					})
			}
		})
	}

	if (champClasse){
		champClasse.addEventListener('input', function(){
			if (dataListEtudiants) clearList(dataListEtudiants);
			if (valueInList(this.value.trim(), dataListClasses)){
				fetchData('api', 'listapi', 'getetudiants', {idclasse: this.getAttribute('data-idforvalue')})
					.then(function (response){
						let script = '';
						for (let etudiant of response.content){
							script += `<option data-idforvalue="${etudiant.id}" value="${etudiant.nom} ${etudiant.prenom}">`;
						}
						dataListEtudiants.innerHTML = script;
					})
					.catch(function (error){
						console.log(error);
					})
			}
		})
	}

})
/**
 * Pour les traitements sur la page de renouvellement du mot de passe
 */
document.addEventListener('DOMContentLoaded', function(){
	let formRenewPassword = document.querySelector('#formAskToken');
	if (formRenewPassword){
		formRenewPassword.addEventListener('submit', function (eve){
			eve.preventDefault();
			document.querySelector('#btnDemandResetPassword').click();
		})
	}

	let btnDemandResetPassword = document.querySelector('#btnDemandResetPassword');
	if (btnDemandResetPassword){
		btnDemandResetPassword.addEventListener('click', async function(event){
			let champEmail = document.querySelector('input#mail');
			if (champEmail.value.trim() === ''){
				champEmail.focus();
				alert("Vous devez renseigner votre adresse email !");
				return false;
			}

			if (champEmail){
				let formData = new FormData();
				formData.append('identifiant', champEmail.value);

				let reponse = await postData('api', 'loginapi', 'checkUserExists', formData);
				if (!reponse.userExists){
					champEmail.focus();
					alert("Cet utilisateur n'existe pas !");
					return false;
				}

				addLoadingImage(btnDemandResetPassword.parentElement);

				formData = new FormData();
				formData.append('email', champEmail.value);
				reponse = await postData('api', 'loginapi', 'askForPasswordRenew', formData);
				if (reponse.processedResult	=== "success"){
					champEmail.value = '';
					removeLoadingImage(btnDemandResetPassword.parentElement);
					alert("Votre demande de réinitialisation de votre mot de passe a été transmise !<br>Un email vous a été envoyé !", 'Enregistrement', buttons.ok, false, function (){
						document.location.href = reponse.returnUrl;
					});
					return true;
				}else{
					alert("Votre demande n'a pas pu nous être transmise, veuillez nous contacter pour traiter votre demande.");
					removeLoadingImage(btnDemandResetPassword.parentElement);
					return false;
				}
			}else{
				removeLoadingImage(btnDemandResetPassword.parentElement);
				return false;
			}
		})
	}

	let formSendNewPassword = document.querySelector('#formSetNewPassword');
	if (formSendNewPassword){
		formSendNewPassword.addEventListener('submit', function (eve){
			eve.preventDefault();
			document.querySelector('#btnSendNewPassword').click();
		})
	}

	let btnSendNewPassword = document.querySelector('#btnSendNewPassword');
	if (btnSendNewPassword){
		btnSendNewPassword.addEventListener('click', async function(event){
			let champPassword = document.querySelector('input#mdp');
			let champPasswordVerif = document.querySelector('input#confirmer-mdp');

			if (champPassword.value.trim() === ''){
				champPassword.focus();
				alert("Vous devez renseigner votre mot de passe !");
				return false;
			}

			if (champPasswordVerif.value.trim() === ''){
				champPasswordVerif.focus();
				alert("Vous devez renseigner le champ de vérification de votre mot de passe !");
				return false;
			}

			if (champPassword && champPasswordVerif){
				if (!checkPasswordFormat(champPassword.value)){
					champPassword.focus();
					alert('Ce mot de passe ne respecte pas le format !<br>Le mot de passe doit contenir au moins 8 caractères dont <br>1 chiffre, 1 caractère spécial, 1 minuscule et 1 majuscule !');
					return false;
				}

				if (!checkPasswordFormat(champPasswordVerif.value)){
					champPasswordVerif.focus();
					alert('Ce mot de passe ne respecte pas le format !<br>Le mot de passe doit contenir au moins 8 caractères dont <br>1 chiffre, 1 caractère spécial, 1 minuscule et 1 majuscule !');
					return false;
				}

				//Mots de passe identiques
				if (champPassword.value !== champPasswordVerif.value){
					champPasswordVerif.focus();
					champPasswordVerif.value = '';
					alert('Les mots de passe saisis ne correspondent pas !');
					return false;
				}
				let champToken = document.querySelector('input#champToken');
				let formData = new FormData();
				formData.append('token', champToken.value);
				formData.append('password', champPassword.value);
				let reponse = await postData('api', 'loginapi', 'renewUserPassword', formData);

				if (reponse.processedResult	=== "success"){
					alert("Votre mot de passe a été réinitialisé, vous pouvez maintenant vous connecter avec ce nouveau mot de passe.", 'Enregistrement', buttons.ok, false, function (){
						document.location.href = reponse.returnUrl;
					});
					setTimeout(function (){
						document.location.href = reponse.returnUrl;
					}, 1000);
					return true;
				}else{
					alert("La réinitialisation de votre mot de passe a échoué, veuillez nous contacter pour traiter votre demande.");
					return false;
				}
			}else{
				return false;
			}
		})
	}
});
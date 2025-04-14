// Fonction pour appel modal indépendant
function openModal(idModal){
	if (idModal !== null && idModal.trim() !== ''){
		let modalElement = document.querySelector(`#${idModal}`);

		//Action sur close
		let btClose = modalElement.querySelector('span.modal-close');
		btClose.addEventListener('click', () => { modalElement.close(); });

		if (typeof modalElement.showModal === "function") {
			modalElement.showModal();
		} else {
			console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
		}
		return modalElement;
	}
}

document.addEventListener('DOMContentLoaded', function () {
	// Pour la modal d'info au lancement de la page
	const urlParams = new URLSearchParams(window.location.search);
	if (urlParams.size === 0 || urlParams.has('page') && urlParams.get('page') === 'index') {
		if (!sessionStorage.getItem('modalUp')) {
			let btnInfos = document.querySelector('button[data-action=modal][data-modal=modal-info-site]');

			if (btnInfos) {
				setTimeout(function () {
					openModal(btnInfos.getAttribute('data-modal'));
					// let modalElement = document.querySelector(`#${btnInfos.getAttribute('data-modal')}`);
					//
					// //Action sur close
					// let btClose = modalElement.querySelector('span.modal-close');
					// btClose.addEventListener('click', () => { modalElement.close(); });
					//
					// if (typeof modalElement.showModal === "function") {
					// 	modalElement.showModal();
					// } else {
					// 	console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
					// }
				}, 1500)


			}
			sessionStorage.setItem('modalUp', true)
		}
	}

	// Je vais chercher les bouton modal
	let btnModals = document.querySelectorAll('button[data-action=modal][data-modal]');
	btnModals.forEach((btnModal) => {
		btnModal.addEventListener('click', function onOpen() {
			openModal(btnModal.getAttribute('data-modal'));
			// let modalElement = document.querySelector(`#${btnModal.getAttribute('data-modal')}`);
			//
			// //Action sur close
			// let btClose = modalElement.querySelector('span.modal-close');
			// btClose.addEventListener('click', () => { modalElement.close(); });
			//
			// if (typeof modalElement.showModal === "function") {
			// 	modalElement.showModal();
			// } else {
			// 	console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
			// }
		});
	});

	// Je vais chercher les links modal
	let linkModals = document.querySelectorAll('a[data-action=modal][data-modal]');
	linkModals.forEach((linkModal) => {
		linkModal.addEventListener('click', function (eve) {
			eve.preventDefault();
			openModal(linkModal.getAttribute('data-modal'));
			// let modalElement = document.querySelector(`#${linkModal.getAttribute('data-modal')}`);
			//
			// //Action sur close
			// let btClose = modalElement.querySelector('span.modal-close');
			// btClose.addEventListener('click', () => { modalElement.close(); });
			//
			// if (typeof modalElement.showModal === "function") {
			// 	modalElement.showModal();
			// } else {
			// 	console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
			// }
		});
	});
});
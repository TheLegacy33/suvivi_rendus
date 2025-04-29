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
	// Je vais chercher les bouton modal
	let btnModals = document.querySelectorAll('button[data-action=modal][data-modal]');
	btnModals.forEach((btnModal) => {
		btnModal.addEventListener('click', function onOpen() {
			openModal(btnModal.getAttribute('data-modal'));
		});
	});

	// Je vais chercher les links modal
	let linkModals = document.querySelectorAll('a[data-action=modal][data-modal]');
	linkModals.forEach((linkModal) => {
		linkModal.addEventListener('click', function (eve) {
			eve.preventDefault();
			openModal(linkModal.getAttribute('data-modal'));
		});
	});
});
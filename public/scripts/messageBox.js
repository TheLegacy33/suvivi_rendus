const buttons = {
	ok: 1,
	cancel: 2,
	okcancel : 3,
	ouinon: 4,
	none: 0
}
class MessageBox{
	myMessage = null;
	myTitle = null;
	myFooter = null;

	myDialog = null;
	constructor(){
		let openedDialog = document.querySelector('dialog#msgBox');
		if (openedDialog){
			openedDialog.close();
			// document.body.removeChild(openedDialog);
			openedDialog = null;
		}
		this.myDialog = document.createElement('dialog');
		this.myDialog.id = 'msgBox';

		// HEADER
		let myHeader = document.createElement('header');
		myHeader.id = 'msgBoxTitle';

		this.myTitle = document.createElement('h4');
		this.myTitle.textContent = "Information";
		myHeader.appendChild(this.myTitle);

		let btClose = document.createElement('span');
		btClose.innerHTML = '<svg id="Capa_1" viewBox="0 0 413.348 413.348" xmlns="http://www.w3.org/2000/svg"><path d="m413.348 24.354-24.354-24.354-182.32 182.32-182.32-182.32-24.354 24.354 182.32 182.32-182.32 182.32 24.354 24.354 182.32-182.32 182.32 182.32 24.354-24.354-182.32-182.32z" /></svg>';
		btClose.classList.add('modal-close');
		btClose.addEventListener('click', () => {
			this.myDialog.close();
			document.body.removeChild(this.myDialog);
		});

		myHeader.appendChild(btClose);

		this.myDialog.appendChild(myHeader);

		// CONTENT
		let myContent = document.createElement('section');

		this.myMessage = document.createElement('p');
		this.myMessage.textContent = "Informations à afficher";

		myContent.appendChild(this.myMessage);

		this.myDialog.appendChild(myContent);
	}

	setTitle(title){
		this.myTitle.textContent = title;
	}
	setMessage(contentMessage){
		// this.myMessage.textContent = contentMessage;
		this.myMessage.innerHTML = contentMessage;
	}

	setButtons(btns = buttons.ok, callbackFctValid, callbackFctCancel){
		// Boutons
		this.myFooter = document.createElement('footer');
		this.myFooter.classList.add('btn-actions');

		let myDialog = this.myDialog;
		switch (btns){
			case buttons.ok:{
				let btnOk = document.createElement('button');
				btnOk.classList.add('btn', 'btn-outline');
				btnOk.textContent = 'Valider';
				btnOk.addEventListener('click', function (){
					if (callbackFctValid != null){
						callbackFctValid();
					}
					myDialog.close();
					document.body.removeChild(myDialog);
				})

				this.myFooter.appendChild(btnOk);
				break;
			}

			case buttons.cancel:{
				let btnCancel = document.createElement('button');
				btnCancel.classList.add('btn', 'btn-outline');
				btnCancel.textContent = 'Annuler';
				btnCancel.addEventListener('click', function (){
					myDialog.returnValue = false;
					myDialog.close();
					document.body.removeChild(myDialog);
				})

				this.myFooter.appendChild(btnCancel);
				break;
			}

			case buttons.okcancel:{
				let btnOk = document.createElement('button');
				let btnCancel = document.createElement('button');
				btnOk.classList.add('btn', 'btn-outline');
				btnCancel.classList.add('btn', 'btn-outline');
				btnOk.textContent = 'Valider';
				btnCancel.textContent = 'Annuler';
				btnOk.addEventListener('click', function (){
					myDialog.returnValue = true;
					if (callbackFctValid != null){
						callbackFctValid();
					}
					myDialog.close();
					document.body.removeChild(myDialog);
				})
				btnCancel.addEventListener('click', function (){
					myDialog.returnValue = false;
					if (callbackFctCancel != null){
						callbackFctCancel();
					}
					myDialog.close();
					document.body.removeChild(myDialog);
				})

				this.myFooter.appendChild(btnCancel);
				this.myFooter.appendChild(btnOk);
				break;
			}

			case buttons.ouinon:{
				let btnOui = document.createElement('button');
				let btnNon = document.createElement('button');
				btnOui.classList.add('btn', 'btn-outline', 'btn-black');
				btnNon.classList.add('btn', 'btn-outline');
				btnOui.textContent = 'Oui';
				btnNon.textContent = 'Non';
				btnOui.addEventListener('click', function (){
					myDialog.returnValue = true;
					if (callbackFctValid != null){
						callbackFctValid();
					}
					myDialog.close();
					document.body.removeChild(myDialog);
				})
				btnNon.addEventListener('click', function (){
					myDialog.returnValue = false;
					if (callbackFctCancel != null){
						callbackFctCancel();
					}
					myDialog.close();
					document.body.removeChild(myDialog);
				})

				this.myFooter.appendChild(btnNon);
				this.myFooter.appendChild(btnOui);
				break;
			}
		}
		myDialog.appendChild(this.myFooter);
	}


	show(){
		document.body.appendChild(this.myDialog);
		this.myDialog.showModal();
	}

	addLoadingImg(){
		this.myFooter = document.createElement('footer');
		this.myFooter.classList.add('img-loader');
		let myDialog = this.myDialog;

		let divLoader = document.createElement('div');
		divLoader.id = 'loader'; //'imgloading-' + parentElement.childNodes.length;
		divLoader.classList.add('loaderinmsgbox');
		this.myFooter.appendChild(divLoader);

		myDialog.appendChild(this.myFooter);
	}
}
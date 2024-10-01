; document.addEventListener("DOMContentLoaded", function () {
	const { __ } = wp.i18n;
	const crudPageUrl = crudJsData.crudPageUrl;
	let deleteLinks = document.querySelectorAll('.rbcrud-delete-link');
	deleteLinks.forEach(element => { 
		element.addEventListener('click', (e) =>{
			let message = __( "Do you want to DELETE this record?", 'rb-crud' );
			window.confirm(message) ? true : e.preventDefault();
		} )
	});

	let addCancle = document.getElementById('crud-cancle');
	let formContainer = document.getElementsByClassName('from-container');
	let addNewButton = document.getElementById('crud-add-new');
	addNewButton.addEventListener('click', (e) => {
		formContainer[0].classList.remove('hidden');
	})

	addCancle.addEventListener('click', (e) => {
		const urlString = window.location.search;
		let urlParams = new URLSearchParams(urlString);
		if (urlParams.get('cid')) {
			window.location.href = crudPageUrl;
		} else {
			formContainer[0].classList.add('hidden');
		}
	})
  });
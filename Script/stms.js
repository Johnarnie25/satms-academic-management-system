const allSideMenu = document.querySelectorAll('#sidebar .side-menu li a');

allSideMenu.forEach(item=> {
	const li = item.parentElement;

	item.addEventListener('click', function () {
		allSideMenu.forEach(i=> {
			i.parentElement.classList.remove('active');
		})
		li.classList.add('active');
	})
});






// TOGGLE SIDEBAR
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
	sidebar.classList.toggle('hide');
})







const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

// Guard search-related behavior: header form may be removed in some views.
if (searchButton && searchButtonIcon && searchForm) {
	searchButton.addEventListener('click', function (e) {
		if(window.innerWidth < 576) {
			e.preventDefault();
			searchForm.classList.toggle('show');
			if(searchForm.classList.contains('show')) {
				searchButtonIcon.classList.replace('bx-search', 'bx-x');
			} else {
				searchButtonIcon.classList.replace('bx-x', 'bx-search');
			}
		}
	})
}





if(window.innerWidth < 768) {
	sidebar.classList.add('hide');
}

if (searchButtonIcon && searchForm) {
	if(window.innerWidth > 576) {
		searchButtonIcon.classList.replace('bx-x', 'bx-search');
		searchForm.classList.remove('show');
	}

	window.addEventListener('resize', function () {
		if(this.innerWidth > 576) {
			if (searchButtonIcon.classList) searchButtonIcon.classList.replace('bx-x', 'bx-search');
			if (searchForm.classList) searchForm.classList.remove('show');
		}
	})

} else {
	// keep responsive fallback which only toggles sidebar hide
	window.addEventListener('resize', function () {
		if(this.innerWidth < 768) sidebar.classList.add('hide');
	})
}



const switchMode = document.getElementById('switch-mode');
const DARK_MODE_KEY = 'stms_dark_mode';

if (switchMode) {
	try {
		const saved = localStorage.getItem(DARK_MODE_KEY);
		const enabled = saved === '1';
		// restore body class and checkbox state
		if (enabled) document.body.classList.add('dark');
		else document.body.classList.remove('dark');
		switchMode.checked = enabled;
	} catch (err) {
		// ignore storage errors
		console.warn('Could not restore dark mode state', err);
	}

	switchMode.addEventListener('change', function () {
		try {
			if (this.checked) {
				document.body.classList.add('dark');
				localStorage.setItem(DARK_MODE_KEY, '1');
			} else {
				document.body.classList.remove('dark');
				localStorage.setItem(DARK_MODE_KEY, '0');
			}
		} catch (err) {
			console.warn('Could not persist dark mode state', err);
		}
	})
}

// Clock: update date and time elements in the navbar (safe init)
(function () {
	function initClock() {
		try {
			const dateEl = document.getElementById('current-date');
			const timeEl = document.getElementById('current-time');
			if (!dateEl || !timeEl) return;

			function pad(n){ return n < 10 ? '0'+n : n }
			function updateClock() {
				const now = new Date();
				const d = now.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
				const t = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
				dateEl.textContent = d;
				timeEl.textContent = t;
			}
			updateClock();
			setInterval(updateClock, 1000);
		} catch (err) {
			console.warn('Clock init failed', err);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initClock);
	} else initClock();
})();

/* Page-specific: auto-search and limit controls for listing pages
   Moved from inline script in create_room_section.php. Guards for absence. */
(function () {
	function initListControls() {
		try {
			const searchInput = document.getElementById('searchInput');
			const limitSelect = document.getElementById('limitSelect');
			if (!searchInput || !limitSelect) return;

			let typingTimer;
			const typingDelay = 500;

			searchInput.addEventListener('keyup', () => {
				clearTimeout(typingTimer);
				typingTimer = setTimeout(() => {
					const params = new URLSearchParams(window.location.search);
					params.set('search', searchInput.value);
					params.set('page', 1);
					params.set('limit', limitSelect.value);
					window.location.search = params.toString();
				}, typingDelay);
			});

			searchInput.addEventListener('keydown', () => clearTimeout(typingTimer));

			limitSelect.addEventListener('change', () => {
				const params = new URLSearchParams(window.location.search);
				params.set('limit', limitSelect.value);
				params.set('page', 1);
				params.set('search', searchInput.value);
				window.location.search = params.toString();
			});
		} catch (err) {
			console.warn('List controls init failed', err);
		}
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initListControls);
	else initListControls();
})();
// Profile edit modal: open, preview and close handlers
(function () {
	const profileBtn = document.getElementById('profile-button');
	const profileModal = document.getElementById('profile-modal');
	const modalClose = profileModal ? profileModal.querySelector('.modal-close') : null;
	const modalCancel = profileModal ? profileModal.querySelector('.btn-cancel') : null;
	const profilePicInput = document.getElementById('profile-pic-input');
	const profilePreview = document.getElementById('profile-preview');
	const profileImg = document.getElementById('profile-img');

	function openModal() {
		if (!profileModal) return;
		profileModal.setAttribute('aria-hidden', 'false');
		// focus first input
		const first = profileModal.querySelector('input, button, textarea');
		if (first) first.focus();
	}
	function closeModal() {
		if (!profileModal) return;
		profileModal.setAttribute('aria-hidden', 'true');
	}

	if (profileBtn) profileBtn.addEventListener('click', function (e) {
		e.preventDefault();
		openModal();
	});

	if (modalClose) modalClose.addEventListener('click', closeModal);
	if (modalCancel) modalCancel.addEventListener('click', closeModal);

	// close on overlay click
	if (profileModal) profileModal.addEventListener('click', function (e) {
		if (e.target === profileModal) closeModal();
	});

	// image preview
	if (profilePicInput && profilePreview) {
		profilePicInput.addEventListener('change', function () {
			const file = this.files && this.files[0];
			if (!file) return;
			const reader = new FileReader();
			reader.onload = function (ev) {
				profilePreview.src = ev.target.result;
			}
			reader.readAsDataURL(file);
		});
	}

	// optional: update header image after successful submit if server returns new URL
	const profileForm = document.getElementById('profile-form');
	if (profileForm && profileImg) {
		profileForm.addEventListener('submit', function (e) {
			// allow normal form submit to server endpoint by default
			// if you want AJAX submit, preventDefault() and implement fetch here
			// e.preventDefault();
			// Example AJAX (disabled by default):
			// const fd = new FormData(profileForm);
			// fetch(profileForm.action, { method: 'POST', body: fd }).then(...)
			// For now close modal on submit to give immediate feedback
			setTimeout(closeModal, 300);
		});
	}

})();


// Make sidebar logo draggable and initially centered
(function () {
	const brand = document.querySelector('#sidebar .brand');
	const logo = brand ? brand.querySelector('img') : null;
	if (!brand || !logo) return;

	function placeLogoCentered() {
		const pRect = brand.getBoundingClientRect();
		const iRect = logo.getBoundingClientRect();
		const left = Math.max(0, Math.round((pRect.width - iRect.width) / 2));
		const top = Math.max(0, Math.round((pRect.height - iRect.height) / 2));
		logo.style.left = left + 'px';
		logo.style.top = top + 'px';
		logo.style.transform = 'none';
		logo.dataset.left = left;
		logo.dataset.top = top;
	}

	if (logo.complete) placeLogoCentered();
	else logo.addEventListener('load', placeLogoCentered);
	window.addEventListener('resize', placeLogoCentered);

	// Re-center logo after sidebar toggle (wait for CSS transition)
	const toggleBtn = document.querySelector('#content nav .bx.bx-menu');
	if (toggleBtn) toggleBtn.addEventListener('click', function () { setTimeout(placeLogoCentered, 320); });

	let dragging = false;
	let startX = 0, startY = 0, startLeft = 0, startTop = 0;

	logo.addEventListener('pointerdown', function (e) {
		e.preventDefault();
		logo.setPointerCapture(e.pointerId);
		dragging = true;
		logo.classList.add('dragging');
		startX = e.clientX;
		startY = e.clientY;
		startLeft = parseFloat(logo.dataset.left) || logo.offsetLeft;
		startTop = parseFloat(logo.dataset.top) || logo.offsetTop;
	});

	document.addEventListener('pointermove', function (e) {
		if (!dragging) return;
		const pRect = brand.getBoundingClientRect();
		const iRect = logo.getBoundingClientRect();
		const dx = e.clientX - startX;
		const dy = e.clientY - startY;
		let newLeft = startLeft + dx;
		let newTop = startTop + dy;
		newLeft = Math.max(0, Math.min(newLeft, pRect.width - iRect.width));
		newTop = Math.max(0, Math.min(newTop, pRect.height - iRect.height));
		logo.style.left = newLeft + 'px';
		logo.style.top = newTop + 'px';
		logo.dataset.left = newLeft;
		logo.dataset.top = newTop;
		logo.style.transform = 'none';
	});

	document.addEventListener('pointerup', function (e) {
		if (!dragging) return;
		dragging = false;
		try { logo.releasePointerCapture(e.pointerId); } catch (err) { }
		logo.classList.remove('dragging');
	});

})();

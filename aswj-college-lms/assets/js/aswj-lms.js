/* ASWJ College LMS front-end: gated video loading + lesson progress ticking. */
(function () {
	'use strict';

	if (typeof aswjLms === 'undefined') {
		return;
	}

	function post(action, data) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', aswjLms.nonce);
		Object.keys(data).forEach(function (key) {
			body.append(key, data[key]);
		});
		return fetch(aswjLms.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (res) {
			return res.json();
		});
	}

	/* Deferred, access-checked video loading -------------------------------- */
	document.querySelectorAll('[data-aswj-video-lesson]').forEach(function (frame) {
		var lessonId = frame.getAttribute('data-aswj-video-lesson');

		post('aswj_get_video', { lesson_id: lessonId })
			.then(function (json) {
				if (json && json.success && json.data && json.data.html) {
					frame.innerHTML = json.data.html;
					// Deter casual right-click → copy on the player area.
					frame.addEventListener('contextmenu', function (e) {
						e.preventDefault();
					});
				} else {
					var message = (json && json.data && json.data.message) ? json.data.message : 'This video could not be loaded.';
					frame.innerHTML = '<div class="aswj-video-error"><p>' + message + '</p></div>';
				}
			})
			.catch(function () {
				frame.innerHTML = '<div class="aswj-video-error"><p>Network error — please refresh the page.</p></div>';
			});
	});

	/* Lesson complete toggle ------------------------------------------------ */
	document.querySelectorAll('.aswj-complete-toggle').forEach(function (button) {
		button.addEventListener('click', function () {
			var lessonId = button.getAttribute('data-aswj-lesson');
			var nowComplete = button.getAttribute('data-completed') !== '1';

			button.disabled = true;

			post('aswj_toggle_lesson', {
				lesson_id: lessonId,
				completed: nowComplete ? '1' : '0'
			})
				.then(function (json) {
					if (!json || !json.success) {
						var message = (json && json.data && json.data.message) ? json.data.message : 'Could not save your progress.';
						window.alert(message);
						return;
					}
					button.setAttribute('data-completed', nowComplete ? '1' : '0');
					button.classList.toggle('is-complete', nowComplete);

					// Update sidebar tick for this lesson.
					document.querySelectorAll('.aswj-lesson-item').forEach(function (item) {
						var link = item.querySelector('a.aswj-lesson-link');
						if (link && link.href === window.location.href) {
							item.classList.toggle('is-complete', nowComplete);
							var tick = item.querySelector('.aswj-lesson-tick');
							if (tick && nowComplete) {
								tick.dataset.aswjNumber = tick.textContent;
								tick.textContent = '✓';
							} else if (tick && tick.dataset.aswjNumber) {
								tick.textContent = tick.dataset.aswjNumber;
							}
						}
					});

					// Update any progress bar on the page.
					if (json.data.progress) {
						var bar = document.querySelector('[data-aswj-course-progress] .aswj-progress-bar');
						if (bar) {
							bar.style.width = json.data.progress.percent + '%';
						}
						var text = document.querySelector('[data-aswj-progress-text]');
						if (text) {
							text.textContent = json.data.progress.completed + ' of ' + json.data.progress.total + ' lessons complete (' + json.data.progress.percent + '%)';
						}
					}
				})
				.catch(function () {
					window.alert('Network error — please try again.');
				})
				.finally(function () {
					button.disabled = false;
				});
		});
	});
})();

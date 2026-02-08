(function ($) {
	'use strict';

	var $loginForm = $('#wp-cms-connector-login-form');
	var $loginMessage = $('#wp-cms-connector-login-message');
	var $logoutBtn = $('#wp-cms-connector-logout');
	var $flushBtn = $('#wp-cms-connector-flush-cache');
	var $flushMessage = $('#wp-cms-connector-flush-message');
	var $editForm = $('#wp-cms-connector-edit-article-form');
	var $editMessage = $('#wp-cms-connector-edit-message');

	function showMessage($el, text, isError) {
		$el.removeClass('wp-cms-connector-message--success wp-cms-connector-message--error').addClass('wp-cms-connector-message--' + (isError ? 'error' : 'success')).text(text).show();
	}

	function hideMessage($el) {
		$el.hide().empty();
	}

	if ($loginForm.length) {
		$loginForm.on('submit', function (e) {
			e.preventDefault();
			hideMessage($loginMessage);
			var $submit = $loginForm.find('button[type="submit"]');
			$submit.prop('disabled', true);
			$.post(wpCmsConnector.ajaxUrl, {
				action: 'wp_cms_connector_login',
				nonce: wpCmsConnector.nonce,
				login: $('#wp-cms-connector-login').val(),
				password: $('#wp-cms-connector-password').val(),
				secret_key: $('#wp-cms-connector-secret-key').val()
			}).done(function (r) {
				if (r.success) {
					showMessage($loginMessage, r.data && r.data.message ? r.data.message : 'Connexion réussie.', false);
					setTimeout(function () {
						location.reload();
					}, 800);
				} else {
					showMessage($loginMessage, (r.data && r.data.message) ? r.data.message : 'Erreur de connexion.', true);
					$submit.prop('disabled', false);
				}
			}).fail(function (xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ? xhr.responseJSON.data.message : 'Erreur réseau.';
				showMessage($loginMessage, msg, true);
				$submit.prop('disabled', false);
			});
		});
	}

	if ($logoutBtn.length) {
		$logoutBtn.on('click', function () {
			var $btn = $(this);
			$btn.prop('disabled', true);
			$.post(wpCmsConnector.ajaxUrl, {
				action: 'wp_cms_connector_logout',
				nonce: wpCmsConnector.nonce
			}).done(function (r) {
				if (r.success) {
					location.reload();
				} else {
					$btn.prop('disabled', false);
				}
			}).fail(function () {
				$btn.prop('disabled', false);
			});
		});
	}

	// Vider le cache
	if ($flushBtn.length) {
		$flushBtn.on('click', function () {
			var $btn = $(this);
			$btn.prop('disabled', true);
			hideMessage($flushMessage);
			$.post(wpCmsConnector.ajaxUrl, {
				action: 'wp_cms_connector_flush_cache',
				nonce: wpCmsConnector.nonce
			}).done(function (r) {
				showMessage($flushMessage, r.data && r.data.message ? r.data.message : 'Cache vidé.', false);
				$btn.prop('disabled', false);
			}).fail(function () {
				showMessage($flushMessage, 'Erreur.', true);
				$btn.prop('disabled', false);
			});
		});
	}

	if ($editForm.length) {
		$editForm.on('submit', function (e) {
			e.preventDefault();
			hideMessage($editMessage);
			var $submit = $editForm.find('button[type="submit"]');
			$submit.prop('disabled', true);
			$.post(wpCmsConnector.ajaxUrl, {
				action: 'wp_cms_connector_update_article',
				nonce: wpCmsConnector.nonce,
				article_id: $('#wp-cms-connector-edit-article-id').val(),
				title: $('#wp-cms-connector-edit-title').val(),
				content: $('#wp-cms-connector-edit-content').val()
			}).done(function (r) {
				if (r.success) {
					showMessage($editMessage, r.data && r.data.message ? r.data.message : 'Article mis à jour.', false);
				} else {
					showMessage($editMessage, (r.data && r.data.message) ? r.data.message : 'Erreur.', true);
				}
				$submit.prop('disabled', false);
			}).fail(function (xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ? xhr.responseJSON.data.message : 'Erreur réseau.';
				showMessage($editMessage, msg, true);
				$submit.prop('disabled', false);
			});
		});
	}
})(jQuery);

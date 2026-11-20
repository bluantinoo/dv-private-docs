/**
 * DV Private Docs - embedded login form (consent link, reCAPTCHA token)
 */
jQuery(document).ready(function($) {
    var $form = $('#dv-login-form');

    // Open Complianz consent banner from our notice link by clicking the
    // native Complianz "Manage consent" button (the binding lives there).
    $(document).on('click', '.dv-login-consent-notice .dv-open-consent', function(e) {
        e.preventDefault();
        var $btn = $('#cmplz-manage-consent button.cmplz-manage-consent').first();
        if (!$btn.length) {
            $btn = $('button.cmplz-manage-consent, a.cmplz-manage-consent').not('.dv-open-consent').first();
        }
        if ($btn.length) {
            $btn[0].click();
        } else if (typeof window.cmplz_show_cookie_banner === 'function') {
            window.cmplz_show_cookie_banner();
        }
    });

    // When Complianz enables a consent category and our notice is on the page,
    // reload to re-evaluate server-side state (Complianz reloads on deny but not on accept).
    // See https://complianz.io/reload-after-consent/
    document.addEventListener('cmplz_enable_category', function() {
        if (document.querySelector('.dv-login-consent-notice')) {
            window.location.reload();
        }
    });

    if (!$form.length) {
        return;
    }

    // Without reCAPTCHA the form is a plain POST to wp-login.php.
    if (!dvLogin.recaptcha || !dvLogin.sitekey || typeof grecaptcha === 'undefined') {
        return;
    }

    // With reCAPTCHA: fetch a token, put it in the form, then submit natively.
    $form.on('submit', function(e) {
        e.preventDefault();

        var form = this;
        var $button = $form.find('button[type="submit"]');
        $button.prop('disabled', true);

        grecaptcha.ready(function() {
            grecaptcha.execute(dvLogin.sitekey, {action: 'login'}).then(function(token) {
                $form.find('input[name="dv_recaptcha_response"]').val(token);
                // Native submit does not fire this handler again.
                HTMLFormElement.prototype.submit.call(form);
            }, function() {
                $button.prop('disabled', false);
            });
        });
    });
});

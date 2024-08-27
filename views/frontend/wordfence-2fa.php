<?php
/**
 * wordfence-2fa.php
 *
 * @var array $args
 */

?>
<div class="form-group" id="wf-two-fa-container" style="display:none;">
    <label for="<?php echo esc_attr("wfls-token"); ?>"><?php echo esc_html("Wordfence 2FA Code"); ?></label>
    <input type="text" name="wfls-token" id="wfls-token" class="input form-control"/>
</div>
<div class="wf-login-submit">
    <input type="hidden" name="wfls-token-submit" id="wfls-token-submit" class="btn" value="Log In">
</div>

<script>
window.onload = function() {
  let isUserLoggedIn = false;
  let loginUsernameElements = document.querySelectorAll('.login-username');
  let loginPasswordElements = document.querySelectorAll('.login-password');
  let loginRememberElements = document.querySelectorAll('.login-remember');
  let loginSubmitElements = document.querySelectorAll('.login-submit');
  let loginLostPassElements = document.querySelectorAll('.password-lost');
  let loginErrorMsgElements = document.querySelectorAll('.error-msg');
  let defaultLoginLink = document.querySelectorAll('.wpstg-default-login-link');
  let loginForm = document.getElementById("<?php echo esc_attr($args['form_id']); ?>");
  let loginUsername = document.getElementById("<?php echo esc_attr($args['id_username']); ?>");
  let loginPassword = document.getElementById("<?php echo esc_attr($args['id_password']); ?>");
  let wfToken = document.getElementById("wfls-token");
  let loginSubmitButton = document.getElementById("<?php echo esc_attr($args['id_submit']); ?>");
  let wfLoginSubmitButton = document.getElementById("wfls-token-submit");
  let wfTokenElement = document.getElementById("wf-two-fa-container");
  loginSubmitButton.type = "button";
  loginForm.action = "<?php echo esc_url(home_url() . "/wp-login.php"); ?>";

  /*
  * the action wordfence_ls_authenticate checks if the user have 2fa enabled
  * returns two_factor_required = true if 2fa enabled
  * */
  function submitCustomLoginForm(event) {
    if ((event.key === 'Enter' && !isUserLoggedIn) || (event.type === 'click' && event.target === loginSubmitButton)) {
      const formData = new FormData();
      formData.append('log', loginUsername.value);
      formData.append('pwd', loginPassword.value);
      formData.append('redirect_to', '<?php echo esc_url(home_url()); ?>');
      formData.append('action', 'wordfence_ls_authenticate');

      fetch("wp-admin/admin-ajax.php", {method: 'post', body: formData})
      .then((response) => response.json()).then((res) => {
        if (res.login === 1 && res.two_factor_required) {
          isUserLoggedIn = true;
          loginUsernameElements.forEach(element => {
            element.style.display = 'none';
          });

          defaultLoginLink.forEach(element => {
            element.style.display = 'none';
          });

          loginPasswordElements.forEach(element => {
            element.style.display = 'none';
          });

          loginRememberElements.forEach(element => {
            element.style.display = 'none';
          });

          loginSubmitElements.forEach(element => {
            element.style.display = 'none';
          });

          loginErrorMsgElements.forEach(element => {
            element.innerHTML = "";
            element.innerContent = "";
          });

          loginLostPassElements.forEach(element => {
            element.style.display = 'none';
            element.innerHTML = "";
            element.innerContent = "";
          });

          wfTokenElement.style.display = 'block';
          wfLoginSubmitButton.type = "button";
          loginSubmitButton.type = "hidden";
          loginUsername.name = "log";
          loginPassword.name = "pwd";
        } else if (res.login === 1 && !res.two_factor_required) {
          loginForm.action = "";
          loginForm.submit();
        }
        else {
          loginLostPassElements.forEach(element => {
            element.innerHTML = 'Cannot log in because WordFence 2FA authentication module is detected, but it does not work properly with the WP STAGING login form. Error message: ' + res.error + ' <br><br>Try to log in with the <a href="./wp-login.php">default WordPress login</a> instead.';
          });
        }
    })
    .catch((error) => {});
    }
  }

  function wf2FAForm(event) {
    if (event.type === 'click' && event.target === wfLoginSubmitButton) {
      loginForm.submit();
    }
  }

  document.addEventListener('keydown', submitCustomLoginForm);
  loginSubmitButton.addEventListener('click', submitCustomLoginForm);
  wfLoginSubmitButton.addEventListener('click', wf2FAForm);
};
</script>
      <p class="auth-legal">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
    </div>
  </main>
</div>

<script>
// Show / hide password: any <button data-toggle-password="INPUT_ID">
document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var input = document.getElementById(btn.dataset.togglePassword);
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Hide' : 'Show';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
  });
});
</script>
</body>
</html>

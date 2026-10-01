<?php
require __DIR__ . '/includes/db.php';
if (is_admin()) { header('Location: admin.php'); exit; }
$title = 'Admin login';
$error = '';
$user = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = (string)($_POST['password'] ?? '');
    if (hash_equals(ADMIN_USER, $user) && hash_equals(ADMIN_PASS, $pass)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        flash('Welcome back, admin.');
        header('Location: admin.php');
        exit;
    }
    usleep(600000);
    $error = 'Incorrect username or password.';
}
require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto max-w-md">
  <div class="rounded-lg border border-soil/15 bg-white p-6 sm:p-8">
    <h1 class="flex items-center gap-2 text-2xl font-bold"><?= icon('lock', 'h-6 w-6 text-leaf') ?> Admin login</h1>
    <p class="mt-2 text-sm text-soil/80">Sign in to manage inventory and buyer orders.</p>
    <?php if ($error): ?>
      <div role="alert" class="mt-4 flex items-center gap-2 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-red-800"><?= icon('alert', 'h-5 w-5') ?> <?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" class="mt-5 space-y-4">
      <div>
        <label for="username" class="mb-1 block text-sm font-semibold">Username</label>
        <input id="username" name="username" value="<?= e($user) ?>" required autofocus autocomplete="username" class="<?= INPUT ?>">
      </div>
      <div>
        <label for="password" class="mb-1 block text-sm font-semibold">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="<?= INPUT ?>">
      </div>
      <button class="flex w-full items-center justify-center gap-2 rounded-md bg-leaf px-5 py-2.5 font-semibold text-white hover:bg-leaf-dark focus:outline-none focus:ring-2 focus:ring-leaf/40"><?= icon('check', 'h-5 w-5') ?> Log in</button>
    </form>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
$page = basename($_SERVER['PHP_SELF']);
function nav_link(string $href, string $label, string $ic, string $page): string {
    $on = $page === $href ? 'bg-leaf text-white' : 'text-soil hover:bg-leaf-soft';
    return '<a href="' . $href . '" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium ' . $on . '">' . icon($ic, 'h-4 w-4') . $label . '</a>';
}
$fl = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'AgriLink') ?> | AgriLink Community</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
  fontFamily: { sans: ['"Public Sans"', 'system-ui', 'sans-serif'] },
  colors: { leaf: { DEFAULT: '#2E5E3E', dark: '#214630', soft: '#E3EDE5' }, soil: '#3D3027', paper: '#F1F3EE', harvest: '#C98A1B' }
} } }
</script>
</head>
<body class="min-h-screen bg-paper font-sans text-soil antialiased">
<header class="border-b border-soil/15 bg-white">
  <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
    <a href="index.php" class="flex items-center gap-2 text-xl font-bold text-leaf">
      <?= icon('leaf', 'h-7 w-7') ?> AgriLink Community
    </a>
    <nav class="flex flex-wrap gap-1">
      <?= nav_link('index.php', 'Catalog', 'basket', $page) ?>
      <?= nav_link('reserve.php', 'Reserve', 'list', $page) ?>
      <?php if (is_admin()): ?>
        <?= nav_link('admin.php', 'Admin panel', 'store', $page) ?>
        <form method="post" action="logout.php"><button class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-soil hover:bg-leaf-soft focus:outline-none focus:ring-2 focus:ring-leaf/40"><?= icon('logout', 'h-4 w-4') ?> Log out</button></form>
      <?php else: ?>
        <?= nav_link('login.php', 'Admin login', 'user', $page) ?>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="mx-auto max-w-6xl px-4 py-8">
<?php if ($fl): ?>
  <div role="alert" class="mb-6 flex items-center gap-2 rounded-md border px-4 py-3 text-sm font-medium <?= $fl[1] === 'ok' ? 'border-leaf/40 bg-leaf-soft text-leaf-dark' : 'border-red-300 bg-red-50 text-red-800' ?>">
    <?= icon($fl[1] === 'ok' ? 'check' : 'alert', 'h-5 w-5') ?> <?= e($fl[0]) ?>
  </div>
<?php endif; ?>

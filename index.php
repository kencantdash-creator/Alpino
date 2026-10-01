<?php
require __DIR__ . '/includes/db.php';
$title = 'Catalog';
$q = trim($_GET['q'] ?? '');
$cat = $_GET['cat'] ?? '';
$sql = "SELECT * FROM products WHERE 1=1";
$args = [];
if ($q !== '') { $sql .= " AND (name LIKE ? OR location LIKE ?)"; $args[] = "%$q%"; $args[] = "%$q%"; }
if (in_array($cat, CATEGORIES, true)) { $sql .= " AND category = ?"; $args[] = $cat; }
$sql .= " ORDER BY quantity = 0, name";
$st = $pdo->prepare($sql);
$st->execute($args);
$items = $st->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="mb-8 grid gap-6 lg:grid-cols-2 lg:items-end">
  <div>
    <h1 class="text-3xl font-bold leading-tight sm:text-4xl">Fresh produce from farms in your neighborhood</h1>
    <p class="mt-3 max-w-xl text-soil/80">Check today's prices and available stock, then reserve what you need for pickup or delivery.</p>
  </div>
  <form method="get" class="flex gap-2" role="search">
    <?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
    <div class="relative flex-1">
      <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-soil/50"><?= icon('search', 'h-5 w-5') ?></span>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search produce or location" aria-label="Search produce or location" class="<?= INPUT ?> pl-10">
    </div>
    <button class="rounded-md bg-leaf px-5 py-2.5 font-semibold text-white hover:bg-leaf-dark focus:outline-none focus:ring-2 focus:ring-leaf/40">Search</button>
  </form>
</section>

<nav class="mb-6 flex flex-wrap gap-2" aria-label="Categories">
  <?php foreach (array_merge([''], CATEGORIES) as $c):
    $on = $c === $cat;
    $href = 'index.php?' . http_build_query(array_filter(['cat' => $c, 'q' => $q])); ?>
    <a href="<?= e($href) ?>" class="rounded-full border px-4 py-1.5 text-sm font-medium <?= $on ? 'border-leaf bg-leaf text-white' : 'border-soil/25 bg-white hover:bg-leaf-soft' ?>"><?= $c === '' ? 'All' : e($c) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$items): ?>
  <div class="rounded-lg border border-dashed border-soil/30 bg-white p-10 text-center">
    <p class="font-semibold">No produce matches your search.</p>
    <p class="mt-1 text-sm text-soil/70">Try a different name or <a href="index.php" class="font-medium text-leaf underline">clear the filters</a>.</p>
  </div>
<?php else: ?>
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
  <?php foreach ($items as $i):
    $qty = (int)$i['quantity'];
    if ($qty === 0) { $badge = 'bg-red-50 text-red-800 border-red-200'; $label = 'Sold out'; }
    elseif ($qty <= 10) { $badge = 'bg-amber-50 text-amber-900 border-amber-300'; $label = 'Low stock'; }
    else { $badge = 'bg-leaf-soft text-leaf-dark border-leaf/30'; $label = 'In stock'; } ?>
  <article class="flex flex-col rounded-lg border border-soil/15 bg-white p-5 <?= $qty === 0 ? 'opacity-70' : '' ?>">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold"><?= e($i['name']) ?></h2>
        <p class="text-sm text-soil/70"><?= e($i['category']) ?></p>
      </div>
      <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold <?= $badge ?>"><?= $label ?></span>
    </div>
    <p class="mt-4 text-2xl font-bold text-leaf"><?= peso($i['price']) ?> <span class="text-sm font-medium text-soil/70">per <?= e($i['unit']) ?></span></p>
    <dl class="mt-4 space-y-2 text-sm">
      <div class="flex items-center gap-2"><dt class="sr-only">Available</dt><?= icon('basket', 'h-4 w-4 text-soil/60') ?><dd><?= $qty ?> <?= e($i['unit']) ?> available</dd></div>
      <div class="flex items-center gap-2"><dt class="sr-only">Pickup location</dt><?= icon('pin', 'h-4 w-4 text-soil/60') ?><dd><?= e($i['location']) ?></dd></div>
    </dl>
    <div class="mt-5 pt-1">
      <?php if ($qty > 0): ?>
        <a href="reserve.php?product=<?= (int)$i['id'] ?>" class="flex w-full items-center justify-center gap-2 rounded-md bg-leaf px-4 py-2.5 font-semibold text-white hover:bg-leaf-dark focus:outline-none focus:ring-2 focus:ring-leaf/40"><?= icon('list', 'h-4 w-4') ?> Reserve</a>
      <?php else: ?>
        <span class="flex w-full cursor-not-allowed items-center justify-center rounded-md bg-soil/10 px-4 py-2.5 font-semibold text-soil/50">Unavailable</span>
      <?php endif; ?>
    </div>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

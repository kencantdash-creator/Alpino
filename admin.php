<?php
require __DIR__ . '/includes/db.php';
require_admin();
$title = 'Admin panel';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';

    if ($a === 'add') {
        $name = trim($_POST['name'] ?? ''); $cat = $_POST['category'] ?? '';
        $price = (float)($_POST['price'] ?? 0); $unit = trim($_POST['unit'] ?? '');
        $qty = (int)($_POST['quantity'] ?? -1); $loc = trim($_POST['location'] ?? '');
        if ($name === '' || !in_array($cat, CATEGORIES, true) || $price <= 0 || $unit === '' || $qty < 0 || $loc === '') {
            flash('Could not add produce. Fill in every field with valid values.', 'err');
        } else {
            $pdo->prepare("INSERT INTO products (name,category,price,unit,quantity,location) VALUES (?,?,?,?,?,?)")
                ->execute([mb_substr($name, 0, 80), $cat, $price, mb_substr($unit, 0, 20), $qty, mb_substr($loc, 0, 120)]);
            flash($name . ' was added to the catalog.');
        }
    } elseif ($a === 'update') {
        $price = (float)($_POST['price'] ?? 0); $qty = (int)($_POST['quantity'] ?? -1);
        if ($price <= 0 || $qty < 0) {
            flash('Price must be above 0 and quantity cannot be negative.', 'err');
        } else {
            $pdo->prepare("UPDATE products SET price = ?, quantity = ? WHERE id = ?")->execute([$price, $qty, (int)($_POST['id'] ?? 0)]);
            flash('Inventory updated.');
        }
    } elseif ($a === 'delete') {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
        flash('Produce removed from the catalog.');
    } elseif ($a === 'status') {
        $new = $_POST['status'] ?? '';
        if (in_array($new, STATUSES, true)) {
            $pdo->beginTransaction();
            $st = $pdo->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
            $st->execute([(int)($_POST['order_id'] ?? 0)]);
            $o = $st->fetch();
            $ok = true;
            if ($o && $o['status'] !== $new) {
                if ($new === 'Cancelled') {
                    $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$o['quantity'], $o['product_id']]);
                } elseif ($o['status'] === 'Cancelled') {
                    $u = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
                    $u->execute([$o['quantity'], $o['product_id'], $o['quantity']]);
                    $ok = $u->rowCount() === 1;
                }
                if ($ok) $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new, $o['id']]);
            }
            if ($ok) { $pdo->commit(); flash('Order status changed to ' . $new . '.'); }
            else { $pdo->rollBack(); flash('Not enough stock to reopen this cancelled order.', 'err'); }
        }
    }
    header('Location: admin.php');
    exit;
}

$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();
$orders = $pdo->query("SELECT o.*, p.name, p.unit, p.price FROM orders o JOIN products p ON p.id = o.product_id ORDER BY o.id DESC")->fetchAll();
$pending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
$low = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 10")->fetchColumn();
$badge = ['Pending' => 'bg-amber-50 text-amber-900 border-amber-300', 'Confirmed' => 'bg-sky-50 text-sky-900 border-sky-300',
          'Ready' => 'bg-leaf-soft text-leaf-dark border-leaf/40', 'Completed' => 'bg-soil/10 text-soil border-soil/30', 'Cancelled' => 'bg-red-50 text-red-800 border-red-200'];
$th = 'px-4 py-3 text-left font-semibold';
$btn = 'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-leaf/40';
require __DIR__ . '/includes/header.php';
?>
<h1 class="text-3xl font-bold">Admin panel</h1>
<p class="mt-2 text-soil/80">Update daily inventory, add newly harvested goods, and manage buyer orders.</p>
<p class="mt-3 text-sm font-medium text-soil/80"><?= count($products) ?> products listed, <?= $low ?> low or sold out, <?= $pending ?> orders waiting for confirmation.</p>

<section class="mt-8 rounded-lg border border-soil/15 bg-white p-5 sm:p-6" aria-labelledby="add-h">
  <h2 id="add-h" class="flex items-center gap-2 text-xl font-bold"><?= icon('plus', 'h-5 w-5 text-leaf') ?> Add harvested goods</h2>
  <form method="post" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
    <input type="hidden" name="action" value="add">
    <div class="lg:col-span-2"><label for="n" class="mb-1 block text-sm font-semibold">Produce name</label><input id="n" name="name" maxlength="80" required class="<?= INPUT ?>"></div>
    <div><label for="c" class="mb-1 block text-sm font-semibold">Category</label>
      <select id="c" name="category" required class="<?= INPUT ?>"><?php foreach (CATEGORIES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></div>
    <div><label for="p" class="mb-1 block text-sm font-semibold">Price (₱)</label><input id="p" name="price" type="number" step="0.01" min="0.01" required class="<?= INPUT ?>"></div>
    <div><label for="u" class="mb-1 block text-sm font-semibold">Unit</label><input id="u" name="unit" maxlength="20" placeholder="kg, piece, bundle" required class="<?= INPUT ?>"></div>
    <div><label for="q" class="mb-1 block text-sm font-semibold">Quantity</label><input id="q" name="quantity" type="number" min="0" required class="<?= INPUT ?>"></div>
    <div class="sm:col-span-2 lg:col-span-5"><label for="l" class="mb-1 block text-sm font-semibold">Farm pickup location</label><input id="l" name="location" maxlength="120" required class="<?= INPUT ?>"></div>
    <div class="flex items-end"><button class="<?= $btn ?> w-full justify-center bg-leaf py-2.5 text-white hover:bg-leaf-dark"><?= icon('plus', 'h-4 w-4') ?> Add produce</button></div>
  </form>
</section>

<section class="mt-8" aria-labelledby="inv-h">
  <h2 id="inv-h" class="flex items-center gap-2 text-xl font-bold"><?= icon('basket', 'h-5 w-5 text-leaf') ?> Daily inventory</h2>
  <div class="mt-3 overflow-x-auto rounded-lg border border-soil/15 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-leaf-soft text-leaf-dark"><tr>
        <th class="<?= $th ?>">Produce</th><th class="<?= $th ?>">Location</th><th class="<?= $th ?>">Price (₱)</th><th class="<?= $th ?>">Quantity</th><th class="<?= $th ?>">Actions</th>
      </tr></thead>
      <tbody class="divide-y divide-soil/10">
      <?php if (!$products): ?><tr><td colspan="5" class="px-4 py-8 text-center text-soil/70">No produce yet. Add your first harvest above.</td></tr><?php endif; ?>
      <?php foreach ($products as $p): $id = (int)$p['id']; ?>
        <tr>
          <td class="px-4 py-3"><p class="font-semibold"><?= e($p['name']) ?></p><p class="text-xs text-soil/70"><?= e($p['category']) ?> / <?= e($p['unit']) ?></p></td>
          <td class="px-4 py-3"><?= e($p['location']) ?></td>
          <td class="px-4 py-3"><input form="u<?= $id ?>" name="price" type="number" step="0.01" min="0.01" value="<?= e($p['price']) ?>" aria-label="Price for <?= e($p['name']) ?>" class="<?= INPUT ?> w-28"></td>
          <td class="px-4 py-3"><input form="u<?= $id ?>" name="quantity" type="number" min="0" value="<?= (int)$p['quantity'] ?>" aria-label="Quantity for <?= e($p['name']) ?>" class="<?= INPUT ?> w-24"></td>
          <td class="px-4 py-3">
            <div class="flex gap-2">
              <form id="u<?= $id ?>" method="post"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $id ?>">
                <button class="<?= $btn ?> bg-leaf text-white hover:bg-leaf-dark"><?= icon('save', 'h-4 w-4') ?> Save</button></form>
              <form method="post" onsubmit="return confirm('Remove this produce and its orders?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $id ?>">
                <button class="<?= $btn ?> border border-red-300 text-red-800 hover:bg-red-50" aria-label="Remove <?= e($p['name']) ?>"><?= icon('trash', 'h-4 w-4') ?> Remove</button></form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="mt-8" aria-labelledby="ord-h">
  <h2 id="ord-h" class="flex items-center gap-2 text-xl font-bold"><?= icon('list', 'h-5 w-5 text-leaf') ?> Buyer orders</h2>
  <div class="mt-3 overflow-x-auto rounded-lg border border-soil/15 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-leaf-soft text-leaf-dark"><tr>
        <th class="<?= $th ?>">Ref</th><th class="<?= $th ?>">Buyer</th><th class="<?= $th ?>">Order</th><th class="<?= $th ?>">Method</th><th class="<?= $th ?>">Total</th><th class="<?= $th ?>">Status</th>
      </tr></thead>
      <tbody class="divide-y divide-soil/10">
      <?php if (!$orders): ?><tr><td colspan="6" class="px-4 py-8 text-center text-soil/70">No reservations yet. New orders from buyers will appear here.</td></tr><?php endif; ?>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td class="whitespace-nowrap px-4 py-3 font-semibold">AL-<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
          <td class="px-4 py-3"><p class="font-semibold"><?= e($o['buyer_name']) ?></p><p class="flex items-center gap-1 text-xs text-soil/70"><?= icon('phone', 'h-3.5 w-3.5') ?> <?= e($o['contact']) ?></p></td>
          <td class="px-4 py-3"><?= e($o['name']) ?> x <?= (int)$o['quantity'] ?> <?= e($o['unit']) ?></td>
          <td class="px-4 py-3"><p class="font-medium"><?= e($o['method']) ?></p><?php if ($o['address']): ?><p class="text-xs text-soil/70"><?= e($o['address']) ?></p><?php endif; ?></td>
          <td class="whitespace-nowrap px-4 py-3 font-semibold"><?= peso($o['price'] * $o['quantity']) ?></td>
          <td class="px-4 py-3">
            <form method="post" class="flex items-center gap-2">
              <input type="hidden" name="action" value="status"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
              <select name="status" aria-label="Status for order <?= (int)$o['id'] ?>" onchange="this.form.submit()" class="rounded-md border px-2 py-1.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-leaf/30 <?= $badge[$o['status']] ?>">
                <?php foreach (STATUSES as $s): ?><option value="<?= $s ?>" <?= $s === $o['status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
              </select>
              <noscript><button class="<?= $btn ?> bg-leaf text-white">Update</button></noscript>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

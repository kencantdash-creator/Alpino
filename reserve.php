<?php
require __DIR__ . '/includes/db.php';
$title = 'Reserve';
$errors = [];
$f = ['product_id' => (int)($_GET['product'] ?? 0), 'quantity' => 1, 'buyer_name' => '', 'contact' => '', 'method' => 'Pickup', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) { if (isset($_POST[$k]) && is_string($_POST[$k])) $f[$k] = trim($_POST[$k]); }
    $f['product_id'] = (int)$f['product_id'];
    $f['quantity'] = (int)$f['quantity'];

    if ($f['product_id'] < 1) $errors[] = 'Select a produce item.';
    if ($f['quantity'] < 1) $errors[] = 'Quantity must be at least 1.';
    if ($f['buyer_name'] === '' || mb_strlen($f['buyer_name']) > 80) $errors[] = 'Enter your full name.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $f['contact'])) $errors[] = 'Enter a valid contact number (digits only, 7 to 20 characters).';
    if (!in_array($f['method'], ['Pickup', 'Delivery'], true)) $f['method'] = 'Pickup';
    if ($f['method'] === 'Delivery' && ($f['address'] === '' || mb_strlen($f['address']) > 160)) $errors[] = 'Enter a delivery address.';

    if (!$errors) {
        $pdo->beginTransaction();
        $st = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
        $st->execute([$f['product_id']]);
        $prod = $st->fetch();
        if (!$prod) {
            $errors[] = 'That produce item no longer exists.';
        } elseif ($f['quantity'] > (int)$prod['quantity']) {
            $errors[] = 'Only ' . (int)$prod['quantity'] . ' ' . $prod['unit'] . ' of ' . $prod['name'] . ' is available.';
        }
        if (!$errors) {
            $pdo->prepare("INSERT INTO orders (product_id,buyer_name,contact,quantity,method,address) VALUES (?,?,?,?,?,?)")
                ->execute([$f['product_id'], $f['buyer_name'], $f['contact'], $f['quantity'], $f['method'], $f['method'] === 'Delivery' ? $f['address'] : null]);
            $oid = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")->execute([$f['quantity'], $f['product_id']]);
            $pdo->commit();
            header('Location: reserve.php?done=' . $oid);
            exit;
        }
        $pdo->rollBack();
    }
}

$done = null;
if (isset($_GET['done'])) {
    $st = $pdo->prepare("SELECT o.*, p.name, p.unit, p.price, p.location FROM orders o JOIN products p ON p.id = o.product_id WHERE o.id = ?");
    $st->execute([(int)$_GET['done']]);
    $done = $st->fetch() ?: null;
}
$products = $pdo->query("SELECT * FROM products WHERE quantity > 0 ORDER BY name")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<?php if ($done): ?>
<section class="mx-auto max-w-xl rounded-lg border border-leaf/40 bg-white p-6 sm:p-8">
  <div class="flex items-center gap-3 text-leaf"><?= icon('check', 'h-8 w-8') ?><h1 class="text-2xl font-bold">Reservation received</h1></div>
  <p class="mt-2 text-soil/80">Reference <strong>AL-<?= str_pad($done['id'], 5, '0', STR_PAD_LEFT) ?></strong>. The farmer will contact you at <?= e($done['contact']) ?> to confirm.</p>
  <dl class="mt-5 divide-y divide-soil/10 text-sm">
    <div class="flex justify-between py-2"><dt>Produce</dt><dd class="font-semibold"><?= e($done['name']) ?> x <?= (int)$done['quantity'] ?> <?= e($done['unit']) ?></dd></div>
    <div class="flex justify-between py-2"><dt>Total</dt><dd class="font-semibold"><?= peso($done['price'] * $done['quantity']) ?></dd></div>
    <div class="flex justify-between gap-4 py-2"><dt><?= e($done['method']) ?></dt><dd class="text-right font-semibold"><?= e($done['method'] === 'Delivery' ? $done['address'] : $done['location']) ?></dd></div>
    <div class="flex justify-between py-2"><dt>Status</dt><dd class="font-semibold"><?= e($done['status']) ?></dd></div>
  </dl>
  <a href="index.php" class="mt-6 inline-flex items-center gap-2 rounded-md bg-leaf px-5 py-2.5 font-semibold text-white hover:bg-leaf-dark"><?= icon('basket', 'h-4 w-4') ?> Back to catalog</a>
</section>
<?php else: ?>
<section class="mx-auto max-w-2xl">
  <h1 class="text-3xl font-bold">Reserve produce</h1>
  <p class="mt-2 text-soil/80">Choose what you need and tell the farmer how to reach you.</p>

  <?php if ($errors): ?>
    <div role="alert" class="mt-5 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-800">
      <p class="flex items-center gap-2 font-semibold"><?= icon('alert', 'h-5 w-5') ?> Fix the following:</p>
      <ul class="mt-2 list-disc pl-9"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <?php if (!$products): ?>
    <div class="mt-6 rounded-lg border border-dashed border-soil/30 bg-white p-10 text-center">All produce is sold out right now. Please check back after the next harvest.</div>
  <?php else: ?>
  <form method="post" class="mt-6 space-y-5 rounded-lg border border-soil/15 bg-white p-5 sm:p-7" novalidate>
    <div>
      <label for="product_id" class="mb-1 block text-sm font-semibold">Produce</label>
      <select id="product_id" name="product_id" required class="<?= INPUT ?>">
        <option value="">Select produce</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= (int)$p['id'] ?>" data-price="<?= e($p['price']) ?>" data-max="<?= (int)$p['quantity'] ?>" data-unit="<?= e($p['unit']) ?>" <?= $f['product_id'] === (int)$p['id'] ? 'selected' : '' ?>>
            <?= e($p['name']) ?> - <?= peso($p['price']) ?>/<?= e($p['unit']) ?> (<?= (int)$p['quantity'] ?> available)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
      <div>
        <label for="quantity" class="mb-1 block text-sm font-semibold">Order quantity</label>
        <input id="quantity" name="quantity" type="number" min="1" value="<?= (int)$f['quantity'] ?>" required class="<?= INPUT ?>">
        <p id="hint" class="mt-1 text-xs text-soil/70"></p>
      </div>
      <div>
        <span class="mb-1 block text-sm font-semibold">Estimated total</span>
        <p id="total" class="rounded-md bg-leaf-soft px-3 py-2.5 text-lg font-bold text-leaf-dark">₱0.00</p>
      </div>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
      <div>
        <label for="buyer_name" class="mb-1 block text-sm font-semibold">Full name</label>
        <input id="buyer_name" name="buyer_name" maxlength="80" value="<?= e($f['buyer_name']) ?>" required autocomplete="name" class="<?= INPUT ?>">
      </div>
      <div>
        <label for="contact" class="mb-1 block text-sm font-semibold">Contact number</label>
        <input id="contact" name="contact" type="tel" maxlength="20" value="<?= e($f['contact']) ?>" placeholder="09XX XXX XXXX" required autocomplete="tel" class="<?= INPUT ?>">
      </div>
    </div>
    <fieldset>
      <legend class="mb-2 text-sm font-semibold">Receive your order by</legend>
      <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex cursor-pointer items-center gap-3 rounded-md border border-soil/25 p-3 has-[:checked]:border-leaf has-[:checked]:bg-leaf-soft">
          <input type="radio" name="method" value="Pickup" class="accent-[#2E5E3E]" <?= $f['method'] === 'Pickup' ? 'checked' : '' ?>><?= icon('pin', 'h-5 w-5 text-leaf') ?> Farm pickup
        </label>
        <label class="flex cursor-pointer items-center gap-3 rounded-md border border-soil/25 p-3 has-[:checked]:border-leaf has-[:checked]:bg-leaf-soft">
          <input type="radio" name="method" value="Delivery" class="accent-[#2E5E3E]" <?= $f['method'] === 'Delivery' ? 'checked' : '' ?>><?= icon('truck', 'h-5 w-5 text-leaf') ?> Delivery
        </label>
      </div>
    </fieldset>
    <div id="addrBox" class="hidden">
      <label for="address" class="mb-1 block text-sm font-semibold">Delivery address</label>
      <input id="address" name="address" maxlength="160" value="<?= e($f['address']) ?>" placeholder="House no., street, barangay" class="<?= INPUT ?>">
    </div>
    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-md bg-leaf px-5 py-3 font-semibold text-white hover:bg-leaf-dark focus:outline-none focus:ring-2 focus:ring-leaf/40"><?= icon('check', 'h-5 w-5') ?> Confirm reservation</button>
  </form>
  <script>
    const sel = document.getElementById('product_id'), qty = document.getElementById('quantity'),
          total = document.getElementById('total'), hint = document.getElementById('hint'),
          box = document.getElementById('addrBox'), radios = document.querySelectorAll('[name=method]');
    function calc() {
      const o = sel.options[sel.selectedIndex];
      if (!o || !o.dataset.price) { total.textContent = '₱0.00'; hint.textContent = ''; return; }
      qty.max = o.dataset.max;
      hint.textContent = 'Up to ' + o.dataset.max + ' ' + o.dataset.unit;
      const t = (parseFloat(o.dataset.price) * (parseInt(qty.value) || 0));
      total.textContent = '₱' + t.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function addr() { box.classList.toggle('hidden', document.querySelector('[name=method]:checked').value !== 'Delivery'); }
    sel.addEventListener('change', calc); qty.addEventListener('input', calc);
    radios.forEach(r => r.addEventListener('change', addr));
    calc(); addr();
  </script>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

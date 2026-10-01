<?php
// AgriLink Community - database, helpers, icons
ob_start();
session_start();

// Admin login - change these before submitting
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'agrilink@2026';
function is_admin(): bool { return !empty($_SESSION['admin']); }
function require_admin(): void { if (!is_admin()) { header('Location: login.php'); exit; } }

const CATEGORIES = ['Vegetables', 'Fruits', 'Root Crops', 'Grains', 'Poultry & Eggs', 'Herbs'];
const STATUSES   = ['Pending', 'Confirmed', 'Ready', 'Completed', 'Cancelled'];
const INPUT = 'w-full rounded-md border border-soil/30 bg-white px-3 py-2.5 text-soil focus:border-leaf focus:outline-none focus:ring-2 focus:ring-leaf/30';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    die('Cannot connect to MySQL. Open XAMPP Control Panel and start MySQL.');
}
$pdo->exec("CREATE DATABASE IF NOT EXISTS agrilink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE agrilink");
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    category VARCHAR(30) NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    location VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    buyer_name VARCHAR(80) NOT NULL,
    contact VARCHAR(30) NOT NULL,
    quantity INT NOT NULL,
    method ENUM('Pickup','Delivery') NOT NULL,
    address VARCHAR(160) NULL,
    status ENUM('Pending','Confirmed','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB");

if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) {
    $seed = $pdo->prepare("INSERT INTO products (name,category,price,unit,quantity,location) VALUES (?,?,?,?,?,?)");
    foreach ([
        ['Native Tomato', 'Vegetables', 60, 'kg', 40, 'Purok 3, Brgy. Cabalawan'],
        ['Eggplant', 'Vegetables', 55, 'kg', 25, 'Purok 1, Brgy. Sagkahan'],
        ['Banana (Lakatan)', 'Fruits', 70, 'kg', 8, 'Purok 5, Brgy. San Jose'],
        ['Sweet Potato', 'Root Crops', 45, 'kg', 60, 'Purok 2, Brgy. Cabalawan'],
        ['Brown Rice', 'Grains', 62, 'kg', 100, 'Brgy. Abucay Farm Hub'],
        ['Native Eggs', 'Poultry & Eggs', 12, 'piece', 120, 'Purok 4, Brgy. Sagkahan'],
        ['Lemongrass', 'Herbs', 30, 'bundle', 0, 'Purok 5, Brgy. San Jose'],
    ] as $r) $seed->execute($r);
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function peso($v): string { return '₱' . number_format((float)$v, 2); }
function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function icon(string $name, string $cls = 'h-5 w-5'): string {
    static $p = [
        'leaf'    => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        'pin'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'basket'  => '<path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/>',
        'store'   => '<path d="m2 7 4.4-4.4A2 2 0 0 1 7.8 2h8.4a2 2 0 0 1 1.4.6L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M2 7h20v3a2 2 0 0 1-2 2 3 3 0 0 1-4-.5 3 3 0 0 1-4 0 3 3 0 0 1-4 0A3 3 0 0 1 4 12a2 2 0 0 1-2-2V7Z"/>',
        'search'  => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'plus'    => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'check'   => '<path d="M20 6 9 17l-5-5"/>',
        'trash'   => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'phone'   => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
        'truck'   => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.7a1 1 0 0 0-.2-.6l-3.5-4.4A1 1 0 0 0 17.5 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'list'    => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/>',
        'alert'   => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'user'    => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'lock'    => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'save'    => '<path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/>',
    ];
    return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $cls . ' shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

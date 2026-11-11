<?php
/**
 * loadClothingStore.php
 * Drops ALL ClothingStore tables and recreates them with full structure.
 * Seed data is inserted after each table is created.
 *
 * SAVE TO: C:\xampp\htdocs\WEDE ASSINGMENT 2\loadClothingStore.php
 * RUN VIA: http://localhost/WEDE%20ASSINGMENT%202/loadClothingStore.php
 *
 * Author: ClothingStore Team
 * Date:   2025
 */

// ─── FIX 1: Correct path — DBConn.php is inside /includes/ ───────────────────
// Wrong:  require_once __DIR__ . '/DbConn.php';
// Wrong:  require_once __DIR__ . '/DBConn.php';
// Correct: file lives in the /includes/ subfolder, capital D, B, C
require_once __DIR__ . '/includes/DBConn.php';

$conn = getDBConnection();

echo "<pre>";
echo "=== ClothingStore :: loadClothingStore.php ===\n\n";

// ─── Helper function: run a query and report success or failure ───────────────
// PHPMD fix: removed else clause — early return pattern used instead.
// The success branch returns immediately, so the error lines below
// only execute when $conn->query() returns false (query failed).
function runSQL(mysqli $conn, string $sql, string $message): void {
    if ($conn->query($sql)) {
        echo "  ✔  $message\n";
        return; // Success — exit function here, no else block needed
    }
    // Only reached if query() failed
    echo "  ✖  $message\n";
    echo "     Error: " . $conn->error . "\n\n";
}

// ─── Step 1: Disable FK checks so tables can be dropped in any order ─────────
runSQL($conn, "SET FOREIGN_KEY_CHECKS = 0", "FK checks disabled");

// ─── Step 2: Drop all existing tables ────────────────────────────────────────
echo "\n── Dropping existing tables ──────────────────────────────────\n";
runSQL($conn, "DROP TABLE IF EXISTS tblAorder",  "DROP tblAorder");
runSQL($conn, "DROP TABLE IF EXISTS tblClothes", "DROP tblClothes");
runSQL($conn, "DROP TABLE IF EXISTS tblUser",    "DROP tblUser");
runSQL($conn, "DROP TABLE IF EXISTS tblAdmin",   "DROP tblAdmin");

// ─── Step 3: Re-enable FK checks before creating tables ──────────────────────
runSQL($conn, "SET FOREIGN_KEY_CHECKS = 1", "FK checks re-enabled");

// ─── Step 4: Create tblAdmin ──────────────────────────────────────────────────
// FIX 2: Added ALL required columns with NOT NULL constraints
echo "\n── Creating tables ────────────────────────────────────────────\n";

runSQL($conn, "
CREATE TABLE IF NOT EXISTS tblAdmin (
    AdminID     INT           NOT NULL AUTO_INCREMENT,
    AdminName   VARCHAR(100)  NOT NULL,
    AdminEmail  VARCHAR(150)  NOT NULL UNIQUE,
    AdminPass   VARCHAR(255)  NOT NULL,
    CreatedAt   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_Admin PRIMARY KEY (AdminID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "CREATE tblAdmin");

// ─── Step 5: Create tblUser ───────────────────────────────────────────────────
// FIX 3: Added LastName — login.php uses $user['FirstName'] . ' ' . $user['LastName']
//        Added Phone, Address, Status, RegisteredAt — used by manage_users.php
runSQL($conn, "
CREATE TABLE IF NOT EXISTS tblUser (
    UserID       INT           NOT NULL AUTO_INCREMENT,
    FirstName    VARCHAR(80)   NOT NULL,
    LastName     VARCHAR(80)   NOT NULL,
    Email        VARCHAR(150)  NOT NULL UNIQUE,
    UserPass     VARCHAR(255)  NOT NULL,
    Phone        VARCHAR(20)   DEFAULT NULL,
    Address      TEXT          DEFAULT NULL,
    Status       ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
    RegisteredAt DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_User PRIMARY KEY (UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "CREATE tblUser");

// ─── Step 6: Create tblClothes ────────────────────────────────────────────────
// FIX 4: Added Description, Category, Size, StockQty, ImageFile, AdminID, CreatedAt
//        clothes.php reads ALL of these columns — they must exist
runSQL($conn, "
CREATE TABLE IF NOT EXISTS tblClothes (
    ClothesID   INT            NOT NULL AUTO_INCREMENT,
    ItemName    VARCHAR(150)   NOT NULL,
    Description TEXT           DEFAULT NULL,
    Category    VARCHAR(80)    NOT NULL,
    Size        VARCHAR(20)    NOT NULL,
    SellPrice   DECIMAL(10,2)  NOT NULL,
    StockQty    INT            NOT NULL DEFAULT 0,
    ImageFile   VARCHAR(200)   DEFAULT NULL,
    AdminID     INT            DEFAULT NULL,
    CreatedAt   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_Clothes     PRIMARY KEY (ClothesID),
    CONSTRAINT fk_Clothes_Admin FOREIGN KEY (AdminID)
        REFERENCES tblAdmin(AdminID)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "CREATE tblClothes");

// ─── Step 7: Create tblAorder ─────────────────────────────────────────────────
// FIX 5: Added Quantity, TotalPrice, OrderDate, OrderStatus
//        FIX 6: Declared REAL foreign key constraints with CONSTRAINT keyword
runSQL($conn, "
CREATE TABLE IF NOT EXISTS tblAorder (
    OrderID     INT            NOT NULL AUTO_INCREMENT,
    UserID      INT            NOT NULL,
    ClothesID   INT            NOT NULL,
    Quantity    INT            NOT NULL DEFAULT 1,
    TotalPrice  DECIMAL(10,2)  NOT NULL,
    OrderDate   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    OrderStatus ENUM('pending','confirmed','shipped','delivered','cancelled')
                               NOT NULL DEFAULT 'pending',
    CONSTRAINT pk_Order         PRIMARY KEY (OrderID),
    CONSTRAINT fk_Order_User    FOREIGN KEY (UserID)
        REFERENCES tblUser(UserID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_Order_Clothes FOREIGN KEY (ClothesID)
        REFERENCES tblClothes(ClothesID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "CREATE tblAorder");

// ─── Step 8: Insert seed data (5 rows per table minimum) ─────────────────────
echo "\n── Seeding data ───────────────────────────────────────────────\n";

// Admin seed — password for all is 'admin2025'
// MD5('admin2025') = d2b79f0adabea7fb9b53b56f7bd03b59
runSQL($conn, "
INSERT INTO tblAdmin (AdminName, AdminEmail, AdminPass) VALUES
    ('Super Admin',    'admin@clothingstore.co.za',    MD5('admin2025')),
    ('Jane Sithole',   'jane.s@clothingstore.co.za',   MD5('admin2025')),
    ('Thabo Nkosi',    'thabo.n@clothingstore.co.za',  MD5('admin2025')),
    ('Priya Pillay',   'priya.p@clothingstore.co.za',  MD5('admin2025')),
    ('Marcus Dlamini', 'marcus.d@clothingstore.co.za', MD5('admin2025'))
", "Seeded tblAdmin — 5 rows (password: admin2025)");

// User seed — password for all is 'password123'
// MD5('password123') = 482c811da5d5b4bc6d497ffa98491e38
runSQL($conn, "
INSERT INTO tblUser (FirstName, LastName, Email, UserPass, Phone, Address, Status) VALUES
    ('John',    'Doe',     'j.doe@abc.co.za',      MD5('password123'), '0821234567', '12 Rose St, Pretoria',         'active'),
    ('Sarah',   'Smith',   's.smith@xyz.co.za',    MD5('password123'), '0839876543', '5 Oak Ave, Johannesburg',       'active'),
    ('Michael', 'Johnson', 'm.johnson@mail.co.za', MD5('password123'), '0761112233', '88 Pine Rd, Cape Town',         'active'),
    ('Nomsa',   'Zulu',    'n.zulu@web.co.za',     MD5('password123'), '0724445566', '3 Lily Ln, Durban',             'active'),
    ('Pieter',  'van Wyk', 'p.vanwyk@net.co.za',   MD5('password123'), '0837778899', '21 Baobab St, Bloemfontein',    'pending')
", "Seeded tblUser — 5 rows (password: password123)");

// Clothes seed
runSQL($conn, "
INSERT INTO tblClothes (ItemName, Description, Category, Size, SellPrice, StockQty, ImageFile, AdminID) VALUES
    ('Classic Denim Jacket',   'Vintage style blue denim jacket, lightly used.', 'Jackets', 'M',  350.00, 3, 'denim_jacket.jpg',  1),
    ('Floral Summer Dress',    'Bright floral pattern, perfect for summer.',      'Dresses', 'S',  220.00, 5, 'floral_dress.jpg',  1),
    ('Slim Fit Chinos',        'Beige chinos in excellent condition.',            'Pants',   'L',  180.00, 4, 'chinos.jpg',        1),
    ('Graphic Tee - Pastimes', 'White cotton tee with brand logo.',              'Tops',    'XL', 120.00, 8, 'graphic_tee.jpg',   1),
    ('Leather Ankle Boots',    'Brown leather boots, size 7, barely worn.',      'Shoes',   '7',  550.00, 2, 'leather_boots.jpg', 1)
", "Seeded tblClothes — 5 rows");

// Orders seed
runSQL($conn, "
INSERT INTO tblAorder (UserID, ClothesID, Quantity, TotalPrice, OrderStatus) VALUES
    (1, 1, 1,  350.00, 'confirmed'),
    (2, 2, 2,  440.00, 'shipped'),
    (3, 3, 1,  180.00, 'delivered'),
    (4, 4, 3,  360.00, 'pending'),
    (1, 5, 1,  550.00, 'pending')
", "Seeded tblAorder — 5 rows");

echo "\n=== loadClothingStore.php completed successfully. ===\n";
echo "\nLogin credentials:\n";
echo "  User:  j.doe@abc.co.za       / password123\n";
echo "  Admin: admin@clothingstore.co.za / admin2025\n";
echo "</pre>";

$conn->close();
?>
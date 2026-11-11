<?php
/**
 * createTable.php
 * Checks if tblUser exists; if it does, drops it.
 * Then (re)creates tblUser and bulk-loads data from userData.txt.
 *
 * SAVE TO: C:\xampp\htdocs\WEDE ASSINGMENT 2\createTable.php
 * RUN VIA: http://localhost/WEDE%20ASSINGMENT%202/createTable.php
 *
 * Author: ClothingStore Team
 * Date:   2025
 */

// ─── Include the shared database connection ───────────────────────────────────
// DBConn.php lives in /includes/ — __DIR__ points to the project root
require_once __DIR__ . '/includes/DBConn.php';

// Open the database connection using the function from DBConn.php
$conn = getDBConnection();

echo "<pre>\n";
echo "=== ClothingStore :: createTable.php ===\n\n";

// ─── Step 1: Drop tblUser if it already exists ────────────────────────────────
// DROP TABLE IF EXISTS will NOT error if the table is missing — safe to run anytime
$dropSQL = "DROP TABLE IF EXISTS tblUser";

if ($conn->query($dropSQL) === TRUE) {
    echo "✔  tblUser dropped (or did not exist).\n";
} else {
    echo "✖  Error dropping tblUser: " . $conn->error . "\n";
    $conn->close();
    exit; // Stop here — no point continuing if drop failed
}

// ─── Step 2: Re-create tblUser ────────────────────────────────────────────────
// Define all columns with their data types and constraints
$createSQL = "
CREATE TABLE tblUser (
    UserID       INT          NOT NULL AUTO_INCREMENT,
    FirstName    VARCHAR(80)  NOT NULL,
    LastName     VARCHAR(80)  NOT NULL,
    Email        VARCHAR(150) NOT NULL UNIQUE,
    UserPass     VARCHAR(255) NOT NULL,
    Phone        VARCHAR(20)  DEFAULT NULL,
    Address      TEXT         DEFAULT NULL,
    Status       ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
    RegisteredAt DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_User PRIMARY KEY (UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if ($conn->query($createSQL) === TRUE) {
    echo "✔  tblUser created successfully.\n";
} else {
    echo "✖  Error creating tblUser: " . $conn->error . "\n";
    $conn->close();
    exit; // Stop here — no point loading data into a table that wasn't created
}

// ─── Step 3: Load data from userData.txt ─────────────────────────────────────
// Expected format per line (comma-separated, exactly 7 columns):
// FirstName,LastName,Email,UserPass(MD5hash),Phone,Address,Status
//
// Example:
// John,Doe,j.doe@abc.co.za,482c811da5d5b4bc6d497ffa98491e38,0821234567,12 Rose St Pretoria,active

$textFile = __DIR__ . '/userData.txt';

// Check the file actually exists before trying to read it
if (!file_exists($textFile)) {
    echo "✖  userData.txt not found at: $textFile\n";
    echo "   Please create userData.txt in the project root folder.\n";
    $conn->close();
    exit;
}

// Read all non-empty lines from the file into an array
$lines         = file($textFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$insertedCount = 0; // Track how many rows were successfully inserted

// ─── Prepare parameterised INSERT (prevents SQL injection) ───────────────────
// Using ? placeholders — bind_param fills them in safely
$stmt = $conn->prepare(
    "INSERT INTO tblUser
        (FirstName, LastName, Email, UserPass, Phone, Address, Status)
     VALUES
        (?, ?, ?, ?, ?, ?, ?)"
);

// Check prepare() worked — it returns false if there's a SQL syntax error
if (!$stmt) {
    echo "✖  Prepare failed: " . $conn->error . "\n";
    $conn->close();
    exit;
}

// Bind 7 string parameters to the 7 placeholders above
// "sssssss" = seven strings
$stmt->bind_param(
    "sssssss",
    $firstName, $lastName, $email,
    $userPass,  $phone,    $address, $status
);

// ─── Loop through each line of userData.txt ───────────────────────────────────
foreach ($lines as $lineNum => $line) {

    // Split on comma — limit of 7 allows addresses that contain commas
    $parts = explode(',', $line, 7);

    // Skip malformed lines that don't have exactly 7 columns
    if (count($parts) < 7) {
        echo "⚠  Line " . ($lineNum + 1) . " skipped — expected 7 columns, "
           . "got " . count($parts) . ".\n";
        continue;
    }

    // Assign each column to the bound variable — trim() removes whitespace
    $firstName = trim($parts[0]);
    $lastName  = trim($parts[1]);
    $email     = trim($parts[2]);
    $userPass  = trim($parts[3]); // Already stored as MD5 hash in the text file
    $phone     = trim($parts[4]);
    $address   = trim($parts[5]);
    $status    = trim($parts[6]);

    // Execute the prepared statement with the current row's values
    if ($stmt->execute()) {
        $insertedCount++;
        echo "  ✔  Inserted: $firstName $lastName <$email>\n";
    } else {
        // Show which line failed and why (e.g. duplicate email)
        echo "  ✖  Line " . ($lineNum + 1) . " failed: " . $stmt->error . "\n";
    }
}

// Clean up the prepared statement
$stmt->close();

echo "\n=== Done: $insertedCount record(s) loaded into tblUser. ===\n";
echo "</pre>\n";

// Close the database connection
$conn->close();
?>
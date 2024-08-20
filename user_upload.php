<?php

// Ayarlar (Settings)
$host = 'localhost';
$dbname = 'your_database';
$user = 'your_username';
$password = 'your_password';
$table = 'users';

// PDO_PGSQL sürücüsü mevcut mu? (Is PDO_PGSQL driver available?)
if (!extension_loaded('pdo_pgsql')) {
    die("Error: PDO_PGSQL driver is not available.\n");
}

// Komut satırı parametrelerini kontrol et (Check command line parameters)
$options = getopt('', ['test', 'create_table']);
$filename = 'users.csv';

// PostgreSQL veritabanına bağlan (Connect to PostgreSQL database)
try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
} catch (PDOException $e) {
    die("Error: Unable to connect to the database. " . $e->getMessage() . "\n");
}

if (isset($options['test'])) {
    // Test modu (Test mode)
    if (!file_exists($filename)) {
        die("Error: CSV file not found.\n");
    }

    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
    } catch (PDOException $e) {
        die("Error: Table '$table' is missing.\n");
    }

    echo "All tests passed successfully.\n";
    exit;
}

if (isset($options['create_table'])) {
    // Tablo oluşturma (Create table)
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS $table (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            surname VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL
        );
    ";

    try {
        $pdo->exec($createTableQuery);
        echo "Table '$table' created successfully.\n";
    } catch (PDOException $e) {
        die("Error: Unable to create table. " . $e->getMessage() . "\n");
    }

    exit;
}

// CSV dosyasını aç ve verileri oku (Open CSV file and read data)
if (($handle = fopen($filename, 'r')) !== false) {
    $pdo->beginTransaction();

    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $stmt = $pdo->prepare("INSERT INTO $table (name, surname, email) VALUES (?, ?, ?) ON CONFLICT (email) DO NOTHING");
        $stmt->execute([$data[0], $data[1], $data[2]]);
    }

    fclose($handle);
    $pdo->commit();

    echo "Data successfully inserted into the table.\n";
} else {
    die("Error: Unable to open CSV file.\n");
}

?>

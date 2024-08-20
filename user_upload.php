<?php

// Komut satırı parametrelerini kontrol et (Check command line parameters)
$options = getopt('u:p:d:h:f:?', ['create-table', 'create_table', 'file:', 'help']);

// Yardım ve seçenekleri göster (Show help and options)
if (isset($options['?']) || isset($options['help'])) {
    echo "Usage: php user_upload.php [options]\n";
    echo "Options:\n";
    echo "  -u username      PostgreSQL username\n";
    echo "  -p password      PostgreSQL password\n";
    echo "  -d database      PostgreSQL database name\n";
    echo "  -h host          PostgreSQL host address\n";
    echo "  --file filename  CSV file to parse\n";
    echo "  --create-table   Create the 'users' table (use --create_table or --create-table)\n";
    echo "  -? or --help     Display this help message\n";
    exit;
}

// Kullanıcı seçeneklerini doğrula (Validate user options)
$host = $options['h'] ?? 'localhost';
$dbname = $options['d'] ?? die("Error: Database name is required.\n");
$user = $options['u'] ?? die("Error: Username is required.\n");
$password = $options['p'] ?? die("Error: Password is required.\n");
$table = 'users';

// CSV dosyası yalnızca tablo oluşturulmadığında gerekli (CSV file is only required if not creating a table)
$filename = $options['file'] ?? $options['f'] ?? null;  // Ensure both long and short options are checked

// PDO_PGSQL sürücüsü mevcut mu? (Is PDO_PGSQL driver available?)
if (!extension_loaded('pdo_pgsql')) {
    die("Error: PDO_PGSQL driver is not available.\n");
}

// PostgreSQL veritabanına bağlan (Connect to PostgreSQL database)
try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
} catch (PDOException $e) {
    die("Error: Unable to connect to the database. " . $e->getMessage() . "\n");
}

if (isset($options['create-table']) || isset($options['create_table'])) {
    // Tablo var mı kontrol et (Check if the table exists)
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        // Table does not exist
    }

    if ($tableExists) {
        // Kullanıcıya sor (Ask the user)
        echo "The table '$table' exists. Would you like to drop the table and recreate it? [y/N]: ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if (strtolower($line) === 'y') {
            try {
                $pdo->exec("DROP TABLE IF EXISTS $table");
                echo "Table '$table' dropped successfully.\n";
            } catch (PDOException $e) {
                die("Error: Unable to drop the table. " . $e->getMessage() . "\n");
            }
        } else {
            echo "Table creation skipped.\n";
            exit;
        }
    }

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

// Bu noktada bir CSV dosyası gerekli (At this point, a CSV file is required)
if (!$filename) {
    die("Error: CSV file name is required.\n");
}

// Tablo mevcut mu kontrol et (Check if the table exists)
try {
    $pdo->query("SELECT 1 FROM $table LIMIT 1");
} catch (PDOException $e) {
    die("Error: Table '$table' is missing. Please run the script with '--create-table' to create the table.\n");
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

<?php

// Check command line parameters
$options = getopt('u:p:d:h:f:?', ['create-table', 'create_table', 'file:', 'dry-run', 'help']);

// Is PDO_PGSQL driver available?
if (!extension_loaded('pdo_pgsql')) {
    die("Error: PDO_PGSQL driver is not available.\n");
}

// If no parameters are provided, show help and options
if ($options === false || empty($options)) {
    echo "Usage: php user_upload.php [options]\n";
    echo "Options:\n";
    echo "  -u username      PostgreSQL username\n";
    echo "  -p password      PostgreSQL password\n";
    echo "  -d database      PostgreSQL database name\n";
    echo "  -h host          PostgreSQL host address\n";
    echo "  --file filename  CSV file to parse\n";
    echo "  --dry-run        Run script to validate CSV and database connection without inserting data\n";
    echo "  --create-table   Create the 'users' table (use --create_table or --create-table)\n";
    echo "  -? or --help     Display this help message\n";
    exit;
}

// Validate user options
$host = $options['h'] ?? 'localhost';
$dbname = $options['d'] ?? die("Error: Database name is required.\n");
$user = $options['u'] ?? die("Error: Username is required.\n");
$password = $options['p'] ?? die("Error: Password is required.\n");
$table = 'users';

// CSV file is only required IF NOT creating a table
$filename = $options['file'] ?? $options['f'] ?? null;
$isDryRun = isset($options['dry-run']);

// Connect to PostgreSQL database
try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
} catch (PDOException $e) {
    die("Error: Unable to connect to the database. " . $e->getMessage() . "\n");
}

if (isset($options['create-table']) || isset($options['create_table'])) {
    // Check if the table exists
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        // Table does not exist
    }
    if ($tableExists) {
        // Ask the user what to do with the existing table. Drop and recreate or leave it.
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
    // Create table
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
// a CSV file is needed here
if (!$filename) {
    die("Error: CSV file name is required.\n");
}
// Check if the table exists
try {
    $pdo->query("SELECT 1 FROM $table LIMIT 1");
} catch (PDOException $e) {
    die("Error: Table '$table' is missing. Please run the script with '--create-table' to create the table.\n");
}
// Open CSV file and check data
if (($handle = fopen($filename, 'r')) !== false) {
    // Duplicate detection arrays
    $csvEmails = [];
    
    // Skip header row
    fgetcsv($handle, 1000, ',');
    $pdo->beginTransaction();
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        // Ad ve soyadları büyük harfle başlat (Capitalize name and surname)
        $name = ucfirst(strtolower(trim($data[0])));
        $surname = ucfirst(strtolower(trim($data[1])));
        $email = strtolower(trim($data[2]));

        // Check for duplicates in CSV
        if (in_array($email, $csvEmails)) {
            echo "Warning: Duplicate entry found in CSV for email '$email'. Skipping...\n";
            continue;
        }
        $csvEmails[] = $email;

        // Validate name and surname
        if (!preg_match("/^[a-zA-Z' -]+$/", $name)) {
            echo "Error: Invalid name '$name'. Skipping...\n";
            continue;
        }
        if (!preg_match("/^[a-zA-Z' -]+$/", $surname)) {
            echo "Error: Invalid surname '$surname'. Skipping...\n";
            continue;
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Error: Invalid email '$email'. Skipping...\n";
            continue;
        }

        if ($isDryRun) {
            // Only show data, do not insert
            echo "Would insert: Name = $name, Surname = $surname, Email = $email\n";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO $table (name, surname, email) VALUES (?, ?, ?)");
                $stmt->execute([$name, $surname, $email]);
            } catch (PDOException $e) {
                if ($e->getCode() == '23505') { // 23505 is the code for unique constraint violation
                    echo "Warning: Duplicate email '$email' found in database!! Skipping...\n";
                } else {
                    echo "Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    fclose($handle);

    if (!$isDryRun) {
        $pdo->commit();
        echo "Data successfully inserted into the table.\n";
    } else {
        echo "Dry run complete. No data was inserted into the database.\n";
    }
} else {
    die("Error: Unable to open CSV file.\n");
}

?>

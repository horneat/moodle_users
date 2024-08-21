USER UPLOAD SCRIPT

This PHP script uploads user data from a CSV file into a PostgreSQL database.

Prerequisites
PHP 8.2.x
PostgreSQL database
pdo_pgsql PHP extension

USAGE
To run the script, use the following command:

php user_upload.php [options]

OPTIONS
-u username: PostgreSQL username (required)
-p password: PostgreSQL password (required)
-d database: PostgreSQL database name (required)
-h host: PostgreSQL host address (default: localhost)
--file filename or -f filename: CSV file to parse (required unless creating the table)
--dry-run: Validate CSV and database connection without inserting data
--create-table or --create_table: Create the 'users' table in the database
-? or --help: Display help and usage information

EXAMPLES
Create the users table:
php user_upload.php --create-table -u your_username -p your_password -d your_database -h your_host

Insert data from CSV file:
php user_upload.php --file your_file.csv -u your_username -p your_password -d your_database -h your_host

Dry run to validate CSV and database connection:
php user_upload.php --file your_file.csv --dry-run -u your_username -p your_password -d your_database -h your_host

NOTES
Table Creation: If the --create-table or --create_table option is used and the users table already exists, the script will prompt you to drop and recreate the table.
CSV File: The first row (header) of the CSV file is skipped, and data is inserted into the name, surname, and email fields.
Name and Surname Validation: Names and surnames must only contain letters, apostrophes, and hyphens. They are automatically capitalized before insertion.
Email Validation: Email addresses are validated for correct format and converted to lowercase before insertion.
Dry Run: Use the --dry-run option to check if the script can parse the CSV and connect to the database without actually inserting any data.
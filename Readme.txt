First thing first. 
It tests the presence of PDO_PGSQL driver first which everything depends on.
If there's no driver installed, then it throws an error and exits.
If driver is there, then we would move to test connectivity to PGSQL. 
If we've passed them both, then, let the fun begin!



Following command line options are included:
--file (or -f) to specify the CSV filename.
-u for PostgreSQL username.
-p for PostgreSQL password.
-d for PostgreSQL database name.
-h for PostgreSQL host address.
--create-table or --create_table to create the table.
--help to display quick help instructions.

- TABLE CREATION
If --create-table parameter was used;
- Checks if the table exists.
- If it exists, prompts the user to confirm whether to drop and recreate it.
- Skips table creation if user chooses [N]ot to drop the existing table.

- USAGE
php user_upload.php [options]

- OPTIONS
--file your_file.csv 	: Defines the name of CSV file to be used for input.
php user_upload.php --file your_file.csv -u db_username -p db_password -d your_database -h your_host

--dry-run				: Used with --file option to test readability of the CSV file and execution of all functions while NOT altering the database. Displays the parsed CSV file content.
php user_upload.php --file users.csv --dry-run -u your_username -p your_password -d your_database -h your_host

--create-table			: Creates the users table in PostgreSQL database (--create_table also works)
php user_upload.php --create-table -u your_username -p your_password -d your_database -h your_host

--help : Display help




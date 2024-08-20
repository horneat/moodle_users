First thing first. 
It tests the presence of PDO_PGSQL driver first which everything depends on.
If there's no driver installed, then it throws an error and exits.
If driver is there, then we would move to test connectivity to PGSQL. 
If we've passed them both, then, woohoo!

Following command line options are added:
--file or -f to specify the CSV file.
-u for PostgreSQL username.
-p for PostgreSQL password.
-db for PostgreSQL database name.
-h for PostgreSQL host address.
--create-table or --create_table to create the table.
-? or --help to display usage instructions.

- TABLE CREATION
If --create-table parameter was used;
- Checks if the table exists.
- If it exists, prompts the user to confirm whether to drop and recreate it.
- Skips table creation if user chooses not to drop the existing table.

- USAGE
php user_upload.php --file your_file.csv -u db_username -p db_password -db your_database -h your_host
php user_upload.php --create-table -u your_username -p your_password -db your_database -h your_host
php user_upload.php --help : Display help




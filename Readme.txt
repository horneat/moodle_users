First thing first. It should test the presence of PDO_PGSQL driver first which everything depends on.
If there's no driver installed, then it throws an error and exits.
If driver is there, then we would move to test connectivity to PGSQL. 
If we've passed them both, then, woohoo!
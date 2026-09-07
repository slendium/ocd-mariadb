# ocd-mariadb

MariaDB implementation of the [slendium/ocd](https://git.frisiapp.com/slendium/ocd) ORM.

## Installation

Run `composer require slendium/ocd-mariadb` to add it to your project.

## Testing

Testing the implementation requires setting up a test database.
Create a file called `phpunit-dbconfig.php` and declare the following constants: `MARIA_HOST`, `MARIA_DBNAME`,
`MARIA_USER` and `MARIA_PASS`.
All must be strings, but the username and password may also be `null`.
The test database must be empty before running the tests.

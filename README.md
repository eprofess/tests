## Install the Application

Run this command from the directory in which you want to install your new Slim Framework application.

    php composer.phar install 

Create `src/settings.php` file from default file`src/settings.default.php`.

Create `mysql` database.

Configure connection to database in `db` section in settings.php file.

* Point your virtual host document root to application's `public/` directory.
* Ensure `logs/` is web writeable.

Create tables by openig fixtures page: `/fixtures`

Run this command in the application directory to run the test suite

	php composer.phar test

That's it! Now go build something cool.

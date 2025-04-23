API Laravel EBilling
=

Laravel library to manage common operations used in electronic billing processes. <br>
It includes:
- <b>Document file manager:</b> store and retrieve request and response files used in electronic billing.


### Install

Run the following commands to install: <br>
```
composer require experteam/api-laravel-ebilling
```

### Update
Run the composer command to update the package: <br>
```
composer update experteam/api-laravel-ebilling
```

### Use
After installing the package, you should run the migration command to create the necessary tables in your database. <br>
```
php artisan migrate
```
this will create the following tables:
document_files

### License
[MIT license](https://opensource.org/licenses/MIT).

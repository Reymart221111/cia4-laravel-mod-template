# Authorization: Gates and Policies

## Gates
Gates provide a simple way to authorize user actions across your application. They are Closure-based approach to authorization.

### Defining Gates
Gates are defined in the `app/Libraries/AuthServiceProvider.php` file within the `register` method:

# CodeIgniter 4 with Laravel Features Template

This template extends CodeIgniter 4 with Laravel-like features for a more familiar development experience. The integration brings popular Laravel functionalities while maintaining CodeIgniter's lightweight nature.

## Added Features

### Laravel-style Commands
Located in `app/Commands/`:
- `LaravelMigrate.php` - Enhanced migration command
- `MakeLaravelMigration.php` - Create Laravel-style migrations
- `MakeLaravelModel.php` - Generate Laravel-compatible models
- `MakeLaravelRequest.php` - Create form request validation classes
# Make:Policy Command Documentation

The `make:policy` command generates authorization policy classes for your application, similar to Laravel's policy generator.

## Command Syntax
- `MakePolicy.php` - Generate authorization policies

### Configuration
New configurations in `app/Config/`:
- `Eloquent.php` - Laravel's Eloquent ORM configuration
- Other standard CodeIgniter configs remain unchanged

### Directory Structure
Standard CodeIgniter 4 structure with added Laravel-specific directories:
- `app/Facades/` - For Laravel-style facade patterns
- `app/Traits/` - Shared trait files

## Usage

### Creating Models
```bash
php spark make:laravel-model UserModel
```

### Creating Migrations
```bash
php spark make:laravel-migration create_users_table
```

### Running Migrations
```bash
php spark laravel:migrate
```

### Creating Form Requests
```bash
php spark make:laravel-request UserRequest
```

### Creating Policies
```bash
php spark make:policy UserPolicy
```

## Requirements
- PHP 8.1 or higher
- CodeIgniter 4.x
- Composer

## Installation
1. Clone this repository
2. Run `composer install`
3. Copy `env` to `.env`
4. Create your database on your database server eg. MySQL or PostgreSQL
5. Configure your database in `.env`
6. Run migrations: `php spark laravel:migrate`

## License
This template is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## CodeIgniter 4-Laravel Mod Template

This repository contains a template for Laravel developers who want to use CodeIgniter 4 as their backend framework. It provides a seamless integration of Laravel-like features into CodeIgniter, allowing developers to leverage the strengths of both frameworks. This template provides a starting point for developers who want to leverage the power of both frameworks while maintaining a consistent development experience. Syntax is similar to Laravel, making it easy for developers to switch between frameworks.

# Features Template

This template extends CodeIgniter 4 with Laravel-like features to provide a familiar development experience for Laravel developers. The integration brings popular Laravel functionalities while maintaining CodeIgniter's lightweight performance.

## Features

### Laravel Eloquent ORM

Includes Eloquent ORM integration for elegant database interactions:

- Intuitive ActiveRecord implementation
- Relationship mapping (hasMany, belongsTo, etc.)
- Query builder with chainable methods

### Form Request Validation

Laravel-style form request objects for clean validation:

- Separate validation logic from controllers
- Pre-validation middleware capabilities
- Custom validation rules

### Laravel Form Validation

Comprehensive validation system with familiar syntax:

- Rule-based validation
- Custom error messages
- Field-specific validation rules

### Gates and Policies

Authorization system mirroring Laravel's approach:

- Define access control through Gates
- Policy-based authorization for models
- Role-based permissions

### Simple Authentication

Streamlined authentication system:

- User registration and login
- User authentication middleware/filter
- User Session Information Getter

### Blade Templating Engine

Full Blade template integration:

- Layout inheritance
- Component support
- Directives and control structures

## Features Overview

### Authorization System

The template includes a Laravel-style authorization system with Gates and Policies.

#### Defining Gates

Gates are defined in `app/Libraries/AuthServiceProvider.php`:

```php
public function register(): void
{
    gate()->define('edit-post', function($user, $post) {
        return $user->id === $post->user_id;
    });
}
```

Using Gates in your code:

```php
if (can('edit-post', $post)) {
    // User can edit the post
}
```

#### Creating Policies

```bash
php spark make:policy PostPolicy
```

Example Policy:

```php
class PostPolicy
{
    public function update($user, $post)
    {
        return $user->id === $post->user_id;
    }
}
```

To use the `before` method in your custom policy, you'll need to add it to your existing `GoatPolicy` class. The `before` method will run before any other authorization checks and can either grant permission, deny permission, or defer to the specific policy method.

Here's how to implement it in your `GoatPolicy`:

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Goat;

class GoatPolicy
{
    /**
     * Handle all authorization checks before specific policy methods.
     *
     * @param  User    $user     The user requesting authorization
     * @param  string  $ability  The ability being checked
     * @param  Goat    $goat     The goat model instance (when applicable)
     * @return bool|null         True to allow, false to deny, null to continue to specific method
     */
    public function before(User $user, $ability, ?Goat $goat = null)
    {
        // Example: Super admins can perform any action
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Example: If the goat belongs to the user, allow all actions
        if ($goat && $goat->user_id === $user->id) {
            return true;
        }

        // Example: Block specific users from specific actions
        if ($user->isBlocked() && in_array($ability, ['create', 'update', 'delete'])) {
            return false;
        }

        // Return null to fall through to the specific policy method
        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    // ... other existing policy methods ...
}
```

The `before` method receives:

1. The current user
2. The name of the ability being checked (like 'update', 'view', etc.)
3. The model instance (when applicable)

It should return:

- `true` to grant permission immediately
- `false` to deny permission immediately
- `null` to continue to the specific policy method

The examples I've included show common use cases:

- Granting super admins full access
- Allowing users to manage their own resources
- Blocking specific users from certain actions

Adjust the conditions to fit your application's authorization requirements. Once implemented, the `before` method will automatically be called by the Gate whenever any policy method is checked.

### Form Request Validation

Create form request classes for validation:

```bash
php spark make:laravel-request CreatePostRequest
```

Example usage:

```php
class CreatePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|min:3',
            'content' => 'required'
        ];
    }
}
```

### Database Operations

#### Creating Models

```bash
php spark make:laravel-model Post
```

Generated model includes Eloquent features:

```php
class Post extends Model
{
    protected $fillable = ['title', 'content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#### Creating Migrations

```bash
php spark make:laravel-migration create_users_table
```

Example migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

```

### Eloquent ORM Usage

#### In Models

Models extend the Eloquent Model class for database operations:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'content'];

    // Define relationships
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Define scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
```

#### In Controllers

Use Eloquent methods in your controllers:

```php
namespace App\Controllers;

use App\Models\Post;

class PostController extends BaseController
{
    public function index()
    {
        // Fetch all posts
        $posts = Post::all();

        // Query builder
        $posts = Post::where('status', 'published')
                    ->orderBy('created_at', 'desc')
                    ->get();

        // With relationships
        $posts = Post::with('comments')->get();

        return blade_view('posts/index', ['posts' => $posts]);
    }
}
```

## Installation & Setup

1. Clone repository:

```bash
git clone https://github.com/Reymart221111/cia4-laravel-mod-template.git
```

2. Install dependencies:

```bash
composer install
```

3. Configure environment:

```bash
cp env .env
```

4. Update database configuration in `.env`:

use mysql, pgsql, sqlite, or sqlsrv as the configuration value for the database if you want to use eloquent features, else use CodeIgniter's default database configuration.

```
database.default.DBDriver = mysql
database.default.hostname = localhost
database.default.database = your_database
database.default.username = your_username
database.default.password = your_password
```

5. Run migrations:

```bash
php spark laravel:migrate
```

## Requirements

- PHP 8.1+
- CodeIgniter 4.x
- Composer

## License

MIT License - See [LICENSE](LICENSE) for details.

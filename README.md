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
git clone [repository-url]
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
```
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

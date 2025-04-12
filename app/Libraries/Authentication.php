<?php
namespace App\Libraries;

use App\Models\User;

/**
 * Authentication management class
 * 
 * Handles user authentication, login/logout functionality, and session management
 */
class Authentication
{
    /**
     * Session service instance
     * 
     * @var \CodeIgniter\Session\Session
     */
    protected $session;
    
    /**
     * Currently authenticated user
     * 
     * @var User|null
     */
    protected $user = null;
    
    /**
     * Constructor
     * 
     * Initializes the session service
     */
    public function __construct()
    {
        $this->session = \Config\Services::session();
    }
    
    /**
     * Get the currently authenticated user
     * 
     * Retrieves the authenticated user from cache or database
     * 
     * @return User|null The authenticated user or null if not authenticated
     */
    public function user(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }
        
        $userId = $this->session->get('auth_user_id');
        if (!$userId) {
            return null;
        }
        
        $this->user = User::find($userId);
        return $this->user;
    }
    
    /**
     * Check if a user is currently authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }
    
    /**
     * Attempt to authenticate a user with the provided credentials
     * 
     * @param array $credentials Credentials containing 'email' and 'password'
     * @return bool True if authentication successful, false otherwise
     */
    public function attempt(array $credentials): bool
    {
        $user = User::where('email', $credentials['email'])->first();
        
        if ($user && password_verify($credentials['password'], $user->password)) {
            $this->login($user);
            return true;
        }
        
        return false;
    }
    
    /**
     * Log in a user
     * 
     * Sets up the session for the authenticated user
     * 
     * @param User $user The user to log in
     * @return bool Always returns true on success
     */
    public function login($user): bool
    {
        $this->session->set('auth_user_id', $user->id);
        $this->user = $user;
        
        // Regenerate session ID for security
        $this->session->regenerate(true);
        
        return true;
    }
    
    /**
     * Log out the current user
     * 
     * Clears user data from session and regenerates session ID
     * 
     * @return bool Always returns true on success
     */
    public function logout(): bool
    {
        $this->user = null;
        $this->session->remove('auth_user_id');
        $this->session->regenerate(true);
        
        return true;
    }
}
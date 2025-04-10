<?php
namespace App\Libraries;

use App\Models\User;

class Authentication
{
    protected $session;
    protected $user = null;
    
    public function __construct()
    {
        $this->session = \Config\Services::session();
    }
    
    public function user()
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
    
    public function check()
    {
        return $this->user() !== null;
    }
    
    public function attempt(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();
        
        if ($user && password_verify($credentials['password'], $user->password)) {
            $this->login($user);
            return true;
        }
        
        return false;
    }
    
    public function login($user)
    {
        $this->session->set('auth_user_id', $user->id);
        $this->user = $user;
        
        // Regenerate session ID for security
        $this->session->regenerate(true);
        
        return true;
    }
    
    public function logout()
    {
        $this->user = null;
        $this->session->remove('auth_user_id');
        $this->session->regenerate(true);
        
        return true;
    }
}
<?php

namespace App\Exceptions;

use CodeIgniter\HTTP\RedirectResponse;
use Exception;

class ValidationException extends Exception
{
    protected $response;

    public function __construct(RedirectResponse $response)
    {
        parent::__construct('Validation failed');
        $this->response = $response;    
        $this->handleResponse();
    }

    public function getResponse()
    {
        return $this->response;
    }
    
    protected function handleResponse()
    {
        $this->response->send();
        exit;
    }
}
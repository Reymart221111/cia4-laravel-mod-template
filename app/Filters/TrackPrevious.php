<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;

class TrackPrevious implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');
    
        // Skip AJAX requests
        if ($request instanceof IncomingRequest && $request->isAJAX()) {
            return;
        }
    
        // For GET requests, always update the 'url.previous'
        if ($request->getMethod() === 'get') {
            $session->set('url.previous', current_url(true)->getPath() . '?' . $_SERVER['QUERY_STRING']);
        }
    }
    

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed here anymore
    }
}

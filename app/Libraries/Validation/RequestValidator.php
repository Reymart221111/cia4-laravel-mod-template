<?php

namespace App\Libraries\Validation;

use App\Exceptions\ValidationException;
use CodeIgniter\HTTP\IncomingRequest;

class RequestValidator
{
     /**
     * Validate request data directly from controller
     *
     * @param array $rules Validation rules
     * @param array $messages Custom error messages (optional)
     * @param array $attributes Custom attribute names (optional)
     * @return \App\Libraries\Validation\ValidatedData Object containing validated data and helper methods
     * @throws ValidationException When validation fails
     */
    public static function validate(array $rules, array $messages = [], array $attributes = []): ValidatedData
    {
        $request = \Config\Services::request();
        $validator = new LaravelValidator();
        
        // Combine POST data and FILES
        $data = array_merge($request->getPost(), self::collectFiles($request));
        
        $result = $validator->validate($data, $rules, $messages, $attributes);
        
        if (!$result['success']) {
            $response = redirect()->back()
                ->withInput()
                ->with('errors', $result['errorsByField']);
            
            throw new ValidationException($response);
        }
        
        return new ValidatedData($result['validated']);
    }
    
    /**
     * Collect files from the request and format them for validation
     * 
     * @param IncomingRequest $request The request instance
     * @return array Array of processed files
     */
    protected static function collectFiles(IncomingRequest $request)
    {
        $files = [];
        $uploadedFiles = $request->getFiles();

        if (empty($uploadedFiles)) {
            return $files;
        }

        foreach ($uploadedFiles as $fieldName => $fileInfo) {
            if (is_array($fileInfo)) {
                $files[$fieldName] = self::processMultipleFiles($fileInfo);
            } else {
                $files[$fieldName] = self::processSingleFile($fileInfo);
            }
        }

        return $files;
    }

    /**
     * Process multiple files from the same input
     * 
     * @param array $fileInfoArray Array of uploaded files
     * @return array Processed files in Laravel-compatible format
     */
    private static function processMultipleFiles(array $fileInfoArray)
    {
        $processedFiles = [];

        foreach ($fileInfoArray as $key => $file) {
            if ($file->isValid()) {
                $processedFiles[$key] = self::formatFileData($file);
            }
        }

        return $processedFiles;
    }

    /**
     * Process a single file upload
     * 
     * @param object $file The uploaded file object
     * @return array|null File in Laravel-compatible format or null if invalid
     */
    private static function processSingleFile($file)
    {
        return $file->isValid() ? self::formatFileData($file) : null;
    }

    /**
     * Format file data into Laravel-compatible structure
     * 
     * @param object $file The uploaded file object
     * @return array Formatted file data
     */
    private static function formatFileData($file)
    {
        return [
            'name' => $file->getName(),
            'type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'tmp_name' => $file->getTempName(),
            'error' => 0,
            '_ci_file' => $file // Store the original file for later use
        ];
    }
}
<?php
class ApiResponse
{
    public static function success($data = null, $message = 'Success', $code = 200)
    {
        http_response_code($code);
        return json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function error($message = 'Error', $code = 400, $errors = null)
    {
        http_response_code($code);
        return json_encode([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ]);
    }

    public static function unauthorized($message = 'Unauthorized access')
    {
        return self::error($message, 401);
    }

    public static function notFound($message = 'Resource not found')
    {
        return self::error($message, 404);
    }

    public static function validation($errors, $message = 'Validation failed')
    {
        return self::error($message, 422, $errors);
    }
}
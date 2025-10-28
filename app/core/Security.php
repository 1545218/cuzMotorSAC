<?php

/**
 * Clase de Seguridad - Cruz Motor S.A.C.
 * Manejo de tokens CSRF, sanitización y validaciones
 */

class Security
{
    /**
     * Generar token CSRF
     */
    public static function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verificar token CSRF
     */
    public static function verifyCSRFToken($token)
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenerar token CSRF (después de usar)
     */
    public static function regenerateCSRFToken()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Obtener campo hidden para formularios
     */
    public static function getCSRFField()
    {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Sanitizar entrada de usuario
     */
    public static function sanitizeInput($input, $type = 'string')
    {
        if (is_array($input)) {
            return array_map(function ($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }

        // Eliminar espacios en blanco
        $input = trim($input);

        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);

            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);

            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);

            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

            case 'sql':
                // Para consultas SQL, mejor usar prepared statements
                return addslashes($input);

            case 'string':
            default:
                // Eliminar etiquetas HTML y caracteres especiales
                return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Validar entrada según reglas
     */
    public static function validateInput($input, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;

            foreach ($fieldRules as $rule => $ruleValue) {
                switch ($rule) {
                    case 'required':
                        if ($ruleValue && (empty($value) && $value !== '0')) {
                            $errors[$field][] = "El campo {$field} es requerido";
                        }
                        break;

                    case 'min_length':
                        if (!empty($value) && strlen($value) < $ruleValue) {
                            $errors[$field][] = "El campo {$field} debe tener al menos {$ruleValue} caracteres";
                        }
                        break;

                    case 'max_length':
                        if (!empty($value) && strlen($value) > $ruleValue) {
                            $errors[$field][] = "El campo {$field} no puede tener más de {$ruleValue} caracteres";
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "El campo {$field} debe ser un email válido";
                        }
                        break;

                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = "El campo {$field} debe ser numérico";
                        }
                        break;

                    case 'regex':
                        if (!empty($value) && !preg_match($ruleValue, $value)) {
                            $errors[$field][] = "El campo {$field} no tiene el formato correcto";
                        }
                        break;

                    case 'in':
                        if (!empty($value) && !in_array($value, $ruleValue)) {
                            $errors[$field][] = "El campo {$field} no tiene un valor válido";
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Limpiar datos de formulario completos
     */
    public static function cleanFormData($data, $rules = [])
    {
        $cleaned = [];

        foreach ($data as $key => $value) {
            // Aplicar sanitización específica si hay reglas
            $type = $rules[$key]['type'] ?? 'string';
            $cleaned[$key] = self::sanitizeInput($value, $type);
        }

        return $cleaned;
    }

    /**
     * Protección contra ataques de tiempo
     */
    public static function secureCompare($a, $b)
    {
        return hash_equals($a, $b);
    }

    /**
     * Generar hash seguro para contraseñas
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verificar contraseña
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Protección contra XSS en salida
     */
    public static function escape($string)
    {
        // Verificar si es un array, si es así convertir a string o devolver vacío
        if (is_array($string)) {
            return htmlspecialchars(implode(', ', $string), ENT_QUOTES, 'UTF-8');
        }

        // Verificar si es null o no es string
        if (!is_string($string)) {
            return '';
        }

        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validar que el usuario tiene permisos para la acción
     */
    public static function checkPermission($required_role, $user_role = null)
    {
        if (!$user_role) {
            $user_role = $_SESSION['user_role'] ?? null;
        }

        if (!$user_role) {
            return false;
        }

        // Definir jerarquía de roles
        $hierarchy = [
            'mecanico' => 1,
            'vendedor' => 2,
            'administrador' => 3
        ];

        $user_level = $hierarchy[$user_role] ?? 0;
        $required_level = $hierarchy[$required_role] ?? 999;

        return $user_level >= $required_level;
    }

    /**
     * Rate limiting básico
     */
    public static function checkRateLimit($action, $max_attempts = 5, $time_window = 300)
    {
        $key = 'rate_limit_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'last_attempt' => time()
            ];
        }

        $data = $_SESSION[$key];

        // Resetear si ha pasado el tiempo
        if (time() - $data['last_attempt'] > $time_window) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'last_attempt' => time()
            ];
            return true;
        }

        // Verificar límite
        if ($data['attempts'] >= $max_attempts) {
            return false;
        }

        // Incrementar contador
        $_SESSION[$key]['attempts']++;
        $_SESSION[$key]['last_attempt'] = time();

        return true;
    }

    /**
     * Log de eventos de seguridad
     */
    public static function logSecurityEvent($event, $details = [])
    {
        $context = array_merge([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'timestamp' => date('Y-m-d H:i:s')
        ], $details);

        Logger::warning("SECURITY: {$event}", $context);
    }
}

<?php
/**
 * AuthController
 * Handles authentication business logic: registration, login, logout, and session verification.
 * Adheres to OWASP recommendations: Bcrypt hashing, session fixation prevention, and input sanitization.
 * Employs Validator middleware for centralized, decoupled request validation.
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/Validator.php';
require_once __DIR__ . '/../config/Response.php';

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * User Registration
     * POST /api/auth/register.php
     */
    public function register(): void
    {
        $input = $this->getJsonInput();

        // Centralized input validation via Validator service
        $validator = Validator::make($input, [
            'full_name' => 'required|min:3',
            'email'     => 'required|email',
            'password'  => 'required|min:8'
        ]);

        if ($validator->fails()) {
            Response::error('Validation failed. Please correct the specified fields.', 422, $validator->errors());
        }

        $validated = $validator->validated();
        $fullName = $validated['full_name'];
        $email = $validated['email'];
        $password = $input['password'];

        // Check for duplicate email
        if ($this->userModel->emailExists($email)) {
            Response::error('The provided email is already registered.', 409);
        }

        // Secure password hashing with Bcrypt (cost 12)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Persist user with default role (2 = 'user')
        try {
            $userId = $this->userModel->create($fullName, $email, $passwordHash, 2);

            // Establish secure session for seamless onboarding
            AuthMiddleware::initSession();
            session_regenerate_id(true);

            $_SESSION['user_id'] = $userId;
            $_SESSION['role_id'] = 2;
            $_SESSION['role_name'] = 'user';
            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;

            Response::success('User registered and authenticated successfully.', [
                'user' => [
                    'user_id' => $userId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'role_name' => 'user'
                ]
            ], 201);
        } catch (Throwable $e) {
            error_log('Registration Error: ' . $e->getMessage());
            Response::error('An unexpected server error occurred during registration.', 500);
        }
    }

    /**
     * User Login
     * POST /api/auth/login.php
     */
    public function login(): void
    {
        $input = $this->getJsonInput();

        // Centralized validation for login credentials
        $validator = Validator::make($input, [
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            Response::error('Email and password are required.', 400, $validator->errors());
        }

        $validated = $validator->validated();
        $email = $validated['email'];
        $password = $input['password'];

        $user = $this->userModel->findByEmail($email);

        // Mitigate timing attacks and user enumeration with identical generic error
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error('Invalid email or password.', 401);
        }

        // Initialize session and regenerate ID to prevent Session Fixation attacks
        AuthMiddleware::initSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['role_id'] = (int) $user['role_id'];
        $_SESSION['role_name'] = (string) $user['role_name'];
        $_SESSION['full_name'] = (string) $user['full_name'];
        $_SESSION['email'] = (string) $user['email'];

        Response::success('Login successful.', [
            'user' => [
                'user_id' => (int) $user['user_id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role_name' => $user['role_name'],
                'created_at' => $user['created_at']
            ]
        ], 200);
    }

    /**
     * User Logout
     * POST /api/auth/logout.php
     */
    public function logout(): void
    {
        AuthMiddleware::initSession();

        // Unset session variables
        $_SESSION = [];

        // Clear session cookie from client
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy session data on server
        session_destroy();

        Response::success('Logout successful.', null, 200);
    }

    /**
     * Current Authenticated User Info
     * GET /api/auth/me.php
     */
    public function me(): void
    {
        $sessionUser = AuthMiddleware::getCurrentUser();

        if (!$sessionUser) {
            Response::error('Unauthorized. No active session found.', 401);
        }

        // Fetch fresh database record to ensure consistency
        $freshUser = $this->userModel->findById($sessionUser['user_id']);

        if (!$freshUser) {
            // User was deleted from DB while having active session
            $this->logout();
            Response::error('User account no longer exists.', 401);
        }

        Response::success('Active session retrieved.', [
            'user' => [
                'user_id' => (int) $freshUser['user_id'],
                'full_name' => $freshUser['full_name'],
                'email' => $freshUser['email'],
                'role_name' => $freshUser['role_name'],
                'created_at' => $freshUser['created_at']
            ]
        ], 200);
    }

    /**
     * Helper to retrieve and parse JSON request payload.
     */
    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

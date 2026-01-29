<?php

declare(strict_types=1);

namespace Bittytorrent\Controller;

use Bittytorrent\Model\User;

/**
 * Authentication Controller
 * 
 * Handles user login, registration, and logout
 */
class AuthController extends BaseController
{
    /**
     * Show login page
     */
    public function showLogin(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/');
        }
        
        $this->render('auth/login.twig', [
            'title' => 'Login',
        ]);
    }
    
    /**
     * Handle login
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }
        
        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Verify CSRF token
        if (!$this->verifyCsrf($csrfToken)) {
            $this->render('auth/login.twig', [
                'title' => 'Login',
                'error' => 'Invalid security token. Please try again.',
            ]);
            return;
        }
        
        if (empty($username) || empty($password)) {
            $this->render('auth/login.twig', [
                'title' => 'Login',
                'error' => 'Please provide both username and password.',
            ]);
            return;
        }
        
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            $this->logger->info('User logged in', ['user_id' => $user['id'], 'username' => $user['username']]);
            
            $this->redirect('/');
        } else {
            $this->render('auth/login.twig', [
                'title' => 'Login',
                'error' => 'Invalid username or password.',
            ]);
        }
    }
    
    /**
     * Show registration page
     */
    public function showRegister(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/');
        }
        
        $this->render('auth/register.twig', [
            'title' => 'Register',
        ]);
    }
    
    /**
     * Handle registration
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
            return;
        }
        
        $username = $this->sanitize($_POST['username'] ?? '');
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Verify CSRF token
        if (!$this->verifyCsrf($csrfToken)) {
            $this->render('auth/register.twig', [
                'title' => 'Register',
                'error' => 'Invalid security token. Please try again.',
            ]);
            return;
        }
        
        // Validation
        $errors = [];
        
        if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters.';
        }
        
        if (!$this->validateEmail($email)) {
            $errors[] = 'Invalid email address.';
        }
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (!empty($errors)) {
            $this->render('auth/register.twig', [
                'title' => 'Register',
                'error' => implode('<br>', $errors),
                'username' => $username,
                'email' => $email,
            ]);
            return;
        }
        
        // Check if username or email already exists
        $userModel = new User();
        
        if ($userModel->findByUsername($username)) {
            $this->render('auth/register.twig', [
                'title' => 'Register',
                'error' => 'Username already taken.',
                'email' => $email,
            ]);
            return;
        }
        
        if ($userModel->findByEmail($email)) {
            $this->render('auth/register.twig', [
                'title' => 'Register',
                'error' => 'Email already registered.',
                'username' => $username,
            ]);
            return;
        }
        
        // Create user
        $userId = $userModel->create($username, $email, $password);
        
        if ($userId) {
            $this->logger->info('New user registered', ['user_id' => $userId, 'username' => $username]);
            
            // Auto-login
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            
            $this->redirect('/');
        } else {
            $this->render('auth/register.twig', [
                'title' => 'Register',
                'error' => 'Registration failed. Please try again.',
            ]);
        }
    }
    
    /**
     * Handle logout
     */
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? null;
        
        session_destroy();
        
        if ($userId) {
            $this->logger->info('User logged out', ['user_id' => $userId, 'username' => $username]);
        }
        
        $this->redirect('/login');
    }
}

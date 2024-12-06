<?php
define('ENVIRONMENT', 'development');
require_once 'includes/db_connect.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // Validate input
        if (empty($username) || empty($password)) {
            throw new Exception("Please enter both username and password.");
        }

        // Get user from database
        $stmt = $pdo->prepare("SELECT user_id, username, password, role FROM `user` WHERE username = ? AND password = ? LIMIT 1");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();

        // Check if user exists
        if (!$user) {
            throw new Exception("Invalid username or password.");
        }

        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // If the user is a supplier, retrieve the supplier_id
        if ($user['role'] === 'Supplier') {
            $stmt = $pdo->prepare("SELECT supplier_id FROM supplier WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            $supplier = $stmt->fetch();

            if ($supplier) {
                $_SESSION['supplier_id'] = $supplier['supplier_id']; // Store supplier_id in session
            } else {
                throw new Exception("Supplier ID not found for this user.");
            }
        }

        // Update last login time
        $pdo->prepare("UPDATE user SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?")->execute([$user['user_id']]);

        // Redirect to home page after login
        header("Location: index.php");
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hospital Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 to-teal-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white/90 backdrop-blur-lg rounded-3xl shadow-2xl p-10 animate__animated animate__fadeIn">
            <div class="text-center mb-10">
                <div class="flex justify-center mb-6">
                    <div class="bg-gradient-to-r from-green-600 to-teal-600 p-5 rounded-full shadow-lg">
                        <i class="fas fa-hospital text-4xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">Welcome Back</h1>
                <p class="text-teal-600 font-medium">Hospital Management System</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 animate__animated animate__shake" role="alert">
                    <p class="font-medium">Login Error</p>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" id="username" name="username" required
                               class="w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition-colors"
                               placeholder="Enter your username">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition-colors"
                               placeholder="Enter your password">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-300 transform hover:scale-[1.02] flex items-center justify-center space-x-2 shadow-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </button>
            </form>

            <?php if (ENVIRONMENT === 'development'): ?>
            <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <p class="text-sm text-gray-600 font-medium mb-2">Demo Credentials:</p>
                <div class="space-y-1 text-sm text-gray-500">
                    <p><span class="font-medium">Admin:</span> admin / password</p>
                    <p><span class="font-medium">Doctor:</span> doctor / password</p>
                    <p><span class="font-medium">Nurse:</span> nurse / password</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                <p class="text-gray-600 text-sm">
                    Don't have an account? 
                    <a href="signup.php" class="text-teal-600 hover:text-teal-700 font-semibold transition-colors">
                        Sign up here
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

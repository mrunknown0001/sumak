{{-- <!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    
    @if ($errors->any())
        <div style="color: red;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        
        <div>
            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div>
            <label for="password_confirmation">Confirm Password:</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        
        <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">Reset Password</button>
    </form>
</body>
</html> --}}



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SumakQuiz</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-green-600">SumakQuiz</h1>
                <p class="text-gray-600 mt-2">Reset Password</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" id="resetForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">
                        Email
                    </label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}"
                        required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    >
                    <p id="emailError" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            class="shadow appearance-none border rounded w-full py-2 px-3 pr-12 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        >
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-sm font-medium text-gray-600 hover:text-gray-800 focus:outline-none focus:underline"
                            data-toggle="password"
                            data-target="password"
                        >
                            Show
                        </button>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="mt-2">
                        <div class="flex gap-1 mb-1">
                            <div id="strength-bar-1" class="h-1 flex-1 bg-gray-200 rounded transition-colors duration-300"></div>
                            <div id="strength-bar-2" class="h-1 flex-1 bg-gray-200 rounded transition-colors duration-300"></div>
                            <div id="strength-bar-3" class="h-1 flex-1 bg-gray-200 rounded transition-colors duration-300"></div>
                            <div id="strength-bar-4" class="h-1 flex-1 bg-gray-200 rounded transition-colors duration-300"></div>
                        </div>
                        <p id="strengthText" class="text-xs text-gray-600"></p>
                    </div>
                    
                    <div class="mt-2 text-xs text-gray-600">
                        <p class="font-semibold mb-1">Password must contain:</p>
                        <ul class="space-y-1">
                            <li id="req-length" class="flex items-center">
                                <span class="mr-2">○</span> At least 8 characters
                            </li>
                            <li id="req-uppercase" class="flex items-center">
                                <span class="mr-2">○</span> One uppercase letter
                            </li>
                            <li id="req-lowercase" class="flex items-center">
                                <span class="mr-2">○</span> One lowercase letter
                            </li>
                            <li id="req-number" class="flex items-center">
                                <span class="mr-2">○</span> One number
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            class="shadow appearance-none border rounded w-full py-2 px-3 pr-12 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        >
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-sm font-medium text-gray-600 hover:text-gray-800 focus:outline-none focus:underline"
                            data-toggle="password"
                            data-target="password_confirmation"
                        >
                            Show
                        </button>
                    </div>
                    <p id="passwordMatchError" class="text-red-500 text-xs mt-1 hidden"></p>
                    <p id="passwordMatchSuccess" class="text-green-600 text-xs mt-1 hidden">✓ Passwords match</p>
                </div>

                <div class="flex items-center justify-between">
                    <button 
                        type="submit"
                        id="submitBtn"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    >
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('resetForm');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            const passwordInput = document.getElementById('password');
            const passwordConfirmation = document.getElementById('password_confirmation');
            const passwordMatchError = document.getElementById('passwordMatchError');
            const passwordMatchSuccess = document.getElementById('passwordMatchSuccess');
            const strengthText = document.getElementById('strengthText');
            const submitBtn = document.getElementById('submitBtn');

            // Password toggle functionality
            document.querySelectorAll('[data-toggle="password"]').forEach((button) => {
                const targetId = button.dataset.target;
                const target = document.getElementById(targetId);

                if (!target) return;

                button.addEventListener('click', () => {
                    const isHidden = target.type === 'password';
                    target.type = isHidden ? 'text' : 'password';
                    button.textContent = isHidden ? 'Hide' : 'Show';
                });
            });

            // Email validation
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            function checkEmail() {
                const email = emailInput.value.trim();
                
                if (email === '') {
                    emailError.classList.add('hidden');
                    emailInput.classList.remove('border-red-500', 'border-green-500');
                    return true;
                }
                
                if (!validateEmail(email)) {
                    emailError.textContent = 'Please enter a valid email address';
                    emailError.classList.remove('hidden');
                    emailInput.classList.add('border-red-500');
                    emailInput.classList.remove('border-green-500');
                    return false;
                } else {
                    emailError.classList.add('hidden');
                    emailInput.classList.remove('border-red-500');
                    emailInput.classList.add('border-green-500');
                    return true;
                }
            }

            emailInput.addEventListener('input', checkEmail);
            emailInput.addEventListener('blur', checkEmail);

            // Password strength checker
            function checkPasswordStrength(password) {
                let strength = 0;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                };

                // Update requirement indicators
                document.getElementById('req-length').innerHTML = requirements.length 
                    ? '<span class="mr-2 text-green-600">✓</span> At least 8 characters' 
                    : '<span class="mr-2">○</span> At least 8 characters';
                
                document.getElementById('req-uppercase').innerHTML = requirements.uppercase 
                    ? '<span class="mr-2 text-green-600">✓</span> One uppercase letter' 
                    : '<span class="mr-2">○</span> One uppercase letter';
                
                document.getElementById('req-lowercase').innerHTML = requirements.lowercase 
                    ? '<span class="mr-2 text-green-600">✓</span> One lowercase letter' 
                    : '<span class="mr-2">○</span> One lowercase letter';
                
                document.getElementById('req-number').innerHTML = requirements.number 
                    ? '<span class="mr-2 text-green-600">✓</span> One number' 
                    : '<span class="mr-2">○</span> One number';

                // Calculate strength
                if (requirements.length) strength++;
                if (requirements.uppercase) strength++;
                if (requirements.lowercase) strength++;
                if (requirements.number) strength++;
                if (requirements.special) strength++;

                return { strength, requirements };
            }

            function updateStrengthIndicator(strength) {
                const bars = [
                    document.getElementById('strength-bar-1'),
                    document.getElementById('strength-bar-2'),
                    document.getElementById('strength-bar-3'),
                    document.getElementById('strength-bar-4')
                ];

                // Reset all bars
                bars.forEach(bar => {
                    bar.className = 'h-1 flex-1 bg-gray-200 rounded transition-colors duration-300';
                });

                let color = '';
                let text = '';

                if (strength === 0) {
                    text = '';
                } else if (strength <= 2) {
                    color = 'bg-red-500';
                    text = 'Weak password';
                    bars[0].className = `h-1 flex-1 ${color} rounded transition-colors duration-300`;
                } else if (strength === 3) {
                    color = 'bg-yellow-500';
                    text = 'Fair password';
                    bars[0].className = `h-1 flex-1 ${color} rounded transition-colors duration-300`;
                    bars[1].className = `h-1 flex-1 ${color} rounded transition-colors duration-300`;
                } else if (strength === 4) {
                    color = 'bg-blue-500';
                    text = 'Good password';
                    bars[0].className = `h-1 flex-1 ${color} rounded transition-colors duration-300`;
                    bars[1].className = `h-1 flex-1 ${color} rounded transition-colors duration-300`;
                    bars[2].className = `h-1 flex-1 ${color} rounded transition-colors duration-300`;
                } else {
                    color = 'bg-green-500';
                    text = 'Strong password';
                    bars.forEach(bar => {
                        bar.className = `h-1 flex-1 ${color} rounded transition-colors duration-300`;
                    });
                }

                strengthText.textContent = text;
                strengthText.className = `text-xs ${strength <= 2 ? 'text-red-600' : strength === 3 ? 'text-yellow-600' : strength === 4 ? 'text-blue-600' : 'text-green-600'}`;
            }

            passwordInput.addEventListener('input', () => {
                const password = passwordInput.value;
                const { strength } = checkPasswordStrength(password);
                updateStrengthIndicator(strength);
                
                // Check password match if confirmation is filled
                if (passwordConfirmation.value) {
                    checkPasswordMatch();
                }
            });

            // Password confirmation match
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmation = passwordConfirmation.value;

                if (confirmation === '') {
                    passwordMatchError.classList.add('hidden');
                    passwordMatchSuccess.classList.add('hidden');
                    passwordConfirmation.classList.remove('border-red-500', 'border-green-500');
                    return true;
                }

                if (password !== confirmation) {
                    passwordMatchError.textContent = 'Passwords do not match';
                    passwordMatchError.classList.remove('hidden');
                    passwordMatchSuccess.classList.add('hidden');
                    passwordConfirmation.classList.add('border-red-500');
                    passwordConfirmation.classList.remove('border-green-500');
                    return false;
                } else {
                    passwordMatchError.classList.add('hidden');
                    passwordMatchSuccess.classList.remove('hidden');
                    passwordConfirmation.classList.remove('border-red-500');
                    passwordConfirmation.classList.add('border-green-500');
                    return true;
                }
            }

            passwordConfirmation.addEventListener('input', checkPasswordMatch);
            passwordConfirmation.addEventListener('blur', checkPasswordMatch);

            // Form submission validation
            form.addEventListener('submit', (e) => {
                let isValid = true;

                // Validate email
                const email = emailInput.value.trim();
                if (!validateEmail(email)) {
                    emailError.textContent = 'Please enter a valid email address';
                    emailError.classList.remove('hidden');
                    emailInput.classList.add('border-red-500');
                    isValid = false;
                }

                // Validate password requirements
                const password = passwordInput.value;
                const { requirements } = checkPasswordStrength(password);
                
                if (!requirements.length || !requirements.uppercase || !requirements.lowercase || !requirements.number) {
                    alert('Password must meet all requirements');
                    isValid = false;
                }

                // Validate password match
                if (!checkPasswordMatch()) {
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
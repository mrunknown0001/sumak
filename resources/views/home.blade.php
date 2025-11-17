<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} - Personalized Quiz Platform</title>
    <link rel="icon" href="{{ asset('img/logo.ico') }}" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        /* === NAVIGATION (MOBILE-FIRST) === */
        nav {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 1rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: auto;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-weight: bold;
            font-size: 1.25rem;
            text-decoration: none;
        }

        .logo img {
            height: 28px;
        }

        /* Mobile Navigation Hidden by Default */
        .nav-links {
            list-style: none;
            display: none;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            opacity: 0.9;
        }

        .nav-links a:hover {
            opacity: 1;
        }

        /* Login Button */
        .btn-login {
            background: white;
            color: #10b981;
            padding: 0.4rem 1.2rem;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-align: center;
            padding: 4rem 1.5rem;
        }

        .hero-content {
            max-width: 800px;
            margin: auto;
        }

        .hero h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
        }

        .cta-buttons {
            /* display: flex; */
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
            animation: fadeInUp 0.8s ease-out 0.4s backwards;
        }


        /* BUTTONS */
        .btn {
            padding: 0.9rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: white;
            color: #10b981;
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        /* FEATURES */
        .features {
            padding: 3rem 1.5rem;
            background: #f8f9fa;
        }

        .section-title {
            text-align: center;
            font-size: 1.75rem;
            margin-bottom: 2.5rem;
        }

        .features-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: 1fr;
            max-width: 1200px;
            margin: auto;
        }

        .feature-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-8px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #10b981;
        }

        /* HOW IT WORKS */
        .how-it-works {
            padding: 3rem 1.5rem;
            max-width: 1200px;
            margin: auto;
        }

        .steps {
            display: grid;
            gap: 1.5rem;
        }

        .step-number {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 50%;
            font-size: 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 0.75rem;
        }

        /* CTA SECTION */
        .cta-section {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        /* FOOTER */
        footer {
            background: #2d3748;
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }

        /* ANIMATIONS */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* === RESPONSIVE BREAKPOINTS === */

        /* Tablet (≥ 600px) */
        @media (min-width: 600px) {
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1.2rem; }
        }

        /* Tablet Large (≥ 768px) */
        @media (min-width: 768px) {

            /* Navigation shows inline links */
            .nav-links {
                display: flex;
                flex-direction: row;
                gap: 2rem;
                margin-top: 0;
            }

            .cta-buttons {
                flex-direction: row;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .steps {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Desktop (≥ 1024px) */
        @media (min-width: 1024px) {
            .hero {
                padding: 6rem 2rem;
            }

            .hero h1 { font-size: 3rem; }

            .features-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .steps {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="#" class="logo">
                <img src="{{ asset('img/logo.png') }}" alt="Logo">
                {{ config('app.name', 'AI Learning Assistant') }}
            </a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#about">About</a></li>
            </ul>
            @if(auth()->check())
                <a href="{{ route('dashboard') }}" class="btn-login">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-login">Login</a>
            @endif
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Master Your Learning with AI-Powered Quizzes</h1>
            <p>Upload your lecture materials and get personalized, adaptive quizzes that help you learn smarter, not
                harder.</p>
            <div class="cta-buttons">
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Get Started Free</a>
                @endif
                <a href="#how-it-works" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2 class="section-title">Why Choose Our Platform?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>AI-Powered Generation</h3>
                <p>Advanced AI analyzes your materials and generates targeted quizzes aligned with learning outcomes.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Adaptive Learning</h3>
                <p>Questions adapt to your skill level using Item Response Theory for optimal learning progression.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Instant Feedback</h3>
                <p>Get immediate, personalized insights and explanations to understand your mistakes and improve.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Progress Tracking</h3>
                <p>Monitor your mastery across subtopics with detailed analytics and performance dashboards.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Multiple Attempts</h3>
                <p>Regenerate quizzes up to three times to reinforce learning.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3>OBTL-Aligned</h3>
                <p>Questions are generated based on Outcome-Based Teaching and Learning principles for effective
                    assessment.</p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <h2 class="section-title">How It Works</h2>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Upload Materials</h3>
                <p>Upload your lecture notes, slides, or OBTL documents to the platform.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>AI Analyzes Content</h3>
                <p>Our AI processes your materials and creates a Table of Specification focused on key concepts.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Take Adaptive Quiz</h3>
                <p>Answer multiple-choice questions with customized timer per item in an adaptive format.</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <h3>Get Insights</h3>
                <p>Receive personalized feedback and track your progress across different subtopics.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2>Ready to Transform Your Learning?</h2>
        <p>Join thousands of students who are mastering their courses with AI-powered personalized quizzes.</p>
        @if(auth()->check())
            <p>
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
            </p>
        @else
            <p>
                <br/>
                <a href="{{ route('register') }}" class="btn btn-primary"">Start Learning Today</a>
            </p>
        @endif
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 {{ config('app.name') }}. All rights reserved.</p>
        <p>Empowering students through intelligent, adaptive learning.</p>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add subtle scroll animation effect
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .step').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            observer.observe(el);
        });
    </script>
</body>

</html>

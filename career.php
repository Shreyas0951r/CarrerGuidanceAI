<?php
require_once 'config/database.php';
$customContent = loadCustomContent();

$currentHour = (int) date('G');
$defaultGreeting = 'Good Evening';
if ($currentHour < 12) {
    $defaultGreeting = 'Good Morning';
} elseif ($currentHour < 17) {
    $defaultGreeting = 'Good Afternoon';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerPath AI - Intelligent Career Guidance</title>
    <meta name="csrf-token" content="<?php echo escape(csrfToken()); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                    },
                    colors: {
                        primary: '#6366f1',
                        secondary: '#ec4899',
                        accent: '#8b5cf6',
                        dark: '#0f172a',
                        surface: '#1e293b',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'fade-in': 'fadeIn 0.5s ease-out',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .gradient-text {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 50%, #f472b6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
        }

        .option-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .option-card:hover {
            transform: scale(1.02);
            border-color: #818cf8;
        }

        .option-card.selected {
            border-color: #818cf8;
            background: rgba(99, 102, 241, 0.1);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }

        .progress-bar {
            transition: width 0.5s ease-out;
        }

        .typing-cursor::after {
            content: '|';
            animation: blink 1s infinite;
        }

        @keyframes blink {

            0%,
            50% {
                opacity: 1;
            }

            51%,
            100% {
                opacity: 0;
            }
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
            animation: float 20s infinite;
        }

        body.light-mode {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            color: #0f172a;
        }

        body.light-mode .glass {
            background: rgba(255, 255, 255, 0.82);
            border-color: rgba(15, 23, 42, 0.08);
        }

        body.light-mode .text-white,
        body.light-mode .text-gray-300,
        body.light-mode .text-gray-400,
        body.light-mode .text-gray-500 {
            color: #334155 !important;
        }
    </style>
</head>

<body class="text-white overflow-x-hidden">

    <!-- Background Particles -->
    <div id="particles" class="fixed inset-0 overflow-hidden pointer-events-none"></div>

<nav class="fixed top-0 w-full z-50 glass border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <!-- LEFT SIDE: Logo (Clickable to homepage) -->
            <a href="<?php echo escape(appUrl('career.php')); ?>" class="flex items-center space-x-2 group cursor-pointer">
                <i class="fas fa-compass text-2xl text-primary group-hover:rotate-45 transition-transform duration-300"></i>
                <span class="font-display font-bold text-xl tracking-tight group-hover:text-primary transition-colors">
                    CareerPath<span class="text-primary">AI</span>
                </span>
            </a>

            <!-- RIGHT SIDE: Navigation Buttons -->
            <div class="hidden md:flex items-center space-x-6">
                
                <button onclick="resetJourney()" class="hover:text-primary transition-colors flex items-center gap-2">
                    <i class="fas fa-redo text-sm"></i>
                    <span>Start Over</span>
                </button>

                <button onclick="showSavedPaths()" class="hover:text-primary transition-colors flex items-center gap-2">
                    <i class="fas fa-bookmark text-sm"></i>
                    <span>Saved Paths</span>
                </button>

                <button onclick="showAbout()" class="hover:text-primary transition-colors flex items-center gap-2">
                    <i class="fas fa-info-circle text-sm"></i>
                    <span>About</span>
                </button>

                <button onclick="toggleTheme()" class="hover:text-primary transition-colors flex items-center gap-2">
                    <i class="fas fa-circle-half-stroke text-sm"></i>
                    <span id="themeToggleLabel">Light Mode</span>
                </button>

                <?php if (isLoggedIn()) { ?>
                    
                    <!-- Logged In: Show Dashboard & Logout -->
                    <a href="<?php echo escape(appUrl('user/dashboard.php')); ?>" class="hover:text-primary transition-colors flex items-center gap-2">
                        <i class="fas fa-user-circle text-sm"></i>
                        <span><?php echo escape($_SESSION['fullname'] ?? 'User'); ?></span>
                    </a>

                    <a href="<?php echo escape(appUrl('user/profile.php')); ?>" class="hover:text-primary transition-colors flex items-center gap-2">
                        <i class="fas fa-sliders-h text-sm"></i>
                        <span>Profile</span>
                    </a>

                    <form method="POST" action="<?php echo escape(appUrl('auth/logout.php')); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                        <button type="submit"
                            class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg text-white transition flex items-center gap-2">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                            <span>Logout</span>
                        </button>
                    </form>

                <?php } else { ?>
                    
                    <!-- Not Logged In: Show Login -->
                    <a href="<?php echo escape(appUrl('auth/login.php')); ?>" 
                       class="bg-primary hover:bg-indigo-600 px-5 py-2 rounded-lg text-white transition flex items-center gap-2 font-medium">
                        <i class="fas fa-sign-in-alt text-sm"></i>
                        <span>Login</span>
                    </a>

                <?php } ?>

            </div>

            <!-- Mobile Menu Button -->
            <button id="menuBtn" class="md:hidden text-2xl hover:text-primary transition-colors">
                <i class="fas fa-bars"></i>
            </button>

        </div>
    </div>

    <!-- Mobile Menu (Hidden by default) -->
    <div id="mobileMenu" class="hidden md:hidden glass border-t border-white/10">
        <div class="px-4 pt-2 pb-4 space-y-2">
            <button onclick="resetJourney()" class="block w-full text-left py-2 hover:text-primary transition-colors">
                <i class="fas fa-redo mr-2"></i>Start Over
            </button>
            <button onclick="showSavedPaths()" class="block w-full text-left py-2 hover:text-primary transition-colors">
                <i class="fas fa-bookmark mr-2"></i>Saved Paths
            </button>
            <button onclick="showAbout()" class="block w-full text-left py-2 hover:text-primary transition-colors">
                <i class="fas fa-info-circle mr-2"></i>About
            </button>
            <button onclick="toggleTheme()" class="block w-full text-left py-2 hover:text-primary transition-colors">
                <i class="fas fa-circle-half-stroke mr-2"></i><span id="themeToggleLabelMobile">Light Mode</span>
            </button>
            
             <?php if (isLoggedIn()) { ?>
                <a href="<?php echo escape(appUrl('user/dashboard.php')); ?>" class="block py-2 hover:text-primary transition-colors">
                    <i class="fas fa-user-circle mr-2"></i>Dashboard
                </a>
                <a href="<?php echo escape(appUrl('user/profile.php')); ?>" class="block py-2 hover:text-primary transition-colors">
                    <i class="fas fa-sliders-h mr-2"></i>Profile
                </a>
                <form method="POST" action="<?php echo escape(appUrl('auth/logout.php')); ?>" class="pt-1">
                    <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                    <button type="submit" class="block py-2 text-red-400 hover:text-red-300 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </form>
             <?php } else { ?>
                <a href="<?php echo escape(appUrl('auth/login.php')); ?>" class="block py-2 text-primary hover:text-indigo-400 transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
            <?php } ?>
        </div>
    </div>
</nav>

    <!-- Main Container -->
    <main
        class="relative z-10 pt-20 pb-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen flex flex-col justify-center">

        <!-- Hero Section -->
        <section id="heroSection" class="text-center py-20 animate-fade-in">
            <?php if (isLoggedIn()): ?>
                <div class="max-w-2xl mx-auto mb-8">
                    <div class="glass rounded-2xl px-6 py-4 border border-primary/20">
                        <p id="careerGreetingMessage" class="text-lg md:text-xl font-medium" data-greeting-name="<?php echo escape($_SESSION['fullname'] ?? 'User'); ?>">
                            <?php echo escape($defaultGreeting); ?>, <?php echo escape($_SESSION['fullname'] ?? 'User'); ?>!
                        </p>
                        <p class="text-sm text-gray-400 mt-1">Ready to explore your next career move?</p>
                    </div>
                </div>
            <?php endif; ?>
            <div
                class="inline-block mb-4 px-4 py-2 rounded-full bg-primary/20 border border-primary/30 text-primary text-sm font-medium animate-pulse-slow">
                <i class="fas fa-sparkles mr-2"></i>AI-Powered Career Discovery
            </div>
            <h1 class="font-display text-5xl md:text-7xl font-bold mb-6 leading-tight">
                Discover Your <span class="gradient-text">Perfect Career</span><br>
                With Intelligent Guidance
            </h1>
            <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto leading-relaxed">
                Navigate through personalized pathways, explore diverse industries, and let our AI analyze your
                preferences to recommend the ideal career trajectory tailored just for you.
            </p>
            <button onclick="startAssessment()"
                class="group relative px-8 py-4 bg-primary hover:bg-primary/90 rounded-full font-semibold text-lg transition-all transform hover:scale-105 shadow-lg shadow-primary/50 overflow-hidden">
                <span class="relative z-10 flex items-center">
                    Start Your Journey <i
                        class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </span>
                <div
                    class="absolute inset-0 bg-gradient-to-r from-primary via-accent to-secondary opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
            </button>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-primary mb-2">50+</div>
                    <div class="text-gray-400">Career Paths</div>
                </div>
                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-secondary mb-2">10K+</div>
                    <div class="text-gray-400">Success Stories</div>
                </div>
                <div class="glass rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-accent mb-2">98%</div>
                    <div class="text-gray-400">Accuracy Rate</div>
                </div>
            </div>
        </section>

        <!-- Assessment Container -->
        <section id="assessmentSection" class="hidden w-full max-w-4xl mx-auto">
            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex justify-between text-sm text-gray-400 mb-2">
                    <span id="stepIndicator">Step 1 of 5</span>
                    <span id="progressPercent">20%</span>
                </div>
                <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                    <div id="progressBar" class="h-full bg-gradient-to-r from-primary to-secondary progress-bar"
                        style="width: 20%"></div>
                </div>
            </div>

            <!-- Question Container -->
            <div id="questionContainer" class="glass rounded-3xl p-8 md:p-12 shadow-2xl">
                <h2 id="questionTitle" class="font-display text-3xl md:text-4xl font-bold mb-8 text-center"></h2>
                <div id="optionsGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between mt-8">
                <button id="prevBtn" onclick="previousStep()"
                    class="hidden px-6 py-3 rounded-xl border border-white/20 hover:bg-white/10 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </button>
                <button id="nextBtn" onclick="nextStep()"
                    class="ml-auto px-8 py-3 bg-primary hover:bg-primary/90 rounded-xl font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    Continue<i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </section>

        <!-- Results Section -->
        <section id="resultsSection" class="hidden w-full">
            <div class="text-center mb-12 animate-slide-up">
                <h2 class="font-display text-4xl md:text-5xl font-bold mb-4">Your Career <span
                        class="gradient-text">Blueprint</span></h2>
                <p class="text-gray-400 text-lg">Based on your preferences, here are your personalized recommendations
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Primary Recommendation -->
                <div class="lg:col-span-2 space-y-6">
                    <div id="primaryRecommendation"
                        class="glass rounded-3xl p-8 border-2 border-primary/30 animate-slide-up">
                        <!-- Dynamic Content -->
                    </div>

                    <!-- Career Roadmap -->
                    <div class="glass rounded-3xl p-8 animate-slide-up" style="animation-delay: 0.1s">
                        <h3 class="font-display text-2xl font-bold mb-6 flex items-center">
                            <i class="fas fa-map-marked-alt text-primary mr-3"></i>Your Roadmap
                        </h3>
                        <div id="roadmapContainer" class="space-y-4">
                            <!-- Dynamic Roadmap -->
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Skills Analysis -->
                    <div class="glass rounded-3xl p-6 animate-slide-up" style="animation-delay: 0.2s">
                        <h3 class="font-display text-xl font-bold mb-4">Required Skills</h3>
                        <div id="skillsContainer" class="space-y-3">
                            <!-- Dynamic Skills -->
                        </div>
                    </div>

                    <!-- Salary Insights -->
                    <div class="glass rounded-3xl p-6 animate-slide-up" style="animation-delay: 0.3s">
                        <h3 class="font-display text-xl font-bold mb-4">Salary Insights</h3>
                        <div id="salaryContainer">
                            <!-- Dynamic Salary Data -->
                        </div>
                    </div>

                    <div class="glass rounded-3xl p-6 animate-slide-up" style="animation-delay: 0.35s">
                        <h3 class="font-display text-xl font-bold mb-4">Recommendation Chart</h3>
                        <canvas id="recommendationChart" height="220"></canvas>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3 animate-slide-up" style="animation-delay: 0.4s">
                        <button onclick="exploreResources()"
                            class="w-full py-3 bg-primary/20 hover:bg-primary/30 border border-primary/50 rounded-xl transition-all flex items-center justify-center">
                            <i class="fas fa-external-link-alt mr-2"></i>Explore Resources
                        </button>
                        <?php if (isLoggedIn()): ?>
                            <button onclick="savePath()"
                                class="w-full py-3 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl transition-all flex items-center justify-center">
                                <i class="fas fa-bookmark mr-2"></i>Save This Path
                            </button>
                        <?php else: ?>
                            <a href="<?php echo escape(appUrl('auth/login.php')); ?>"
                                class="w-full py-3 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl transition-all flex items-center justify-center">
                                <i class="fas fa-sign-in-alt mr-2"></i>Login to Save
                            </a>
                        <?php endif; ?>
                        <button onclick="shareResults()"
                            class="w-full py-3 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl transition-all flex items-center justify-center">
                            <i class="fas fa-share-alt mr-2"></i>Share Results
                        </button>
                    </div>

                    <div class="glass rounded-3xl p-6 animate-slide-up" style="animation-delay: 0.5s">
                        <h3 class="font-display text-xl font-bold mb-4">Recommendation Feedback</h3>
                        <?php if (isLoggedIn()): ?>
                            <div class="space-y-3">
                                <select id="feedbackRating" class="w-full p-3 rounded-xl bg-white/10 border border-white/10">
                                    <option value="">Rate this recommendation</option>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Average</option>
                                    <option value="2">2 - Poor</option>
                                    <option value="1">1 - Not useful</option>
                                </select>
                                <textarea id="feedbackText" rows="4" class="w-full p-3 rounded-xl bg-white/10 border border-white/10" placeholder="Tell us what was useful or what should improve"></textarea>
                                <button onclick="submitFeedback()" class="w-full py-3 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl transition-all">
                                    Submit Feedback
                                </button>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-400 text-sm">Login to rate this recommendation and help improve the system.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Alternative Careers -->
            <div class="mt-12">
                <h3 class="font-display text-2xl font-bold mb-6 text-center">Alternative Career Paths</h3>
                <div id="alternativeCareers" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Dynamic Alternative Cards -->
                </div>
            </div>

            <div class="text-center mt-12">
                <button onclick="resetJourney()"
                    class="px-8 py-3 border border-white/20 rounded-full hover:bg-white/10 transition-all">
                    <i class="fas fa-redo mr-2"></i>Start New Assessment
                </button>
            </div>
        </section>

        <!-- Resources Modal -->
        <div id="resourcesModal" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeResources()"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="glass rounded-3xl max-w-4xl w-full max-h-[90vh] overflow-y-auto p-8 animate-slide-up">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-display text-2xl font-bold">Learning Resources</h3>
                        <button onclick="closeResources()" class="text-2xl hover:text-primary transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="resourcesContent" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Dynamic Resources -->
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        const appConfig = {
            saveAssessmentUrl: <?php echo json_encode(appUrl('user/save-assessment.php')); ?>,
            submitFeedbackUrl: <?php echo json_encode(appUrl('user/submit-feedback.php')); ?>,
            csrfToken: <?php echo json_encode(csrfToken()); ?>,
            customCareers: <?php echo json_encode($customContent['careers'] ?? []); ?>,
            customQuestions: <?php echo json_encode($customContent['questions'] ?? []); ?>
        };

        // Career Database
        const careerDatabase = {
    technology: {
        software_engineering: {
            title: "Software Engineering",
            icon: "fa-code",
            description: "Design, develop, and maintain software systems and applications that power the modern world.",
            skills: ["Programming", "Problem Solving", "System Design", "Version Control", "Testing"],
            salary: { entry: "$70,000", mid: "$120,000", senior: "$180,000+" },
            roadmap: [
                { phase: "Foundation", duration: "0-6 months", tasks: ["Learn Python/JavaScript", "Basic algorithms", "Git fundamentals"] },
                { phase: "Junior Developer", duration: "1-2 years", tasks: ["Build projects", "Learn frameworks", "Code reviews"] },
                { phase: "Mid-Level", duration: "3-5 years", tasks: ["System architecture", "Mentoring", "Specialization"] },
                { phase: "Senior/Lead", duration: "5+ years", tasks: ["Technical leadership", "Architecture decisions", "Team management"] }
            ],
            resources: [
                { name: "freeCodeCamp", type: "Course", url: "https://www.freecodecamp.org", icon: "fa-laptop-code" },
                { name: "LeetCode", type: "Practice", url: "https://leetcode.com", icon: "fa-brain" },
                { name: "System Design Primer", type: "GitHub", url: "https://github.com/donnemartin/system-design-primer", icon: "fa-github" }
            ]
        },
        data_science: {
            title: "Data Science",
            icon: "fa-chart-line",
            description: "Extract insights from complex data using statistical analysis and machine learning.",
            skills: ["Python/R", "Statistics", "Machine Learning", "SQL", "Data Visualization"],
            salary: { entry: "$80,000", mid: "$130,000", senior: "$200,000+" },
            roadmap: [
                { phase: "Learning", duration: "0-12 months", tasks: ["Statistics fundamentals", "Python for data", "SQL mastery"] },
                { phase: "Junior Data Scientist", duration: "1-2 years", tasks: ["Data cleaning", "Basic modeling", "Visualization"] },
                { phase: "Data Scientist", duration: "3-5 years", tasks: ["Advanced ML", "Big data tools", "Business impact"] },
                { phase: "Principal/Lead", duration: "5+ years", tasks: ["AI strategy", "Research", "Team leadership"] }
            ],
            resources: [
                { name: "Kaggle", type: "Platform", url: "https://www.kaggle.com", icon: "fa-trophy" },
                { name: "Fast.ai", type: "Course", url: "https://www.fast.ai", icon: "fa-graduation-cap" },
                { name: "Towards Data Science", type: "Blog", url: "https://towardsdatascience.com", icon: "fa-newspaper" }
            ]
        },
        cybersecurity: {
            title: "Cybersecurity",
            icon: "fa-shield-alt",
            description: "Protect systems and networks from digital attacks and security breaches.",
            skills: ["Network Security", "Ethical Hacking", "Risk Assessment", "Incident Response", "Cryptography"],
            salary: { entry: "$75,000", mid: "$125,000", senior: "$190,000+" },
            roadmap: [
                { phase: "Fundamentals", duration: "0-6 months", tasks: ["Network+", "Security+", "Linux basics"] },
                { phase: "Analyst", duration: "1-2 years", tasks: ["SOC operations", "Threat detection", "SIEM tools"] },
                { phase: "Engineer", duration: "3-5 years", tasks: ["Security architecture", "Penetration testing", "Cloud security"] },
                { phase: "Architect/CISO", duration: "5+ years", tasks: ["Enterprise strategy", "Compliance", "Risk management"] }
            ],
            resources: [
                { name: "TryHackMe", type: "Platform", url: "https://tryhackme.com", icon: "fa-flag" },
                { name: "Hack The Box", type: "Practice", url: "https://www.hackthebox.com", icon: "fa-box-open" },
                { name: "SANS Institute", type: "Certification", url: "https://www.sans.org", icon: "fa-certificate" }
            ]
        }
    },
    creative: {
        ux_design: {
            title: "UX/UI Design",
            icon: "fa-paint-brush",
            description: "Create intuitive and engaging user experiences for digital products.",
            skills: ["User Research", "Wireframing", "Prototyping", "Visual Design", "Figma/Sketch"],
            salary: { entry: "$65,000", mid: "$105,000", senior: "$160,000+" },
            roadmap: [
                { phase: "Basics", duration: "0-6 months", tasks: ["Design principles", "Figma mastery", "Portfolio projects"] },
                { phase: "Junior Designer", duration: "1-2 years", tasks: ["User research", "Wireframing", "Design systems"] },
                { phase: "Product Designer", duration: "3-5 years", tasks: ["End-to-end design", "Strategy", "Leadership"] },
                { phase: "Design Director", duration: "5+ years", tasks: ["Team management", "Design ops", "Vision setting"] }
            ],
            resources: [
                { name: "Figma Community", type: "Tool", url: "https://www.figma.com/community", icon: "fa-figma" },
                { name: "Interaction Design Foundation", type: "Course", url: "https://www.interaction-design.org", icon: "fa-book" },
                { name: "Dribbble", type: "Inspiration", url: "https://dribbble.com", icon: "fa-basketball-ball" }
            ]
        },
        content_creation: {
            title: "Content Strategy",
            icon: "fa-video",
            description: "Develop and manage content that drives engagement and business results.",
            skills: ["Copywriting", "SEO", "Analytics", "Video Production", "Social Media"],
            salary: { entry: "$55,000", mid: "$90,000", senior: "$140,000+" },
            roadmap: [
                { phase: "Foundation", duration: "0-6 months", tasks: ["Writing skills", "SEO basics", "Platform knowledge"] },
                { phase: "Specialist", duration: "1-2 years", tasks: ["Content calendars", "Analytics", "Brand voice"] },
                { phase: "Manager", duration: "3-5 years", tasks: ["Strategy development", "Team coordination", "Campaigns"] },
                { phase: "Director", duration: "5+ years", tasks: ["Editorial strategy", "Revenue impact", "Industry thought leadership"] }
            ],
            resources: [
                { name: "HubSpot Academy", type: "Course", url: "https://academy.hubspot.com", icon: "fa-graduation-cap" },
                { name: "Copyblogger", type: "Blog", url: "https://copyblogger.com", icon: "fa-pen" },
                { name: "YouTube Creator Academy", type: "Video", url: "https://creatoracademy.youtube.com", icon: "fa-youtube" }
            ]
        },
        influencer: {
            title: "Content Creator / Influencer",
            icon: "fa-camera",
            description: "Create engaging digital content and build audience on YouTube, Instagram, and emerging platforms. Monetize through brand partnerships and creator economy.",
            skills: ["Video Production", "Storytelling", "Personal Branding", "Social Media Strategy", "Editing", "Audience Engagement"],
            salary: { entry: "Variable", mid: "₹50,000+", senior: "₹5,00,000+" },
            roadmap: [
                { phase: "Niche Selection", duration: "0-3 months", tasks: ["Choose niche", "Platform selection", "Content strategy", "Equipment setup"] },
                { phase: "Growth", duration: "6-12 months", tasks: ["Consistent posting", "Audience building", "Engagement tactics", "Algorithm mastery"] },
                { phase: "Monetization", duration: "1-2 years", tasks: ["Brand collaborations", "Ad revenue", "Affiliate marketing", "Product launches"] },
                { phase: "Established Creator", duration: "3+ years", tasks: ["Agency representation", "Multi-platform expansion", "Own brand", "Investments"] }
            ],
            resources: [
                { name: "YouTube Creator Academy", type: "Course", url: "https://creatoracademy.youtube.com", icon: "fa-youtube" },
                { name: "Instagram Creator", type: "Platform", url: "https://creators.instagram.com", icon: "fa-instagram" }
            ]
        },
        video_editor: {
            title: "Video Editor",
            icon: "fa-film",
            description: "Edit and produce professional videos for films, YouTube, advertisements, social media, and streaming platforms.",
            skills: ["Premiere Pro", "After Effects", "Color Grading", "Sound Design", "Storytelling", "Motion Graphics"],
            salary: { entry: "₹25,000", mid: "₹60,000", senior: "₹1,50,000+" },
            roadmap: [
                { phase: "Learning", duration: "3-6 months", tasks: ["Editing software mastery", "YouTube tutorials", "Practice projects", "Portfolio building"] },
                { phase: "Freelance", duration: "1-2 years", tasks: ["Client acquisition", "Social media content", "Wedding/corporate videos", "Network building"] },
                { phase: "Professional", duration: "3-5 years", tasks: ["Ad agency work", "Film/TV editing", "Colorist work", "Post-production house"] },
                { phase: "Expert", duration: "5+ years", tasks: ["Feature films", "International projects", "Own studio", "Teaching"] }
            ],
            resources: [
                { name: "Adobe Premiere", type: "Software", url: "https://www.adobe.com", icon: "fa-adobe" },
                { name: "Film Riot", type: "Tutorial", url: "https://www.youtube.com/filmriot", icon: "fa-youtube" }
            ]
        },
        social_media_manager: {
            title: "Social Media Manager",
            icon: "fa-hashtag",
            description: "Manage brand presence, create content strategies, and drive engagement across social media platforms for businesses and influencers.",
            skills: ["Content Strategy", "Analytics", "Copywriting", "Trend Analysis", "Community Management", "Paid Advertising"],
            salary: { entry: "₹30,000", mid: "₹70,000", senior: "₹1,50,000+" },
            roadmap: [
                { phase: "Learning", duration: "3-6 months", tasks: ["Platform algorithms", "Content planning", "Analytics tools", "Ad management"] },
                { phase: "Executive", duration: "1-2 years", tasks: ["Handle client accounts", "Content calendar", "Engagement growth", "Reporting"] },
                { phase: "Manager", duration: "3-5 years", tasks: ["Team leadership", "Strategy development", "Budget management", "Crisis handling"] },
                { phase: "Director", duration: "5+ years", tasks: ["Department head", "Multi-brand strategy", "Influencer partnerships", "Trend forecasting"] }
            ],
            resources: [
                { name: "Meta Blueprint", type: "Certification", url: "https://www.facebook.com/business/learn", icon: "fa-facebook" },
                { name: "Hootsuite", type: "Tool", url: "https://www.hootsuite.com", icon: "fa-share-alt" }
            ]
        }
    },
    business: {
        product_management: {
            title: "Product Management",
            icon: "fa-tasks",
            description: "Lead product development from conception to launch, bridging business and technology.",
            skills: ["Market Research", "Agile/Scrum", "Data Analysis", "Stakeholder Management", "Roadmapping"],
            salary: { entry: "$85,000", mid: "$140,000", senior: "$220,000+" },
            roadmap: [
                { phase: "Learning", duration: "0-6 months", tasks: ["Product fundamentals", "Agile methodology", "SQL basics"] },
                { phase: "Associate PM", duration: "1-2 years", tasks: ["Feature specification", "User stories", "A/B testing"] },
                { phase: "Product Manager", duration: "3-5 years", tasks: ["Product strategy", "Cross-functional leadership", "Metrics ownership"] },
                { phase: "VP Product", duration: "5+ years", tasks: ["Portfolio strategy", "Business growth", "Team scaling"] }
            ],
            resources: [
                { name: "Product School", type: "Course", url: "https://productschool.com", icon: "fa-school" },
                { name: "Mind the Product", type: "Community", url: "https://www.mindtheproduct.com", icon: "fa-users" },
                { name: "Lenny's Newsletter", type: "Newsletter", url: "https://www.lennysnewsletter.com", icon: "fa-envelope" }
            ]
        },
        entrepreneurship: {
            title: "Entrepreneurship",
            icon: "fa-rocket",
            description: "Build and scale your own business ventures, from startup to enterprise.",
            skills: ["Business Strategy", "Fundraising", "Leadership", "Financial Modeling", "Growth Hacking"],
            salary: { entry: "Variable", mid: "$100,000+", senior: "Unlimited" },
            roadmap: [
                { phase: "Ideation", duration: "0-6 months", tasks: ["Problem validation", "MVP planning", "Market research"] },
                { phase: "Startup", duration: "1-3 years", tasks: ["Product-market fit", "Initial funding", "Team building"] },
                { phase: "Scale", duration: "3-7 years", tasks: ["Series A/B", "Process building", "Market expansion"] },
                { phase: "Exit/Scale", duration: "7+ years", tasks: ["Acquisition/IPO", "New ventures", "Investment"] }
            ],
            resources: [
                { name: "Y Combinator", type: "Accelerator", url: "https://www.ycombinator.com", icon: "fa-seedling" },
                { name: "Startup School", type: "Course", url: "https://www.startupschool.org", icon: "fa-school" },
                { name: "AngelList", type: "Platform", url: "https://angel.co", icon: "fa-hand-holding-usd" }
            ]
        },
        banking_officer: {
            title: "Bank Officer (IBPS/SBI PO)",
            icon: "fa-university",
            description: "Join public sector banks as Probationary Officer through IBPS/SBI exams. Manage banking operations, loans, and customer relationships.",
            skills: ["Financial Analysis", "Customer Service", "Risk Assessment", "Accounting", "Communication", "Sales"],
            salary: { entry: "₹40,000", mid: "₹80,000", senior: "₹1,50,000+" },
            roadmap: [
                { phase: "Preparation", duration: "6-12 months", tasks: ["IBPS/SBI exam prep", "Quantitative aptitude", "Reasoning", "English", "Banking awareness"] },
                { phase: "Probation", duration: "2 years", tasks: ["Branch rotation", "Customer handling", "Loan processing", "Operations", "Forex"] },
                { phase: "Assistant Manager", duration: "3-5 years", tasks: ["Branch management", "Team leadership", "Target achievement", "Recovery"] },
                { phase: "Senior Management", duration: "10+ years", tasks: ["Regional manager", "Credit management", "Treasury", "Corporate banking"] }
            ],
            resources: [
                { name: "IBPS", type: "Official", url: "https://www.ibps.in", icon: "fa-university" },
                { name: "SBI Careers", type: "Official", url: "https://bank.sbi/careers", icon: "fa-landmark" }
            ]
        },
        chartered_accountant: {
            title: "Chartered Accountant (CA)",
            icon: "fa-calculator",
            description: "Become a certified CA through ICAI. Handle auditing, taxation, financial reporting, and corporate finance for top companies.",
            skills: ["Accounting", "Auditing", "Taxation", "Financial Analysis", "Law", "Ethics"],
            salary: { entry: "₹50,000", mid: "₹1,00,000", senior: "₹3,00,000+" },
            roadmap: [
                { phase: "Foundation", duration: "1 year", tasks: ["CA Foundation exam", "Basic accounting", "Economics", "Business law"] },
                { phase: "Intermediate", duration: "1.5 years", tasks: ["CA Intermediate groups", "Articleship begins", "Advanced accounting", "Taxation"] },
                { phase: "Final", duration: "3 years", tasks: ["CA Final exam", "Articleship completion", "Specialization", "Big 4 experience"] }
            ],
            resources: [
                { name: "ICAI", type: "Official", url: "https://www.icai.org", icon: "fa-calculator" }
            ]
        }
    },
    healthcare: {
        health_informatics: {
            title: "Health Informatics",
            icon: "fa-heartbeat",
            description: "Bridge healthcare and IT to improve patient care through data and technology.",
            skills: ["Healthcare Systems", "Data Analysis", "EHR Systems", "HIPAA Compliance", "Project Management"],
            salary: { entry: "$70,000", mid: "$110,000", senior: "$170,000+" },
            roadmap: [
                { phase: "Education", duration: "0-12 months", tasks: ["Healthcare basics", "IT fundamentals", "Compliance training"] },
                { phase: "Analyst", duration: "1-2 years", tasks: ["Data management", "Workflow optimization", "System support"] },
                { phase: "Manager", duration: "3-5 years", tasks: ["Project leadership", "System implementation", "Strategy"] },
                { phase: "Director", duration: "5+ years", tasks: ["Digital transformation", "Enterprise architecture", "Innovation"] }
            ],
            resources: [
                { name: "HIMSS", type: "Organization", url: "https://www.himss.org", icon: "fa-hospital" },
                { name: "Coursera Health IT", type: "Course", url: "https://www.coursera.org", icon: "fa-laptop" },
                { name: "Healthcare IT News", type: "News", url: "https://www.healthcareitnews.com", icon: "fa-newspaper" }
            ]
        }
    },
    science: {
        research_scientist: {
            title: "Research Scientist",
            icon: "fa-flask",
            description: "Conduct cutting-edge research to advance knowledge in your field of expertise.",
            skills: ["Research Methods", "Data Analysis", "Technical Writing", "Grant Writing", "Specialized Domain Knowledge"],
            salary: { entry: "$60,000", mid: "$95,000", senior: "$150,000+" },
            roadmap: [
                { phase: "Education", duration: "4-6 years", tasks: ["PhD/Masters", "Publications", "Conference presentations"] },
                { phase: "Postdoc/Industry", duration: "2-4 years", tasks: ["Specialized research", "Collaboration", "Grant applications"] },
                { phase: "Senior Scientist", duration: "5-10 years", tasks: ["Research leadership", "Team management", "Strategic direction"] },
                { phase: "Principal Investigator", duration: "10+ years", tasks: ["Lab direction", "Funding acquisition", "Field leadership"] }
            ],
            resources: [
                { name: "ResearchGate", type: "Network", url: "https://www.researchgate.net", icon: "fa-network-wired" },
                { name: "Google Scholar", type: "Search", url: "https://scholar.google.com", icon: "fa-search" },
                { name: "Nature Careers", type: "Jobs", url: "https://www.nature.com/naturecareers", icon: "fa-leaf" }
            ]
        }
    },
    government: {
        defence_officer: {
            title: "Defence Officer (Army/Navy/Air Force)",
            icon: "fa-shield-alt",
            description: "Serve the nation as a commissioned officer in the Indian Armed Forces. Lead troops, strategize operations, and ensure national security.",
            skills: ["Leadership", "Physical Fitness", "Strategic Planning", "Discipline", "Decision Making", "Crisis Management"],
            salary: { entry: "₹56,100", mid: "₹1,20,000", senior: "₹2,50,000+" },
            roadmap: [
                { phase: "Preparation", duration: "6-12 months", tasks: ["NDA/CDS exam preparation", "Physical fitness training", "SSB interview coaching", "General knowledge"] },
                { phase: "Training", duration: "3 years", tasks: ["NDA/IMA training", "Military academics", "Field training", "Leadership development"] },
                { phase: "Junior Officer", duration: "5-10 years", tasks: ["Command platoon/company", "Operational deployments", "Specialized training"] },
                { phase: "Senior Officer", duration: "10+ years", tasks: ["Battalion command", "Strategic planning", "Staff appointments"] }
            ],
            resources: [
                { name: "UPSC NDA", type: "Official", url: "https://upsc.gov.in", icon: "fa-graduation-cap" },
                { name: "Join Indian Army", type: "Official", url: "https://joinindianarmy.nic.in", icon: "fa-shield-alt" },
                { name: "SSB Crack", type: "Preparation", url: "https://ssbcrack.com", icon: "fa-book" }
            ]
        },
        police_officer: {
            title: "Police Officer (IPS/State Police)",
            icon: "fa-user-shield",
            description: "Maintain law and order, investigate crimes, and ensure public safety as a police officer in Indian Police Service or state police forces.",
            skills: ["Law Enforcement", "Investigation", "Crisis Management", "Physical Fitness", "Leadership", "Communication"],
            salary: { entry: "₹56,100", mid: "₹1,20,000", senior: "₹2,00,000+" },
            roadmap: [
                { phase: "Preparation", duration: "1-2 years", tasks: ["UPSC CSE preparation", "Physical training", "Law and constitution study"] },
                { phase: "Training", duration: "2 years", tasks: ["Police academy training", "Field training", "Weapons training", "Law enforcement"] },
                { phase: "Sub-Inspector/ASP", duration: "5-8 years", tasks: ["District policing", "Crime investigation", "Law and order duties"] },
                { phase: "Senior Officer", duration: "10+ years", tasks: ["SP/DIG rank", "Commissioner roles", "Policy making"] }
            ],
            resources: [
                { name: "UPSC CSE", type: "Exam", url: "https://upsc.gov.in", icon: "fa-graduation-cap" },
                { name: "SVPNPA", type: "Academy", url: "https://www.svpnpa.gov.in", icon: "fa-university" }
            ]
        },
        ias_officer: {
            title: "IAS Officer",
            icon: "fa-landmark",
            description: "Lead district administration, formulate policies, and serve as the backbone of Indian bureaucracy at central and state levels.",
            skills: ["Administration", "Policy Making", "Leadership", "Public Speaking", "Crisis Management", "Decision Making"],
            salary: { entry: "₹56,100", mid: "₹1,50,000", senior: "₹2,50,000+" },
            roadmap: [
                { phase: "Preparation", duration: "1-3 years", tasks: ["UPSC CSE preparation", "Optional subject mastery", "Current affairs", "Answer writing"] },
                { phase: "Training", duration: "2 years", tasks: ["LBSNAA training", "District attachment", "Central attachment", "Foreign exposure"] },
                { phase: "SDM/DM", duration: "6-10 years", tasks: ["District magistrate", "Development works", "Law and order", "Election duties"] },
                { phase: "Secretary", duration: "15+ years", tasks: ["Secretary level posts", "Policy formulation", "International representation"] }
            ],
            resources: [
                { name: "UPSC", type: "Official", url: "https://upsc.gov.in", icon: "fa-graduation-cap" },
                { name: "IAS Parliament", type: "Preparation", url: "https://www.iasparliament.com", icon: "fa-book" }
            ]
        },
        railway_officer: {
            title: "Railway Officer (IRTS/IRAS)",
            icon: "fa-train",
            description: "Manage Indian Railways operations, accounts, and traffic services as a Group A officer through UPSC or RRB exams.",
            skills: ["Operations Management", "Logistics", "Finance", "Leadership", "Technical Knowledge"],
            salary: { entry: "₹56,100", mid: "₹1,00,000", senior: "₹2,00,000+" },
            roadmap: [
                { phase: "Preparation", duration: "6-12 months", tasks: ["RRB NTPC/UPSC preparation", "Technical knowledge", "General awareness"] },
                { phase: "Training", duration: "18 months", tasks: ["Railway staff college", "Field training", "Divisional exposure"] },
                { phase: "Junior Officer", duration: "5-10 years", tasks: ["Station management", "Traffic control", "Commercial operations"] }
            ],
            resources: [
                { name: "RRB", type: "Official", url: "https://rrbcdg.gov.in", icon: "fa-train" }
            ]
        },
        ssc_gazetted: {
            title: "SSC Gazetted Officer",
            icon: "fa-id-card",
            description: "Join central government departments as Group B/Gazetted officer through SSC CGL/CHSL exams. Work in ministries, departments, and autonomous bodies.",
            skills: ["Administration", "Data Management", "Communication", "Policy Implementation", "Office Management"],
            salary: { entry: "₹35,000", mid: "₹70,000", senior: "₹1,20,000+" },
            roadmap: [
                { phase: "Preparation", duration: "6-12 months", tasks: ["SSC CGL/CHSL prep", "Quantitative aptitude", "English", "General awareness", "Reasoning"] },
                { phase: "Training", duration: "6 months", tasks: ["Departmental training", "Computer proficiency", "Office procedures"] },
                { phase: "Section Officer", duration: "3-5 years", tasks: ["File management", "Policy implementation", "Public dealing"] },
                { phase: "Gazetted Officer", duration: "8+ years", tasks: ["Independent charge", "Policy making", "Department head"] }
            ],
            resources: [
                { name: "SSC", type: "Official", url: "https://ssc.nic.in", icon: "fa-id-card" }
            ]
        },
        state_psc_officer: {
            title: "State PSC Officer",
            icon: "fa-building",
            description: "Serve state governments as administrative officers, police officers, or forest officers through State Public Service Commission exams.",
            skills: ["State Administration", "Regional Knowledge", "Leadership", "Public Service", "Crisis Management"],
            salary: { entry: "₹40,000", mid: "₹90,000", senior: "₹2,00,000+" },
            roadmap: [
                { phase: "Preparation", duration: "1-2 years", tasks: ["State PSC exam prep", "State history/culture", "Regional language", "Current affairs"] },
                { phase: "Training", duration: "1-2 years", tasks: ["State academy training", "Field attachment", "Department exposure"] },
                { phase: "Deputy Collector", duration: "5-10 years", tasks: ["Tehsil management", "Revenue administration", "Development works"] },
                { phase: "District Collector", duration: "12+ years", tasks: ["District administration", "Law and order", "Election conduct"] }
            ],
            resources: [
                { name: "State PSC Portal", type: "Official", url: "https://uppsc.up.nic.in", icon: "fa-building" }
            ]
        },
        teaching_government: {
            title: "Government Teacher (TGT/PGT)",
            icon: "fa-chalkboard-teacher",
            description: "Teach in government schools and colleges through CTET, TET, and state teaching eligibility tests. Shape future generations.",
            skills: ["Teaching", "Subject Expertise", "Classroom Management", "Communication", "Patience", "Child Psychology"],
            salary: { entry: "₹35,000", mid: "₹60,000", senior: "₹1,00,000+" },
            roadmap: [
                { phase: "Education", duration: "2-4 years", tasks: ["B.Ed degree", "CTET/TET preparation", "Subject mastery", "Teaching practice"] },
                { phase: "TGT Teacher", duration: "3-5 years", tasks: ["Classroom teaching", "Lesson planning", "Student assessment", "Co-curricular activities"] },
                { phase: "PGT/Principal", duration: "8-12 years", tasks: ["Senior classes", "Department head", "Administrative duties"] },
                { phase: "Education Officer", duration: "15+ years", tasks: ["District education officer", "Policy implementation", "Teacher training"] }
            ],
            resources: [
                { name: "CTET", type: "Exam", url: "https://ctet.nic.in", icon: "fa-chalkboard-teacher" },
                { name: "NCERT", type: "Resource", url: "https://ncert.nic.in", icon: "fa-book" }
            ]
        }
    }
};

        appConfig.customCareers.forEach(career => {
            if (!career.category || !career.key) {
                return;
            }

            if (!careerDatabase[career.category]) {
                careerDatabase[career.category] = {};
            }

            careerDatabase[career.category][career.key] = {
                title: career.title || 'Custom Career',
                icon: career.icon || 'fa-briefcase',
                description: career.description || '',
                skills: Array.isArray(career.skills) ? career.skills : [],
                salary: career.salary || { entry: 'N/A', mid: 'N/A', senior: 'N/A' },
                roadmap: Array.isArray(career.roadmap) ? career.roadmap : [],
                resources: Array.isArray(career.resources) ? career.resources : []
            };
        });

       // Assessment Questions - COMPLETE REPLACEMENT
const questions = [
    {
        id: "interest_area",
        title: "Which broad area interests you most?",
        options: [
            { id: "technology", label: "Technology & Computing", icon: "fa-laptop-code", desc: "Software, AI, cybersecurity, data" },
            { id: "creative", label: "Creative & Design", icon: "fa-palette", desc: "UX, content, media, arts" },
            { id: "business", label: "Business & Strategy", icon: "fa-chart-line", desc: "Management, finance, entrepreneurship" },
            { id: "healthcare", label: "Healthcare & Medicine", icon: "fa-user-md", desc: "Clinical, research, health tech" },
            { id: "science", label: "Science & Research", icon: "fa-microscope", desc: "Academic, lab, field research" },
            { id: "government", label: "Government & Public Service", icon: "fa-landmark", desc: "IAS, Defence, Police, Banking, Railways" }
        ]
    },
    {
        id: "work_style",
        title: "How do you prefer to work?",
        options: [
            { id: "analytical", label: "Analytical & Structured", icon: "fa-brain", desc: "Data-driven, logical, systematic" },
            { id: "creative", label: "Creative & Flexible", icon: "fa-lightbulb", desc: "Innovative, artistic, adaptable" },
            { id: "social", label: "Social & Collaborative", icon: "fa-users", desc: "Team-oriented, communicative" },
            { id: "independent", label: "Independent & Autonomous", icon: "fa-user", desc: "Self-directed, remote-friendly" },
            { id: "disciplined", label: "Disciplined & Hierarchical", icon: "fa-user-shield", desc: "Clear chain of command, protocols" }
        ]
    },
    {
        id: "impact_type",
        title: "What type of impact motivates you?",
        options: [
            { id: "technical", label: "Technical Innovation", icon: "fa-cogs", desc: "Building systems, solving complex problems" },
            { id: "human", label: "Helping People", icon: "fa-hands-helping", desc: "Improving lives, healthcare, education" },
            { id: "business", label: "Business Growth", icon: "fa-rocket", desc: "Scaling companies, market impact" },
            { id: "creative", label: "Creative Expression", icon: "fa-paint-brush", desc: "Design, storytelling, aesthetics" },
            { id: "nation", label: "Serving the Nation", icon: "fa-flag", desc: "Public service, defence, governance" }
        ]
    },
    {
        id: "risk_tolerance",
        title: "What's your risk tolerance for career stability?",
        options: [
            { id: "low", label: "Stability First", icon: "fa-shield-alt", desc: "Established companies, steady income" },
            { id: "medium", label: "Balanced Growth", icon: "fa-balance-scale", desc: "Growth companies, moderate risk" },
            { id: "high", label: "High Risk/High Reward", icon: "fa-mountain", desc: "Startups, entrepreneurship, trading" },
            { id: "government", label: "Job Security Priority", icon: "fa-building", desc: "Government job, pension, lifelong stability" }
        ]
    },
    {
        id: "learning_style",
        title: "How do you prefer to learn new skills?",
        options: [
            { id: "formal", label: "Formal Education", icon: "fa-graduation-cap", desc: "Degrees, certifications, structured" },
            { id: "self", label: "Self-Taught", icon: "fa-book-reader", desc: "Online courses, documentation, projects" },
            { id: "mentorship", label: "Mentorship", icon: "fa-user-tie", desc: "Apprenticeship, coaching, on-the-job" },
            { id: "mixed", label: "Mixed Approach", icon: "fa-random", desc: "Combination of methods" },
            { id: "exam", label: "Competitive Exam Preparation", icon: "fa-file-alt", desc: "Structured coaching, test series, rigorous study" }
        ]
    },
    {
        id: "physical_fitness",
        title: "How do you feel about physical fitness requirements?",
        options: [
            { id: "not_required", label: "Not Important", icon: "fa-couch", desc: "Desk job, minimal physical demands" },
            { id: "moderate", label: "Moderate Fitness", icon: "fa-walking", desc: "Basic health, occasional activity" },
            { id: "high", label: "High Fitness Required", icon: "fa-running", desc: "Regular exercise, physical standards" },
            { id: "extreme", label: "Extreme Physical Standards", icon: "fa-dumbbell", desc: "Rigorous training, endurance, strength" }
        ]
    },
    {
        id: "leadership_style",
        title: "What leadership role suits you?",
        options: [
            { id: "team_lead", label: "Team Leader", icon: "fa-users-cog", desc: "Lead small teams, project management" },
            { id: "strategic", label: "Strategic Leader", icon: "fa-chess", desc: "Long-term planning, vision setting" },
            { id: "command", label: "Command Leadership", icon: "fa-bullhorn", desc: "Direct authority, decisive orders" },
            { id: "administrative", label: "Administrative Leader", icon: "fa-tasks", desc: "Policy implementation, public service" },
            { id: "none", label: "Individual Contributor", icon: "fa-user", desc: "Specialist role, minimal management" }
        ]
    }
];

        appConfig.customQuestions.forEach(question => {
            if (!question.id || !question.title || !Array.isArray(question.options)) {
                return;
            }

            questions.push(question);
        });

        // State Management
        let currentStep = 0;
        let answers = {};
        let currentRecommendations = [];
        let currentSavedAssessmentId = null;
        let recommendationChart = null;

        // Initialize Particles
        function createParticles() {
            const container = document.getElementById('particles');
            if (!container) {
                return;
            }

            container.innerHTML = '';
            const colors = ['#6366f1', '#ec4899', '#8b5cf6'];

            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.width = Math.random() * 4 + 'px';
                particle.style.height = particle.style.width;
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (Math.random() * 20 + 10) + 's';
                container.appendChild(particle);
            }
        }

        // Navigation Functions
        function startAssessment() {
            document.getElementById('heroSection').classList.add('hidden');
            document.getElementById('assessmentSection').classList.remove('hidden');
            renderQuestion();
        }

        function renderQuestion() {
            const question = questions[currentStep];
            document.getElementById('stepIndicator').textContent = `Step ${currentStep + 1} of ${questions.length}`;
            document.getElementById('progressPercent').textContent = `${((currentStep + 1) / questions.length * 100).toFixed(0)}%`;
            document.getElementById('progressBar').style.width = `${(currentStep + 1) / questions.length * 100}%`;

            document.getElementById('questionTitle').innerHTML = `
                <span class="text-primary text-lg block mb-2">Question ${currentStep + 1}</span>
                ${question.title}
            `;

            const grid = document.getElementById('optionsGrid');
            grid.innerHTML = '';

            question.options.forEach((option, index) => {
                const card = document.createElement('div');
                card.className = `option-card glass rounded-2xl p-6 cursor-pointer border-2 border-transparent hover:border-primary/50 ${answers[question.id] === option.id ? 'selected' : ''}`;
                card.innerHTML = `
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-primary/20 flex items-center justify-center text-primary text-xl flex-shrink-0">
                            <i class="fas ${option.icon}"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg mb-1">${option.label}</h3>
                            <p class="text-gray-400 text-sm">${option.desc}</p>
                        </div>
                    </div>
                `;
                card.onclick = () => selectOption(option.id, card);
                grid.appendChild(card);
            });

            // Update navigation
            document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 0);
            document.getElementById('nextBtn').disabled = !answers[question.id];

            if (currentStep === questions.length - 1) {
                document.getElementById('nextBtn').innerHTML = 'See Results <i class="fas fa-star ml-2"></i>';
            } else {
                document.getElementById('nextBtn').innerHTML = 'Continue <i class="fas fa-arrow-right ml-2"></i>';
            }
        }

        function selectOption(optionId, cardElement) {
            const question = questions[currentStep];
            answers[question.id] = optionId;

            // Visual feedback
            document.querySelectorAll('.option-card').forEach(card => card.classList.remove('selected'));
            cardElement.classList.add('selected');

            document.getElementById('nextBtn').disabled = false;
        }

        function nextStep() {
            if (currentStep < questions.length - 1) {
                currentStep++;
                renderQuestion();
            } else {
                showResults();
            }
        }

        function previousStep() {
            if (currentStep > 0) {
                currentStep--;
                renderQuestion();
            }
        }

        // AI Recommendation Logic - COMPLETE REPLACEMENT
function calculateCareerMatch() {
    const { interest_area, work_style, impact_type, risk_tolerance, learning_style, physical_fitness, leadership_style } = answers;
    
    let recommendations = [];
    let scores = {};
    const maxPossibleScore = 52;
    
    // Initialize scores for all careers
    const allCareers = Object.values(careerDatabase).flatMap(cat => Object.values(cat));
    allCareers.forEach(career => {
        scores[career.title] = 0;
    });
    
    // SCORING LOGIC
    
    // 1. Interest Area Matching (Highest weight)
    if (interest_area === 'technology') {
        scores["Software Engineering"] += 10;
        scores["Data Science"] += 10;
        scores["Cybersecurity"] += 8;
        scores["Health Informatics"] += 6;
    } else if (interest_area === 'creative') {
        scores["UX/UI Design"] += 10;
        scores["Content Strategy"] += 8;
        scores["Content Creator / Influencer"] += 9;
        scores["Video Editor"] += 9;
        scores["Social Media Manager"] += 8;
    } else if (interest_area === 'business') {
        scores["Product Management"] += 10;
        scores["Entrepreneurship"] += 8;
        scores["Bank Officer (IBPS/SBI PO)"] += 9;
        scores["Chartered Accountant (CA)"] += 9;
    } else if (interest_area === 'healthcare') {
        scores["Health Informatics"] += 10;
        scores["Data Science"] += 6;
    } else if (interest_area === 'science') {
        scores["Research Scientist"] += 10;
        scores["Data Science"] += 8;
    } else if (interest_area === 'government') {
        scores["IAS Officer"] += 10;
        scores["Defence Officer (Army/Navy/Air Force)"] += 9;
        scores["Police Officer (IPS/State Police)"] += 9;
        scores["Bank Officer (IBPS/SBI PO)"] += 8;
        scores["Railway Officer (IRTS/IRAS)"] += 8;
        scores["SSC Gazetted Officer"] += 7;
        scores["State PSC Officer"] += 7;
        scores["Government Teacher (TGT/PGT)"] += 6;
    }
    
    // 2. Work Style Matching
    if (work_style === 'analytical') {
        scores["Data Science"] += 5;
        scores["Software Engineering"] += 5;
        scores["Chartered Accountant (CA)"] += 5;
        scores["Bank Officer (IBPS/SBI PO)"] += 4;
    } else if (work_style === 'disciplined') {
        scores["Defence Officer (Army/Navy/Air Force)"] += 8;
        scores["Police Officer (IPS/State Police)"] += 7;
        scores["IAS Officer"] += 6;
        scores["Railway Officer (IRTS/IRAS)"] += 5;
    } else if (work_style === 'social') {
        scores["UX/UI Design"] += 4;
        scores["Social Media Manager"] += 5;
        scores["Government Teacher (TGT/PGT)"] += 6;
    } else if (work_style === 'independent') {
        scores["Content Creator / Influencer"] += 5;
        scores["Video Editor"] += 4;
        scores["Entrepreneurship"] += 5;
    }
    
    // 3. Impact Type Matching
    if (impact_type === 'nation') {
        scores["IAS Officer"] += 8;
        scores["Defence Officer (Army/Navy/Air Force)"] += 8;
        scores["Police Officer (IPS/State Police)"] += 7;
        scores["Railway Officer (IRTS/IRAS)"] += 5;
        scores["Government Teacher (TGT/PGT)"] += 5;
    } else if (impact_type === 'technical') {
        scores["Software Engineering"] += 5;
        scores["Data Science"] += 5;
        scores["Cybersecurity"] += 4;
    } else if (impact_type === 'human') {
        scores["Health Informatics"] += 5;
        scores["Government Teacher (TGT/PGT)"] += 6;
        scores["Police Officer (IPS/State Police)"] += 4;
    } else if (impact_type === 'business') {
        scores["Entrepreneurship"] += 5;
        scores["Product Management"] += 4;
        scores["Chartered Accountant (CA)"] += 4;
    }
    
    // 4. Risk Tolerance
    if (risk_tolerance === 'government') {
        scores["IAS Officer"] += 6;
        scores["Defence Officer (Army/Navy/Air Force)"] += 6;
        scores["Bank Officer (IBPS/SBI PO)"] += 6;
        scores["Police Officer (IPS/State Police)"] += 6;
        scores["Railway Officer (IRTS/IRAS)"] += 6;
        scores["SSC Gazetted Officer"] += 6;
        scores["State PSC Officer"] += 6;
        scores["Government Teacher (TGT/PGT)"] += 6;
        scores["Chartered Accountant (CA)"] += 4;
    } else if (risk_tolerance === 'low') {
        scores["Bank Officer (IBPS/SBI PO)"] += 4;
        scores["Software Engineering"] += 3;
        scores["Health Informatics"] += 3;
    } else if (risk_tolerance === 'high') {
        scores["Entrepreneurship"] += 6;
        scores["Content Creator / Influencer"] += 5;
    }
    
    // 5. Learning Style
    if (learning_style === 'exam') {
        scores["IAS Officer"] += 6;
        scores["Defence Officer (Army/Navy/Air Force)"] += 6;
        scores["Police Officer (IPS/State Police)"] += 6;
        scores["Bank Officer (IBPS/SBI PO)"] += 6;
        scores["Railway Officer (IRTS/IRAS)"] += 5;
        scores["SSC Gazetted Officer"] += 6;
        scores["State PSC Officer"] += 6;
        scores["Government Teacher (TGT/PGT)"] += 5;
        scores["Chartered Accountant (CA)"] += 5;
    } else if (learning_style === 'formal') {
        scores["Chartered Accountant (CA)"] += 4;
        scores["Data Science"] += 3;
    } else if (learning_style === 'self') {
        scores["Software Engineering"] += 4;
        scores["Content Creator / Influencer"] += 4;
        scores["Video Editor"] += 4;
    }
    
    // 6. Physical Fitness (Critical for some careers)
    if (physical_fitness === 'extreme') {
        scores["Defence Officer (Army/Navy/Air Force)"] += 8;
        scores["Police Officer (IPS/State Police)"] += 6;
    } else if (physical_fitness === 'high') {
        scores["Police Officer (IPS/State Police)"] += 4;
        scores["Defence Officer (Army/Navy/Air Force)"] += 3;
    } else if (physical_fitness === 'not_required') {
        scores["IAS Officer"] += 4;
        scores["Software Engineering"] += 4;
        scores["Data Science"] += 4;
        scores["Bank Officer (IBPS/SBI PO)"] += 4;
        scores["Chartered Accountant (CA)"] += 4;
    }
    
    // 7. Leadership Style
    if (leadership_style === 'command') {
        scores["Defence Officer (Army/Navy/Air Force)"] += 6;
        scores["Police Officer (IPS/State Police)"] += 5;
    } else if (leadership_style === 'administrative') {
        scores["IAS Officer"] += 6;
        scores["State PSC Officer"] += 5;
        scores["SSC Gazetted Officer"] += 4;
    } else if (leadership_style === 'strategic') {
        scores["Product Management"] += 4;
        scores["IAS Officer"] += 4;
    } else if (leadership_style === 'none') {
        scores["Software Engineering"] += 3;
        scores["Video Editor"] += 4;
        scores["Data Science"] += 3;
    }
    
    // Sort careers by score and get top 3
    const sortedCareers = allCareers
        .map(career => {
            const rawScore = scores[career.title] || 0;
            const matchScore = Math.max(35, Math.min(99, Math.round((rawScore / maxPossibleScore) * 100)));
            return {
                career,
                score: rawScore,
                matchScore
            };
        })
        .sort((a, b) => b.score - a.score)
        .slice(0, 3);
    
    recommendations = sortedCareers;
    
    // Ensure we have at least 3 recommendations
    while (recommendations.length < 3) {
        const random = allCareers[Math.floor(Math.random() * allCareers.length)];
        if (!recommendations.some(item => item.career.title === random.title)) {
            recommendations.push({
                career: random,
                score: 0,
                matchScore: 35
            });
        }
    }
    
    return recommendations;
}

        function renderResults() {
            const primaryItem = currentRecommendations[0];
            if (!primaryItem) {
                showNotification('Unable to render recommendations.', 'error');
                return;
            }

            const primary = primaryItem.career;
            const alternatives = currentRecommendations.slice(1, 4);

            document.getElementById('primaryRecommendation').innerHTML = `
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 rounded-2xl bg-primary/20 flex items-center justify-center text-primary text-3xl">
                            <i class="fas ${primary.icon}"></i>
                        </div>
                        <div>
                            <div class="text-sm text-primary font-semibold mb-1">Best Match</div>
                            <h3 class="font-display text-3xl font-bold">${primary.title}</h3>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-green-400">${primaryItem.matchScore}%</div>
                        <div class="text-sm text-gray-400">Match Score</div>
                    </div>
                </div>
                <p class="text-gray-300 text-lg mb-6 leading-relaxed">${primary.description}</p>
                <div class="flex flex-wrap gap-2">
                    ${primary.skills.map(skill => `<span class="px-3 py-1 rounded-full bg-white/10 text-sm">${skill}</span>`).join('')}
                </div>
            `;

            document.getElementById('roadmapContainer').innerHTML = primary.roadmap.map((phase, index) => `
                <div class="flex items-start space-x-4 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold flex-shrink-0">
                        ${index + 1}
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-semibold text-lg">${phase.phase}</h4>
                            <span class="text-sm text-primary bg-primary/10 px-3 py-1 rounded-full">${phase.duration}</span>
                        </div>
                        <ul class="space-y-1 text-gray-400">
                            ${phase.tasks.map(task => `<li class="flex items-center"><i class="fas fa-check text-primary mr-2 text-xs"></i>${task}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `).join('');

            document.getElementById('skillsContainer').innerHTML = primary.skills.map((skill, index) => `
                <div class="flex items-center justify-between">
                    <span class="text-sm">${skill}</span>
                    <div class="w-32 h-2 bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-primary to-secondary" style="width: ${90 - index * 10}%"></div>
                    </div>
                </div>
            `).join('');

            document.getElementById('salaryContainer').innerHTML = `
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 rounded-lg bg-white/5">
                        <span class="text-gray-400">Entry Level</span>
                        <span class="font-bold text-green-400">${primary.salary.entry}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-lg bg-white/5">
                        <span class="text-gray-400">Mid Career</span>
                        <span class="font-bold text-blue-400">${primary.salary.mid}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-lg bg-white/5">
                        <span class="text-gray-400">Senior Level</span>
                        <span class="font-bold text-purple-400">${primary.salary.senior}</span>
                    </div>
                </div>
            `;

            const recommendationChartCanvas = document.getElementById('recommendationChart');
            if (recommendationChartCanvas) {
                if (recommendationChart) {
                    recommendationChart.destroy();
                }

                recommendationChart = new Chart(recommendationChartCanvas, {
                    type: 'bar',
                    data: {
                        labels: currentRecommendations.map(item => item.career.title),
                        datasets: [{
                            label: 'Match Score',
                            data: currentRecommendations.map(item => item.matchScore),
                            backgroundColor: ['rgba(99, 102, 241, 0.75)', 'rgba(236, 72, 153, 0.75)', 'rgba(34, 197, 94, 0.75)'],
                            borderRadius: 12
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.08)' } },
                            y: { beginAtZero: true, max: 100, ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.08)' } }
                        }
                    }
                });
            }

            window.currentResources = primary.resources;

            document.getElementById('alternativeCareers').innerHTML = alternatives.map((item, index) => `
                <div class="glass rounded-2xl p-6 card-hover cursor-pointer" onclick="switchPrimary(${index + 1})">
                    <div class="w-12 h-12 rounded-xl bg-secondary/20 flex items-center justify-center text-secondary text-xl mb-4">
                        <i class="fas ${item.career.icon}"></i>
                    </div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <h4 class="font-display text-xl font-bold">${item.career.title}</h4>
                        <span class="text-xs text-green-300">${item.matchScore}%</span>
                    </div>
                    <p class="text-gray-400 text-sm mb-4">${item.career.description}</p>
                    <div class="flex items-center text-primary text-sm font-semibold">
                        Explore Path <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
            `).join('');
        }

        function showResults() {
            document.getElementById('assessmentSection').classList.add('hidden');
            document.getElementById('resultsSection').classList.remove('hidden');

            currentRecommendations = calculateCareerMatch();
            renderResults();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function switchPrimary(index) {
            if (!currentRecommendations[index]) {
                return;
            }

            const [selected] = currentRecommendations.splice(index, 1);
            currentRecommendations.unshift(selected);
            renderResults();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function exploreResources() {
            const modal = document.getElementById('resourcesModal');
            const content = document.getElementById('resourcesContent');

            content.innerHTML = window.currentResources.map(resource => `
                <a href="${resource.url}" target="_blank" class="block glass rounded-xl p-6 hover:bg-white/10 transition-all group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-primary/20 flex items-center justify-center text-primary text-xl group-hover:scale-110 transition-transform">
                            <i class="fas ${resource.icon}"></i>
                        </div>
                        <span class="text-xs text-gray-400 border border-white/20 px-2 py-1 rounded">${resource.type}</span>
                    </div>
                    <h4 class="font-semibold text-lg mb-2 group-hover:text-primary transition-colors">${resource.name}</h4>
                    <div class="flex items-center text-sm text-gray-400 group-hover:text-white transition-colors">
                        Visit Resource <i class="fas fa-external-link-alt ml-2"></i>
                    </div>
                </a>
            `).join('');

            modal.classList.remove('hidden');
        }

        function closeResources() {
            document.getElementById('resourcesModal').classList.add('hidden');
        }

        function persistSavedPathLocally(primaryItem) {
            const saved = JSON.parse(localStorage.getItem('savedCareerPaths') || '[]');
            const nextEntry = {
                title: primaryItem.career.title,
                matchScore: primaryItem.matchScore,
                savedAt: new Date().toISOString()
            };

            saved.unshift(nextEntry);
            localStorage.setItem('savedCareerPaths', JSON.stringify(saved.slice(0, 20)));
        }

        function savePath() {
            const primaryItem = currentRecommendations[0];
            if (!primaryItem) {
                showNotification('Complete the assessment first.', 'error');
                return;
            }

            fetch(appConfig.saveAssessmentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': appConfig.csrfToken
                },
                body: JSON.stringify({
                    answers,
                    result: primaryItem.career.title,
                    match_score: primaryItem.matchScore
                })
            })
            .then(async response => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Unable to save assessment.');
                }

                currentSavedAssessmentId = payload.assessment_id || null;
                persistSavedPathLocally(primaryItem);
                showNotification(payload.message || 'Saved successfully', 'success');
            })
            .catch(error => {
                showNotification(error.message, 'error');
            });
        }

        function submitFeedback() {
            const primaryItem = currentRecommendations[0];
            const rating = document.getElementById('feedbackRating');
            const feedbackText = document.getElementById('feedbackText');

            if (!primaryItem || !rating || !feedbackText) {
                showNotification('Feedback form is not available.', 'error');
                return;
            }

            if (!rating.value) {
                showNotification('Select a rating first.', 'info');
                return;
            }

            fetch(appConfig.submitFeedbackUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': appConfig.csrfToken
                },
                body: JSON.stringify({
                    career_title: primaryItem.career.title,
                    rating: Number(rating.value),
                    feedback_text: feedbackText.value.trim(),
                    assessment_id: currentSavedAssessmentId
                })
            })
            .then(async response => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Unable to submit feedback.');
                }

                rating.value = '';
                feedbackText.value = '';
                showNotification(payload.message || 'Feedback submitted.', 'success');
            })
            .catch(error => {
                showNotification(error.message, 'error');
            });
        }

        function showSavedPaths() {
            const saved = JSON.parse(localStorage.getItem('savedCareerPaths') || '[]');
            if (saved.length === 0) {
                showNotification('No saved paths yet. Complete an assessment first!', 'info');
                return;
            }
            // In a real app, this would show a modal with history
            showNotification(`You have ${saved.length} saved career path(s)`, 'info');
        }

        function shareResults() {
            if (navigator.share) {
                navigator.share({
                    title: 'My Career Path Recommendation',
                    text: 'Check out my personalized career guidance from CareerPath AI!',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                showNotification('Link copied to clipboard!', 'success');
            }
        }

        function resetJourney() {
            currentStep = 0;
            answers = {};
            currentRecommendations = [];

            document.getElementById('resultsSection').classList.add('hidden');
            document.getElementById('assessmentSection').classList.add('hidden');
            document.getElementById('heroSection').classList.remove('hidden');

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showAbout() {
            showNotification('CareerPath AI v1.0 - Personalized career guidance powered by intelligent algorithms', 'info');
        }

        function applyTheme(theme) {
            const isLight = theme === 'light';
            document.body.classList.toggle('light-mode', isLight);

            const label = isLight ? 'Dark Mode' : 'Light Mode';
            const desktopLabel = document.getElementById('themeToggleLabel');
            const mobileLabel = document.getElementById('themeToggleLabelMobile');
            if (desktopLabel) desktopLabel.textContent = label;
            if (mobileLabel) mobileLabel.textContent = label;
            localStorage.setItem('careerPathTheme', theme);
        }

        function toggleTheme() {
            const currentTheme = localStorage.getItem('careerPathTheme') || 'dark';
            applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
        }

        function getGreetingByHour(hour) {
            if (hour < 12) return 'Good Morning';
            if (hour < 17) return 'Good Afternoon';
            return 'Good Evening';
        }

        function updateTimeGreetings() {
            const currentHour = new Date().getHours();
            document.querySelectorAll('[data-greeting-name]').forEach((element) => {
                const username = element.dataset.greetingName || 'User';
                element.textContent = `${getGreetingByHour(currentHour)}, ${username}!`;
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                info: 'bg-primary',
                error: 'bg-red-500'
            };

            notification.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-lg transform translate-y-20 transition-transform duration-300 z-50 flex items-center`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'} mr-2"></i>
                ${message}
            `;

            document.body.appendChild(notification);

            setTimeout(() => notification.classList.remove('translate-y-20'), 100);
            setTimeout(() => {
                notification.classList.add('translate-y-20');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
            applyTheme(localStorage.getItem('careerPathTheme') || 'dark');
            updateTimeGreetings();
            setInterval(updateTimeGreetings, 60000);
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' && !document.getElementById('nextBtn').disabled) {
                nextStep();
            } else if (e.key === 'ArrowLeft' && currentStep > 0) {
                previousStep();
            }
        });
        // Add this inside your existing <script> tag or at the bottom

// Mobile menu toggle
document.getElementById('menuBtn').addEventListener('click', function() {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.classList.toggle('hidden');
});

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const mobileMenu = document.getElementById('mobileMenu');
    const menuBtn = document.getElementById('menuBtn');
    
    if (!mobileMenu.contains(event.target) && !menuBtn.contains(event.target)) {
        mobileMenu.classList.add('hidden');
    }
});
    </script>
</body>

</html>

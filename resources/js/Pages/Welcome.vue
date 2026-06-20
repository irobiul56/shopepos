<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const { props } = usePage();

// Check if user is authenticated
const isAuthenticated = computed(() => {
    return !!props.auth?.user;
});

// Shop info (if authenticated)
const shop = ref(props.shop || null);

// Features list
const features = [
    {
        icon: 'fa-cash-register',
        title: 'Point of Sale',
        description: 'Fast and easy checkout process with barcode support',
        color: 'bg-indigo-500',
    },
    {
        icon: 'fa-chart-bar',
        title: 'Sales Reports',
        description: 'Detailed reports and analytics for your business',
        color: 'bg-emerald-500',
    },
    {
        icon: 'fa-hand-holding-usd',
        title: 'Due Collection',
        description: 'Track and collect customer dues efficiently',
        color: 'bg-amber-500',
    },
    {
        icon: 'fa-users',
        title: 'Customer Management',
        description: 'Manage customer information and purchase history',
        color: 'bg-purple-500',
    },
    {
        icon: 'fa-boxes',
        title: 'Inventory Management',
        description: 'Track stock levels and get low stock alerts',
        color: 'bg-cyan-500',
    },
    {
        icon: 'fa-file-invoice',
        title: 'Invoice Generation',
        description: 'Generate professional invoices for customers',
        color: 'bg-rose-500',
    },
];

// Testimonials
const testimonials = [
    {
        name: 'John Doe',
        role: 'Retail Store Owner',
        quote: 'This POS system has transformed my business. Inventory tracking and sales reports are amazing!',
        avatar: 'JD',
    },
    {
        name: 'Jane Smith',
        role: 'Restaurant Manager',
        quote: 'Easy to use, fast checkout, and excellent customer support. Highly recommended!',
        avatar: 'JS',
    },
    {
        name: 'Robert Wilson',
        role: 'Electronics Shop Owner',
        quote: 'The due collection feature is a lifesaver. I can now track all my customers dues easily.',
        avatar: 'RW',
    },
];

// Pricing plans
const pricingPlans = [
    {
        name: 'Starter',
        price: 'Free',
        description: 'Perfect for small businesses just starting out',
        features: ['Up to 100 products', 'Basic reports', '3 users', 'Email support'],
        button: 'Get Started',
        popular: false,
    },
    {
        name: 'Professional',
        price: 'Tk 2,999',
        description: 'Ideal for growing businesses with more needs',
        features: ['Unlimited products', 'Advanced reports', 'Unlimited users', 'Priority support', 'Due collection'],
        button: 'Start Free Trial',
        popular: true,
    },
    {
        name: 'Enterprise',
        price: 'Custom',
        description: 'For large businesses with custom requirements',
        features: ['Everything in Professional', 'Custom integrations', 'Dedicated support', 'API access'],
        button: 'Contact Sales',
        popular: false,
    },
];

// Navigate to POS
const goToPos = () => {
    if (isAuthenticated.value) {
        router.visit(route('pos.index'));
    } else {
        router.visit(route('login'));
    }
};

// Navigate to login
const goToLogin = () => {
    router.visit(route('login'));
};

// Navigate to register
const goToRegister = () => {
    router.visit(route('register'));
};

// Navigate to dashboard if authenticated
const goToDashboard = () => {
    if (isAuthenticated.value) {
        router.visit(route('dashboard'));
    } else {
        router.visit(route('login'));
    }
};

// Scroll to section
const scrollToSection = (id) => {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(() => {
    console.log('Home page loaded');
});
</script>

<template>
    <Head title="Smart POS - Point of Sale System" />
    
    <!-- ============================================================ -->
    <!-- NAVBAR -->
    <!-- ============================================================ -->
    <nav class="fixed top-0 left-0 right-0 bg-white/95 backdrop-blur-sm shadow-sm z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center gap-2 cursor-pointer" @click="goToDashboard">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                        <i class="fas fa-store"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-800">Smart<span class="text-indigo-600">POS</span></span>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden md:flex items-center gap-6">
                    <button @click="scrollToSection('features')" class="text-sm text-gray-600 hover:text-indigo-600 transition">Features</button>
                    <button @click="scrollToSection('testimonials')" class="text-sm text-gray-600 hover:text-indigo-600 transition">Testimonials</button>
                    <button @click="scrollToSection('pricing')" class="text-sm text-gray-600 hover:text-indigo-600 transition">Pricing</button>
                    
                    <div class="flex items-center gap-3 ml-4">
                        <template v-if="isAuthenticated">
                            <button @click="goToPos" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
                                <i class="fas fa-cash-register mr-1"></i> POS
                            </button>
                            <button @click="goToDashboard" class="text-sm text-gray-600 hover:text-indigo-600 transition">
                                Dashboard
                            </button>
                        </template>
                        <template v-else>
                            <button @click="goToLogin" class="text-sm text-gray-600 hover:text-indigo-600 transition">Log In</button>
                            <button @click="goToRegister" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
                                Get Started
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobileMenuBtn" class="p-2 rounded-lg hover:bg-gray-100 transition" @click="toggleMobileMenu">
                        <i class="fas fa-bars text-gray-600 text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="md:hidden hidden bg-white border-t border-gray-100 py-4 px-4">
            <div class="flex flex-col gap-3">
                <button @click="scrollToSection('features')" class="text-sm text-gray-600 hover:text-indigo-600 transition text-left py-2">Features</button>
                <button @click="scrollToSection('testimonials')" class="text-sm text-gray-600 hover:text-indigo-600 transition text-left py-2">Testimonials</button>
                <button @click="scrollToSection('pricing')" class="text-sm text-gray-600 hover:text-indigo-600 transition text-left py-2">Pricing</button>
                <div class="border-t border-gray-200 pt-3 flex flex-col gap-2">
                    <template v-if="isAuthenticated">
                        <button @click="goToPos" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
                            <i class="fas fa-cash-register mr-1"></i> Open POS
                        </button>
                        <button @click="goToDashboard" class="text-sm text-gray-600 hover:text-indigo-600 transition">Dashboard</button>
                    </template>
                    <template v-else>
                        <button @click="goToLogin" class="text-sm text-gray-600 hover:text-indigo-600 transition">Log In</button>
                        <button @click="goToRegister" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
                            Get Started
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </nav>

    <!-- ============================================================ -->
    <!-- HERO SECTION -->
    <!-- ============================================================ -->
    <section class="pt-20 pb-16 bg-gradient-to-br from-indigo-50 via-white to-indigo-50/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div>
                    <div class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm font-medium mb-6">
                        <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                        Now available
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                        Smart <span class="text-indigo-600">POS</span> System
                        <br />
                        <span class="text-gray-600 text-3xl sm:text-4xl lg:text-5xl">For Modern Business</span>
                    </h1>
                    <p class="mt-4 text-lg text-gray-500 max-w-lg">
                        Streamline your sales, manage inventory, track customers, and grow your business with our all-in-one Point of Sale solution.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <button 
                            @click="goToPos"
                            class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200 flex items-center gap-2"
                        >
                            <i class="fas fa-rocket"></i> 
                            {{ isAuthenticated ? 'Open POS' : 'Get Started' }}
                        </button>
                        <button 
                            @click="scrollToSection('features')"
                            class="bg-white text-gray-700 px-8 py-3 rounded-xl font-semibold border border-gray-200 hover:bg-gray-50 transition flex items-center gap-2"
                        >
                            <i class="fas fa-play-circle"></i> See Features
                        </button>
                    </div>
                    <div class="mt-6 flex items-center gap-6 text-sm text-gray-500">
                        <span class="flex items-center gap-1"><i class="fas fa-check-circle text-emerald-500"></i> Free Trial</span>
                        <span class="flex items-center gap-1"><i class="fas fa-check-circle text-emerald-500"></i> No Credit Card</span>
                        <span class="flex items-center gap-1"><i class="fas fa-check-circle text-emerald-500"></i> 24/7 Support</span>
                    </div>
                </div>

                <!-- Right: Hero Image / Stats -->
                <div class="relative">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                        <!-- Dashboard Preview -->
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-red-400"></span>
                                <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                                <span class="w-3 h-3 rounded-full bg-green-400"></span>
                            </div>
                            <span class="text-sm text-gray-400">Dashboard Preview</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-indigo-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-indigo-600">Tk 37,925</p>
                                <p class="text-xs text-gray-500">Revenue</p>
                            </div>
                            <div class="bg-emerald-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-emerald-600">39</p>
                                <p class="text-xs text-gray-500">Total Sales</p>
                            </div>
                            <div class="bg-amber-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-amber-600">Tk 21,000</p>
                                <p class="text-xs text-gray-500">Profit</p>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-purple-600">Tk 972</p>
                                <p class="text-xs text-gray-500">Avg Sale</p>
                            </div>
                        </div>
                        <div class="mt-4 h-16 bg-gray-100 rounded-lg flex items-center justify-between px-4">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 text-sm">📈</span>
                                <span class="text-sm text-gray-600">Sales growth +12% this month</span>
                            </div>
                            <span class="text-xs text-emerald-600 font-semibold">↑ 12%</span>
                        </div>
                    </div>
                    <!-- Floating Badge -->
                    <div class="absolute -right-4 -top-4 bg-emerald-500 text-white px-4 py-2 rounded-xl shadow-lg text-sm font-semibold animate-bounce">
                        <i class="fas fa-star mr-1"></i> Trusted by 1000+ businesses
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- STATS SECTION -->
    <!-- ============================================================ -->
    <section class="py-12 bg-white border-y border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <p class="text-3xl font-bold text-indigo-600">10K+</p>
                    <p class="text-sm text-gray-500">Businesses Using</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-emerald-600">1M+</p>
                    <p class="text-sm text-gray-500">Transactions Processed</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-amber-600">99.9%</p>
                    <p class="text-sm text-gray-500">Uptime Guarantee</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-purple-600">4.9★</p>
                    <p class="text-sm text-gray-500">User Rating</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- FEATURES SECTION -->
    <!-- ============================================================ -->
    <section id="features" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Everything You Need to <span class="text-indigo-600">Grow</span></h2>
                <p class="mt-2 text-gray-500 max-w-2xl mx-auto">
                    Powerful features designed to help you manage your business efficiently and boost sales.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="feature in features" :key="feature.title" 
                     class="bg-white rounded-xl shadow-sm p-6 hover:shadow-xl transition group border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl"
                             :class="feature.color">
                            <i :class="['fas', feature.icon]"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ feature.title }}</h3>
                            <p class="text-sm text-gray-500">{{ feature.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-10">
                <button @click="goToPos" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-indigo-700 transition">
                    <i class="fas fa-rocket mr-2"></i> Start Using POS Now
                </button>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- TESTIMONIALS SECTION -->
    <!-- ============================================================ -->
    <section id="testimonials" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">What Our <span class="text-indigo-600">Customers</span> Say</h2>
                <p class="mt-2 text-gray-500">Hear from real business owners who use Smart POS</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="testimonial in testimonials" :key="testimonial.name" 
                     class="bg-gray-50 rounded-xl p-6 border border-gray-100 hover:shadow-md transition">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg">
                            {{ testimonial.avatar }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">{{ testimonial.name }}</p>
                            <p class="text-sm text-gray-500">{{ testimonial.role }}</p>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm italic">"{{ testimonial.quote }}"</p>
                    <div class="mt-3 text-amber-400 text-sm">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- PRICING SECTION -->
    <!-- ============================================================ -->
    <section id="pricing" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Simple, <span class="text-indigo-600">Transparent</span> Pricing</h2>
                <p class="mt-2 text-gray-500">Choose the plan that fits your business needs</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="plan in pricingPlans" :key="plan.name" 
                     class="bg-white rounded-xl shadow-sm p-6 border relative"
                     :class="plan.popular ? 'border-indigo-500 shadow-lg' : 'border-gray-200'">
                    <div v-if="plan.popular" class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white px-4 py-1 rounded-full text-xs font-semibold">
                        Most Popular
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">{{ plan.name }}</h3>
                    <p class="text-3xl font-bold text-gray-900 mt-2">
                        {{ plan.price }}
                        <span class="text-sm font-normal text-gray-500" v-if="plan.price !== 'Free' && plan.price !== 'Custom'">/mo</span>
                    </p>
                    <p class="text-sm text-gray-500 mt-2">{{ plan.description }}</p>
                    <ul class="mt-4 space-y-2">
                        <li v-for="feature in plan.features" :key="feature" class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="fas fa-check text-emerald-500"></i> {{ feature }}
                        </li>
                    </ul>
                    <button @click="goToRegister" 
                            class="w-full mt-6 py-2 rounded-lg font-semibold transition"
                            :class="plan.popular ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        {{ plan.button }}
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- CTA SECTION -->
    <!-- ============================================================ -->
    <section class="py-16 bg-indigo-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white">Ready to <span class="text-indigo-200">Transform</span> Your Business?</h2>
            <p class="mt-4 text-indigo-100 text-lg">
                Join thousands of satisfied business owners using Smart POS to grow their business.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <button @click="goToPos" class="bg-white text-indigo-600 px-8 py-3 rounded-xl font-semibold hover:bg-indigo-50 transition shadow-lg">
                    <i class="fas fa-rocket mr-2"></i> Start Free Trial
                </button>
                <button @click="scrollToSection('features')" class="bg-indigo-500 text-white px-8 py-3 rounded-xl font-semibold hover:bg-indigo-400 transition border border-indigo-400">
                    <i class="fas fa-info-circle mr-2"></i> Learn More
                </button>
            </div>
            <p class="mt-4 text-indigo-200 text-sm">
                <i class="fas fa-check-circle mr-1"></i> No credit card required
            </p>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- FOOTER -->
    <!-- ============================================================ -->
    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                            <i class="fas fa-store"></i>
                        </div>
                        <span class="text-xl font-bold text-white">Smart<span class="text-indigo-400">POS</span></span>
                    </div>
                    <p class="text-sm">The all-in-one Point of Sale solution for modern businesses.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><button @click="scrollToSection('features')" class="hover:text-white transition">Features</button></li>
                        <li><button @click="scrollToSection('pricing')" class="hover:text-white transition">Pricing</button></li>
                        <li><button @click="goToPos" class="hover:text-white transition">Demo</button></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact</a></li>
                        <li><a href="#" class="hover:text-white transition">Careers</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition">Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition">API Reference</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-6 text-sm text-center">
                <p>&copy; 2024 Smart POS. All rights reserved.</p>
            </div>
        </div>
    </footer>
</template>

<script>
// Mobile menu toggle
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('hidden');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('mobileMenu');
    const btn = document.getElementById('mobileMenuBtn');
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.add('hidden');
    }
});
</script>

<style scoped>
/* Smooth scroll behavior */
html {
    scroll-behavior: smooth;
}

/* Animations */
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-bounce {
    animation: bounce 2s infinite;
}
</style>
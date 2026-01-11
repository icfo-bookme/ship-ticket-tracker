<x-frontend-layout>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-emerald-50 dark:from-gray-900 dark:to-gray-800 py-8 px-4">
        <div class="max-w-7xl mx-auto">
            <!-- Success Message Container -->
            <div class="text-center mb-5 animate-fade-in">
                <div class="relative lg:flex items-center justify-center">
                    <!-- Animated Checkmark -->
                    <div class="">
                        <div class="w-8 h-8 mx-auto  relative">
                            <div
                                class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shadow-lg shadow-green-200 dark:shadow-green-900/30">
                                <svg class="w-8 h-8 text-white animate-bounce" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <!-- Pulsing Ring -->
                            <div class="absolute inset-0 w-8 h-8 bg-green-400 rounded-full animate-ping opacity-20">
                            </div>
                        </div>

                        <!-- Success Message -->
                        <h1 class="text-xl font-bold text-gray-800 dark:text-white mb-4">
                            Your Information Saved Successfully! 🎉
                        </h1>
                    </div>
                    
                </div>
            </div>

            <!-- Hotel Recommendations Section -->
            <div class="mt-16">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">
                        <i class="fas fa-hotel mr-3 text-yellow-500"></i>
                        Recommended Hotels for Your Stay
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        Make your journey complete with a comfortable stay at these highly-rated hotels
                    </p>
                </div>

                <!-- Hotels Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Hotel 1 -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden transform transition duration-300 hover:-translate-y-2 hover:shadow-2xl border border-gray-100 dark:border-gray-700">
                        <div class="relative">
                            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                                alt="Sea View Hotel" class="w-full h-48 object-cover">
                            <div
                                class="absolute top-4 right-4 bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                4.8 ★
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">
                                        Sea View Grand Hotel
                                    </h3>
                                    <div class="flex items-center text-gray-600 dark:text-gray-400 text-sm">
                                        <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                                        Near Port, Coastal Area
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-wifi text-green-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Free WiFi</span>
                                </div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-swimming-pool text-blue-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Swimming Pool</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-utensils text-red-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Restaurant</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Starting from</p>
                                    <p class="text-2xl font-bold text-gray-800 dark:text-white">৳ 4,500<span
                                            class="text-sm font-normal">/night</span></p>
                                </div>
                                <a href="#"
                                    class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Hotel 2 -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden transform transition duration-300 hover:-translate-y-2 hover:shadow-2xl border border-gray-100 dark:border-gray-700">
                        <div class="relative">
                            <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                                alt="Paradise Resort" class="w-full h-48 object-cover">
                            <div
                                class="absolute top-4 right-4 bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                4.9 ★
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">
                                        Paradise Beach Resort
                                    </h3>
                                    <div class="flex items-center text-gray-600 dark:text-gray-400 text-sm">
                                        <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                                        Beachfront, 5 min from Port
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-spa text-purple-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Spa & Wellness</span>
                                </div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-dumbbell text-orange-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Gym</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-concierge-bell text-yellow-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">24/7 Room Service</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Starting from</p>
                                    <p class="text-2xl font-bold text-gray-800 dark:text-white">৳ 6,200<span
                                            class="text-sm font-normal">/night</span></p>
                                </div>
                                <a href="#"
                                    class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-lg font-semibold transition duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Hotel 3 -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden transform transition duration-300 hover:-translate-y-2 hover:shadow-2xl border border-gray-100 dark:border-gray-700">
                        <div class="relative">
                            <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                                alt="Budget Inn" class="w-full h-48 object-cover">
                            <div
                                class="absolute top-4 right-4 bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                4.5 ★
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">
                                        Budget Inn
                                    </h3>
                                    <div class="flex items-center text-gray-600 dark:text-gray-400 text-sm">
                                        <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                                        City Center, 10 min from Port
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-parking text-gray-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Free Parking</span>
                                </div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-coffee text-yellow-700 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Free Breakfast</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-shuttle-van text-red-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">Airport/Port Shuttle</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Starting from</p>
                                    <p class="text-2xl font-bold text-gray-800 dark:text-white">৳ 2,500<span
                                            class="text-sm font-normal">/night</span></p>
                                </div>
                                <a href="#"
                                    class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="mt-16 text-center">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-8 shadow-2xl">
                    <h3 class="text-2xl font-bold text-white mb-4">
                        Need Help With Your Booking?
                    </h3>
                    <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                        Our customer support team is available 24/7 to assist you with any questions about your ticket
                        or hotel booking.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="tel:+8801234567890"
                            class="bg-white text-blue-600 hover:bg-blue-50 px-8 py-3 rounded-lg font-semibold transition duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-phone-alt mr-2"></i>
                            Call Now: +880 1234 567890
                        </a>
                        <a href="mailto:support@example.com"
                            class="bg-transparent border-2 border-white text-white hover:bg-white/10 px-8 py-3 rounded-lg font-semibold transition duration-300 transform hover:scale-105">
                            <i class="fas fa-envelope mr-2"></i>
                            Email Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Add success message from session
            document.addEventListener('DOMContentLoaded', function() {
                // Show success toast if session has success message
                @if (session('success'))
                    setTimeout(() => {
                        const toast = document.createElement('div');
                        toast.className =
                            'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in-down';
                        toast.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-3 text-xl"></i>
                            <div>
                                <p class="font-semibold">Success!</p>
                                <p class="text-sm">${@json(session('success'))}</p>
                            </div>
                        </div>
                    `;
                        document.body.appendChild(toast);

                        // Remove toast after 5 seconds
                        setTimeout(() => {
                            toast.remove();
                        }, 5000);
                    }, 1000);
                @endif

                // Add confetti effect on page load
                setTimeout(() => {
                    createConfetti();
                }, 500);

                function createConfetti() {
                    const colors = ['#3B82F6', '#10B981', '#8B5CF6', '#EF4444', '#F59E0B'];

                    for (let i = 0; i < 50; i++) {
                        const confetti = document.createElement('div');
                        confetti.className = 'fixed w-2 h-2 rounded-full z-40';
                        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                        confetti.style.left = Math.random() * 100 + 'vw';
                        confetti.style.top = '-10px';
                        confetti.style.opacity = '0.8';

                        document.body.appendChild(confetti);

                        // Animate confetti
                        const animation = confetti.animate([{
                                transform: 'translateY(0) rotate(0deg)',
                                opacity: 0.8
                            },
                            {
                                transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`,
                                opacity: 0
                            }
                        ], {
                            duration: 2000 + Math.random() * 2000,
                            easing: 'cubic-bezier(0.215, 0.610, 0.355, 1)'
                        });

                        animation.onfinish = () => confetti.remove();
                    }
                }
            });
        </script>
    @endpush
</x-frontend-layout>

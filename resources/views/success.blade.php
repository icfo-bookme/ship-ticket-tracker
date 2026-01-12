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

                    @forelse ($hotels as $hotel)
                        <div
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden transform transition duration-300  border border-gray-100 dark:border-gray-700">

                            <!-- Image & Rating -->
                            <div class="relative">
                                <img src="{{ $hotel->main_photo
                                    ? 'https://bookme.com.bd/admin/storage/' . $hotel->main_photo
                                    : 'https://via.placeholder.com/800x400?text=Hotel+Image' }}"
                                    alt="{{ $hotel->name }}" class="w-full h-48 object-cover">

                                @if ($hotel->star_rating > 0)
                                    <div
                                        class="absolute top-4 right-4 bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                        {{ number_format($hotel->star_rating, 1) }} ★
                                    </div>
                                @endif


                            </div>

                            <!-- Content -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">
                                        {{ $hotel->name }}
                                    </h3>

                                    <div class="flex  text-gray-600 dark:text-gray-400 text-sm">
                                        <i class="fas fa-map-marker-alt mr-2 text-blue-500 mt-1"></i>
                                        {{ $hotel->street_address }}, {{ $hotel->city }}
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Starting from
                                        </p>

                                        @if ($hotel->price)
                                            <p class="text-2xl font-bold text-gray-800 dark:text-white">
                                                ৳ {{ number_format($hotel->price) }}
                                                <span class="text-sm font-normal">/night</span>
                                            </p>
                                        @else
                                            <p class="text-sm text-red-500">
                                                Price not available
                                            </p>
                                        @endif
                                    </div>

                                    <a href="{{ url('https://bookme.com.bd/hotel/list/details/' . Str::slug($hotel->name) . '/' . $hotel->id) }}"
                                        class="bg-gradient-to-r from-blue-950 to-blue-900 hover:from-blue-950 hover:to-blue-700
          text-white px-3 py-2 rounded-lg font-semibold transition duration-300
          transform hover:scale-105 shadow-lg hover:shadow-xl">
                                        View Details
                                    </a>

                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 text-center text-gray-500">
                            No hotels found.
                        </div>
                    @endforelse

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

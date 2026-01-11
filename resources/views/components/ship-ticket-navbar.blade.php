{{-- resources/views/components/ship-ticket-navbar.blade.php --}}
<nav class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white shadow-xl  top-0 ">
    <div class=" ">
        <div class="flex items-center justify-between py-3">
            {{-- Brand Logo --}}
            {{-- <div class="flex items-center space-x-2">
                <img src="https://bookme.com.bd/logo.png" alt="">
            </div> --}}

            {{-- Marketing Banner --}}
            <div class=" flex flex-1 px-4">
                <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg    text-center mx-4 animate-pulse">
                    <p class="font-bold text-sm p-2 text-center">🏨 SPECIAL OFFER: 20% OFF Hotel Bookings This Week!
                        <br> Only For Our
                        Customers</p>
                </div>
                <div>
                    {{-- Marketing Mobile Banner --}}
                    <a href="https://bookme.com.bd/hotel/Saint-Martin?locationID=702">
                        <div class=" text-center">
                            <div class="bg-yellow-500 text-blue-900 text-base font-bold px-3 py-1 rounded ">
                                Book Hotel <br> NOW
                            </div>
                        </div>
                    </a>
                </div>
            </div>


            {{-- Mobile Menu Button --}}
            {{-- <button id="mobileMenuButton" class="lg:hidden p-2 rounded-lg hover:bg-blue-800 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button> --}}
        </div>

        {{-- Marketing Strip --}}
        <div class="bg-white/10 border-t border-blue-400 py-2 px-4 hidden lg:block">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-6">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 text-green-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>Best Price Guarantee</span>
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 text-green-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>24/7 Customer Support</span>
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 text-green-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>Flexible Cancellation</span>
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-yellow-300 font-bold">📞 Call Now: 1-800-TRAVEL</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div id="mobileMenu" class="lg:hidden bg-blue-800 border-t border-blue-700 hidden">
        <div class="container mx-auto px-4 py-4">

            {{-- <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg p-3 mb-4 text-center">
                <p class="font-bold text-sm">🚢 20% OFF Cruise Bookings!</p>
                <p class="text-xs mt-1">Limited Time Offer</p>
            </div> --}}

            {{-- <div class="grid grid-cols-2 gap-2">
                <a href="#" class="mobile-nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    HOTELS
                </a>
                <a href="#" class="mobile-nav-link bg-blue-700 border-2 border-yellow-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    SHIP TICKETS
                    <span class="bg-red-500 text-xs px-1.5 py-0.5 rounded-full ml-1">HOT</span>
                </a>
                <a href="#" class="mobile-nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    FLIGHTS
                </a>
                <a href="#" class="mobile-nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    VISA
                </a>
                <a href="#" class="mobile-nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    TOUR PACKAGES
                </a>
                <a href="#" class="mobile-nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    ACTIVITIES
                </a>
                <a href="#" class="mobile-nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    CAR RENTAL
                </a>
                <a href="#" class="mobile-nav-link bg-gradient-to-r from-green-600 to-teal-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    LAST MINUTE
                </a>
            </div> --}}

            <div class="mt-4 p-3 bg-blue-900 rounded-lg">
                <p class="text-center text-yellow-300 font-bold text-sm mb-2">Need Help? Call Now!</p>
                <a href="tel:1800872835"
                    class="block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-center transition">
                    📞 1-800-TRAVEL
                </a>
            </div>
        </div>
    </div>
</nav>

{{-- Floating Call Button --}}
<div class="fixed bottom-4 right-4 z-50">
    <button id="floatingCall"
        class="bg-green-600 hover:bg-green-700 text-white p-3 rounded-full shadow-lg transition transform hover:scale-110 animate-pulse">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
        </svg>
    </button>
</div>

<style>
    .nav-link {
        @apply flex items-center space-x-2 px-4 py-2 rounded-lg transition-all duration-300 hover:bg-blue-700 hover:shadow-lg hover:-translate-y-0.5 text-sm font-semibold whitespace-nowrap;
    }

    .mobile-nav-link {
        @apply flex flex-col items-center justify-center p-3 bg-blue-900 hover:bg-blue-700 rounded-lg transition text-sm font-medium border border-blue-800;
    }

    .dropdown-link {
        @apply flex items-center justify-between px-4 py-3 hover:bg-blue-50 transition text-sm border-b border-gray-100 last:border-b-0;
    }

    [data-tooltip] {
        position: relative;
    }

    [data-tooltip]:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: -40px;
        left: 50%;
        transform: translateX(-50%);
        background: #1e40af;
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 100;
        border: 1px solid #3b82f6;
    }

    [data-tooltip]:hover::before {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        border-width: 4px;
        border-style: solid;
        border-color: transparent transparent #1e40af transparent;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
                mobileMenu.classList.toggle('block');
            });
        }

        // Floating call button
        const floatingCall = document.getElementById('floatingCall');
        if (floatingCall) {
            floatingCall.addEventListener('click', function() {
                alert(
                    'Calling 1-800-TRAVEL...\n\nOur travel experts are available 24/7 to help with your ship ticket booking!'
                );
            });
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (mobileMenu && !mobileMenu.contains(event.target) &&
                mobileMenuButton && !mobileMenuButton.contains(event.target) &&
                !mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('block');
            }
        });

        // Auto-hide marketing banner after 30 seconds
        setTimeout(() => {
            const marketingBanners = document.querySelectorAll('.animate-pulse');
            marketingBanners.forEach(banner => {
                banner.classList.remove('animate-pulse');
            });
        }, 30000);
    });
</script>

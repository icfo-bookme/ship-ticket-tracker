<!DOCTYPE html>
<html data-theme="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Ship Booking Form - BookMe</title>

    <!-- Toastify -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    <!-- DataTables CSS (self-hosted) -->
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/buttons.dataTables.min.css') }}">

    <!-- Flowbite -->
    <link href="https://cdn.jsdelivr.net/npm/flowbite@1.6.5/dist/flowbite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@1.6.5/dist/flowbite.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen max-w-[1480px] mx-auto bg-slate-200">
        <!-- Fixed Header -->
        <div class="fixed bg-[#006172] top-0 left-0 w-full z-50">
            @include('layouts.navigation')
        </div>

        <!-- Content Area -->
        <div class="pt-16 min-h-screen flex">
            <!-- Dark overlay for the mobile sidebar (hidden on lg+) -->
            <div id="sidebar-overlay" class="fixed inset-0 z-[110] bg-black/40 hidden lg:hidden"></div>

            <!-- Sidebar: fixed on desktop, off-canvas overlay on mobile/tablet -->
            <div id="sidebar"
                class="fixed top-16 left-0 z-[120] h-[calc(100vh-4rem)] w-60 transition-all duration-300 ease-in-out -translate-x-full lg:translate-x-0">
                @include('layouts.sidebar')
            </div>

            <!-- Main Content: full width on mobile, offset on large screens -->
            <main id="main-content"
                class="ml-0 lg:ml-60 flex-1 transition-all duration-300 ease-in-out overflow-x-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const mobileToggle = document.getElementById('mobileSidebarToggle');
            const overlay = document.getElementById('sidebar-overlay');
            const lgMedia = window.matchMedia('(min-width: 1024px)');

            if (!sidebar || !mainContent) return;

            // ---------- Desktop collapse / expand ----------
            function applyCollapse(collapsed) {
                if (collapsed) {
                    sidebar.classList.add('lg:w-20');
                    sidebar.classList.remove('lg:w-60');
                    mainContent.classList.add('lg:ml-20');
                    mainContent.classList.remove('lg:ml-60');
                } else {
                    sidebar.classList.add('lg:w-60');
                    sidebar.classList.remove('lg:w-20');
                    mainContent.classList.add('lg:ml-60');
                    mainContent.classList.remove('lg:ml-20');
                }
            }

            // Restore saved state (defaults to expanded)
            applyCollapse(localStorage.getItem('sidebarCollapsed') === 'true');

            // Toggle on desktop OR close on mobile
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    if (lgMedia.matches) {
                        const isCollapsed = sidebar.classList.contains('lg:w-20');
                        applyCollapse(!isCollapsed);
                        localStorage.setItem('sidebarCollapsed', String(!isCollapsed));
                    } else {
                        closeMobileSidebar();
                    }
                });
            }

            // ---------- Mobile off-canvas sidebar ----------
            function openMobileSidebar() {
                document.body.style.overflow = 'hidden'; // prevent background scroll
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                if (overlay) overlay.classList.remove('hidden');
            }

            function closeMobileSidebar() {
                document.body.style.overflow = '';
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                if (overlay) overlay.classList.add('hidden');
            }

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function () {
                    if (sidebar.classList.contains('translate-x-0')) {
                        closeMobileSidebar();
                    } else {
                        openMobileSidebar();
                    }
                });
            }

            if (overlay) {
                overlay.addEventListener('click', closeMobileSidebar);
            }

            // Close mobile menu after clicking any sidebar link
            document.querySelectorAll('#nav-container a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (!lgMedia.matches) closeMobileSidebar();
                });
            });

            // Clear inline body overflow if resized back to desktop
            window.addEventListener('resize', function () {
                if (lgMedia.matches) {
                    document.body.style.overflow = '';
                }
            });
        });
    </script>

    <!-- jQuery (self-hosted) -->
    <script src="{{ asset('vendor/jquery/jquery-3.6.0.min.js') }}"></script>

    <!-- DataTables (self-hosted) -->
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.colVis.min.js') }}"></script>

    <script>
        if (window.jQuery && jQuery.fn && jQuery.fn.dataTable && jQuery.fn.dataTable.defaults) {
            jQuery.extend(true, jQuery.fn.dataTable.defaults, { scrollX: true });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>

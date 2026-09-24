<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="light"
>
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>Ship Booking Form - BookMe</title>

    <!-- Toastify -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"
    >

    <!-- Fonts -->
    <link
        rel="preconnect"
        href="https://fonts.bunny.net"
    >
    <link
        rel="stylesheet"
        href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
    >

    <!-- DataTables -->
    <link
        rel="stylesheet"
        href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}"
    >
    <link
        rel="stylesheet"
        href="{{ asset('vendor/datatables/css/buttons.dataTables.min.css') }}"
    >

    <!-- Flowbite -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/flowbite@1.6.5/dist/flowbite.min.css"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen w-full overflow-x-hidden bg-slate-200">

        <!-- Header -->
        <header
            class="fixed inset-x-0 top-0 z-50 h-16 bg-[#006172]"
        >
            @include('layouts.navigation')
        </header>

        <!-- Mobile Sidebar Backdrop -->
        <div
            id="sidebar-backdrop"
            class="fixed inset-0 top-16 z-30 hidden bg-black/50 backdrop-blur-[1px] lg:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            id="sidebar"
            class="
                fixed bottom-0 left-0 top-16 z-40
                w-60 -translate-x-full
                overflow-x-hidden overflow-y-auto
                bg-white shadow-xl
                transition-all duration-300 ease-in-out
                lg:translate-x-0 lg:shadow-none
            "
        >
            @include('layouts.sidebar')
        </aside>

        <!-- Main Content -->
        <main
            id="main-content"
            class="
                min-h-screen min-w-0
                pt-16
                transition-all duration-300 ease-in-out
                lg:ml-60
            "
        >
            <div
                class="
                    w-full min-w-0
                    p-2
                    sm:p-3
                    md:p-4
                    lg:p-5
                "
            >
                {{ $slot }}
            </div>
        </main>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('vendor/jquery/jquery-3.6.0.min.js') }}"></script>

    <!-- DataTables -->
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.colVis.min.js') }}"></script>

    <!-- Toastify -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Flowbite -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@1.6.5/dist/flowbite.min.js"></script>

    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables Defaults -->
    <script>
        if (
            window.jQuery
            && jQuery.fn
            && jQuery.fn.dataTable
            && jQuery.fn.dataTable.defaults
        ) {
            jQuery.extend(true, jQuery.fn.dataTable.defaults, {
                scrollX: true,
                autoWidth: false,
            });
        }
    </script>

    <!-- Sidebar -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebarBackdrop = document.getElementById('sidebar-backdrop');

            const desktopBreakpoint = 1024;
            const transitionDuration = 300;

            const isDesktop = () => {
                return window.innerWidth >= desktopBreakpoint;
            };

            const adjustDataTables = () => {
                window.setTimeout(() => {
                    if (
                        window.jQuery
                        && jQuery.fn
                        && jQuery.fn.dataTable
                    ) {
                        jQuery.fn.dataTable
                            .tables({
                                visible: true,
                                api: true,
                            })
                            .columns
                            .adjust();
                    }

                    window.dispatchEvent(new Event('resize'));
                }, transitionDuration + 50);
            };

            const collapseSidebar = (saveState = true) => {
                if (!isDesktop()) {
                    return;
                }

                sidebar.classList.remove('w-60');
                sidebar.classList.add('w-20');

                mainContent.classList.remove('lg:ml-60');
                mainContent.classList.add('lg:ml-20');

                if (saveState) {
                    localStorage.setItem(
                        'sidebarCollapsed',
                        'true'
                    );
                }

                adjustDataTables();
            };

            const expandSidebar = (saveState = true) => {
                if (!isDesktop()) {
                    return;
                }

                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-60');

                mainContent.classList.remove('lg:ml-20');
                mainContent.classList.add('lg:ml-60');

                if (saveState) {
                    localStorage.setItem(
                        'sidebarCollapsed',
                        'false'
                    );
                }

                adjustDataTables();
            };

            const openMobileSidebar = () => {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');

                sidebarBackdrop.classList.remove('hidden');

                document.body.classList.add('overflow-hidden');
            };

            const closeMobileSidebar = () => {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');

                sidebarBackdrop.classList.add('hidden');

                document.body.classList.remove('overflow-hidden');
            };

            const restoreSidebarState = () => {
                if (!isDesktop()) {
                    return;
                }

                const isCollapsed = localStorage.getItem(
                    'sidebarCollapsed'
                ) === 'true';

                if (isCollapsed) {
                    collapseSidebar(false);

                    return;
                }

                expandSidebar(false);
            };

            sidebarToggle?.addEventListener('click', () => {
                if (isDesktop()) {
                    const isCollapsed = sidebar.classList.contains(
                        'w-20'
                    );

                    if (isCollapsed) {
                        expandSidebar();

                        return;
                    }

                    collapseSidebar();

                    return;
                }

                const isOpen = sidebar.classList.contains(
                    'translate-x-0'
                );

                if (isOpen) {
                    closeMobileSidebar();

                    return;
                }

                openMobileSidebar();
            });

            sidebarBackdrop?.addEventListener(
                'click',
                closeMobileSidebar
            );

            let resizeTimeout;

            window.addEventListener('resize', () => {
                window.clearTimeout(resizeTimeout);

                resizeTimeout = window.setTimeout(() => {
                    if (isDesktop()) {
                        closeMobileSidebar();
                        restoreSidebarState();

                        return;
                    }

                    mainContent.classList.remove('lg:ml-20');
                    mainContent.classList.add('lg:ml-60');

                    adjustDataTables();
                }, 150);
            });

            restoreSidebarState();
        });
    </script>
</body>
</html>
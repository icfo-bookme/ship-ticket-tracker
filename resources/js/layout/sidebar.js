document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarContainer = document.getElementById('sidebar-container');
        const mainContent = document.getElementById('main-content');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
        const sidebarBackdrop = document.getElementById('sidebar-backdrop');
        const toggleIcon = document.getElementById('toggle-icon');
        const logoText = document.getElementById('sidebar-logo-text');
        const divHide = document.getElementById('divHide');
        const sidebarTexts = document.querySelectorAll('.sidebar-text:not(#sidebar-logo-text)');
        const dropdownContents = document.querySelectorAll('[id$="-dropdown-list"]');
        const dropdownIcons = document.querySelectorAll('[id$="-dropdown-icon"]');
        const navLinks = document.querySelectorAll('#nav-container a[href]');
        let drawerCloseTimeout;

        const desktopBreakpoint = 1024;
        const isDesktop = () => window.innerWidth >= desktopBreakpoint;
        const isCollapsed = isDesktop() && localStorage.getItem('sidebarCollapsed') === 'true';
        let resizeTimeout;

        const adjustDataTables = () => {
            window.setTimeout(() => {
                if (window.jQuery?.fn?.dataTable) {
                    window.jQuery.fn.dataTable
                        .tables({ visible: true, api: true })
                        .columns.adjust();
                }
            }, 350);
        };

        const setDesktopWidth = (collapsed) => {
            sidebar.classList.toggle('w-14', collapsed);
            sidebar.classList.toggle('w-48', !collapsed);
            mainContent.classList.toggle('lg:ml-14', collapsed);
            mainContent.classList.toggle('lg:ml-48', !collapsed);
        };

        const closeMobileSidebar = () => {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            sidebarBackdrop.classList.remove('opacity-100');
            mobileSidebarToggle.setAttribute('aria-expanded', 'false');
            mobileSidebarToggle.setAttribute('aria-label', 'Open navigation menu');
            document.body.classList.remove('overflow-hidden');

            window.clearTimeout(drawerCloseTimeout);
            drawerCloseTimeout = window.setTimeout(() => {
                sidebarBackdrop.classList.add('hidden');
            }, 300);
        };

        const toggleMobileSidebar = () => {
            if (sidebar.classList.contains('translate-x-0')) {
                closeMobileSidebar();
                return;
            }

            window.clearTimeout(drawerCloseTimeout);
            sidebarBackdrop.classList.remove('hidden');
            mobileSidebarToggle.setAttribute('aria-expanded', 'true');
            mobileSidebarToggle.setAttribute('aria-label', 'Close navigation menu');
            document.body.classList.add('overflow-hidden');

            window.requestAnimationFrame(() => {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                sidebarBackdrop.classList.add('opacity-100');
            });

        };

        if (isCollapsed) {
            collapseSidebar();
        } else {
            expandSidebar();
        }
        setDesktopWidth(isCollapsed);

        sidebarToggle.addEventListener('click', function() {
            if (!isDesktop()) {
                toggleMobileSidebar();
                return;
            }

            const isCollapsed = sidebarContainer.classList.contains('sidebar-collapsed');

            if (isCollapsed) {
                expandSidebar();
                setDesktopWidth(false);
                localStorage.setItem('sidebarCollapsed', 'false');
            } else {
                collapseSidebar();
                setDesktopWidth(true);
                localStorage.setItem('sidebarCollapsed', 'true');
            }
            adjustDataTables();
        });

        mobileSidebarToggle.addEventListener('click', toggleMobileSidebar);
        sidebarBackdrop.addEventListener('click', closeMobileSidebar);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !isDesktop()) {
                closeMobileSidebar();
            }
        });

        navLinks.forEach((link) => {
            link.addEventListener('click', () => {
                if (!isDesktop()) {
                    closeMobileSidebar();
                }
            });
        });

        window.addEventListener('resize', function() {
            window.clearTimeout(resizeTimeout);
            resizeTimeout = window.setTimeout(() => {
                if (isDesktop()) {
                    closeMobileSidebar();
                    const shouldCollapse = localStorage.getItem('sidebarCollapsed') === 'true';

                    if (shouldCollapse) {
                        collapseSidebar();
                    } else {
                        expandSidebar();
                    }

                    setDesktopWidth(shouldCollapse);
                } else {
                    expandSidebar();
                    setDesktopWidth(false);
                    mainContent.classList.remove('lg:ml-14', 'lg:ml-48');
                }

                adjustDataTables();
            }, 150);
        });

        function collapseSidebar() {
            sidebarContainer.classList.add('sidebar-collapsed');
            sidebarContainer.classList.remove('sidebar-expanded');
            sidebarContainer.classList.add('w-14');
            sidebarContainer.classList.remove('w-48');
            divHide.classList.remove('w-48');
            divHide.classList.add('w-14');

            // Change icon to double arrow right
            toggleIcon.innerHTML =
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>';

            // Hide logo text
            logoText.classList.add('hidden');

            // Close all dropdowns
            dropdownContents.forEach(dropdown => dropdown.classList.add('hidden'));
            dropdownIcons.forEach(icon => icon.classList.remove('rotate-180'));

            // Remove any existing expanded menus
            document.querySelectorAll('.expanded-menu').forEach(menu => menu.remove());
        }

        function expandSidebar() {
            sidebarContainer.classList.remove('sidebar-collapsed');
            sidebarContainer.classList.add('sidebar-expanded');
            sidebarContainer.classList.remove('w-14');
            sidebarContainer.classList.add('w-48');
            divHide.classList.remove('w-14');
            divHide.classList.add('w-48');

            // Change icon to double arrow left
            toggleIcon.innerHTML =
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>';

            // Show logo text
            logoText.classList.remove('hidden');

            // Remove any existing expanded menus
            document.querySelectorAll('.expanded-menu').forEach(menu => menu.remove());
        }

        // Dropdown toggle functionality - only for expanded sidebar
        document.querySelectorAll('[id$="-dropdown"] button').forEach(button => {
            button.addEventListener('click', function(e) {
                // Only toggle if sidebar is expanded
                if (sidebarContainer.classList.contains('sidebar-expanded')) {
                    e.stopPropagation();
                    const dropdownId = this.parentElement.id;
                    const contentId = `${dropdownId}-list`;
                    const iconId = `${dropdownId}-icon`;

                    const content = document.getElementById(contentId);
                    const icon = document.getElementById(iconId);

                    // Toggle current dropdown
                    content.classList.toggle('hidden');
                    icon.classList.toggle('rotate-180');

                    // Close all other dropdowns
                    document.querySelectorAll('[id$="-dropdown-list"]').forEach(
                        otherContent => {
                            if (otherContent.id !== contentId) {
                                otherContent.classList.add('hidden');
                                const otherIconId = otherContent.id.replace('-list',
                                    '-icon');
                                const otherIcon = document.getElementById(otherIconId);
                                if (otherIcon) otherIcon.classList.remove('rotate-180');
                            }
                        });
                }
            });
        });

        // Close dropdowns when clicking outside - only for expanded sidebar
        document.addEventListener('click', function(e) {
            if (sidebarContainer.classList.contains('sidebar-expanded') &&
                !e.target.closest('[id$="-dropdown"]') &&
                !e.target.closest('[id$="-dropdown-list"]')) {
                dropdownContents.forEach(content => content.classList.add('hidden'));
                dropdownIcons.forEach(icon => icon.classList.remove('rotate-180'));
            }
        });

        // Handle hover effects for collapsed sidebar
        document.querySelectorAll('[id$="-dropdown"]').forEach(item => {
            item.addEventListener('mouseenter', function() {
                if (sidebarContainer.classList.contains('sidebar-collapsed')) {
                    // Remove any existing expanded menus first
                    document.querySelectorAll('.expanded-menu').forEach(menu => menu.remove());

                    // Create expanded menu container
                    const expandedMenu = document.createElement('div');
                    expandedMenu.className = 'expanded-menu';

                    // Get the main item
                    const mainItem = this.querySelector('button');

                    // Create main menu item
                    const mainMenuItem = document.createElement('div');
                    mainMenuItem.className = 'expanded-menu-item font-medium text-gray-700';

                    const mainIcon = mainItem.querySelector('svg').cloneNode(true);
                    mainIcon.className = 'w-5 h-5 flex-shrink-0 text-blue-600';

                    mainMenuItem.appendChild(mainIcon);

                    const mainTextSpan = document.createElement('span');
                    mainTextSpan.className = 'ml-3';
                    mainTextSpan.textContent = mainItem.querySelector('.sidebar-text')
                        ?.textContent || '';
                    mainMenuItem.appendChild(mainTextSpan);

                    expandedMenu.appendChild(mainMenuItem);

                    // Add divider
                    const divider = document.createElement('div');
                    divider.className = 'expanded-menu-divider';
                    expandedMenu.appendChild(divider);

                    // Add dropdown items
                    const dropdownList = this.querySelector('[id$="-dropdown-list"]');
                    if (dropdownList) {
                        const dropdownItems = dropdownList.querySelectorAll('a');
                        dropdownItems.forEach(item => {
                            const clonedItem = item.cloneNode(true);
                            clonedItem.className =
                                'expanded-menu-item text-gray-600 hover:text-blue-600';

                            // Remove any existing classes and add our own
                            const iconSpan = clonedItem.querySelector(
                                'span:first-child');
                            if (iconSpan) {
                                iconSpan.className =
                                    'w-1.5 h-1.5 rounded-full bg-blue-500 mr-3';
                            }

                            const textSpan = clonedItem.querySelector(
                                'span:last-child');
                            if (textSpan) {
                                textSpan.className = 'whitespace-nowrap';
                            }

                            expandedMenu.appendChild(clonedItem);
                        });
                    }

                    // Position the expanded menu
                    const rect = this.getBoundingClientRect();
                    expandedMenu.style.top = `${rect.top}px`;
                    expandedMenu.style.left = `${rect.right}px`;

                    // Add to DOM
                    document.body.appendChild(expandedMenu);

                    // Close when mouse leaves
                    expandedMenu.addEventListener('mouseleave', function() {
                        this.remove();
                    });

                    // Also close when leaving the original item
                    this.addEventListener('mouseleave', function() {
                        setTimeout(() => {
                            if (!expandedMenu.matches(':hover')) {
                                expandedMenu.remove();
                            }
                        }, 100);
                    });
                }
            });
        });

        // Highlight active menu item based on current URL
        function setActiveMenuItem() {
            const currentPath = window.location.pathname;

            navLinks.forEach(link => {
                const linkPath = new URL(link.href, window.location.origin).pathname;
                    if (currentPath === linkPath || (linkPath !== '/' && currentPath.startsWith(
                        `${linkPath}/`))) {
                    link.classList.add('bg-blue-50', 'text-blue-700');
                    const icon = link.querySelector('svg');
                    if (icon) {
                        icon.classList.add('text-blue-700');
                        icon.classList.remove('text-blue-600');
                    }

                    // If this is a dropdown item, open its parent dropdown
                    const dropdownItem = link.closest('[id$="-dropdown-list"]');
                    if (dropdownItem) {
                        const dropdownId = dropdownItem.id.replace('-list', '');
                        const dropdownButton = document.querySelector(`#${dropdownId} button`);
                        if (dropdownButton) {
                            const dropdownIcon = document.getElementById(`${dropdownId}-icon`);
                            dropdownItem.classList.remove('hidden');
                            if (dropdownIcon) dropdownIcon.classList.add('rotate-180');
                        }
                    }
                }
            });
        }

        setActiveMenuItem();
    });

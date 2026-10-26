(function () {
    const config = window.howseyConfig || {};
    const enableChatbot = Boolean(config.enableChatbot);
    const enableScreensaver = Boolean(config.enableScreensaver);
    const enablePwaUi = Boolean(config.enablePwaUi);
    const enablePwaInstallUi = config.enablePwaInstallUi !== undefined ? Boolean(config.enablePwaInstallUi) : enablePwaUi;
    const enablePwaNotificationUi = config.enablePwaNotificationUi !== undefined ? Boolean(config.enablePwaNotificationUi) : (enablePwaUi && Boolean(config.authenticated));
    const userRole = config.userRole || 'guest';
    const currentRoute = config.currentRoute || '';
    const isAuthenticated = Boolean(config.authenticated);
    const chatbotEndpoint = config.chatbotEndpoint || '/bot/message';
    const loginRoute = config.loginRoute || '/login';
    const serviceWorkerUrl = config.serviceWorkerUrl || '/service-worker.js';
    const pushRoutes = config.pushRoutes || {};
    const shortcutRoutes = config.shortcutRoutes || {};

    let driverLoaderPromise = null;
    let deferredInstallPrompt = null;
    let chatbotInitialized = false;
    let pushPublicKeyPromise = null;
    let pendingShortcutPrefix = false;
    let pendingShortcutPrefixTimer = null;
    let lastShortcutHandledAt = 0;

    function ensureDriverAssetsLoaded() {
        if (window.driver && window.driver.js && typeof window.driver.js.driver === 'function') {
            return Promise.resolve();
        }

        if (driverLoaderPromise) {
            return driverLoaderPromise;
        }

        driverLoaderPromise = new Promise((resolve, reject) => {
            const existingCss = document.getElementById('driver-js-styles');
            if (!existingCss) {
                const css = document.createElement('link');
                css.id = 'driver-js-styles';
                css.rel = 'stylesheet';
                css.href = 'https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css';
                document.head.appendChild(css);
            }

            const existingScript = document.getElementById('driver-js-script');
            if (existingScript) {
                existingScript.addEventListener('load', () => resolve(), { once: true });
                existingScript.addEventListener('error', () => reject(new Error('Unable to load tour assets.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.id = 'driver-js-script';
            script.src = 'https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Unable to load tour assets.'));
            document.body.appendChild(script);
        });

        return driverLoaderPromise;
    }

    function runWhenIdle(callback, timeout = 1200) {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(callback, { timeout });
            return;
        }

        window.setTimeout(callback, 1);
    }

    function isTypingTarget(target) {
        if (!(target instanceof HTMLElement)) {
            return false;
        }

        const tagName = target.tagName;
        return target.isContentEditable ||
            tagName === 'INPUT' ||
            tagName === 'TEXTAREA' ||
            tagName === 'SELECT' ||
            Boolean(target.closest('[contenteditable="true"]'));
    }

    function findVisibleSearchInput() {
        const selectors = [
            'input[type="search"]',
            'input[id$="Search"]',
            'input[placeholder*="Search"]',
            'input[placeholder*="search"]',
        ];

        for (const selector of selectors) {
            const input = document.querySelector(selector);
            if (!(input instanceof HTMLInputElement)) {
                continue;
            }

            const hiddenByLayout = input.offsetParent === null && input !== document.activeElement;
            if (input.disabled || input.readOnly || hiddenByLayout) {
                continue;
            }

            return input;
        }

        return null;
    }

    function focusSearchInput() {
        const input = findVisibleSearchInput();
        if (!input) {
            return false;
        }

        input.focus();
        if (typeof input.select === 'function') {
            input.select();
        }

        return true;
    }

    function getActiveModal() {
        const visibleModals = Array.from(document.querySelectorAll('.modal.show'));
        return visibleModals.length ? visibleModals[visibleModals.length - 1] : null;
    }

    function closeActiveUiLayer() {
        const activeModal = getActiveModal();
        if (activeModal) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modalInstance = bootstrap.Modal.getInstance(activeModal) || new bootstrap.Modal(activeModal);
                modalInstance.hide();
            } else {
                activeModal.classList.remove('show');
                activeModal.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                    backdrop.remove();
                });
            }

            return true;
        }

        const sidebar = document.getElementById('sidebarMenu');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            return true;
        }

        return false;
    }

    function navigateWithShortcut(url) {
        if (!url || window.location.href === url) {
            return false;
        }

        if (window.Loader) {
            window.Loader.show();
        }

        window.location.href = url;
        return true;
    }

    function submitLogoutShortcut() {
        const logoutForm = document.getElementById('keyboard-logout-form') ||
            document.querySelector('form[action$="/logout"]');

        if (!logoutForm) {
            return false;
        }

        if (window.Loader) {
            window.Loader.show();
        }

        if (typeof logoutForm.requestSubmit === 'function') {
            logoutForm.requestSubmit();
            return true;
        }

        logoutForm.submit();
        return true;
    }

    function clearShortcutPrefix() {
        pendingShortcutPrefix = false;

        if (pendingShortcutPrefixTimer) {
            window.clearTimeout(pendingShortcutPrefixTimer);
            pendingShortcutPrefixTimer = null;
        }
    }

    function armShortcutPrefix() {
        pendingShortcutPrefix = true;

        if (pendingShortcutPrefixTimer) {
            window.clearTimeout(pendingShortcutPrefixTimer);
        }

        pendingShortcutPrefixTimer = window.setTimeout(function () {
            clearShortcutPrefix();
        }, 1200);
    }

    function shouldSkipShortcutEvent(event) {
        return event.defaultPrevented || event.repeat || isTypingTarget(event.target);
    }

    function wasRecentlyHandled() {
        return Date.now() - lastShortcutHandledAt < 150;
    }

    function markShortcutHandled() {
        lastShortcutHandledAt = Date.now();
    }

    function handlePrefixedShortcut(key) {
        const normalizedKey = String(key || '').toLowerCase();
        let handled = false;

        switch (normalizedKey) {
            case 'h':
                handled = navigateWithShortcut(shortcutRoutes.home);
                break;
            case 'd':
                handled = navigateWithShortcut(shortcutRoutes.dashboard);
                break;
            case 'a':
                handled = navigateWithShortcut(shortcutRoutes.analytics);
                break;
            case 'b':
                handled = navigateWithShortcut(shortcutRoutes.buildings);
                break;
            case 't':
                handled = navigateWithShortcut(shortcutRoutes.tenants);
                break;
            case 'r':
                handled = navigateWithShortcut(shortcutRoutes.rentals);
                break;
            case 'p':
                handled = navigateWithShortcut(shortcutRoutes.payments);
                break;
            case 'm':
                handled = navigateWithShortcut(shortcutRoutes.maintenance);
                break;
            case 'c':
                handled = navigateWithShortcut(shortcutRoutes.accounting);
                break;
            case 'l':
                handled = submitLogoutShortcut();
                break;
            case '/':
            case '?':
                showShortcutHelp();
                handled = true;
            default:
                break;
        }

        clearShortcutPrefix();

        return handled;
    }

    function handleGlobalShortcut(event) {
        if (event.defaultPrevented || event.repeat) {
            return;
        }

        if (pendingShortcutPrefix) {
            if (!event.altKey && !event.ctrlKey && !event.metaKey && !isTypingTarget(event.target)) {
                if (handlePrefixedShortcut(event.key)) {
                    markShortcutHandled();
                    event.preventDefault();
                    event.stopPropagation();
                }
                return;
            }

            clearShortcutPrefix();
        }

        if (event.key === 'Escape') {
            clearShortcutPrefix();
            if (closeActiveUiLayer()) {
                markShortcutHandled();
                event.preventDefault();
                event.stopPropagation();
            }
            return;
        }

        if (!event.altKey && !event.shiftKey && !event.ctrlKey && !event.metaKey && event.key === '/' && !isTypingTarget(event.target)) {
            if (focusSearchInput()) {
                markShortcutHandled();
                event.preventDefault();
                event.stopPropagation();
            }
            return;
        }

        if (!event.altKey && !event.shiftKey && !event.ctrlKey && !event.metaKey && !isTypingTarget(event.target) && String(event.key || '').toLowerCase() === 'g') {
            armShortcutPrefix();
            markShortcutHandled();
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        if (!event.altKey || !event.shiftKey || event.ctrlKey || event.metaKey || isTypingTarget(event.target)) {
            return;
        }

        let handled = false;
        const key = String(event.key || '').toLowerCase();
        const code = String(event.code || '');

        switch (code) {
            case 'KeyH':
                handled = navigateWithShortcut(shortcutRoutes.home);
                break;
            case 'KeyD':
                handled = navigateWithShortcut(shortcutRoutes.dashboard);
                break;
            case 'KeyA':
                handled = navigateWithShortcut(shortcutRoutes.analytics);
                break;
            case 'KeyB':
                handled = navigateWithShortcut(shortcutRoutes.buildings);
                break;
            case 'KeyT':
                handled = navigateWithShortcut(shortcutRoutes.tenants);
                break;
            case 'KeyR':
                handled = navigateWithShortcut(shortcutRoutes.rentals);
                break;
            case 'KeyP':
                handled = navigateWithShortcut(shortcutRoutes.payments);
                break;
            case 'KeyM':
                handled = navigateWithShortcut(shortcutRoutes.maintenance);
                break;
            case 'KeyC':
                handled = navigateWithShortcut(shortcutRoutes.accounting);
                break;
            case 'KeyL':
                handled = submitLogoutShortcut();
                break;
            case 'Slash':
                showShortcutHelp();
                handled = true;
                break;
            default:
                break;
        }

        if (!handled) {
            switch (key) {
                case 'h':
                    handled = navigateWithShortcut(shortcutRoutes.home);
                    break;
                case 'd':
                    handled = navigateWithShortcut(shortcutRoutes.dashboard);
                    break;
                case 'a':
                    handled = navigateWithShortcut(shortcutRoutes.analytics);
                    break;
                case 'b':
                    handled = navigateWithShortcut(shortcutRoutes.buildings);
                    break;
                case 't':
                    handled = navigateWithShortcut(shortcutRoutes.tenants);
                    break;
                case 'r':
                    handled = navigateWithShortcut(shortcutRoutes.rentals);
                    break;
                case 'p':
                    handled = navigateWithShortcut(shortcutRoutes.payments);
                    break;
                case 'm':
                    handled = navigateWithShortcut(shortcutRoutes.maintenance);
                    break;
                case 'c':
                    handled = navigateWithShortcut(shortcutRoutes.accounting);
                    break;
                case 'l':
                    handled = submitLogoutShortcut();
                    break;
                case '?':
                case '/':
                    showShortcutHelp();
                    handled = true;
                    break;
                default:
                    break;
            }
        }

        if (handled) {
            markShortcutHandled();
            event.preventDefault();
            event.stopPropagation();
        }
    }

    function showShortcutHelp() {
        const lines = ['Keyboard shortcuts'];
        const shortcutEntries = [
            ['Alt + Shift + H or G then H', 'Home', shortcutRoutes.home],
            ['Alt + Shift + D or G then D', 'Dashboard', shortcutRoutes.dashboard],
            ['Alt + Shift + A or G then A', 'Analytics', shortcutRoutes.analytics],
            ['Alt + Shift + B or G then B', 'Buildings', shortcutRoutes.buildings],
            ['Alt + Shift + T or G then T', 'Tenants', shortcutRoutes.tenants],
            ['Alt + Shift + R or G then R', 'Rentals', shortcutRoutes.rentals],
            ['Alt + Shift + P or G then P', 'Payments', shortcutRoutes.payments],
            ['Alt + Shift + M or G then M', 'Maintenance', shortcutRoutes.maintenance],
            ['Alt + Shift + C or G then C', 'Accounting', shortcutRoutes.accounting],
            ['Alt + Shift + L or G then L', 'Logout', true],
        ];

        shortcutEntries.forEach(function (entry) {
            if (entry[2]) {
                lines.push(entry[0] + ' - ' + entry[1]);
            }
        });

        lines.push('/ - Focus search');
        lines.push('Esc - Close modal or sidebar');
        lines.push('Alt + Shift + ? or G then / - Show this help');

        window.alert(lines.join('\n'));
    }

    function isStandaloneDisplayMode() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function canUsePwaShell() {
        return enablePwaUi && (enablePwaInstallUi || enablePwaNotificationUi);
    }

    function supportsWebPush() {
        return enablePwaNotificationUi &&
            isAuthenticated &&
            window.isSecureContext &&
            'serviceWorker' in navigator &&
            'Notification' in window &&
            'PushManager' in window;
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const normalized = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(normalized);
        const outputArray = new Uint8Array(rawData.length);

        for (let index = 0; index < rawData.length; ++index) {
            outputArray[index] = rawData.charCodeAt(index);
        }

        return outputArray;
    }

    async function fetchPushServerConfig() {
        if (!supportsWebPush() || !pushRoutes.publicKey) {
            return { supported: false, publicKey: null };
        }

        if (pushPublicKeyPromise) {
            return pushPublicKeyPromise;
        }

        pushPublicKeyPromise = fetch(pushRoutes.publicKey, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Unable to load Web Push configuration.');
            }

            return response.json();
        }).catch(function () {
            return { supported: false, publicKey: null };
        });

        return pushPublicKeyPromise;
    }

    async function persistPushSubscription(subscription) {
        if (!pushRoutes.subscribe) {
            return false;
        }

        const response = await fetch(pushRoutes.subscribe, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(subscription.toJSON()),
        });

        return response.ok;
    }

    async function removeStoredPushSubscription(subscription) {
        if (!pushRoutes.unsubscribe || !subscription) {
            return false;
        }

        const response = await fetch(pushRoutes.unsubscribe, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ endpoint: subscription.endpoint }),
        });

        return response.ok;
    }

    async function ensurePushSubscription(options) {
        const requestPermission = Boolean(options && options.requestPermission);

        if (!supportsWebPush()) {
            return false;
        }

        const pushConfig = await fetchPushServerConfig();
        if (!pushConfig.supported || !pushConfig.publicKey) {
            return false;
        }

        let permission = Notification.permission;

        if (requestPermission && permission === 'default') {
            permission = await Notification.requestPermission();
        }

        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (permission !== 'granted') {
            if (permission === 'denied' && subscription) {
                await removeStoredPushSubscription(subscription);
                await subscription.unsubscribe().catch(function () {});
            }

            return false;
        }

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(pushConfig.publicKey),
            });
        }

        return persistPushSubscription(subscription);
    }

    async function syncExistingPushSubscription() {
        if (!supportsWebPush() || Notification.permission !== 'granted') {
            return false;
        }

        try {
            return await ensurePushSubscription({ requestPermission: false });
        } catch (error) {
            console.warn('Push subscription sync failed', error);
            return false;
        }
    }

    async function updatePushOptInButton() {
        const button = document.getElementById('pwa-notification-button');
        if (!button) {
            return;
        }

        if (!supportsWebPush() || !isStandaloneDisplayMode()) {
            button.style.display = 'none';
            return;
        }

        const pushConfig = await fetchPushServerConfig();

        if (!pushConfig.supported || !pushConfig.publicKey || Notification.permission !== 'default') {
            button.style.display = 'none';
            return;
        }

        button.style.display = 'flex';
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const methodOverride = form.querySelector('input[name="_method"]');
        const formMethod = (methodOverride ? methodOverride.value : form.method || 'GET').toUpperCase();

        // Preserve Laravel as the source of truth by requiring connectivity for writes.
        if (formMethod !== 'GET' && !navigator.onLine) {
            event.preventDefault();
            window.alert('You are offline. This action was not submitted. Reconnect to continue.');
            return;
        }

        const inlineSubmitHandler = form.getAttribute('onsubmit') || '';

        if (formMethod !== 'DELETE' || inlineSubmitHandler.toLowerCase().includes('confirm(')) {
            return;
        }

        const confirmMessage = form.dataset.confirmMessage || 'Are you sure you want to delete this item?';
        if (!window.confirm(confirmMessage)) {
            event.preventDefault();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const themeToggleBtn = document.getElementById('mobileThemeToggle');
        const themes = ['light', 'dark'];
        let currentTheme;
        let themeRefreshTimer = null;

        function updateThemeButtonIcon(theme) {
            if (!themeToggleBtn) {
                return;
            }
            if (theme === 'dark') {
                themeToggleBtn.innerHTML = '<i class="bi bi-moon-stars"></i>';
            } else {
                themeToggleBtn.innerHTML = '<i class="bi bi-brightness-high"></i>';
            }
        }

        function applyTheme(theme) {
            themes.forEach(function (value) {
                body.classList.remove('theme-' + value);
            });
            body.classList.add('theme-' + theme);
            currentTheme = theme;
            updateThemeButtonIcon(theme);
        }

        function refreshThemeFromCurrentTime() {
            applyTheme(getThemeForHour(new Date().getHours()));
        }

        function getThemeForHour(hour) {
            // Light through the day, dark overnight.
            if (hour >= 6 && hour < 18) {
                return 'light';
            }
            return 'dark';
        }

        function scheduleNextThemeChange() {
            const now = new Date();
            const hour = now.getHours();

            let nextChangeHour;
            if (hour < 6) {
                nextChangeHour = 6;
            } else if (hour < 18) {
                nextChangeHour = 18;
            } else {
                nextChangeHour = 30;
            }

            const nextChange = new Date(now.getTime());
            nextChange.setHours(nextChangeHour, 0, 0, 0);

            const delay = nextChange.getTime() - now.getTime();
            clearTimeout(themeRefreshTimer);
            themeRefreshTimer = setTimeout(function () {
                refreshThemeFromCurrentTime();
                scheduleNextThemeChange();
            }, delay);
        }

        refreshThemeFromCurrentTime();

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function () {
                const index = themes.indexOf(currentTheme);
                const nextIndex = (index + 1) % themes.length;
                applyTheme(themes[nextIndex]);
            });
        }

        ['visibilitychange', 'pageshow', 'focus'].forEach(function (eventName) {
            window.addEventListener(eventName, function () {
                if (eventName === 'visibilitychange' && document.visibilityState !== 'visible') {
                    return;
                }

                refreshThemeFromCurrentTime();
                scheduleNextThemeChange();
            });
        });

        runWhenIdle(function () {
            scheduleNextThemeChange();

            let lastTouchEnd = 0;
            document.addEventListener('touchend', function (event) {
                const interactiveTarget = event.target instanceof Element
                    ? event.target.closest('a, button, input, select, textarea, label, [role="button"], [data-bs-toggle]')
                    : null;
                const now = Date.now();
                if (interactiveTarget) {
                    lastTouchEnd = now;
                    return;
                }
                if (now - lastTouchEnd <= 300) {
                    event.preventDefault();
                }
                lastTouchEnd = now;
            }, false);
        });
    });

    window.addEventListener('keydown', function (event) {
        if (wasRecentlyHandled() || shouldSkipShortcutEvent(event)) {
            return;
        }

        handleGlobalShortcut(event);
    }, true);

    document.addEventListener('keydown', function (event) {
        if (wasRecentlyHandled() || shouldSkipShortcutEvent(event)) {
            return;
        }

        handleGlobalShortcut(event);
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        window.startTour = async function () {
            try {
                await ensureDriverAssetsLoaded();
            } catch (error) {
                console.error(error);
                return;
            }

            const driver = window.driver.js.driver;
            const isMobile = window.matchMedia('(max-width: 768px)').matches;

            let steps = [];

            const commonSteps = isMobile
                ? [
                    { element: '.mobile-header', popover: { title: 'Header', description: 'Open the sidebar using the menu button.', side: 'bottom' } },
                    { element: '#sidebarToggleBtn', popover: { title: 'Menu', description: 'Tap to open app navigation.', side: 'bottom' } },
                    { element: '#announcement-badge-mobile', popover: { title: 'Announcements', description: 'See new notices for your properties or tenancy.', side: 'left' } },
                    { element: '#chatbot-toggle', popover: { title: 'AI Assistant', description: 'Chat with the HOWSEY assistant for guidance.', side: 'top' } },
                    {
                        element: '.sidebar-user',
                        popover: { title: 'Profile', description: 'View your profile info here.', side: 'top' },
                        onHighlightStarted: function () {
                            const sidebar = document.getElementById('sidebarMenu');
                            const overlay = document.getElementById('sidebarOverlay');
                            if (sidebar && !sidebar.classList.contains('show')) {
                                sidebar.classList.add('show');
                                overlay.classList.add('show');
                            }
                        },
                    },
                ]
                : [
                    { element: '#sidebarMenu', popover: { title: 'Navigation', description: 'Use this sidebar to access different sections of the application.', side: 'right' } },
                    { element: '#announcement-badge-desktop', popover: { title: 'Announcements', description: 'Unread announcements for your role are highlighted here.', side: 'bottom' } },
                    { element: '#chatbot-toggle', popover: { title: 'AI Assistant', description: 'Open the HOWSEY assistant to ask questions anytime.', side: 'top' } },
                    { element: '.sidebar-user', popover: { title: 'User Profile', description: 'View your profile info here.', side: 'right' } },
                ];

            const pageSteps = {
                'buildings.index': [
                    { element: '#buildings-header', popover: { title: 'Building Management', description: 'Manage your property portfolio here.', side: 'bottom' } },
                    { element: '#add-building-btn', popover: { title: 'Add New Property', description: 'Click here to register a new building in the system.', side: 'left' } },
                    { element: '#buildingSearch', popover: { title: 'Quick Search', description: 'Find specific buildings by name or address.', side: 'bottom' } },
                    { element: '#buildingsTable', popover: { title: 'Building List', description: 'View details, occupancy rates, and manage individual buildings.', side: 'top' } },
                ],
                'tenants.index': [
                    { element: '#tenants-header', popover: { title: 'Tenant Registry', description: 'Complete list of all registered tenants.', side: 'bottom' } },
                    { element: '#add-tenant-btn', popover: { title: 'Onboard Tenant', description: 'Register a new tenant and assign them to a unit.', side: 'left' } },
                    { element: '#tenantSearch', popover: { title: 'Find Tenants', description: 'Search by name, email, or phone number.', side: 'bottom' } },
                    { element: '#tenantsTable', popover: { title: 'Tenant Directory', description: 'View status, contact info, and manage tenant profiles.', side: 'top' } },
                ],
                'rents.index': [
                    { element: '#rents-header', popover: { title: 'Lease Management', description: 'Track active and past rental agreements.', side: 'bottom' } },
                    { element: '#add-rent-btn', popover: { title: 'New Lease', description: 'Create a new rental agreement for a tenant.', side: 'left' } },
                    { element: '#rentSearch', popover: { title: 'Search Agreements', description: 'Find leases by tenant name or unit.', side: 'bottom' } },
                    { element: '#rentsTable', popover: { title: 'Agreements List', description: 'Monitor lease terms, amounts, and statuses.', side: 'top' } },
                ],
                'maintenance.index': [
                    { element: '#maintenance-header', popover: { title: 'Maintenance Hub', description: 'Track and manage property repairs and requests.', side: 'bottom' } },
                    { element: '#add-maintenance-btn', popover: { title: 'Log Request', description: 'Manually record a new maintenance issue.', side: 'left' } },
                    { element: '#maintenanceSearch', popover: { title: 'Filter Requests', description: 'Search by issue type, priority, or unit.', side: 'bottom' } },
                    { element: '#maintenanceTable', popover: { title: 'Request Log', description: 'View status, priority, and take action on tickets.', side: 'top' } },
                ],
                'payments.index': [
                    { element: '#payments-header', popover: { title: 'Financial Records', description: 'Comprehensive log of all rent payments.', side: 'bottom' } },
                    { element: '#add-payment-btn', popover: { title: 'Record Transaction', description: 'Manually log a rent payment.', side: 'left' } },
                    { element: '#paymentSearch', popover: { title: 'Search Transactions', description: 'Find payments by tenant or date.', side: 'bottom' } },
                    { element: '#paymentsTable', popover: { title: 'Payment History', description: 'Review amounts, methods, and payment statuses.', side: 'top' } },
                ],
                'dashboard.analytics': [
                    { element: '#analytics-header', popover: { title: 'Analytics Workspace', description: 'This admin-only page expands the dashboard graph into a full analytics workspace.', side: 'bottom' } },
                    { element: '#analytics-summary-cards', popover: { title: 'Summary Cards', description: 'These cards summarize the same recent analysis window used by the dashboard revenue view.', side: 'bottom' } },
                    { element: '#analytics-notes', popover: { title: 'Analyst Notes', description: 'These insight notes update from the current chart data rather than staying fixed.', side: 'bottom' } },
                    { element: '#analytics-building-revenue', popover: { title: 'Building Revenue', description: 'Compare how each building contributes to collections.', side: 'top' } },
                    { element: '#analytics-payment-methods', popover: { title: 'Payment Methods', description: 'Review which payment channels are used most often and by value.', side: 'top' } },
                    { element: '#analytics-income-expenses', popover: { title: 'Income Vs Expenses', description: 'Track the relationship between incoming funds and recorded expenses over time.', side: 'top' } },
                ],
            };

            if (pageSteps[currentRoute]) {
                steps = pageSteps[currentRoute].concat(commonSteps);
            } else if (currentRoute === 'dashboard') {
                if (userRole === 'admin') {
                    steps = isMobile
                        ? [
                            { element: '#stats-cards', popover: { title: 'Quick Stats', description: 'Overview of properties, tenants, and revenue.', side: 'bottom' } },
                            { element: '#revenue-section', popover: { title: 'Revenue', description: 'Monthly performance at a glance. As admin, the header opens the full analytics page.', side: 'top' } },
                            { element: '#recent-payments-section', popover: { title: 'Recent Payments', description: 'Latest transactions.', side: 'bottom' } },
                            { element: '#overdue-rents-section', popover: { title: 'Overdue', description: 'Accounts needing attention.', side: 'bottom' } },
                        ].concat(commonSteps)
                        : [
                            { element: '#dashboard-title', popover: { title: 'Admin Dashboard', description: 'Central control for administration.', side: 'bottom' } },
                            { element: '#stats-cards', popover: { title: 'Quick Stats', description: 'Real-time overview.', side: 'bottom' } },
                            { element: '#revenue-section', popover: { title: 'Revenue Tracking', description: 'Monitor income performance. Click the header to open the full analytics workspace.', side: 'top' } },
                            { element: '#recent-payments-section', popover: { title: 'Recent Activity', description: 'Latest payments.', side: 'right' } },
                            { element: '#overdue-rents-section', popover: { title: 'Attention Needed', description: 'Track overdue accounts.', side: 'left' } },
                        ].concat(commonSteps);
                } else if (userRole === 'manager') {
                    steps = isMobile
                        ? [
                            { element: '#stats-cards', popover: { title: 'Overview', description: 'Status of units and tenants.', side: 'bottom' } },
                            { element: '#revenue-section', popover: { title: 'Revenue View', description: 'Managers can review the chart here, but the analytics page itself is reserved for admin.', side: 'bottom' } },
                            { element: '#overdue-rents-section', popover: { title: 'Collections', description: 'Focus on overdue accounts.', side: 'bottom' } },
                            { element: '#manager-announcements-section', popover: { title: 'Announcements', description: 'Updates for the buildings you manage.', side: 'bottom' } },
                        ].concat(commonSteps)
                        : [
                            { element: '#dashboard-title', popover: { title: 'Manager Dashboard', description: 'Manage operations.', side: 'bottom' } },
                            { element: '#stats-cards', popover: { title: 'Overview', description: 'Units and tenants.', side: 'bottom' } },
                            { element: '#revenue-section', popover: { title: 'Revenue View', description: 'The chart remains visible here, while full analytics access is limited to admin.', side: 'left' } },
                            { element: '#overdue-rents-section', popover: { title: 'Collections', description: 'Overdue accounts.', side: 'left' } },
                            { element: '#manager-announcements-section', popover: { title: 'Announcements', description: 'Building communications from admins and managers.', side: 'left' } },
                        ].concat(commonSteps);
                } else if (userRole === 'tenant') {
                    steps = isMobile
                        ? [
                            { element: '#lease-card', popover: { title: 'Lease', description: 'Rent amount and due dates.', side: 'bottom' } },
                            { element: '#payment-history', popover: { title: 'Payments', description: 'Past payments and receipts.', side: 'bottom' } },
                            { element: '#announcements', popover: { title: 'Announcements', description: 'Building messages from your landlord or manager.', side: 'bottom' } },
                        ].concat(commonSteps)
                        : [
                            { element: '#dashboard-title', popover: { title: 'Tenant Portal', description: 'Your personal dashboard.', side: 'bottom' } },
                            { element: '#lease-card', popover: { title: 'Lease Details', description: 'Rent and unit info.', side: 'right' } },
                            { element: '#payment-history', popover: { title: 'Payment History', description: 'Receipts and records.', side: 'left' } },
                            { element: '#announcements', popover: { title: 'Announcements', description: 'Important updates for your tenancy.', side: 'bottom' } },
                        ].concat(commonSteps);
                }
            }

            const activeSteps = steps.filter(function (step) {
                return document.querySelector(step.element);
            });

            if (activeSteps.length > 0) {
                const tour = driver({
                    showProgress: true,
                    steps: activeSteps,
                    onDestroyed: function () {
                        if (currentRoute === 'dashboard' && !localStorage.getItem('tour_seen')) {
                            localStorage.setItem('tour_seen', 'true');
                        }
                    },
                });
                tour.drive();
            }
        };

        if (window.location.pathname.endsWith('dashboard') && !localStorage.getItem('tour_seen') && isAuthenticated) {
            setTimeout(function () {
                window.startTour();
            }, 1000);
        }
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebarMenu');
        const overlay = document.getElementById('sidebarOverlay');
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }

    function setupTableSearch(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);

        if (!input || !table) {
            return;
        }

        input.addEventListener('keyup', function () {
            const filter = input.value.toLowerCase();
            const tbody = table.querySelector('tbody');
            if (!tbody) {
                return;
            }

            const dataRows = tbody.getElementsByTagName('tr');
            let visibleCount = 0;
            let noResultsRow = tbody.querySelector('.no-results-search-row');

            for (let index = 0; index < dataRows.length; index++) {
                const row = dataRows[index];
                if (row.classList.contains('no-results-search-row')) {
                    continue;
                }

                const text = row.textContent || row.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            if (visibleCount === 0 && filter !== '') {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-search-row';
                    const cell = document.createElement('td');

                    let colCount = 0;
                    const headerRow = table.querySelector('thead tr');
                    if (headerRow) {
                        colCount = headerRow.cells.length;
                    } else {
                        colCount = 5;
                    }

                    cell.colSpan = colCount;
                    cell.className = 'text-center py-4 text-muted';
                    cell.innerHTML = '<i class="bi bi-search fs-3 d-block mb-2"></i>No matching records found';
                    noResultsRow.appendChild(cell);
                    tbody.appendChild(noResultsRow);
                } else {
                    noResultsRow.style.display = '';
                }
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        });
    }

    class PremiumLoader {
        constructor() {
            if (PremiumLoader.instance) {
                return PremiumLoader.instance;
            }

            this.overlay = document.getElementById('howsey-loader-overlay');
            this.isVisible = false;
            this.isPending = false;
            this.isBooting = true;
            this.startTime = 0;
            this.minDisplayTime = 350;
            this.delay = 120;
            this.timer = null;
            PremiumLoader.instance = this;

            if (this.overlay) {
                this.overlay.classList.add('booting');
                this.overlay.classList.add('visible');
                this.overlay.setAttribute('aria-busy', 'true');
                this.isVisible = true;
                this.startTime = Date.now();
            }
        }

        show() {
            if (!this.overlay || this.isBooting || this.isVisible || this.isPending) {
                return;
            }
            this.isPending = true;
            this.timer = setTimeout(() => {
                this.isPending = false;
                this.startTime = Date.now();
                this.overlay.classList.add('visible');
                this.overlay.setAttribute('aria-busy', 'true');
                this.isVisible = true;
            }, this.delay);
        }

        async hide() {
            if (!this.overlay) {
                return;
            }

            if (this.isPending) {
                clearTimeout(this.timer);
                this.isPending = false;
                return;
            }

            if (!this.isVisible) {
                return;
            }

            const elapsed = Date.now() - this.startTime;
            const remaining = Math.max(0, this.isBooting ? 0 : this.minDisplayTime - elapsed);

            if (remaining > 0) {
                await new Promise((resolve) => setTimeout(resolve, remaining));
            }

            this.overlay.style.opacity = '0';

            await new Promise((resolve) => {
                setTimeout(() => {
                    this.overlay.classList.remove('booting');
                    this.overlay.classList.remove('visible');
                    this.overlay.style.opacity = '';
                    this.overlay.setAttribute('aria-busy', 'false');
                    this.isVisible = false;
                    this.isBooting = false;
                    resolve();
                }, 200);
            });
        }

        async complete() {
            await this.hide();
        }

        async error() {
            await this.hide();
        }
    }

    window.Loader = new PremiumLoader();
    window.toggleSidebar = toggleSidebar;
    window.setupTableSearch = setupTableSearch;

    document.addEventListener('DOMContentLoaded', function () {
        function dismissBlockingOverlays() {
            const sidebar = document.getElementById('sidebarMenu');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const screensaver = document.getElementById('screensaver');

            if (window.Loader) {
                window.Loader.hide();
            }

            if (sidebar) {
                sidebar.classList.remove('show');
            }

            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('show');
            }

            if (screensaver && screensaver.style.display === 'flex') {
                screensaver.style.display = 'none';
            }
        }

        document.addEventListener('show.bs.modal', function () {
            dismissBlockingOverlays();
        });

        document.addEventListener('shown.bs.modal', function (event) {
            dismissBlockingOverlays();

            const focusTarget = event.target.querySelector('textarea, input, select, button, a[href]');
            if (focusTarget && typeof focusTarget.focus === 'function') {
                window.setTimeout(function () {
                    focusTarget.focus();
                }, 50);
            }
        });

        runWhenIdle(function () {
            document.addEventListener('click', function (event) {
                const link = event.target.closest('a');
                if (!link) {
                    return;
                }

                const href = link.getAttribute('href');
                const target = link.getAttribute('target');
                const isDownloadLink = link.hasAttribute('download');
                const isSamePageAnchor = href.includes('#') && href.split('#')[0] === window.location.href.split('#')[0];
                const hasBypass = link.hasAttribute('data-loader-ignore');

                if (!href ||
                    href === '#' ||
                    href.startsWith('javascript:') ||
                    isDownloadLink ||
                    hasBypass ||
                    target === '_blank' ||
                    event.ctrlKey || event.metaKey || event.shiftKey ||
                    isSamePageAnchor) {
                    return;
                }

                window.Loader.show();

                // File downloads do not trigger a normal page navigation lifecycle,
                // so hide the loader if the page remains visible after the click.
                window.setTimeout(function () {
                    if (document.visibilityState === 'visible') {
                        window.Loader.hide();
                    }
                }, 1200);
            });

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (event.defaultPrevented || form.target === '_blank' || form.hasAttribute('data-loader-ignore')) {
                    return;
                }

                if (!form.checkValidity()) {
                    return;
                }

                window.Loader.show();
            });

            (function startLiveWatermark() {
                const target = document.querySelector('.role-watermarked[data-watermark]');

                if (!target) {
                    return;
                }

                const pattern = /(\d{4}-\d{2}-\d{2} \d{2}:\d{2})$/;

                const stamp = function () {
                    const parts = new Intl.DateTimeFormat('en-GB', {
                        timeZone: 'Africa/Lagos',
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        hourCycle: 'h23'
                    }).formatToParts(new Date());

                    const value = function (type) {
                        const part = parts.find(function (item) {
                            return item.type === type;
                        });

                        return part ? part.value : '';
                    };

                    return value('year') + '-' + value('month') + '-' + value('day') + ' ' + value('hour') + ':' + value('minute');
                };

                const tile = function (text) {
                    const escaped = text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');

                    const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="960" height="220" viewBox="0 0 960 220">'
                        + '<text x="24" y="138" fill="#000" fill-opacity="0.14" font-family="Arial, Helvetica, sans-serif" font-size="20" font-weight="800">'
                        + escaped
                        + '</text></svg>';

                    return "url('data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg) + "')";
                };

                const refresh = function () {
                    const current = target.getAttribute('data-watermark') || '';

                    if (!pattern.test(current)) {
                        return;
                    }

                    const next = current.replace(pattern, stamp());

                    target.setAttribute('data-watermark', next);
                    target.style.setProperty('--wm-tile', tile(next));
                };

                refresh();
                window.setInterval(refresh, 20000);
            })();
        });
    });

    window.addEventListener('load', function () {
        if (window.Loader) {
            window.Loader.hide();
        }
    });

    function initializeChatbot() {
        if (!enableChatbot || chatbotInitialized) {
            return;
        }

        const chatbotInput = document.getElementById('chatbot-input');
        const chatbotSend = document.getElementById('chatbot-send');
        const chatbotToggle = document.getElementById('chatbot-toggle');
        const chatbotClose = document.querySelector('[data-chatbot-close]');

        addChatbotMessage("Hello! I'm your AI assistant. How can I help you today?", 'bot');

        if (chatbotSend) {
            chatbotSend.addEventListener('click', sendChatbotMessage);
        }

        if (chatbotInput) {
            chatbotInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    sendChatbotMessage();
                }
            });
        }

        if (chatbotToggle) {
            chatbotToggle.addEventListener('click', toggleChatbot);
        }

        if (chatbotClose) {
            chatbotClose.addEventListener('click', toggleChatbot);
        }

        chatbotInitialized = true;
    }

    function toggleChatbot() {
        initializeChatbot();

        const chatbotWindow = document.getElementById('chatbot-window');
        const chatbotInput = document.getElementById('chatbot-input');
        if (!chatbotWindow) {
            return;
        }

        if (chatbotWindow.style.display === 'flex') {
            chatbotWindow.style.display = 'none';
        } else {
            chatbotWindow.style.display = 'flex';
            if (chatbotInput) {
                chatbotInput.focus();
            }
        }
    }

    async function sendChatbotMessage() {
        const input = document.getElementById('chatbot-input');
        if (!input) {
            return;
        }

        const message = input.value.trim();
        if (!message) {
            return;
        }

        addChatbotMessage(message, 'user');
        input.value = '';
        showTypingIndicator();

        try {
            const response = await fetch(chatbotEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ message: message }),
            });
            const text = await response.text();

            if (!response.ok) {
                throw new Error('Server Error (' + response.status + '): ' + text.substring(0, 50) + '...');
            }

            const data = JSON.parse(text);
            removeTypingIndicator();
            addChatbotMessage(data.response, 'bot');
        } catch (error) {
            removeTypingIndicator();
            console.error('Chatbot Error:', error);
            let errMsg = "Sorry, I'm having trouble connecting to the server right now.";

            if (error.message) {
                errMsg += ' (' + error.message + ')';
            }

            if (error.message === 'Failed to fetch') {
                errMsg += ' - Please check your internet connection or if the server URL is correct.';
            }

            addChatbotMessage(errMsg, 'bot');
        }
    }

    function addChatbotMessage(text, sender) {
        const messagesDiv = document.getElementById('chatbot-messages');
        if (!messagesDiv) {
            return;
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message ' + sender + '-message';
        if (sender === 'bot') {
            messageDiv.innerHTML = text;
        } else {
            messageDiv.textContent = text;
        }
        messagesDiv.appendChild(messageDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function showTypingIndicator() {
        const messagesDiv = document.getElementById('chatbot-messages');
        if (!messagesDiv) {
            return;
        }

        const indicator = document.createElement('div');
        indicator.className = 'chat-message bot-message typing-indicator';
        indicator.id = 'typing-indicator';
        indicator.innerHTML = '<i class="bi bi-three-dots"></i>';
        messagesDiv.appendChild(indicator);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    function checkDisplayMode() {
        if (window.matchMedia('(display-mode: standalone)').matches) {
            return 'Standalone';
        }
        if (navigator.standalone) {
            return 'Standalone (iOS)';
        }
        return 'Browser';
    }

    checkDisplayMode();

    async function installPwaApp() {
        if (window.Loader) {
            window.Loader.show();
        }

        await new Promise((resolve) => setTimeout(resolve, 500));
        if (window.Loader) {
            await window.Loader.hide();
        }

        if (!deferredInstallPrompt) {
            const modalEl = document.getElementById('pwaInstallModal');

            if (modalEl && typeof bootstrap !== 'undefined') {
                try {
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } catch (error) {
                    console.error('Bootstrap Modal Error:', error);
                    alert('To install this app:\n\nAndroid: Tap the Menu (⋮) > Install App\niOS: Tap Share > Add to Home Screen');
                }
            } else {
                alert('To install this app:\n\nAndroid: Tap the Menu (⋮) > Install App\niOS: Tap Share > Add to Home Screen');
            }
            return;
        }

        deferredInstallPrompt.prompt();
        deferredInstallPrompt.userChoice.finally(function () {
            deferredInstallPrompt = null;
        });
    }

    function handleLogout(event) {
        event.preventDefault();
        const form = event.target;

        if (enablePwaUi && 'serviceWorker' in navigator) {
            caches.keys().then(function (names) {
                names.forEach(function (name) {
                    caches.delete(name);
                });
            });
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: new FormData(form),
        }).then(function () {
            window.location.href = loginRoute;
        }).catch(function (error) {
            console.error('Logout failed:', error);
            form.submit();
        });
    }

    window.installPwaApp = installPwaApp;
    window.handleLogout = handleLogout;

    document.addEventListener('DOMContentLoaded', function () {
        const chatbotToggle = document.getElementById('chatbot-toggle');
        const pwaBtn = document.getElementById('pwa-install-button');
        const pushBtn = document.getElementById('pwa-notification-button');

        if (chatbotToggle) {
            chatbotToggle.addEventListener('click', toggleChatbot, { once: true });
        }

        if (pwaBtn) {
            pwaBtn.addEventListener('click', installPwaApp);
        }

        if (pushBtn) {
            pushBtn.addEventListener('click', async function () {
                pushBtn.disabled = true;

                try {
                    const subscribed = await ensurePushSubscription({ requestPermission: true });
                    if (!subscribed && Notification.permission === 'denied') {
                        window.alert('Notifications are blocked for this app on this device. Enable them in your browser or app settings to receive mobile alerts.');
                    }
                } catch (error) {
                    console.warn('Push opt-in failed', error);
                } finally {
                    pushBtn.disabled = false;
                    updatePushOptInButton();
                }
            });
        }

        runWhenIdle(function () {
            if (canUsePwaShell() && 'serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register(serviceWorkerUrl).catch(function (error) {
                        console.warn('Service worker registration failed', error);
                    });
                }, { once: true });
            }

            if (enablePwaInstallUi && pwaBtn && !window.matchMedia('(display-mode: standalone)').matches && !navigator.standalone) {
                pwaBtn.style.display = 'flex';
            }

            if (enablePwaInstallUi) {
                window.addEventListener('beforeinstallprompt', function (event) {
                    event.preventDefault();
                    deferredInstallPrompt = event;

                    if (pwaBtn && !window.matchMedia('(display-mode: standalone)').matches) {
                        pwaBtn.style.display = 'flex';
                    }
                });

                window.addEventListener('appinstalled', function () {
                    if (pwaBtn) {
                        pwaBtn.style.display = 'none';
                    }
                    deferredInstallPrompt = null;
                });
            }

            if (supportsWebPush()) {
                syncExistingPushSubscription();
                updatePushOptInButton();

                ['pageshow', 'focus', 'visibilitychange'].forEach(function (eventName) {
                    window.addEventListener(eventName, function () {
                        if (eventName === 'visibilitychange' && document.visibilityState !== 'visible') {
                            return;
                        }

                        updatePushOptInButton();
                    });
                });
            }

            if (!enableScreensaver) {
                return;
            }

            let time;
            const screensaver = document.getElementById('screensaver');
            if (!screensaver) {
                return;
            }

            function resetTimer() {
                if (screensaver.style.display === 'flex') {
                    screensaver.style.display = 'none';
                }
                clearTimeout(time);
                time = setTimeout(function () {
                    screensaver.style.display = 'flex';
                }, 900000);
            }

            ['mousemove', 'keypress', 'touchstart', 'click', 'scroll'].forEach(function (name) {
                document.addEventListener(name, resetTimer, true);
            });

            resetTimer();
        });
    });

    // On mobile, direct receipt PDF downloads are unreliable.
    // Intercept those links and open the receipt view instead, where the
    // mobile PNG download button handles the capture.
    function isMobileReceiptContext() {
        return window.matchMedia('(max-width: 768px)').matches ||
            /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
    }

    document.addEventListener('click', function (event) {
        if (!isMobileReceiptContext()) {
            return;
        }

        const link = event.target.closest('a[href*="/receipt"][href*="download=1"]');
        if (!link) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        try {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.delete('download');
            window.location.href = url.toString();
        } catch (e) {
            window.location.href = link.getAttribute('href').replace(/[?&]download=1(&|$)/, '$1');
        }
    }, true);
})();

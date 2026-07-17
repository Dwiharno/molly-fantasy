$(function () {
    // CSRF setup for all AJAX requests (DataTables, fetch redeem/opname, dsb.)
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Sidebar toggle
    $('#sidebarToggle').on('click', function () {
        const sidebar = $('#sidebar');
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (isMobile) {
            sidebar.toggleClass('show').removeClass('collapsed');
        } else {
            sidebar.toggleClass('collapsed').removeClass('show');
        }

        $(this).attr('aria-expanded', !sidebar.hasClass('collapsed') && (sidebar.hasClass('show') || !isMobile));
    });

    $(window).on('resize', function () {
        const sidebar = $('#sidebar');
        if (window.matchMedia('(max-width: 768px)').matches) {
            sidebar.removeClass('collapsed');
        } else {
            sidebar.removeClass('show');
        }
    });

    // Dark mode with persistence (session storage - in-memory during session)
    const root = document.documentElement;
    const darkKey = 'mf_dark_mode';
    let isDark = window.__mfDarkMode || false;

    function applyTheme() {
        root.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
        $('#darkModeToggle i').toggleClass('fa-moon', !isDark).toggleClass('fa-sun', isDark);
    }

    $('#darkModeToggle').on('click', function () {
        isDark = !isDark;
        window.__mfDarkMode = isDark;
        applyTheme();
    });

    applyTheme();

    // Notification bell: load recent notifications and poll periodically
    function loadNotifications() {
        $.get('/notifications/recent', function (res) {
            const badge = $('#notifBadge');
            if (res.unread_count > 0) {
                badge.text(res.unread_count).removeClass('d-none');
            } else {
                badge.addClass('d-none');
            }

            const list = $('#notifList');
            list.empty();

            if (!res.notifications.length) {
                list.append('<div class="p-3 text-center text-muted small">Belum ada notifikasi.</div>');
                return;
            }

            res.notifications.forEach(function (n) {
                const bg = n.read ? '' : 'bg-primary-subtle';
                list.append(
                    '<div class="p-2 border-bottom small ' + bg + '">' +
                    '<div class="fw-semibold"><i class="fa-solid ' + n.icon + ' text-' + n.color + ' me-1"></i>' + n.title + '</div>' +
                    '<div class="text-muted">' + n.message + '</div>' +
                    '<div class="text-muted" style="font-size:.75rem">' + n.time + '</div>' +
                    '</div>'
                );
            });
        });
    }

    if ($('#notifBell').length) {
        loadNotifications();
        setInterval(loadNotifications, 30000);
    }

    // Global toast helper (SweetAlert2)
    window.mfToast = function (icon, title) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    };

    // Global confirm-delete helper
    window.mfConfirmDelete = function (url, onConfirm) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: 'Data yang dihapus tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
        }).then((result) => {
            if (result.isConfirmed && typeof onConfirm === 'function') {
                onConfirm(url);
            }
        });
    };
});

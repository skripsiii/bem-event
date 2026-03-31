/* ════════════════════════════════════════════════════════════════
   BEM Fasilkom Unsika — Main Script
   Handles: DataTables, Countdown, Read More, AJAX Forms, Admin ops
   ════════════════════════════════════════════════════════════════ */

$(document).ready(function () {

    /* ─────────────────────────────────────────────────────────────
       1. Bootstrap Tooltips
    ───────────────────────────────────────────────────────────── */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    /* ─────────────────────────────────────────────────────────────
       2. DataTables Initialization
    ───────────────────────────────────────────────────────────── */
    if ($('#dataTable').length) {
        $('#dataTable').DataTable({
            language : { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' },
            responsive: true,
            pageLength: 25,
            order    : []
        });
    }

    /* ─────────────────────────────────────────────────────────────
       3. Countdown Timer (Real-time)
    ───────────────────────────────────────────────────────────── */
    function updateCountdowns() {
        $('.countdown').each(function () {
            var closingDate = $(this).data('closing');
            var now         = Date.now();
            var closing     = new Date(closingDate + 'T23:59:59').getTime();
            var dist        = closing - now;

            if (dist < 0) {
                $(this).html('<span class="badge bg-danger badge-sm">Ditutup</span>');
                return;
            }

            var days    = Math.floor(dist / 86400000);
            var hours   = Math.floor((dist % 86400000) / 3600000);
            var minutes = Math.floor((dist % 3600000) / 60000);
            var seconds = Math.floor((dist % 60000) / 1000);
            var urgent  = dist < 86400000; // < 24 jam

            $(this).html(
                '<span class="countdown-chip' + (urgent ? ' urgent' : '') + '">' +
                '<i class="fas fa-clock fa-xs me-1"></i>' +
                days + 'h ' + hours + 'j ' + minutes + 'm ' + seconds + 'd' +
                '</span>'
            );
        });
    }
    updateCountdowns();
    setInterval(updateCountdowns, 1000);

    /* ─────────────────────────────────────────────────────────────
       4. Read More — Event Card Description Toggle
    ───────────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-read-more', function () {
        var $btn      = $(this);
        var $text     = $btn.prev('.event-desc-text');
        var expanded  = $btn.hasClass('expanded');

        if (expanded) {
            $text.text($text.data('short') + '\u2026');
            $btn.removeClass('expanded')
                .html('Selengkapnya <i class="fas fa-chevron-down fa-xs ms-1"></i>');
        } else {
            $text.text($text.data('full'));
            $btn.addClass('expanded')
                .html('Lebih sedikit <i class="fas fa-chevron-up fa-xs ms-1"></i>');
        }
    });

    /* ─────────────────────────────────────────────────────────────
       5. Event Type Field Toggle (Register Form)
    ───────────────────────────────────────────────────────────── */
    $('#event_type').on('change', function () {
        var type = $(this).val();
        if (type === 'umum') {
            $('.internal-field').slideUp(220);
            $('.umum-field').slideDown(220);
        } else if (type === 'internal') {
            $('.umum-field').slideUp(220);
            $('.internal-field').slideDown(220);
        }
    });

    /* ─────────────────────────────────────────────────────────────
       6. Registration Form — AJAX Submit (Same Page Alert)
    ───────────────────────────────────────────────────────────── */
    $('#registerForm').on('submit', function (e) {
        e.preventDefault();

        var $form       = $(this);
        var $btn        = $form.find('[type="submit"]');
        var originalHtml = $btn.html();

        // Client-side guard
        var fullName  = $('#full_name').val().trim();
        var email     = $('#email').val().trim();
        var phone     = $('#phone').val().trim();
        var eventType = $form.data('eventType');

        if (!fullName || !email || !phone) {
            return _swalError('Field Belum Lengkap', 'Nama, email, dan nomor telepon wajib diisi.');
        }

        if (eventType === 'internal' && !$('#npm').val().trim()) {
            return _swalError('NPM Diperlukan', 'NPM wajib diisi untuk event internal.');
        }

        // Loading state
        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>Mendaftarkan…');

        $.ajax({
            url     : $form.attr('action'),
            method  : 'POST',
            data    : $form.serialize(),
            dataType: 'json',
            success : function (res) {
                if (res.success) {
                    Swal.fire({
                        icon             : 'success',
                        title            : 'Pendaftaran Berhasil! 🎉',
                        text             : res.message,
                        confirmButtonText: 'Kembali ke Beranda',
                        confirmButtonColor: '#2563eb',
                        allowOutsideClick: false
                    }).then(function () {
                        window.location.href = 'index.php';
                    });
                } else {
                    _swalError('Pendaftaran Gagal', res.message);
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function () {
                _swalError('Koneksi Gagal', 'Terjadi kesalahan jaringan. Periksa koneksi Anda dan coba lagi.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    /* ─────────────────────────────────────────────────────────────
       7. Admin: Tambah Event Form — AJAX Submit
    ───────────────────────────────────────────────────────────── */
    $('#eventAddForm').on('submit', function (e) {
        e.preventDefault();
        _submitAdminForm(
            $(this),
            'Menyimpan event…',
            'Event Berhasil Ditambahkan!',
            'events.php'
        );
    });

    /* ─────────────────────────────────────────────────────────────
       8. Admin: Edit Event Form — AJAX Submit
    ───────────────────────────────────────────────────────────── */
    $('#eventEditForm').on('submit', function (e) {
        e.preventDefault();
        _submitAdminForm(
            $(this),
            'Memperbarui event…',
            'Event Berhasil Diperbarui!',
            'events.php'
        );
    });

    /* ─────────────────────────────────────────────────────────────
       9. Admin: Hapus Event — AJAX dengan Konfirmasi
    ───────────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        var $form     = $(this).closest('form');
        var eventName = $(this).closest('tr').find('.em-event-name, .fw-semibold').first().text().trim() || 'event ini';

        Swal.fire({
            title           : 'Hapus Event?',
            html            : 'Event "<strong>' + _escHtml(eventName) + '</strong>" akan dihapus beserta seluruh data peserta.<br><small class="text-danger mt-1 d-block">Tindakan ini tidak dapat dibatalkan!</small>',
            icon            : 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor : '#6b7280',
            confirmButtonText : '<i class="fas fa-trash me-1"></i> Ya, Hapus!',
            cancelButtonText  : 'Batal',
            reverseButtons    : true
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url     : $form.attr('action'),
                method  : 'POST',
                data    : $form.serialize(),
                dataType: 'json',
                success : function (res) {
                    if (res.success) {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Terhapus!',
                            text             : res.message,
                            timer            : 1800,
                            showConfirmButton: false
                        }).then(function () { location.reload(); });
                    } else {
                        _swalError('Gagal Menghapus', res.message);
                    }
                },
                error: function () {
                    _swalError('Koneksi Gagal', 'Terjadi kesalahan jaringan.');
                }
            });
        });
    });

    /* ─────────────────────────────────────────────────────────────
       10. Admin: Toggle Status Event — AJAX
    ───────────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-toggle', function (e) {
        e.preventDefault();
        var href     = $(this).attr('href');
        var isActive = $(this).hasClass('em-btn-muted') || $(this).hasClass('btn-act-secondary');

        Swal.fire({
            title           : isActive ? 'Nonaktifkan Event?' : 'Aktifkan Event?',
            text            : isActive
                                ? 'Event akan disembunyikan dari halaman pendaftaran publik.'
                                : 'Event akan tampil dan dapat didaftarkan oleh peserta.',
            icon            : 'question',
            showCancelButton: true,
            confirmButtonColor: isActive ? '#ef4444' : '#10b981',
            cancelButtonColor : '#6b7280',
            confirmButtonText : 'Ya, ubah!',
            cancelButtonText  : 'Batal',
            reverseButtons    : true
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url     : href,
                method  : 'GET',
                dataType: 'json',
                success : function (res) {
                    if (res.success) {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Berhasil!',
                            text             : res.message,
                            timer            : 1600,
                            showConfirmButton: false
                        }).then(function () { location.reload(); });
                    } else {
                        _swalError('Gagal Mengubah Status', res.message);
                    }
                },
                error: function () {
                    _swalError('Koneksi Gagal', 'Terjadi kesalahan jaringan.');
                }
            });
        });
    });

    /* ─────────────────────────────────────────────────────────────
       Utility Helpers
    ───────────────────────────────────────────────────────────── */

    /** Show SweetAlert error dialog */
    function _swalError(title, text) {
        return Swal.fire({
            icon             : 'error',
            title            : title,
            text             : text,
            confirmButtonColor: '#ef4444'
        });
    }

    /** Generic admin form AJAX with file upload support (FormData) */
    function _submitAdminForm($form, loadingText, successTitle, redirectUrl) {
        var $btn         = $form.find('[type="submit"]');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>' + loadingText);

        $.ajax({
            url        : $form.attr('action'),
            method     : 'POST',
            data       : new FormData($form[0]),
            processData: false,
            contentType: false,
            dataType   : 'json',
            success    : function (res) {
                if (res.success) {
                    Swal.fire({
                        icon             : 'success',
                        title            : successTitle,
                        text             : res.message || '',
                        timer            : 2200,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.href = redirectUrl;
                    });
                } else {
                    _swalError('Gagal!', res.message);
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function () {
                _swalError('Koneksi Gagal', 'Terjadi kesalahan. Silakan coba lagi.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    /** Escape HTML untuk dipakai di innerHTML */
    function _escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

});
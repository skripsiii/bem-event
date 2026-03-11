$(document).ready(function() {
    // Inisialisasi DataTables
    $('#dataTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        }
    });

    // SweetAlert untuk konfirmasi hapus
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // SweetAlert untuk toggle status event
    $('.btn-toggle').on('click', function(e) {
        e.preventDefault();
        var href = $(this).attr('href'); // .btn-toggle adalah <a>, bukan <button>
        Swal.fire({
            title: 'Ubah Status Event?',
            text: "Anda akan mengubah status aktif/tidak aktif event ini.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, ubah!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

    // Countdown timer untuk event
    function updateCountdowns() {
        $('.countdown').each(function() {
            var closingDate = $(this).data('closing'); // format YYYY-MM-DD
            var now         = new Date().getTime();
            var closing     = new Date(closingDate + 'T23:59:59').getTime(); // set ke akhir hari
            var distance    = closing - now;

            if (distance < 0) {
                $(this).html('<span class="text-danger">Ditutup</span>');
            } else {
                var days    = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours   = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                $(this).html(days + 'h ' + hours + 'j ' + minutes + 'm ' + seconds + 'd');
            }
        });
    }

    updateCountdowns();
    setInterval(updateCountdowns, 1000); // update setiap detik

    // Tampilkan form field berdasarkan jenis event
    $('#event_type').on('change', function() {
        var type = $(this).val();
        if (type === 'umum') {
            $('.internal-field').hide();
            $('.umum-field').show();
        } else if (type === 'internal') {
            $('.umum-field').hide();
            $('.internal-field').show();
        }
    });

    // Validasi form daftar
    $('#registerForm').on('submit', function(e) {
        var type  = $('#event_type').val();
        var phone = $('#phone').val();
        if (type === 'internal') {
            var npm = $('#npm').val();
            if (npm === '') {
                e.preventDefault();
                Swal.fire('Error', 'NPM harus diisi!', 'error');
                return;
            }
        }
        if (phone === '') {
            e.preventDefault();
            Swal.fire('Error', 'Nomor telepon harus diisi!', 'error');
        }
    });
});
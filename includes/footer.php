</main>

<footer class="mt-auto py-4">
  <style>
    .footer-text {
        font-size: 0.75rem;
        white-space: normal;
    }

    @media (max-width: 576px) {
        .footer-text {
            font-size: 0.65rem;
        }
    }
  </style>

  <div class="container">
    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
      
      <!-- Logo + Brand -->
      <div class="d-flex align-items-center gap-2">
        <img src="<?php echo BASE_URL; ?>assets/img/logo-bem.png"
             alt="Logo BEM"
             style="height:22px;border-radius:4px;opacity:.7;">
        <span class="footer-brand">BEM Fasilkom Unsika</span>
      </div>

      <!-- Copyright -->
      <p class="mb-0 footer-text">
        &copy; <?php echo date('Y'); ?> BEM Fasilkom Unsika &mdash; Sistem Pendaftaran Event
      </p>

    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>
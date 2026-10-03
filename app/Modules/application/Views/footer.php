<?php 
$copyright_info = esc(get_setting_value('copyright_info'));
?>
        <!-- footer content -->
        <?php if($admin_area =='yes'): ?>
        <div class="application-form-footer">
          <div class="container-public text-center">
          <?= $copyright_info; ?>
          </div>
          <div class="clearfix"></div>
        </div>
        <?php endif; ?>
        

    <!-- FastClick -->
    <script src="<?= base_url('assets/vendors/fastclick/lib/fastclick.js'); ?>"></script>
    <!-- NProgress -->
    <script src="<?= base_url('assets/vendors/nprogress/nprogress.js'); ?>"></script>
    <!-- bootstrap-wysiwyg -->
    <script src="<?= base_url('assets/vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js'); ?>"></script>
    <script src="<?= base_url('assets/vendors/jquery.hotkeys/jquery.hotkeys.js'); ?>"></script>
    <script src="<?= base_url('assets/vendors/google-code-prettify/src/prettify.js'); ?>"></script>
    <!-- Switchery -->
    <script src="<?= base_url('assets/vendors/switchery/dist/switchery.min.js'); ?>"></script>

    <!-- Custom Theme Scripts -->
    <script src="<?= base_url('assets/js/custom.js'); ?>"></script>

</body>
</html>
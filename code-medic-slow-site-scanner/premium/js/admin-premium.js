(function($) {
    'use strict';

    var codemedsssPremium = {
        init: function() {
            // Bind page select change handler
            $('#codemedsss_page_select').on('change', this.onPageSelectChange.bind(this));

            // Override the getScanUrl method to use selected URL
            if (typeof codemedsssScan !== 'undefined') {
                var originalGetScanUrl = codemedsssScan.getScanUrl;
                codemedsssScan.getScanUrl = this.getScanUrl.bind(this);
            }

            // Override setControls to disable dropdown during scan
            if (typeof codemedsssScan !== 'undefined') {
                var originalSetControls = codemedsssScan.setControls;
                codemedsssScan.setControls = this.setControls.bind(this);
            }
        },

        onPageSelectChange: function() {
            var selected = $('#codemedsss_page_select').val();
            if (selected === 'custom') {
                $('#codemedsss_scan_url').show().prop('disabled', false).focus();
            } else {
                $('#codemedsss_scan_url').hide().prop('disabled', true);
            }
        },

        getScanUrl: function() {
            var pageSelect = $('#codemedsss_page_select').val();
            if (pageSelect === 'custom') {
                return $('#codemedsss_scan_url').val();
            }
            return pageSelect || '';
        },

        setControls: function(scanning) {
            // Call original setControls first
            $('#codemedsss-scan-btn').toggle(!scanning);
            $('#codemedsss-cancel-btn').toggle(scanning);

            // Disable dropdown during scan
            $('#codemedsss_page_select').prop('disabled', scanning);
            $('#codemedsss_scan_url').prop('disabled', scanning);

            if (scanning) {
                $('#codemedsss-progress').show();
            } else {
                $('#codemedsss-progress').hide();
            }
        }
    };

    $(document).ready(function() {
        codemedsssPremium.init();
    });

})(jQuery);

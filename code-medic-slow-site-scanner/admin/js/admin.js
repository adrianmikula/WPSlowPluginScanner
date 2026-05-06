(function($) {
    'use strict';

    var piaScan = {
        isScanning: false,
        totalPlugins: 0,
        pollInterval: null,

        init: function() {
            $('#pia-scan-btn').on('click', this.startScan.bind(this));
            $('#pia-cancel-btn').on('click', this.cancelScan.bind(this));
            $('#pia_page_select').on('change', this.onPageSelectChange.bind(this));

            if ( piaData.homeUrl && $('#pia_scan_url').val() === '' ) {
                $('#pia_scan_url').val(piaData.homeUrl);
            }

            if (piaData.isScanning && piaData.totalPlugins > 0) {
                this.isScanning = true;
                this.totalPlugins = piaData.totalPlugins;
                this.setControls(true);
                this.setProgress(piaData.scannedCount, this.totalPlugins, '');
                this.startPolling();
            }
        },

        startScan: function(e) {
            e.preventDefault();

            var url = this.getScanUrl();

            this.setControls(true);
            this.showMessage('', '');

            $.ajax({
                url: piaData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pia_start_scan',
                    nonce: piaData.nonce,
                    url: url
                }
            }).done($.proxy(this.onScanStarted, this)).fail($.proxy(this.onError, this));
        },

        onScanStarted: function(response) {
            if (!response.success) {
                this.setControls(false);
                this.showMessage(response.data.message, 'error');
                return;
            }

            this.totalPlugins = response.data.total_plugins;
            this.isScanning = true;
            this.setProgress(0, this.totalPlugins, piaData.scanningText);
            this.startPolling();
        },

        startPolling: function() {
            this.pollInterval = setInterval($.proxy(this.pollScan, this), 2000);
            this.pollScan();
        },

        pollScan: function() {
            if (!this.isScanning) {
                return;
            }

            $.ajax({
                url: piaData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pia_poll_scan',
                    nonce: piaData.nonce
                }
            }).done($.proxy(this.onPolled, this)).fail($.proxy(this.onError, this));
        },

        onPolled: function(response) {
            if (!response.success) {
                this.setControls(false);
                this.showMessage(response.data.message, 'error');
                return;
            }

            var data = response.data;

            if (data.complete) {
                clearInterval(this.pollInterval);
                this.isScanning = false;
                this.setControls(false);

                if (data.cancelled) {
                    this.showMessage(piaData.cancelledText, 'success');
                } else {
                    this.showMessage(piaData.completedText, 'success');
                }

                this.displayResults(data.results);
            } else {
                this.setProgress(data.current, data.total, data.current_plugin);
            }
        },

        cancelScan: function(e) {
            e.preventDefault();

            $.ajax({
                url: piaData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pia_cancel_scan',
                    nonce: piaData.nonce
                }
            }).always($.proxy(function() {
                clearInterval(this.pollInterval);
                this.isScanning = false;
                this.setControls(false);
                this.showMessage(piaData.cancelledText, 'success');
            }, this));
        },

        onError: function(xhr, status, error) {
            this.setControls(false);
            this.showMessage(error || piaData.errorText, 'error');
        },

        onPageSelectChange: function() {
            var selected = $('#pia_page_select').val();
            if (selected === 'custom') {
                $('#pia_scan_url').show().prop('disabled', false).focus();
            } else {
                $('#pia_scan_url').hide().prop('disabled', true);
            }
        },

        getScanUrl: function() {
            var pageSelect = $('#pia_page_select').val();
            if (pageSelect === 'custom') {
                return $('#pia_scan_url').val();
            }
            return pageSelect || piaData.homeUrl || '';
        },

        setControls: function(scanning) {
            $('#pia-scan-btn').toggle(!scanning);
            $('#pia-cancel-btn').toggle(scanning);
            $('#pia_page_select').prop('disabled', scanning);
            $('#pia_scan_url').prop('disabled', scanning);

            if (scanning) {
                $('#pia-progress').show();
            } else {
                $('#pia-progress').hide();
            }
        },

        setProgress: function(current, total, pluginName) {
            var percent = total > 0 ? Math.round((current / total) * 100) : 0;

            $('#pia-progress-bar').val(percent);
            $('#pia-progress-text').text(
                piaData.pluginText
                    .replace('%1$d', current)
                    .replace('%2$d', total)
            );

            if (pluginName) {
                $('#pia-progress-text').append('<br>' + piaData.currentPlugin.replace('%s', pluginName));
            }
        },

        displayResults: function(results) {
            if (!results || !results.baseline) {
                $('#pia-results-area').hide();
                return;
            }

            var html = '<h2>' + piaData.resultsHeader + '</h2>';
            html += '<p><strong>' + piaData.urlLabel + '</strong> ' + this.escapeHtml(results.url) + '</p>';
            html += '<p><strong>' + piaData.baselineStatus + '</strong> ' + this.escapeHtml(results.baseline.status) + '</p>';
            html += '<p><strong>' + piaData.baselineTime + '</strong> ' + results.baseline.time.toFixed(3) + 's</p>';

            if (results.errors && results.errors.length) {
                html += '<div class="notice notice-warning"><p>' + this.escapeHtml(results.errors.join(' ')) + '</p></div>';
            }

            html += '<table class="widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th>' + piaData.pluginCol + '</th>';
            html += '<th>' + piaData.impactCol + '</th>';
            html += '<th>' + piaData.statusCol + '</th>';
            html += '<th>' + piaData.deltaCol + '</th>';
            html += '<th>' + piaData.changeCol + '</th>';
            html += '<th>' + piaData.errorCol + '</th>';
            html += '</tr></thead><tbody>';

            for (var i = 0; i < results.plugins.length; i++) {
                var p = results.plugins[i];
                html += '<tr>';
                html += '<td>' + this.escapeHtml(p.name) + '</td>';
                html += '<td>' + this.escapeHtml(p.impact) + '</td>';
                html += '<td>' + this.escapeHtml(p.status) + '</td>';
                html += '<td>' + p.delta + 's</td>';
                html += '<td>' + (p.hash_changed ? piaData.yesLabel : piaData.noLabel) + '</td>';
                html += '<td>' + this.escapeHtml(p.error || '') + '</td>';
                html += '</tr>';
            }

            html += '</tbody></table>';

            if (results.truncated) {
                html += '<p>' + piaData.truncatedText + '</p>';
            }

            $('#pia-results-area').html(html).show();
        },

        showMessage: function(message, type) {
            var area = $('#pia-message-area');
            if (!message) {
                area.hide();
                return;
            }

            area.html(
                '<div class="notice notice-' + type + '"><p>' + this.escapeHtml(message) + '</p></div>'
            ).show();
        },

        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        piaScan.init();

        if ( $('#pia-telemetry-toggle').length && piaData.supabaseConfigured ) {
            $('#pia-telemetry-toggle').on('change', function() {
                var enabled = $(this).is(':checked');
                $.ajax({
                    url: piaData.ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'pia_toggle_telemetry',
                        nonce: piaData.nonce,
                        enabled: enabled
                    }
                }).fail(function() {
                    $('#pia-telemetry-toggle').prop('checked', !enabled);
                });
            });
        }
    });

})(jQuery);
(function($) {
    'use strict';

    var codemedsssScan = {
        isScanning: false,
        totalPlugins: 0,
        pollInterval: null,

        init: function() {
            $('#codemedsss-scan-btn').on('click', this.startScan.bind(this));
            $('#codemedsss-cancel-btn').on('click', this.cancelScan.bind(this));
            $('#codemedsss_mu_consent').on('change', this.onConsentChange.bind(this));

            if (codemedsssData.isScanning && codemedsssData.totalPlugins > 0) {
                this.isScanning = true;
                this.totalPlugins = codemedsssData.totalPlugins;
                this.setControls(true);
                this.setProgress(codemedsssData.scannedCount, this.totalPlugins, '');
                this.startPolling();
            }

            this.toggleScanButton();
        },

        startScan: function(e) {
            e.preventDefault();

            this.setControls(true);
            this.showMessage('', '');

            $.ajax({
                url: codemedsssData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'codemedsss_start_scan',
                    nonce: codemedsssData.nonce
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
            this.setProgress(0, this.totalPlugins, codemedsssData.scanningText);
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
                url: codemedsssData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'codemedsss_poll_scan',
                    nonce: codemedsssData.nonce
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
                    this.showMessage(codemedsssData.cancelledText, 'success');
                } else {
                    this.showMessage(codemedsssData.completedText, 'success');
                }

                this.displayResults(data.results);
            } else {
                this.setProgress(data.current, data.total, data.current_plugin);
            }
        },

        cancelScan: function(e) {
            e.preventDefault();

            $.ajax({
                url: codemedsssData.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'codemedsss_cancel_scan',
                    nonce: codemedsssData.nonce
                }
            }).always($.proxy(function() {
                clearInterval(this.pollInterval);
                this.isScanning = false;
                this.setControls(false);
                this.showMessage(codemedsssData.cancelledText, 'success');
            }, this));
        },

        onError: function(xhr, status, error) {
            this.setControls(false);
            this.showMessage(error || codemedsssData.errorText, 'error');
        },

        onConsentChange: function() {
            var enabled = $('#codemedsss_mu_consent').is(':checked');
            var nonce = $('#codemedsss_mu_consent').data('nonce');
            $.post(ajaxurl, {
                action: 'codemedsss_save_consent',
                nonce: nonce,
                enabled: enabled
            }, function(response) {
                if (response.success) {
                    $('#codemedsss_consent_status').text('Saved').css('color','green');
                    codemedsssScan.toggleScanButton();
                } else {
                    $('#codemedsss_mu_consent').prop('checked', !enabled);
                    $('#codemedsss_consent_status').text('Error').css('color','red');
                }
            });
        },

        toggleScanButton: function() {
            var consent = $('#codemedsss_mu_consent').is(':checked');
            $('#codemedsss-scan-btn').prop('disabled', !consent);
        },

        setControls: function(scanning) {
            $('#codemedsss-scan-btn').toggle(!scanning);
            $('#codemedsss-cancel-btn').toggle(scanning);

            if (scanning) {
                $('#codemedsss-progress').show();
            } else {
                $('#codemedsss-progress').hide();
            }
        },

        setProgress: function(current, total, pluginName) {
            var percent = total > 0 ? Math.round((current / total) * 100) : 0;

            $('#codemedsss-progress-bar').val(percent);
            $('#codemedsss-progress-text').text(
                codemedsssData.pluginText
                    .replace('%1$d', current)
                    .replace('%2$d', total)
            );

            if (pluginName) {
                $('#codemedsss-progress-text').append('<br>' + codemedsssData.currentPlugin.replace('%s', pluginName));
            }
        },

        displayResults: function(results) {
            if (!results || !results.baseline) {
                $('#codemedsss-results-area').hide();
                return;
            }

            var html = '<h2>' + codemedsssData.resultsHeader + '</h2>';
            html += '<p><strong>' + codemedsssData.urlLabel + '</strong> ' + this.escapeHtml(results.url) + '</p>';
            html += '<p><strong>' + codemedsssData.baselineStatus + '</strong> ' + this.escapeHtml(results.baseline.status) + '</p>';
            html += '<p><strong>' + codemedsssData.baselineTime + '</strong> ' + results.baseline.time.toFixed(3) + 's</p>';

            if (results.errors && results.errors.length) {
                html += '<div class="notice notice-warning"><p>' + this.escapeHtml(results.errors.join(' ')) + '</p></div>';
            }

            html += '<table class="widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th>' + codemedsssData.pluginCol + '</th>';
            html += '<th>' + codemedsssData.impactCol + '</th>';
            html += '<th>' + codemedsssData.statusCol + '</th>';
            html += '<th>' + codemedsssData.deltaCol + '</th>';
            html += '<th>' + codemedsssData.changeCol + '</th>';
            html += '<th>' + codemedsssData.errorCol + '</th>';
            html += '</tr></thead><tbody>';

            for (var i = 0; i < results.plugins.length; i++) {
                var p = results.plugins[i];
                html += '<tr>';
                html += '<td>' + this.escapeHtml(p.name) + '</td>';
                html += '<td>' + this.escapeHtml(p.impact) + '</td>';
                html += '<td>' + this.escapeHtml(p.status) + '</td>';
                html += '<td>' + p.delta + 's</td>';
                html += '<td>' + (p.hash_changed ? codemedsssData.yesLabel : codemedsssData.noLabel) + '</td>';
                html += '<td>' + this.escapeHtml(p.error || '') + '</td>';
                html += '</tr>';
            }

            html += '</tbody></table>';

            if (results.truncated) {
                html += '<p>' + codemedsssData.truncatedText + '</p>';
            }

            $('#codemedsss-results-area').html(html).show();
        },

        showMessage: function(message, type) {
            var area = $('#codemedsss-message-area');
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
        codemedsssScan.init();
    });

})(jQuery);

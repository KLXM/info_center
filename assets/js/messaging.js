/**
 * Messaging Widget JavaScript
 * Handles screenshot taking and message sending functionality
 */

(function($) {
    'use strict';
    
    var MessagingWidget = {
        screenshotData: null,
        
        init: function() {
            this.bindEvents();
            this.initModal();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Send message button in widget
            $(document).on('click', '.messaging-send-btn', function(e) {
                e.preventDefault();
                var currentUrl = $(this).data('url') || window.location.href;
                var currentContext = $(this).data('context') || '';
                self.openMessageModal(currentUrl, currentContext);
            });
            
            // Screenshot button in widget
            $(document).on('click', '.messaging-screenshot-btn', function(e) {
                e.preventDefault();
                self.takeScreenshot();
            });
            
            // Send button in modal
            $(document).on('click', '#messaging-send-btn', function(e) {
                e.preventDefault();
                self.sendMessage();
            });
            
            // Screenshot checkbox change
            $(document).on('change', '#messaging-screenshot', function() {
                if ($(this).is(':checked') && !self.screenshotData) {
                    self.takeScreenshot();
                }
            });
        },
        
        initModal: function() {
            // Create modal if it doesn't exist
            if ($('#info-center-modal').length === 0) {
                var modal = $('<div class="modal fade" id="info-center-modal" tabindex="-1" role="dialog">' +
                    '<div class="modal-dialog" role="document">' +
                        '<div class="modal-content">' +
                            '<div class="modal-header">' +
                                '<h4 class="modal-title">Info Center</h4>' +
                                '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                                    '<span aria-hidden="true">&times;</span>' +
                                '</button>' +
                            '</div>' +
                            '<div class="modal-body"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>');
                $('body').append(modal);
            }
        },
        
        openMessageModal: function(currentUrl, currentContext) {
            var self = this;
            var modalUrl = rex.backend_path + 'index.php?page=info_center/messaging';
            modalUrl += '&current_url=' + encodeURIComponent(currentUrl);
            modalUrl += '&current_context=' + encodeURIComponent(currentContext);
            
            $('#info-center-modal .modal-title').text('Send Message to Agency');
            $('#info-center-modal .modal-body').html('<div class="text-center"><i class="rex-icon rex-icon-spinner fa-spin"></i> Loading...</div>');
            
            $.get(modalUrl, function(data) {
                $('#info-center-modal .modal-body').html(data);
                $('#info-center-modal').modal('show');
            }).fail(function() {
                self.showStatus('Error loading messaging form', 'error');
            });
        },
        
        takeScreenshot: function() {
            var self = this;
            
            self.showStatus('Taking screenshot...', 'info');
            
            // Use html2canvas library to take screenshot
            if (typeof html2canvas !== 'undefined') {
                html2canvas(document.body, {
                    useCORS: true,
                    allowTaint: true,
                    scale: 0.5, // Reduce quality for smaller file size
                    width: window.innerWidth,
                    height: window.innerHeight
                }).then(function(canvas) {
                    self.screenshotData = canvas.toDataURL('image/png');
                    self.showScreenshotPreview();
                    self.showStatus('Screenshot captured successfully', 'success');
                }).catch(function(error) {
                    console.error('Screenshot failed:', error);
                    self.showStatus('Screenshot failed. Continuing without screenshot.', 'error');
                });
            } else {
                // Fallback: Load html2canvas dynamically
                self.loadHtml2Canvas().then(function() {
                    self.takeScreenshot();
                }).catch(function() {
                    self.showStatus('Screenshot functionality not available', 'error');
                });
            }
        },
        
        loadHtml2Canvas: function() {
            return new Promise(function(resolve, reject) {
                if (typeof html2canvas !== 'undefined') {
                    resolve();
                    return;
                }
                
                var script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },
        
        showScreenshotPreview: function() {
            var previewHtml = '<div class="screenshot-preview">' +
                '<h5>Screenshot Preview:</h5>' +
                '<img src="' + this.screenshotData + '" alt="Screenshot" style="max-width: 200px; max-height: 150px;">' +
                '<div class="screenshot-actions">' +
                    '<button type="button" class="btn btn-sm btn-success">Use this screenshot</button>' +
                    '<button type="button" class="btn btn-sm btn-secondary" onclick="MessagingWidget.takeScreenshot()">Retake</button>' +
                    '<button type="button" class="btn btn-sm btn-danger" onclick="MessagingWidget.removeScreenshot()">Remove</button>' +
                '</div>' +
            '</div>';
            
            $('.screenshot-preview').remove();
            $('#messaging-screenshot').closest('.form-group').after(previewHtml);
            $('#messaging-screenshot-data').val(this.screenshotData);
        },
        
        removeScreenshot: function() {
            this.screenshotData = null;
            $('.screenshot-preview').remove();
            $('#messaging-screenshot-data').val('');
            $('#messaging-screenshot').prop('checked', false);
            this.showStatus('Screenshot removed', 'info');
        },
        
        sendMessage: function() {
            var self = this;
            var form = $('#info-center-modal form');
            
            // Validate form
            var subject = $('#messaging-subject').val().trim();
            var message = $('#messaging-message').val().trim();
            
            if (!subject || !message) {
                self.showStatus('Please fill in subject and message', 'error');
                return;
            }
            
            var $sendBtn = $('#messaging-send-btn');
            $sendBtn.prop('disabled', true).addClass('sending').text('Sending...');
            
            var formData = {
                'rex-api-call': 'info_center_messaging',
                'action': 'send_message',
                'subject': subject,
                'message': message,
                'current_url': $('#messaging-current-url').val(),
                'current_context': $('#messaging-current-context').val(),
                'include_screenshot': $('#messaging-screenshot').is(':checked') ? 1 : 0,
                'screenshot_data': $('#messaging-screenshot-data').val()
            };
            
            $.ajax({
                url: rex.backend_path + 'index.php',
                type: 'POST',
                data: formData,
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    self.showStatus('Message sent successfully!', 'success');
                    $('#info-center-modal').modal('hide');
                    
                    // Reset form
                    $('#messaging-subject, #messaging-message').val('');
                    self.removeScreenshot();
                    
                } else {
                    self.showStatus('Error: ' + (response.message || 'Unknown error'), 'error');
                }
            }).fail(function(xhr, status, error) {
                var errorMsg = 'Failed to send message';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    // Use default error message
                }
                self.showStatus('Error: ' + errorMsg, 'error');
            }).always(function() {
                $sendBtn.prop('disabled', false).removeClass('sending').text('Send Message');
            });
        },
        
        showStatus: function(message, type) {
            var $status = $('#messaging-status');
            if ($status.length === 0) {
                $status = $('.messaging-status');
            }
            
            $status.removeClass('success error info').addClass(type).text(message);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $status.text('');
                }, 3000);
            }
        }
    };
    
    // Make MessagingWidget globally available
    window.MessagingWidget = MessagingWidget;
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        MessagingWidget.init();
    });
    
})(jQuery);

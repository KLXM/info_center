/**
 * Messaging Widget JavaScript
 * Handles screenshot taking and message sending functionality
 */

(function($) {
    'use strict';
    
    var MessagingWidget = {
        screenshotData: null,
        backendPath: (typeof rex !== 'undefined' && rex.backend_path) ? rex.backend_path : '/redaxo/',
        
        init: function() {
            this.bindEvents();
            // Modal-Initialisierung entfernt, da wir direkt zur Backend-Seite navigieren
        },
        
        bindEvents: function() {
            var self = this;
            
            // Send message button in widget
            $(document).on('click', '.messaging-send-btn', function(e) {
                e.preventDefault();
                var currentUrl = $(this).data('url') || window.location.href;
                var currentContext = $(this).data('context') || '';
                
                // Falls currentContext ein Object ist, zu String konvertieren
                if (typeof currentContext === 'object') {
                    currentContext = JSON.stringify(currentContext, null, 2);
                }
                
                self.openMessageModal(currentUrl, currentContext);
            });
            
            // Screenshot button in widget
            $(document).on('click', '.messaging-screenshot-btn', function(e) {
                e.preventDefault();
                self.takeScreenshot();
            });
            
            // Send button in modal -> jetzt auf der Backend-Seite
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
        
        
        openMessageModal: function(currentUrl, currentContext) {
            // Erstelle das Formular direkt im JavaScript anstatt Backend-Seite zu laden
            this.createMessagingModal(currentUrl, currentContext);
            $('#info-center-messaging-modal').modal('show');
        },
        
        createMessagingModal: function(currentUrl, currentContext) {
            // Falls currentContext ein Object ist, zu String konvertieren
            if (typeof currentContext === 'object') {
                currentContext = JSON.stringify(currentContext, null, 2);
            }
            
            // Modal HTML direkt erstellen
            var modalHtml = `
                <div class="modal fade" id="info-center-messaging-modal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Nachricht an Agentur senden</h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="messaging-form">
                                    <div class="form-group">
                                        <label for="messaging-current-url">Aktuelle URL</label>
                                        <input type="text" id="messaging-current-url" name="current_url" class="form-control" value="${currentUrl}" readonly />
                                    </div>
                                    <div class="form-group">
                                        <label for="messaging-current-context">Kontext-Informationen</label>
                                        <textarea id="messaging-current-context" name="current_context" class="form-control" rows="3" readonly>${currentContext}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="messaging-subject">Betreff <span class="text-danger">*</span></label>
                                        <input type="text" id="messaging-subject" name="subject" class="form-control" placeholder="Kurze Beschreibung Ihrer Nachricht" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="messaging-message">Nachricht <span class="text-danger">*</span></label>
                                        <textarea id="messaging-message" name="message" class="form-control" rows="8" placeholder="Beschreiben Sie Ihr Problem, Ihre Frage oder Ihr Feedback detailliert..." required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" id="messaging-screenshot" name="include_screenshot" value="1" /> 
                                            Screenshot anhängen
                                        </label>
                                        <small class="form-text text-muted">Ein Screenshot wird automatisch erstellt und angehängt</small>
                                    </div>
                                    <div id="screenshot-preview"></div>
                                    <input type="hidden" id="messaging-screenshot-data" name="screenshot_data" />
                                    <div id="messaging-status" class="messaging-status"></div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" id="messaging-send-btn">Nachricht senden</button>
                                <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Entferne vorhandenes Modal falls vorhanden
            $('#info-center-messaging-modal').remove();
            
            // Füge neues Modal hinzu
            $('body').append(modalHtml);
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
                '<img src="' + this.screenshotData + '" alt="Screenshot" style="max-width: 100%; max-height: 200px;">' +
                '<div class="screenshot-actions" style="margin-top: 10px;">' +
                    '<button type="button" class="btn btn-sm btn-success">Diesen Screenshot verwenden</button>' +
                    '<button type="button" class="btn btn-sm btn-secondary" onclick="MessagingWidget.takeScreenshot()">Neuer Screenshot</button>' +
                    '<button type="button" class="btn btn-sm btn-danger" onclick="MessagingWidget.removeScreenshot()">Entfernen</button>' +
                '</div>' +
            '</div>';
            
            $('#screenshot-preview').html(previewHtml);
            $('#messaging-screenshot-data').val(this.screenshotData);
            $('#messaging-screenshot').prop('checked', true);
        },
        
        removeScreenshot: function() {
            this.screenshotData = null;
            $('#screenshot-preview').html('');
            $('#messaging-screenshot-data').val('');
            $('#messaging-screenshot').prop('checked', false);
            this.showStatus('Screenshot entfernt', 'info');
        },
        
        sendMessage: function() {
            var self = this;
            
            // Validiere Formular
            var subject = $('#messaging-subject').val().trim();
            var message = $('#messaging-message').val().trim();
            
            if (!subject || !message) {
                self.showStatus('Bitte füllen Sie Betreff und Nachricht aus', 'error');
                return;
            }
            
            var $sendBtn = $('#messaging-send-btn');
            $sendBtn.prop('disabled', true).addClass('sending').text('Wird gesendet...');
            
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
                url: self.backendPath + 'index.php',
                type: 'POST',
                data: formData,
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    self.showStatus('Nachricht erfolgreich gesendet!', 'success');
                    
                    // Modal nach kurzer Zeit schließen
                    setTimeout(function() {
                        $('#info-center-messaging-modal').modal('hide');
                        
                        // Formular zurücksetzen
                        $('#messaging-subject, #messaging-message').val('');
                        self.removeScreenshot();
                    }, 2000);
                    
                } else {
                    self.showStatus('Fehler: ' + (response.message || 'Unbekannter Fehler'), 'error');
                }
            }).fail(function(xhr, status, error) {
                var errorMsg = 'Fehler beim Senden der Nachricht';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    // Use default error message
                }
                self.showStatus('Fehler: ' + errorMsg, 'error');
            }).always(function() {
                $sendBtn.prop('disabled', false).removeClass('sending').text('Nachricht senden');
            });
        },
        
        showStatus: function(message, type) {
            var $status = $('#messaging-status');
            if ($status.length === 0) {
                // Falls kein Status-Element da ist, eins nach dem Send-Button erstellen
                var statusHtml = '<div id="messaging-status" class="messaging-status"></div>';
                $('#messaging-send-btn').closest('.modal-footer').before(statusHtml);
                $status = $('#messaging-status');
            }
            
            $status.removeClass('success error info').addClass(type).text(message).show();
            
            // Auto-hide success und info messages
            if (type === 'success' || type === 'info') {
                setTimeout(function() {
                    $status.fadeOut();
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

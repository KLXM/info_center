<?php

namespace KLXM\InfoCenter\QuickNavigation;

use FriendsOfRedaxo\QuickNavigation\Button\ButtonInterface;
use rex_i18n;

class InfoCenterStructureButton implements ButtonInterface
{
    public function get(): string
    {
        // JavaScript-Button der das Info Center öffnet und zur Struktur-Tab wechselt
        $label = rex_i18n::msg('quick_navigation_structure', 'Struktur');
        
        return '
            <button type="button" 
                    class="btn btn-default quick-navigation-button" 
                    onclick="
                        const infoCenter = document.querySelector(\'.info-center-container\');
                        if (infoCenter) {
                            infoCenter.classList.add(\'active\');
                            
                            // Warte kurz bis Info Center sichtbar ist
                            setTimeout(() => {
                                const structureTab = document.querySelector(\'.info-center-tab[data-tab=\\\'structure\\\']\');
                                if (structureTab) {
                                    structureTab.click();
                                }
                            }, 100);
                        }
                        
                        // Schließe Quick Navigation Dropdown
                        const dropdown = this.closest(\'.dropdown\');
                        if (dropdown) {
                            dropdown.classList.remove(\'open\');
                        }
                    "
                    title="' . rex_i18n::msg('info_center_structure_title', 'Struktur im Info Center öffnen') . '">
                <i class="fa fa-solid fa-folder-tree"></i>
                ' . $label . '
            </button>';
    }
}

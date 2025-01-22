// Main Info Center Component
class InfoCenter extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        // Initial HTML Structure
        this.innerHTML = `
            <button class="info-center-toggle" type="button">
                <span>☰</span>
            </button>
            <div class="info-center-panel">
                <div class="info-center-content">
                    ${this.innerHTML}
                </div>
            </div>
        `;

        // Add event listener to toggle button
        this.querySelector('.info-center-toggle').addEventListener('click', () => {
            this.togglePanel();
        });
    }

    togglePanel() {
        const panel = this.querySelector('.info-center-panel');
        const button = this.querySelector('.info-center-toggle');
        
        if (panel.classList.contains('active')) {
            panel.classList.remove('active');
            button.classList.remove('active');
        } else {
            panel.classList.add('active');
            button.classList.add('active');
        }
    }
}

// Register Custom Element
customElements.define('info-center', InfoCenter);

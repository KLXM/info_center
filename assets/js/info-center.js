// Main Info Center Component
class InfoCenter extends HTMLElement {
    constructor() {
        super();
        this.isVisible = false;
    }

    connectedCallback() {
        // Wrap existing content
        const content = this.innerHTML;
        this.innerHTML = `
            <div class="info-center-content" style="
                position: fixed;
                top: 0;
                right: -360px;
                width: 360px;
                height: 100vh;
                background: #fff;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                transition: right 0.3s ease-in-out;
                z-index: 10000;
                padding: 20px;
                overflow-y: auto;
            ">
                ${content}
            </div>
        `;

        // Find the toggle button outside the component
        const toggleBtn = document.querySelector('.info-center-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggle());
        }
    }

    toggle() {
        this.isVisible = !this.isVisible;
        const content = this.querySelector('.info-center-content');
        const toggleBtn = document.querySelector('.info-center-toggle');

        if (this.isVisible) {
            content.style.right = '0';
            toggleBtn.style.right = '360px';
        } else {
            content.style.right = '-360px';
            toggleBtn.style.right = '0';
        }
    }
}

// Register Custom Element
customElements.define('info-center', InfoCenter);

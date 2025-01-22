class InfoCenter extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.isVisible = false;
    }

    connectedCallback() {
        this.render();
        this.setupEventListeners();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    --info-center-width: 360px;
                    --info-center-background: var(--info-center-background-color, #ffffff);
                    --info-center-text-color: var(--info-center-text-color, #333333);
                    --info-center-border-color: var(--info-center-border-color, #dddddd);
                    --info-center-shadow: var(--info-center-shadow, 0 0 10px rgba(0,0,0,0.1));
                    --info-center-header-bg: var(--info-center-header-background, #f5f5f5);
                    
                    position: fixed;
                    top: 0;
                    right: ${this.isVisible ? '0' : '-360px'};
                    width: var(--info-center-width);
                    height: 100vh;
                    background: var(--info-center-background);
                    box-shadow: var(--info-center-shadow);
                    transition: right 0.3s ease-in-out;
                    z-index: 10000;
                    color: var(--info-center-text-color);
                }

                .info-center-container {
                    height: 100%;
                    overflow-y: auto;
                    padding: 20px;
                }
            </style>
            <div class="info-center-container">
                <slot></slot>
            </div>
        `;
    }

    setupEventListeners() {
        // Reagieren auf den Toggle-Button-Click
        document.querySelector('.info-center-toggle').addEventListener('click', () => {
            this.toggleVisibility();
        });
    }

    toggleVisibility() {
        this.isVisible = !this.isVisible;
        if (this.isVisible) {
            this.style.right = '0';
        } else {
            this.style.right = '-360px';
        }

        // Button-Position anpassen
        const toggleButton = document.querySelector('.info-center-toggle');
        if (this.isVisible) {
            toggleButton.style.right = '360px';
            toggleButton.innerHTML = '<span class="info-center-toggle-icon">❯</span>';
        } else {
            toggleButton.style.right = '0';
            toggleButton.innerHTML = '<span class="info-center-toggle-icon">❮</span>';
        }
    }
}

class InfoCenterWidget extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
    }

    connectedCallback() {
        this.render();
        if(this.dataset.lazy === 'true') {
            this.setupLazyLoading();
        }
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    margin-bottom: 20px;
                    background: var(--info-center-background);
                    border: 1px solid var(--info-center-border-color);
                    border-radius: 4px;
                    overflow: hidden;
                }

                .widget-header {
                    padding: 10px 15px;
                    background: var(--info-center-header-bg);
                    border-bottom: 1px solid var(--info-center-border-color);
                }

                .widget-title {
                    margin: 0;
                    font-size: 14px;
                    font-weight: 600;
                }

                .widget-content {
                    padding: 15px;
                }
            </style>

            <div class="widget-header">
                <h3 class="widget-title"><slot name="title"></slot></h3>
            </div>
            <div class="widget-content">
                <slot></slot>
            </div>
        `;
    }
}

// Register Custom Elements
customElements.define('info-center', InfoCenter);
customElements.define('info-center-widget', InfoCenterWidget);

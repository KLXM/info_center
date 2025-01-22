// Main Info Center Component
class InfoCenter extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.visible = false;
    }

    connectedCallback() {
        this.render();
        this.setupToggleButton();
        this.setupEventListeners();
    }

    render() {
        const position = this.dataset.position || 'right';
        const theme = this.dataset.theme || 'auto';

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
                    ${position}: -${this.visible ? 0 : 'var(--info-center-width)'};
                    width: var(--info-center-width);
                    height: 100vh;
                    background: var(--info-center-background);
                    box-shadow: var(--info-center-shadow);
                    transition: right 0.3s ease-in-out, left 0.3s ease-in-out;
                    z-index: 10000;
                    color: var(--info-center-text-color);
                }

                .info-center-toggle {
                    position: fixed;
                    ${position}: ${this.visible ? 'var(--info-center-width)' : '0'};
                    top: 50%;
                    transform: translateY(-50%);
                    background: var(--info-center-background);
                    border: 1px solid var(--info-center-border-color);
                    border-${position}: none;
                    padding: 10px;
                    cursor: pointer;
                    box-shadow: var(--info-center-shadow);
                    transition: right 0.3s ease-in-out, left 0.3s ease-in-out;
                }

                .info-center-container {
                    height: 100%;
                    overflow-y: auto;
                    padding: 20px;
                }

                @media (prefers-color-scheme: dark) {
                    :host([data-theme="auto"]) {
                        --info-center-background-color: #1a1a1a;
                        --info-center-text-color: #ffffff;
                        --info-center-border-color: #333333;
                        --info-center-header-background: #2a2a2a;
                        --info-center-shadow: 0 0 10px rgba(0,0,0,0.3);
                    }
                }

                :host([data-theme="dark"]) {
                    --info-center-background-color: #1a1a1a;
                    --info-center-text-color: #ffffff;
                    --info-center-border-color: #333333;
                    --info-center-header-background: #2a2a2a;
                    --info-center-shadow: 0 0 10px rgba(0,0,0,0.3);
                }
            </style>

            <div class="info-center-toggle" title="${this.visible ? 'Verbergen' : 'Anzeigen'}">
                ${this.visible ? '❯' : '❮'}
            </div>

            <div class="info-center-container">
                <slot></slot>
            </div>
        `;
    }

    setupToggleButton() {
        const toggle = this.shadowRoot.querySelector('.info-center-toggle');
        toggle.addEventListener('click', () => {
            this.visible = !this.visible;
            this.render();
            this.dispatchEvent(new CustomEvent('visibilityChange', { 
                detail: { visible: this.visible } 
            }));
        });
    }

    setupEventListeners() {
        // Listen for theme changes
        this.addEventListener('themeChange', (event) => {
            this.dataset.theme = event.detail.theme;
        });
    }
}

// Widget Base Component
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

                .widget-loading {
                    text-align: center;
                    padding: 20px;
                    color: var(--info-center-text-color);
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

    setupLazyLoading() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadContent();
                    observer.unobserve(this);
                }
            });
        });

        observer.observe(this);
    }

    async loadContent() {
        const content = this.shadowRoot.querySelector('.widget-content');
        content.innerHTML = '<div class="widget-loading">Laden...</div>';

        try {
            const response = await fetch(`/index.php?widget=${this.dataset.id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await response.text();
            content.innerHTML = html;
        } catch (error) {
            content.innerHTML = '<div class="widget-error">Fehler beim Laden</div>';
            console.error('Widget loading error:', error);
        }
    }
}

// Register Custom Elements
customElements.define('info-center', InfoCenter);
customElements.define('info-center-widget', InfoCenterWidget);

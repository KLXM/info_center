/**
 * TimeTracker JavaScript
 * Manages time tracking across frontend and backend
 */
class InfoCenterTimeTracker {
    constructor() {
        this.storageKey = 'infocenter_timetracker';
        this.state = this.loadState();
        this.intervalId = null;
        this.displayStartTime = null;
        this.initialized = false;
        
        // Globale Referenz für PJAX-Updates
        window.InfoCenterTimeTracker = this;
        
        this.init();
    }
    
    init() {
        this.initializeElements();
        this.createMiniTracker();
        this.bindEvents();
        this.updateDisplay();
        this.loadTodayStats();
        this.updateButtons();
        this.updateMiniVisibility();
        
        // Auto-restore running timer
        if (this.state.isRunning && !this.state.isPaused) {
            this.resumeTimer();
            this.updateSessionInfo('Tracking läuft...');
            this.elements.container?.classList.add('tracking');
        } else if (this.state.isRunning && this.state.isPaused) {
            this.updateSessionInfo('Pausiert');
            this.elements.container?.classList.add('paused');
        } else {
            this.updateSessionInfo('Bereit zum Starten');
        }
        
        this.initialized = true;
    }
    
    refreshAfterPjax() {
        // Nach PJAX-Update: Elemente neu initialisieren aber State beibehalten
        if (!this.initialized) return;
        
        console.log('TimeTracker: Refreshing after PJAX');
        
        // Timer stoppen für Reinitialisierung
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        
        // Elemente neu finden
        this.initializeElements();
        
        // Display und Buttons aktualisieren
        this.updateDisplay();
        this.updateButtons();
        this.loadTodayStats();
        
        // Timer wieder starten falls aktiv
        if (this.state.isRunning && !this.state.isPaused) {
            this.resumeTimer();
            this.updateSessionInfo('Tracking läuft...');
            this.elements.container?.classList.add('tracking');
        } else if (this.state.isRunning && this.state.isPaused) {
            this.updateSessionInfo('Pausiert');
            this.elements.container?.classList.add('paused');
        } else {
            this.updateSessionInfo('Bereit zum Starten');
        }
        
        this.updateMiniVisibility();
    }

    initializeElements() {
        this.elements = {
            display: document.getElementById('timeDisplay'),
            sessionInfo: document.getElementById('sessionInfo'),
            startBtn: document.getElementById('startBtn'),
            pauseBtn: document.getElementById('pauseBtn'),
            stopBtn: document.getElementById('stopBtn'),
            todayTime: document.getElementById('todayTime'),
            container: document.querySelector('.time-tracker-container'),
            sidebar: document.querySelector('.info-center-sidebar')
        };
    }

    createMiniTracker() {
        // Erstelle Mini-Tracker Element
        this.miniTracker = document.createElement('div');
        this.miniTracker.className = 'time-tracker-mini';
        this.miniTracker.innerHTML = `
            <div class="mini-display" id="miniTimeDisplay">00:00:00</div>
            <div class="mini-status" id="miniStatus">Bereit</div>
            <div class="mini-controls">
                <button class="mini-btn mini-btn-start" id="miniStartBtn" title="Start/Resume">▶</button>
                <button class="mini-btn mini-btn-pause" id="miniPauseBtn" title="Pause">⏸</button>
                <button class="mini-btn mini-btn-stop" id="miniStopBtn" title="Stop">⏹</button>
            </div>
        `;
        
        document.body.appendChild(this.miniTracker);
        
        // Mini-Tracker Elemente referenzieren
        this.miniElements = {
            tracker: this.miniTracker,
            display: document.getElementById('miniTimeDisplay'),
            status: document.getElementById('miniStatus'),
            startBtn: document.getElementById('miniStartBtn'),
            pauseBtn: document.getElementById('miniPauseBtn'),
            stopBtn: document.getElementById('miniStopBtn')
        };
    }

    bindEvents() {
        // Haupt-Tracker Events
        if (this.elements.startBtn) {
            this.elements.startBtn.addEventListener('click', () => this.start());
        }
        if (this.elements.pauseBtn) {
            this.elements.pauseBtn.addEventListener('click', () => this.pause());
        }
        if (this.elements.stopBtn) {
            this.elements.stopBtn.addEventListener('click', () => this.stop());
        }

        // Mini-Tracker Events
        if (this.miniElements.startBtn) {
            this.miniElements.startBtn.addEventListener('click', () => this.start());
        }
        if (this.miniElements.pauseBtn) {
            this.miniElements.pauseBtn.addEventListener('click', () => this.pause());
        }
        if (this.miniElements.stopBtn) {
            this.miniElements.stopBtn.addEventListener('click', () => this.stop());
        }

        // Mini-Tracker Click zum Sidebar öffnen
        if (this.miniElements.tracker) {
            this.miniElements.tracker.addEventListener('click', (e) => {
                // Nur wenn nicht auf Button geklickt wurde
                if (!e.target.classList.contains('mini-btn')) {
                    this.toggleSidebar();
                }
            });
        }

        // Sync across tabs/windows
        window.addEventListener('storage', (e) => {
            if (e.key === this.storageKey) {
                this.state = this.loadState();
                this.updateDisplay();
                this.updateButtons();
                this.updateMiniVisibility();
            }
        });

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.state.isRunning && !this.state.isPaused) {
                this.resumeTimer();
            }
        });

        // Überwache Sidebar Sichtbarkeit
        this.observeSidebarVisibility();
    }

    start() {
        if (this.state.isPaused) {
            // Resume from pause
            this.state.isPaused = false;
            this.state.lastResumeTime = Date.now();
            this.resumeTimer();
        } else {
            // Fresh start
            const now = Date.now();
            this.state = {
                isRunning: true,
                isPaused: false,
                originalStartTime: now,
                lastResumeTime: now,
                totalPausedDuration: 0
            };
            this.resumeTimer();
        }
        
        this.saveState();
        this.updateButtons();
        this.updateSessionInfo('Tracking läuft...');
        this.updateMiniStatus('Läuft');
        this.elements.container?.classList.add('tracking');
        this.elements.container?.classList.remove('paused', 'stopped');
        this.miniElements.tracker?.classList.add('tracking');
        this.miniElements.tracker?.classList.remove('paused');
        this.updateMiniVisibility();
    }

    pause() {
        if (!this.state.isRunning || this.state.isPaused) return;
        
        // Calculate how long we've been running since last resume
        const runningTime = Date.now() - this.state.lastResumeTime;
        this.state.totalPausedDuration = (this.state.totalPausedDuration || 0);
        this.state.pauseStartTime = Date.now();
        this.state.isPaused = true;
        
        this.stopTimer();
        this.saveState();
        this.updateButtons();
        this.updateSessionInfo('Pausiert');
        this.updateMiniStatus('Pausiert');
        this.elements.container?.classList.add('paused');
        this.elements.container?.classList.remove('tracking');
        this.miniElements.tracker?.classList.add('paused');
        this.miniElements.tracker?.classList.remove('tracking');
    }

    stop() {
        if (!this.state.isRunning) return;

        // Calculate total session time
        let sessionTime;
        if (this.state.isPaused) {
            // Was paused, calculate time up to pause
            sessionTime = this.state.pauseStartTime - this.state.originalStartTime - (this.state.totalPausedDuration || 0);
        } else {
            // Was running, calculate total time
            sessionTime = Date.now() - this.state.originalStartTime - (this.state.totalPausedDuration || 0);
        }

        // Add to today's total
        if (sessionTime > 0) {
            this.addToTodayTotal(sessionTime);
        }
        
        // Reset state
        this.state = {
            isRunning: false,
            isPaused: false,
            originalStartTime: null,
            lastResumeTime: null,
            totalPausedDuration: 0,
            pauseStartTime: null
        };
        
        this.stopTimer();
        this.saveState();
        this.updateButtons();
        this.updateDisplay();
        this.updateSessionInfo('Session beendet');
        this.updateMiniStatus('Beendet');
        this.loadTodayStats();
        
        this.elements.container?.classList.add('stopped');
        this.elements.container?.classList.remove('tracking', 'paused');
        this.miniElements.tracker?.classList.remove('tracking', 'paused');
        
        // Reset to ready state after 2 seconds
        setTimeout(() => {
            this.updateSessionInfo('Bereit zum Starten');
            this.updateMiniStatus('Bereit');
            this.elements.container?.classList.remove('stopped');
            this.updateMiniVisibility();
        }, 2000);
    }

    startTimer() {
        this.intervalId = setInterval(() => {
            this.updateDisplay();
        }, 1000);
    }

    stopTimer() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    resumeTimer() {
        if (this.state.isRunning && !this.state.isPaused) {
            // When resuming from pause or page reload
            if (this.state.isPaused === false && this.state.pauseStartTime) {
                // We just resumed from a pause, add the pause duration to total
                this.state.totalPausedDuration = (this.state.totalPausedDuration || 0) + 
                    (this.state.lastResumeTime - this.state.pauseStartTime);
                this.state.pauseStartTime = null;
            }
            
            this.displayStartTime = Date.now();
            this.startTimer();
        }
    }

    updateDisplay() {
        if (!this.elements.display && !this.miniElements.display) return;

        let displayTime = 0;
        
        if (this.state.isRunning) {
            if (this.state.isPaused) {
                // Show time up to pause
                displayTime = this.state.pauseStartTime - this.state.originalStartTime - (this.state.totalPausedDuration || 0);
            } else {
                // Show current running time
                const now = Date.now();
                displayTime = now - this.state.originalStartTime - (this.state.totalPausedDuration || 0);
            }
        }

        // Ensure we don't show negative time
        displayTime = Math.max(0, displayTime);
        const formattedTime = this.formatTime(displayTime);
        
        // Update main display
        if (this.elements.display) {
            this.elements.display.textContent = formattedTime;
        }
        
        // Update mini display
        if (this.miniElements.display) {
            this.miniElements.display.textContent = formattedTime;
        }
    }

    updateButtons() {
        if (!this.elements.startBtn) return;

        if (this.state.isRunning && !this.state.isPaused) {
            // Running
            this.elements.startBtn.disabled = true;
            this.elements.pauseBtn.disabled = false;
            this.elements.stopBtn.disabled = false;
        } else if (this.state.isRunning && this.state.isPaused) {
            // Paused
            this.elements.startBtn.disabled = false;
            this.elements.startBtn.querySelector('.btn-text').textContent = 'Resume';
            this.elements.pauseBtn.disabled = true;
            this.elements.stopBtn.disabled = false;
        } else {
            // Stopped
            this.elements.startBtn.disabled = false;
            this.elements.startBtn.querySelector('.btn-text').textContent = 'Start';
            this.elements.pauseBtn.disabled = true;
            this.elements.stopBtn.disabled = true;
        }
    }

    updateSessionInfo(info) {
        if (this.elements.sessionInfo) {
            this.elements.sessionInfo.textContent = info;
        }
    }

    formatTime(milliseconds) {
        const totalSeconds = Math.floor(milliseconds / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    formatTimeShort(milliseconds) {
        const totalMinutes = Math.floor(milliseconds / 60000);
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}h`;
        }
        return `${minutes}m`;
    }

    loadState() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : {
                isRunning: false,
                isPaused: false,
                originalStartTime: null,
                lastResumeTime: null,
                totalPausedDuration: 0,
                pauseStartTime: null
            };
        } catch (e) {
            console.warn('TimeTracker: Could not load state', e);
            return {
                isRunning: false,
                isPaused: false,
                originalStartTime: null,
                lastResumeTime: null,
                totalPausedDuration: 0,
                pauseStartTime: null
            };
        }
    }

    saveState() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.state));
        } catch (e) {
            console.warn('TimeTracker: Could not save state', e);
        }
    }

    addToTodayTotal(sessionTime) {
        const today = new Date().toDateString();
        const dailyKey = `${this.storageKey}_daily_${today}`;
        
        try {
            const existing = localStorage.getItem(dailyKey);
            const totalTime = existing ? parseInt(existing) + sessionTime : sessionTime;
            localStorage.setItem(dailyKey, totalTime.toString());
        } catch (e) {
            console.warn('TimeTracker: Could not save daily total', e);
        }
    }

    loadTodayStats() {
        if (!this.elements.todayTime) return;

        const today = new Date().toDateString();
        const dailyKey = `${this.storageKey}_daily_${today}`;
        
        try {
            const totalTime = localStorage.getItem(dailyKey);
            const timeMs = totalTime ? parseInt(totalTime) : 0;
            this.elements.todayTime.textContent = this.formatTimeShort(timeMs);
        } catch (e) {
            console.warn('TimeTracker: Could not load daily stats', e);
            this.elements.todayTime.textContent = '0m';
        }
    }

    updateMiniStatus(status) {
        if (this.miniElements.status) {
            this.miniElements.status.textContent = status;
        }
    }

    updateMiniVisibility() {
        if (!this.miniElements.tracker) return;
        
        const sidebarVisible = this.elements.sidebar && this.elements.sidebar.classList.contains('active');
        const isActive = this.state.isRunning; // Nur zeigen wenn aktiv (läuft oder pausiert)
        
        console.log('Mini visibility check:', { sidebarVisible, isActive, sidebarExists: !!this.elements.sidebar });
        
        if (!sidebarVisible && isActive) {
            this.miniElements.tracker.classList.add('visible');
            console.log('Showing mini tracker');
        } else {
            this.miniElements.tracker.classList.remove('visible');
            console.log('Hiding mini tracker');
        }
    }

    observeSidebarVisibility() {
        if (!this.elements.sidebar) return;
        
        // MutationObserver für Sidebar Klassen-Änderungen
        const observer = new MutationObserver(() => {
            this.updateMiniVisibility();
        });
        
        observer.observe(this.elements.sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });
        
        // Initial check
        this.updateMiniVisibility();
    }

    toggleSidebar() {
        // Versuche das Info Center zu öffnen/schließen
        if (this.elements.sidebar) {
            this.elements.sidebar.classList.toggle('active');
            
            // Auch den Toggle-Button aktualisieren
            const toggleBtn = document.querySelector('.info-center-toggle');
            if (toggleBtn) {
                toggleBtn.classList.toggle('active');
            }
            
            // State in localStorage speichern
            const isOpen = this.elements.sidebar.classList.contains('active');
            localStorage.setItem('infoCenterOpen', isOpen ? '1' : '0');
            
            this.updateMiniVisibility();
        }
    }
}

// Initialize when DOM is ready (mit REDAXO rex:ready Support)
$(document).on('rex:ready', function() {
    // Nur initialisieren wenn TimeTracker-Elemente vorhanden sind
    if (document.getElementById('timeDisplay')) {
        if (!window.InfoCenterTimeTracker || !window.InfoCenterTimeTracker.initialized) {
            new InfoCenterTimeTracker();
        } else {
            // Bereits existierend, nur refreshen
            window.InfoCenterTimeTracker.refreshAfterPjax();
        }
    }
});

// Fallback für Vanilla JS (ohne jQuery)
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('timeDisplay') && !window.InfoCenterTimeTracker) {
        new InfoCenterTimeTracker();
    }
});

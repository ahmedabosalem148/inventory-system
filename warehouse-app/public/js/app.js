/**
 * Inventory System - Main JavaScript Module
 * Shared utilities and functions for RTL Arabic inventory system
 */

(function() {
    'use strict';

    // Audio Context for fallback beep
    let audioContext = null;
    let isAudioInitialized = false;

    /**
     * Initialize audio context (must be called after user interaction)
     */
    function initAudio() {
        if (isAudioInitialized) return;
        
        try {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            isAudioInitialized = true;
        } catch (e) {
            console.warn('Web Audio API not supported:', e);
        }
    }

    /**
     * Get CSRF token from meta tag
     */
    function getCsrf() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    /**
     * Generate fallback beep sound using Web Audio API
     */
    function beep() {
        if (!audioContext) {
            initAudio();
        }
        
        if (!audioContext) {
            console.warn('⚠️ Cannot generate beep: Audio context not available');
            
            // Fallback: try vibration on mobile devices
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
                console.log('📳 Using vibration as fallback');
            }
            return;
        }

        try {
            console.log('🎼 Generating beep sound...');
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
            
            console.log('✅ Beep generated successfully');
        } catch (e) {
            console.warn('❌ Failed to generate beep:', e);
            
            // Last resort: vibration
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
                console.log('📳 Using vibration as last resort');
            }
        }
    }

    /**
     * Play alert sound for low stock (tries low stock audio file, falls back to beep)
     */
    function playAlert() {
        playSpecificAlert('low_stock');
    }

    /**
     * Play sound for new product added
     */
    function playNewProductSound() {
        playSpecificAlert('new_product');
    }

    /**
     * Play specific alert sound based on type
     */
    function playSpecificAlert(type) {
        if (isMuted()) {
            console.log('� Audio is muted, skipping alert');
            return;
        }
        
        let audioFile, description;
        
        switch(type) {
            case 'low_stock':
                audioFile = 'sounds/low_stock_alert.wav';
                description = 'تنبيه انخفاض المخزون';
                break;
            case 'new_product':
                audioFile = 'sounds/new_product_added.mp3';
                description = 'صوت إضافة منتج جديد';
                break;
            default:
                audioFile = 'sounds/alert.mp3';
                description = 'صوت تنبيه عام';
        }
        
        console.log(`🔊 Playing ${description}: ${audioFile}`);
        
        const audio = new Audio(audioFile);
        
        audio.addEventListener('canplaythrough', () => {
            console.log(`✅ Audio loaded: ${audioFile}`);
            audio.play().catch((error) => {
                console.warn(`❌ Failed to play ${audioFile}:`, error.message);
                console.log('🎼 Using fallback beep');
                beep();
            });
        });
        
        audio.addEventListener('error', () => {
            console.warn(`❌ Failed to load ${audioFile}`);
            console.log('🎼 Using fallback beep');
            beep();
        });
        
        // Load the audio file
        audio.load();
    }

    /**
     * Check if audio is muted (from localStorage)
     */
    function isMuted() {
        return localStorage.getItem('muted') === 'true';
    }

    /**
     * Set mute state (saves to localStorage)
     */
    function setMuted(muted) {
        localStorage.setItem('muted', muted ? 'true' : 'false');
        updateAudioControlIcon();
    }

    /**
     * Update audio control icon based on mute state
     */
    function updateAudioControlIcon() {
        const control = document.querySelector('.audio-control');
        if (control) {
            control.textContent = isMuted() ? '🔇' : '🔔';
            control.title = isMuted() ? 'تشغيل الصوت' : 'كتم الصوت';
        }
    }

    /**
     * Show toast notification
     */
    function toast(message, type = 'success') {
        const container = document.querySelector('.toast-container');
        if (!container) {
            console.warn('Toast container not found');
            return;
        }

        const toastEl = document.createElement('div');
        toastEl.className = `toast ${type}`;
        toastEl.textContent = message;
        
        container.appendChild(toastEl);
        
        // Trigger show animation
        setTimeout(() => {
            toastEl.classList.add('show');
        }, 10);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            toastEl.classList.remove('show');
            setTimeout(() => {
                if (toastEl.parentNode) {
                    toastEl.parentNode.removeChild(toastEl);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Enhanced fetch with JSON handling and CSRF
     */
    async function fetchJSON(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrf(),
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, mergedOptions);
            const data = await response.json();
            
            return {
                ok: response.ok,
                status: response.status,
                data: response.ok ? data : null,
                error: response.ok ? null : data.message || 'حدث خطأ غير متوقع'
            };
        } catch (error) {
            return {
                ok: false,
                status: 0,
                data: null,
                error: 'خطأ في الاتصال: ' + error.message
            };
        }
    }

    /**
     * Format number with Arabic separators
     */
    function formatNumber(num) {
        return new Intl.NumberFormat('ar-EG').format(num);
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Initialize app on DOM ready
     */
    function init() {
        console.log('🚀 Initializing App...');
        
        // Initialize audio on first user interaction
        document.addEventListener('click', initAudio, { once: true });
        document.addEventListener('keydown', initAudio, { once: true });
        document.addEventListener('touchstart', initAudio, { once: true });
        
        // Update audio control icon
        updateAudioControlIcon();
        
        // Audio control click handler
        const audioControl = document.querySelector('.audio-control, [data-testid="btn-toggle-mute"]');
        if (audioControl) {
            audioControl.addEventListener('click', (e) => {
                e.preventDefault();
                initAudio(); // Ensure audio is initialized
                setMuted(!isMuted());
                
                // Test sound when unmuting
                if (!isMuted()) {
                    setTimeout(() => {
                        console.log('🔊 Testing sound after unmute...');
                        playAlert();
                    }, 100);
                }
            });
        }
        
        // Add test sound button (for debugging)
        if (window.location.search.includes('debug=1')) {
            addTestSoundButton();
        }
        
        console.log('✅ App initialized');
    }
    
    /**
     * Add test sound button for debugging
     */
    function addTestSoundButton() {
        const testBtn = document.createElement('button');
        testBtn.textContent = '🔊 اختبار الصوت';
        testBtn.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 9999; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;';
        testBtn.onclick = () => {
            initAudio();
            console.log('🎯 Manual sound test triggered');
            playAlert();
        };
        document.body.appendChild(testBtn);
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose public API
    window.App = {
        getCsrf,
        beep,
        playAlert,
        playNewProductSound,
        playSpecificAlert,
        isMuted,
        setMuted,
        toast,
        fetchJSON,
        formatNumber,
        debounce,
        init
    };

})();

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
     * Refresh CSRF token
     */
    function refreshCsrfToken() {
        fetch('/csrf-token', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.token) {
                // Update meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', data.token);
                }
                
                // Update all forms
                const csrfInputs = document.querySelectorAll('input[name="_token"]');
                csrfInputs.forEach(input => {
                    input.value = data.token;
                });
                
                console.log('✅ CSRF token refreshed');
            }
        })
        .catch(error => {
            console.warn('❌ Failed to refresh CSRF token:', error);
        });
    }

    /**
     * Setup form submission with CSRF protection
     */
    function setupFormProtection() {
        const forms = document.querySelectorAll('form[method="POST"]');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const tokenInput = form.querySelector('input[name="_token"]');
                if (tokenInput && !tokenInput.value) {
                    e.preventDefault();
                    refreshCsrfToken();
                    setTimeout(() => {
                        form.submit();
                    }, 500);
                }
            });
        });
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
        
        // Use absolute path to prevent issues with current page path
        const audioPath = audioFile.startsWith('/') ? audioFile : `/${audioFile}`;
        console.log(`🔊 Loading audio from: ${audioPath}`);
        const audio = new Audio(audioPath);
        
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
     * Show success alert with sound
     */
    function showSuccessAlert(message) {
        // Play success sound
        playSpecificAlert('new_product');
        
        // Show styled alert
        const alertDiv = document.createElement('div');
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            font-size: 16px;
            font-weight: bold;
            max-width: 400px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            direction: rtl;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        `;
        alertDiv.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px;">✅</span>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Remove after 4 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 4000);
        
        // Also show browser alert
        alert('✅ ' + message);
    }

    /**
     * Show error alert with details
     */
    function showErrorAlert(message, details = null) {
        // Play error beep
        beep(3); // 3 beeps for error
        
        let fullMessage = '❌ ' + message;
        if (details) {
            fullMessage += '\n\nتفاصيل الخطأ:\n' + details;
        }
        
        // Show styled alert
        const alertDiv = document.createElement('div');
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            font-size: 16px;
            font-weight: bold;
            max-width: 500px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            direction: rtl;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        `;
        alertDiv.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <span style="font-size: 24px;">❌</span>
                <div>
                    <div style="margin-bottom: 8px;">${message}</div>
                    ${details ? `<div style="font-size: 14px; font-weight: normal; background: rgba(0,0,0,0.1); padding: 10px; border-radius: 4px; white-space: pre-wrap;">${details}</div>` : ''}
                </div>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Remove after 8 seconds (longer for errors)
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 8000);
        
        // Also show browser alert
        alert(fullMessage);
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
        const csrfToken = getCsrf();
        console.log('🔑 CSRF Token:', csrfToken);
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
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
            console.log('📡 Making request to:', url, 'with options:', mergedOptions);
            const response = await fetch(url, mergedOptions);
            const data = await response.json();
            
            console.log('📥 Raw response:', {
                ok: response.ok,
                status: response.status,
                statusText: response.statusText,
                data: data
            });
            
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
        
        // Setup form CSRF protection
        setupFormProtection();
        
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

    /**
     * Refresh inventory - reload current page
     */
    function refreshInventory() {
        const container = document.getElementById('inventory-container');
        if (container) {
            container.innerHTML = '<div class="loading">جاري إعادة تحميل المخزون...</div>';
        }
        // Reload the page to refresh data
        window.location.reload();
    }

    // Expose public API
    window.App = {
        getCsrf,
        refreshCsrfToken,
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
        showSuccessAlert,
        showErrorAlert,
        refreshInventory,
        init
    };

    // Also expose refreshInventory globally for onclick handlers
    window.refreshInventory = refreshInventory;

})();

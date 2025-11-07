(function() {
    'use strict';
    
    // Respect Do Not Track
    if (navigator.doNotTrack === '1' || window.doNotTrack === '1') {
        return;
    }
    
    // Get site ID from script tag
    const script = document.currentScript;
    const siteId = script.getAttribute('data-site-id');
    
    if (!siteId) {
        console.error('Analytics: data-site-id attribute is required');
        return;
    }
    
    // Get endpoint URL from script src
    const scriptSrc = script.src;
    const endpoint = scriptSrc.replace('/track.js', '/app/track.php');
    
    // Track page view
    function trackPageView() {
        const data = {
            site_id: siteId,
            path: window.location.pathname + window.location.search,
            referrer: document.referrer || '',
            screen_width: window.screen.width,
            screen_height: window.screen.height
        };
        
        // Use sendBeacon if available (more reliable)
        if (navigator.sendBeacon) {
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }
            navigator.sendBeacon(endpoint, formData);
        } else {
            // Fallback to fetch
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
                keepalive: true
            }).catch(() => {}); // Silently fail
        }
    }
    
    // Track on load
    if (document.readyState === 'complete') {
        trackPageView();
    } else {
        window.addEventListener('load', trackPageView);
    }
    
    // Track on history changes (for SPAs)
    const originalPushState = history.pushState;
    const originalReplaceState = history.replaceState;
    
    history.pushState = function() {
        originalPushState.apply(this, arguments);
        trackPageView();
    };
    
    history.replaceState = function() {
        originalReplaceState.apply(this, arguments);
        trackPageView();
    };
    
    window.addEventListener('popstate', trackPageView);
})();

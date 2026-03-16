(function () {
  'use strict';

  const config = {
    endpoint: 'https://collector.cse135-kook.site/log',
    enableVitals: true,
    enableErrors: true,
    sampleRate: 1.0,
    debug: false,
    respectConsent: true,
    detectBots: true
  };

  let initialized = false;
  let blocked = false;
  const customData = {};
  let userId = null;
  const plugins = [];
  const reportedErrors = new Set();
  let errorCount = 0;
  const MAX_ERRORS = 10;
  let imagesEnabled = null;
  let cssEnabled = null;

  const vitals = { lcp: null, cls: 0, inp: null };

  let pageShowTime = Date.now();
  let totalVisibleTime = 0;

  function round(n) {
    return Math.round(n * 100) / 100;
  }

  function merge(dst, src) {
    for (const key of Object.keys(src)) dst[key] = src[key];
    return dst;
  }

  function hasConsent() {
    if (navigator.globalPrivacyControl) return false;
    const cookies = document.cookie.split(';');
    for (const c of cookies) {
      const cookie = c.trim();
      if (cookie.indexOf('analytics_consent=') === 0) {
        return cookie.split('=')[1] === 'true';
      }
    }
    return false;
  }

  function isBot() {
    if (navigator.webdriver) return true;
    const ua = navigator.userAgent;
    if (/HeadlessChrome|PhantomJS|Lighthouse/i.test(ua)) return true;
    if (/Chrome/.test(ua) && !window.chrome) return true;
    if (window._phantom || window.__nightmare || window.callPhantom) return true;
    return false;
  }

  function isSampled() {
    if (config.sampleRate >= 1.0) return true;
    if (config.sampleRate <= 0) return false;
    const key = '_collector_sample';
    let val = sessionStorage.getItem(key);
    if (val === null) {
      val = Math.random();
      sessionStorage.setItem(key, String(val));
    } else {
      val = parseFloat(val);
    }
    return val < config.sampleRate;
  }

  function getSessionId() {
    let sid = sessionStorage.getItem('_collector_sid');
    if (!sid) {
      sid = Math.random().toString(36).substring(2) + Date.now().toString(36);
      sessionStorage.setItem('_collector_sid', sid);
    }
    return sid;
  }

  function getNetworkInfo() {
    if (!('connection' in navigator)) return {};
    const conn = navigator.connection;
    return {
      effectiveType: conn.effectiveType,
      downlink: conn.downlink,
      rtt: conn.rtt,
      saveData: conn.saveData
    };
  }

  function detectImagesEnabled(timeoutMs) {
    return new Promise((resolve) => {
      try {
        const img = new Image();
        let done = false;

        const finish = (val) => {
          if (done) return;
          done = true;
          resolve(val);
        };

        img.onload = () => finish(true);
        img.onerror = () => finish(false);

        img.src =
          'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

        setTimeout(() => finish(true), timeoutMs || 750);
      } catch {
        resolve(false);
      }
    });
  }

  function detectCssEnabled() {
    try {
      const style = document.createElement('style');
      style.textContent = '.__cse135_css_test{position:absolute;left:123px;top:0px;}';
      document.head.appendChild(style);

      const el = document.createElement('div');
      el.className = '__cse135_css_test';
      document.body.appendChild(el);

      const left = window.getComputedStyle(el).left;

      el.remove();
      style.remove();

      return left === '123px';
    } catch {
      return false;
    }
  }

  function getTechnographics() {
    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      cookiesEnabled: navigator.cookieEnabled,
      javaScriptEnabled: true,
      imagesEnabled: imagesEnabled,
      cssEnabled: cssEnabled,
      viewportWidth: window.innerWidth,
      viewportHeight: window.innerHeight,
      screenWidth: window.screen.width,
      screenHeight: window.screen.height,
      pixelRatio: window.devicePixelRatio,
      cores: navigator.hardwareConcurrency || 0,
      memory: navigator.deviceMemory || 0,
      network: getNetworkInfo(),
      colorScheme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
    };
  }

  function getNavigationTiming() {
    const entries = performance.getEntriesByType('navigation');
    if (!entries.length) return {};
    const n = entries[0];
    return {
      dnsLookup: round(n.domainLookupEnd - n.domainLookupStart),
      tcpConnect: round(n.connectEnd - n.connectStart),
      tlsHandshake: n.secureConnectionStart > 0 ? round(n.connectEnd - n.secureConnectionStart) : 0,
      ttfb: round(n.responseStart - n.requestStart),
      download: round(n.responseEnd - n.responseStart),
      domInteractive: round(n.domInteractive - n.fetchStart),
      domComplete: round(n.domComplete - n.fetchStart),
      loadEvent: round(n.loadEventEnd - n.fetchStart),
      fetchTime: round(n.responseEnd - n.fetchStart),
      transferSize: n.transferSize,
      headerSize: n.transferSize - n.encodedBodySize
    };
  }

  function getResourceSummary() {
    const resources = performance.getEntriesByType('resource');
    const summary = {
      script: { count: 0, totalSize: 0, totalDuration: 0 },
      link: { count: 0, totalSize: 0, totalDuration: 0 },
      img: { count: 0, totalSize: 0, totalDuration: 0 },
      font: { count: 0, totalSize: 0, totalDuration: 0 },
      fetch: { count: 0, totalSize: 0, totalDuration: 0 },
      xmlhttprequest: { count: 0, totalSize: 0, totalDuration: 0 },
      other: { count: 0, totalSize: 0, totalDuration: 0 }
    };
    resources.forEach((r) => {
      const type = summary[r.initiatorType] ? r.initiatorType : 'other';
      summary[type].count++;
      summary[type].totalSize += r.transferSize || 0;
      summary[type].totalDuration += r.duration || 0;
    });
    return { totalResources: resources.length, byType: summary };
  }

  function initWebVitals() {
    try {
      const lcpObs = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        if (entries.length) vitals.lcp = round(entries[entries.length - 1].startTime);
      });
      lcpObs.observe({ type: 'largest-contentful-paint', buffered: true });
    } catch {}

    try {
      const clsObs = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
          if (!entry.hadRecentInput) vitals.cls = round(vitals.cls + entry.value);
        });
      });
      clsObs.observe({ type: 'layout-shift', buffered: true });
    } catch {}

    try {
      const inpObs = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
          if (vitals.inp === null || entry.duration > vitals.inp) vitals.inp = round(entry.duration);
        });
      });
      inpObs.observe({ type: 'event', buffered: true, durationThreshold: 16 });
    } catch {}
  }

  function getWebVitals() {
    return { lcp: vitals.lcp, cls: vitals.cls, inp: vitals.inp };
  }

  function queueForRetry(payload) {
    try {
      const queue = JSON.parse(sessionStorage.getItem('_collector_retry') || '[]');
      if (queue.length >= 50) return;
      queue.push(payload);
      sessionStorage.setItem('_collector_retry', JSON.stringify(queue));
    } catch {}
  }

  function processRetryQueue() {
    try {
      const queue = JSON.parse(sessionStorage.getItem('_collector_retry') || '[]');
      if (!queue.length) return;
      sessionStorage.removeItem('_collector_retry');
      queue.forEach((payload) => send(payload));
    } catch {}
  }

  function send(payload) {
    const markSupported = typeof performance.mark === 'function';
    if (markSupported) performance.mark('collector_send_start');

    if (config.debug) {
      console.log('[Collector] Debug payload:', payload);
      return;
    }

    if (!config.endpoint) {
      console.warn('[Collector] No endpoint configured');
      return;
    }

    const json = JSON.stringify(payload);
    let sent = false;

    if (navigator.sendBeacon) {
      sent = navigator.sendBeacon(config.endpoint, new Blob([json], { type: 'application/json' }));
    }

    if (!sent) {
      fetch(config.endpoint, {
        method: 'POST',
        body: json,
        headers: { 'Content-Type': 'application/json' },
        keepalive: true
      }).catch(() => queueForRetry(payload));
    }

    if (markSupported) {
      performance.mark('collector_send_end');
      performance.measure('collector_send', 'collector_send_start', 'collector_send_end');
    }

    window.dispatchEvent(new CustomEvent('collector:beacon', { detail: payload }));
  }

  function collect(type) {
    let payload = {
      type: type || 'pageview',
      url: window.location.href,
      title: document.title,
      referrer: document.referrer,
      timestamp: new Date().toISOString(),
      session: getSessionId(),
      technographics: getTechnographics(),
      timing: getNavigationTiming(),
      resources: getResourceSummary(),
      vitals: getWebVitals(),
      errorCount: errorCount,
      customData: customData
    };

    if (userId) payload.userId = userId;

    for (const plugin of plugins) {
      if (typeof plugin.beforeSend === 'function') {
        const result = plugin.beforeSend(payload);
        if (result === false) return;
        if (result && typeof result === 'object') payload = result;
      }
    }

    send(payload);
    window.dispatchEvent(new CustomEvent('collector:payload', { detail: payload }));
  }

  function reportError(errorData) {
    if (errorCount >= MAX_ERRORS) return;

    const key = `${errorData.type}:${errorData.message || ''}:${errorData.source || ''}:${errorData.line || ''}`;
    if (reportedErrors.has(key)) return;
    reportedErrors.add(key);
    errorCount++;

    send({
      type: 'error',
      error: errorData,
      timestamp: new Date().toISOString(),
      url: window.location.href,
      session: getSessionId()
    });

    window.dispatchEvent(new CustomEvent('collector:error', { detail: { errorData, count: errorCount } }));
  }

  function initErrorTracking() {
    window.addEventListener(
      'error',
      (event) => {
        if (event instanceof ErrorEvent) {
          reportError({
            type: 'js-error',
            message: event.message,
            source: event.filename,
            line: event.lineno,
            column: event.colno,
            stack: event.error ? event.error.stack : '',
            url: window.location.href
          });
        } else {
          const target = event.target;
          if (target && (target.tagName === 'IMG' || target.tagName === 'SCRIPT' || target.tagName === 'LINK')) {
            reportError({
              type: 'resource-error',
              tagName: target.tagName,
              src: target.src || target.href || '',
              url: window.location.href
            });
          }
        }
      },
      true
    );

    window.addEventListener('unhandledrejection', (event) => {
      const reason = event.reason;
      reportError({
        type: 'promise-rejection',
        message: reason instanceof Error ? reason.message : String(reason),
        stack: reason instanceof Error ? reason.stack : '',
        url: window.location.href
      });
    });
  }

  function initTimeOnPage() {
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        totalVisibleTime += Date.now() - pageShowTime;

        const exitPayload = {
          type: 'page_exit',
          url: window.location.href,
          timeOnPage: totalVisibleTime,
          vitals: getWebVitals(),
          errorCount: errorCount,
          timestamp: new Date().toISOString(),
          session: getSessionId()
        };

        for (const plugin of plugins) {
          if (typeof plugin.onExit === 'function') plugin.onExit(exitPayload);
        }

        send(exitPayload);
      } else {
        pageShowTime = Date.now();
      }
    });
  }

  const activityQueue = [];
  const ACTIVITY_FLUSH_MS = 5000;
  const MAX_ACTIVITY_BATCH = 50;

  let lastActivityAt = Date.now();
  let idle = false;
  let idleStart = 0;

  let lastMouseSent = 0;
  const MOUSE_THROTTLE_MS = 100;

  let lastScrollSent = 0;
  const SCROLL_THROTTLE_MS = 200;

  function activity(type, data) {
    if (!initialized || blocked) return;

    activityQueue.push({
      type,
      data: data || {},
      timestamp: new Date().toISOString(),
      url: window.location.href,
      session: getSessionId()
    });

    if (activityQueue.length >= MAX_ACTIVITY_BATCH) flushActivity('max_batch');
  }

  function flushActivity(reason) {
    if (!initialized || blocked) return;
    if (!activityQueue.length) return;

    const batch = activityQueue.splice(0, activityQueue.length);

    send({
      type: 'activity_batch',
      reason: reason || 'interval',
      url: window.location.href,
      timestamp: new Date().toISOString(),
      session: getSessionId(),
      events: batch
    });
  }

  function markActive() {
    const now = Date.now();

    if (idle) {
      idle = false;
      const idleEnd = now;
      activity('idle_end', {
        endedAt: new Date(idleEnd).toISOString(),
        durationMs: idleEnd - idleStart
      });
    }

    lastActivityAt = now;
  }

  function initActivityTracking() {
    activity('page_enter', { enteredAt: new Date().toISOString() });

    document.addEventListener(
      'mousemove',
      (e) => {
        markActive();
        const now = Date.now();
        if (now - lastMouseSent < MOUSE_THROTTLE_MS) return;
        lastMouseSent = now;
        activity('mousemove', { x: e.clientX, y: e.clientY });
      },
      { passive: true }
    );

    document.addEventListener(
      'click',
      (e) => {
        markActive();
        activity('click', {
          x: e.clientX,
          y: e.clientY,
          button: e.button,
          target: e.target && e.target.tagName ? e.target.tagName : ''
        });
      },
      { passive: true }
    );

    window.addEventListener(
      'scroll',
      () => {
        markActive();
        const now = Date.now();
        if (now - lastScrollSent < SCROLL_THROTTLE_MS) return;
        lastScrollSent = now;
        activity('scroll', { scrollX: window.scrollX, scrollY: window.scrollY });
      },
      { passive: true }
    );

    document.addEventListener(
      'keydown',
      (e) => {
        markActive();
        activity('keydown', { key: e.key, code: e.code });
      },
      { passive: true }
    );

    document.addEventListener(
      'keyup',
      (e) => {
        markActive();
        activity('keyup', { key: e.key, code: e.code });
      },
      { passive: true }
    );

    setInterval(() => {
      const now = Date.now();
      const idleFor = now - lastActivityAt;
      if (!idle && idleFor >= 2000) {
        idle = true;
        idleStart = lastActivityAt;
        activity('idle_start', { startedAt: new Date(idleStart).toISOString() });
      }
    }, 500);

    setInterval(() => flushActivity('interval'), ACTIVITY_FLUSH_MS);

    window.addEventListener('pagehide', () => {
      activity('page_leave', { leftAt: new Date().toISOString() });
      flushActivity('pagehide');
    });

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') flushActivity('visibility_hidden');
    });
  }

  function processQueue() {
    const queue = window._cq || [];
    for (const args of queue) {
      const method = args[0];
      const params = args.slice(1);
      if (typeof publicAPI[method] === 'function') publicAPI[method](...params);
    }
    window._cq = {
      push: (args) => {
        const method = args[0];
        const params = args.slice(1);
        if (typeof publicAPI[method] === 'function') publicAPI[method](...params);
      }
    };
  }

  const publicAPI = {
    init: function (options) {
      if (initialized) {
        console.warn('[Collector] Already initialized');
        return;
      }

      if (typeof performance.mark === 'function') performance.mark('collector_init_start');

      if (options) merge(config, options);

      if (config.respectConsent && !hasConsent()) {
        blocked = true;
        initialized = true;
        return;
      }

      if (config.detectBots && isBot()) {
        blocked = true;
        initialized = true;
        return;
      }

      if (!isSampled()) {
        blocked = true;
        initialized = true;
        return;
      }

      initialized = true;

      if (config.enableVitals) initWebVitals();
      if (config.enableErrors) initErrorTracking();
      initTimeOnPage();
      initActivityTracking();

      processRetryQueue();

      const sendInitialPageview = () => {
        cssEnabled = detectCssEnabled();
        detectImagesEnabled(750).then((val) => {
          imagesEnabled = val;
          setTimeout(() => collect('pageview'), 0);
        });
      };

      if (document.readyState === 'complete') {
        sendInitialPageview();
      } else {
        window.addEventListener('load', sendInitialPageview, { once: true });
      }

      if (typeof performance.mark === 'function') {
        performance.mark('collector_init_end');
        performance.measure('collector_init', 'collector_init_start', 'collector_init_end');
      }
    },

    track: function (eventName, eventData) {
      if (!initialized || blocked) return;
      const payload = {
        type: 'event',
        event: eventName,
        data: eventData || {},
        timestamp: new Date().toISOString(),
        url: window.location.href,
        session: getSessionId(),
        customData: customData
      };
      if (userId) payload.userId = userId;
      send(payload);
    },

    set: function (key, value) {
      customData[key] = value;
    },

    identify: function (id) {
      userId = id;
    },

    use: function (plugin) {
      if (!plugin || typeof plugin !== 'object') {
        console.warn('[Collector] Invalid plugin');
        return;
      }
      plugins.push(plugin);
      if (typeof plugin.init === 'function') plugin.init(config);
    }
  };

  processQueue();

  window.__collector = {
    getNavigationTiming,
    getResourceSummary,
    getTechnographics,
    getWebVitals,
    getSessionId,
    getNetworkInfo,
    reportError,
    collect,
    hasConsent,
    isBot,
    isSampled,
    getErrorCount: () => errorCount,
    getConfig: () => config,
    isBlocked: () => blocked,
    api: publicAPI
  };
})();

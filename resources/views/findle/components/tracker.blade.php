@if(config('findle.enabled', true) && config('findle.tracking_method') === 'javascript')
  <script>
    (function () {
      'use strict';

      if (typeof window.FindleTracker !== 'undefined') {
        return;
      }

      window.FindleTracker = {
        config: {
          tokenEndpoint: '{{ route('findle.api.token') }}',
          trackEndpoint: '{{ route('findle.api.track') }}',
          async: {{ config('findle.js_tracking.async_load', true) ? 'true' : 'false' }},
          requireInteraction: {{ config('findle.js_tracking.require_interaction', true) ? 'true' : 'false' }}
        },

        token: null,
        tracked: false,

        init: function () {
          if (this.tracked || this.isBot()) {
            return;
          }

          const self = this;

          this.whenVisible(function () {
            if (self.config.requireInteraction) {
              self.onFirstInteraction(function () {
                self.track();
              });

              return;
            }

            if (self.config.async) {
              self.loadAsync();
            } else {
              self.track();
            }
          });
        },

        whenVisible: function (callback) {
          if (document.visibilityState !== 'hidden') {
            callback();
            return;
          }

          const onVisibilityChange = function () {
            if (document.visibilityState === 'hidden') {
              return;
            }

            document.removeEventListener('visibilitychange', onVisibilityChange);
            callback();
          };

          document.addEventListener('visibilitychange', onVisibilityChange);
        },

        onFirstInteraction: function (callback) {
          // Scroll is left out on purpose: the browser's scroll restoration fires it without any user input.
          const events = ['pointerdown', 'pointermove', 'keydown', 'touchstart', 'wheel'];

          const onInteraction = function (event) {
            if (!event.isTrusted) {
              return;
            }

            events.forEach(function (eventName) {
              window.removeEventListener(eventName, onInteraction, true);
            });

            callback();
          };

          events.forEach(function (eventName) {
            window.addEventListener(eventName, onInteraction, {capture: true, passive: true});
          });
        },

        isBot: function () {
          const userAgent = navigator.userAgent.toLowerCase();
          const botPatterns = [
            'bot', 'spider', 'crawl', 'slurp', 'scraper', 'scanner',
            'checker', 'monitor', 'validator', 'fetcher', 'collector',
            'gatherer', 'extractor', 'indexer', 'parser', 'googlebot',
            'bingbot', 'yandexbot', 'baiduspider', 'duckduckbot', 'headless',
            'lighthouse', 'preview', 'prerender'
          ];

          if (navigator.webdriver) {
            return true;
          }

          if (window.callPhantom || window._phantom || window.__nightmare) {
            return true;
          }

          if (!navigator.languages || navigator.languages.length === 0) {
            return true;
          }

          for (let i = 0; i < botPatterns.length; i++) {
            if (userAgent.indexOf(botPatterns[i]) !== -1) {
              return true;
            }
          }

          return false;
        },

        loadAsync: function () {
          const self = this;

          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
              setTimeout(function () {
                self.track();
              }, 100);
            });
          } else {
            setTimeout(function () {
              self.track();
            }, 100);
          }
        },

        getToken: function (callback) {
          const self = this;

          if (this.token) {
            callback(this.token);
            return;
          }

          const xhr = new XMLHttpRequest();
          xhr.open('GET', this.config.tokenEndpoint, true);
          xhr.setRequestHeader('Accept', 'application/json');
          xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

          xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
              if (xhr.status === 200) {
                try {
                  const response = JSON.parse(xhr.responseText);
                  self.token = response.token;
                  callback(self.token);
                } catch (e) {
                  console.error('Findle: Failed to parse token response');
                }
              } else {
                console.error('Findle: Failed to get tracking token');
              }
            }
          };

          xhr.send();
        },

        normalizeUrl: function (url) {
          try {
            const urlObj = new URL(url);
            
            let path = urlObj.pathname.replace(/\/+/g, '/');
            
            if (path.length > 1) {
              path = path.replace(/\/$/, '');
            }
            
            urlObj.pathname = path;
            return urlObj.toString();
          } catch (e) {
            return url.replace(/\/+/g, '/').replace(/\/$/, '');
          }
        },

        track: function () {
          if (this.tracked) {
            return;
          }

          const self = this;

          this.getToken(function (token) {
            if (!token) {
              return;
            }

            const data = {
              url: self.normalizeUrl(window.location.href),
              referrer: document.referrer || null,
              token: token
            };

            const xhr = new XMLHttpRequest();
            xhr.open('POST', self.config.trackEndpoint, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onreadystatechange = function () {
              if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                  self.tracked = true;
                }
              }
            };

            xhr.send(JSON.stringify(data));
          });
        }
      };

      window.FindleTracker.init();
    })();
  </script>
@endif

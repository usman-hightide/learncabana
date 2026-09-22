jQuery( document ).ready( function ( $ ) {
  /**
   * TrustedLogin Gravity Forms field controller.
   *
   * Hardened for bulletproof UX (see e2e/popup-messages.spec.ts):
   *   A. Popup-blocked fallback — shows inline error + manual "open in new tab" link
   *      when window.open() returns null (popup blocker).
   *   B. Origin normalization — compares URL.origin on both sides so trailing
   *      slash / scheme drift doesn't silently drop messages.
   *   C. Visible errors — all failure paths produce user-facing text, never
   *      console-only.
   *   D. Real `revoked` message — listens for the client's `revoked` message
   *      instead of assuming revoke completed after 2 seconds.
   *   E. Relaxed URL validation — uses `new URL()` so ports, paths, and
   *      localhost are accepted.
   *   F. Double-click dedup — focuses existing popup instead of opening a
   *      second one.
   *   G. Popup-closed-without-grant — shows "Didn't complete — please try
   *      again" when the popup is closed before a granted/revoked message
   *      arrives.
   *
   * Also handles new client error message types:
   *   - grant_error  { code, message }
   *   - revoke_error { code, message }
   */
  class TLAccess {
    constructor() {
      this.$grantAccess = $( '.tl-grant-access' );
      this.$logoWrapper = this.$grantAccess.find( '.tl-logo' );
      this.$urlField    = this.$grantAccess.find( '.tl-site-url' );
      this.$keyNode     = this.$grantAccess.find( '.tl-site-key' );
      this.$fieldValue  = this.$grantAccess.find( '.tl-field-value' );
      this.$submitBtn   = this.$grantAccess.find( 'input[type="submit"]' );
      this.progressBar  = this.$grantAccess.find( '.tl-progress' );
      this.originUrl    = encodeURIComponent( window.location.origin );
      this.namespace    = this.$urlField.data( 'namespace' );
      this.i18n         = tl_field_vars;

      // Tracks whether the current popup flow received any progress message
      // from the client. If the popup is closed before we see one, that's a
      // user-abandoned / errored state and we should communicate it (fix G).
      this.popupFlowReceivedProgress = false;

      // Fallback timer used only if the client's `revoked` message never
      // arrives (e.g. the client is an older build without revoked support).
      this.revokeFallbackTimer = null;

      this.ensureErrorSlot();
      this.ensureManualKeyEntry();
      this.initForm();
    }

    /**
     * Ensure the field has a container to display user-facing errors and
     * a popup-blocked fallback link. Idempotent.
     */
    ensureErrorSlot() {
      this.$errorSlot = this.$grantAccess.find( '.tl-error-message' );
      if ( ! this.$errorSlot.length ) {
        this.$errorSlot = $( '<div>', {
          class:         'tl-error-message',
          role:          'alert',
          'aria-live':   'polite',
          style:         'display:none',
        } );
        this.$grantAccess.append( this.$errorSlot );
      }

      this.$fallbackSlot = this.$grantAccess.find( '.tl-fallback-link' );
      if ( ! this.$fallbackSlot.length ) {
        this.$fallbackSlot = $( '<div>', {
          class: 'tl-fallback-link',
          style: 'display:none',
        } );
        this.$grantAccess.append( this.$fallbackSlot );
      }
    }

    /**
     * Build the manual access-key entry surface once. Idempotent — safe to
     * call repeatedly. The surface stays hidden until a grant attempt fails
     * and showManualKeyEntry() reveals it; the user can then paste the key
     * they captured from the popup's "Access granted" screen back into the
     * parent form when postMessage didn't make it across.
     */
    ensureManualKeyEntry() {
      this.$manualKeyEntry = this.$grantAccess.find( '.tl-manual-key-entry' );
      if ( this.$manualKeyEntry.length ) {
        return;
      }

      const inputId = 'tl-manual-key-input-' + this.namespace;

      this.$manualKeyEntry = $( '<div>', {
        class: 'tl-manual-key-entry',
        style: 'display:none',
      } );

      const $label = $( '<label>', {
        for:  inputId,
        text: this.i18n.manualKeyLabel || 'Or enter the access key manually:',
        class: 'tl-manual-key-label',
      } );

      const $hint = $( '<p>', {
        class: 'tl-manual-key-hint',
        text:  this.i18n.manualKeyHint || 'Copy the access key shown on the client site after granting access, then paste it here.',
      } );

      const $input = $( '<input>', {
        type:        'text',
        id:          inputId,
        class:       'tl-manual-key-input',
        autocomplete: 'off',
        spellcheck:  'false',
        // 64 lowercase hex chars — language-independent, so hardcoded
        // here rather than going through wp_localize_script. Translators
        // shouldn't be asked to translate `0123456789abcdef…`.
        placeholder: '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
      } );

      const $button = $( '<button>', {
        type:  'button',
        class: 'button tl-manual-key-submit',
        text:  this.i18n.manualKeySubmit || 'Use this key',
      } );

      this.$manualKeyEntry.append( $label, $hint, $input, $button );
      this.$grantAccess.append( this.$manualKeyEntry );

      $button.on( 'click', () => this.applyManualKey() );
      $input.on( 'keydown', ( e ) => {
        // Don't bubble Enter to the parent form's submit handler.
        if ( e.which === 13 ) {
          e.preventDefault();
          this.applyManualKey();
        }
      } );
    }

    /**
     * Reveal the manual entry surface and focus its input. Called from
     * any grant-failure branch so the user can recover without
     * starting the popup flow over.
     */
    showManualKeyEntry() {
      this.ensureManualKeyEntry();
      this.$manualKeyEntry.show();
      // Focus the input on the next tick — `.show()` is synchronous but
      // some browsers ignore focus on an element that became visible
      // in the same task.
      setTimeout( () => this.$manualKeyEntry.find( 'input' ).trigger( 'focus' ), 0 );
    }

    hideManualKeyEntry() {
      if ( this.$manualKeyEntry && this.$manualKeyEntry.length ) {
        this.$manualKeyEntry.hide();
        this.$manualKeyEntry.find( 'input' ).val( '' );
      }
    }

    /**
     * Validate + apply a manually-entered access key. The URL is taken
     * from the existing site-URL input (the user already typed it to
     * start the grant attempt). On success this drives the same UI
     * transition as a postMessage-delivered key.
     */
    applyManualKey() {
      const key = ( this.$manualKeyEntry.find( 'input' ).val() || '' ).trim();
      if ( ! key ) {
        this.showError( this.i18n.manualKeyMissing || 'Please paste the access key before continuing.' );
        return;
      }
      // Site URL must already be populated/valid — applyManualKey is a
      // recovery flow for an attempted grant, so the user has typed it.
      if ( ! this.checkUrl() ) {
        this.showError( this.i18n.invalidUrl || 'Please enter a valid URL (e.g. https://example.com).' );
        return;
      }
      this.setLoginSite();
      this.clearError();
      this.hideManualKeyEntry();
      this.updateKey( key );
      this.showKey();
    }

    showError( message ) {
      this.$errorSlot.text( message ).show();
      this.$grantAccess.addClass( 'tl-has-error' );
    }

    clearError() {
      this.$errorSlot.text( '' ).hide();
      this.$fallbackSlot.empty().hide();
      this.$grantAccess.removeClass( 'tl-has-error' );
    }

    inputState( state ) {
      if ( state === 'disabled' ) {
        this.$urlField.attr( 'readonly', true );
        this.$submitBtn.attr( 'disabled', true );
      } else if ( state === 'enabled' ) {
        this.$urlField.attr( 'readonly', false );
        this.$submitBtn.attr( 'disabled', false );
      }
    }

    changeButtonText( text ) {
      this.$submitBtn.prop( 'value', text );
    }

    changeToRevoke( text ) {
      this.$submitBtn.attr( 'disabled', false ).addClass( 'can-revoke' );
      this.changeButtonText( text );
    }

    changeToGrant( text ) {
      this.inputState( 'enabled' );
      this.changeButtonText( text );
      this.$submitBtn.removeClass( 'can-revoke' );
    }

    showKey() {
      this.changeButtonText( this.i18n.accessGranted );
      const hasAccessToSite = this.i18n.hasAccessToSite.replace( '[login_site]', this.loginSite ).replace( '[vendor]', this.i18n.vendorName );
      this.$grantAccess.find( '.tl-header label' ).text( hasAccessToSite );
      this.$logoWrapper.removeClass( 'tl-loading' ).addClass( 'tl-granted' );
      this.resetProgress();
      setTimeout( () => this.$grantAccess.find( '.tl-footer' ).slideDown( 500 ), 500 );
      setTimeout( () => this.changeToRevoke( this.i18n.revokeAccess ), 2000 );
    }

    updateKey( key ) {
      this.$keyNode.text( key );
      this.updateFieldValue();
    }

    updateFieldValue() {
      const key = this.$keyNode.text();
      this.$fieldValue.val(
        key ? this.$urlField.val() + ' \u{1F517} ' + this.$keyNode.text() : this.$urlField.val(),
      );
    }

    hideKey() {
      clearInterval( this.popupCloseInterval );
      this.changeButtonText( this.i18n.accessRevoked );
      this.$grantAccess
        .find( '.tl-footer' ).slideUp( 500 ).end()
        .find( '.tl-header label' ).text( this.i18n.grantAccessWithTl ).end()
        .find( '.tl-header svg:last-child' ).show().end()
        .find( '.tl-header svg:first-child' ).hide().end();
      this.updateKey( '' );
      this.changeToGrant( this.i18n.grantAccess );
    }

    showProgress() {
      this.progressInterval = setInterval( () => {
        const width = parseInt( this.progressBar[ 0 ].style.width );
        width < 100 ? this.progressBar.width( `${ width + 1 }%` ) : '';
      }, 20 );

      setTimeout( () => clearInterval( this.progressInterval ), 2500 );
    }

    resetProgress() {
      clearInterval( this.popupCloseInterval );
      clearInterval( this.progressInterval );
      this.progressBar.width( '0%' );
    }

    grantOrRevoke() {
      this.clearError();
      // Drop any stale manual-entry surface from a prior failure — if the
      // user is retrying the popup flow we don't want two paths racing.
      this.hideManualKeyEntry();
      if ( ! this.checkUrl() ) {
        this.showError( this.i18n.invalidUrl || 'Please enter a valid URL (e.g. https://example.com).' );
        return;
      }
      this.setLoginSite();
      const canRevoke = this.$submitBtn.hasClass( 'can-revoke' );
      canRevoke ? this.revokeAccess() : this.grantAccess();
    }

    setLoginSite() {
      // Normalize loginSite to a URL.origin so it compares cleanly with
      // event.origin in listenPopupEvents().
      try {
        this.loginSite = new URL( this.$urlField.val() ).origin;
      } catch ( e ) {
        this.loginSite = this.removeTrailingSlash( this.$urlField.val() );
      }
    }

    getTrustedLoginUrl( type = 'grant' ) {
      let url = `${ this.loginSite }/wp-login.php?action=trustedlogin&ns=${ this.namespace }&origin=${ this.originUrl }`;

      if ( 'revoke' === type ) {
        url += '&revoking=true';
      }
      return url;
    }

    /**
     * Strict URL check — the input must be a proper absolute http(s) URL.
     * Used when the user actually tries to grant access (submission path)
     * so we only post real, parseable URLs to the client site.
     */
    checkUrl() {
      const raw = ( this.$urlField.val() || '' ).trim();
      if ( ! raw ) {
        this.$urlField.addClass( 'tl-error' );
        return false;
      }
      try {
        const parsed = new URL( raw );
        if ( parsed.protocol !== 'http:' && parsed.protocol !== 'https:' ) {
          throw new Error( 'Unsupported protocol: ' + parsed.protocol );
        }
        if ( ! parsed.hostname ) {
          throw new Error( 'Missing hostname' );
        }
        this.$urlField.removeClass( 'tl-error' );
        return true;
      } catch ( e ) {
        this.$urlField.addClass( 'tl-error' );
        return false;
      }
    }

    /**
     * Loose "looks plausible" check — used to toggle the submit button's
     * disabled state as the user types. Accepts bare hostnames ("example.com",
     * "mysite.local", "localhost:8080") by prepending https:// before parsing.
     *
     * Deliberately lenient: the strict checkUrl() still runs on actual click,
     * so invalid input can't slip through — but the user gets responsive
     * feedback while typing rather than having to remember `https://`.
     */
    isUrlPlausible() {
      const raw = ( this.$urlField.val() || '' ).trim();
      if ( ! raw ) {
        return false;
      }
      const candidate = /^https?:\/\//i.test( raw ) ? raw : 'https://' + raw;
      try {
        const parsed = new URL( candidate );
        if ( parsed.protocol !== 'http:' && parsed.protocol !== 'https:' ) {
          return false;
        }
        // Hostname must contain at least one dot OR be a known local-style
        // host (localhost, *.local, *.test, etc.). Prevents single-char
        // typos ("a", "h") from enabling the button.
        const host = parsed.hostname;
        if ( ! host ) {
          return false;
        }
        return host.includes( '.' ) || host === 'localhost' || /\.(local|test|internal)$/i.test( host );
      } catch ( e ) {
        return false;
      }
    }

    resetJobs() {
      this.$logoWrapper.removeClass( 'tl-loading' );
      const canRevoke = this.$submitBtn.hasClass( 'can-revoke' );
      canRevoke ? this.changeToRevoke( this.i18n.revokeAccess ) : this.changeToGrant( this.i18n.grantAccess );
    }

    animateLogo() {
      this.$logoWrapper.removeClass( 'tl-granted' ).addClass( 'tl-loading' );
    }

    grantAccess() {
      this.animateLogo();
      this.changeButtonText( this.i18n.granting );
      this.inputState( 'disabled' );
      this.openAccessPopup();
    }

    /**
     * Open the client popup OR fall back to a manual link if blocked.
     * Fixes A + F + G.
     */
    openAccessPopup( type = 'grant' ) {
      // F: if a popup is already open, focus it instead of opening another.
      if ( this.popup && ! this.popup.closed ) {
        try {
          this.popup.focus();
        } catch ( e ) { /* cross-origin focus may throw; ignore */ }
        return;
      }

      this.popupFlowReceivedProgress = false;

      // Popup dimensions — was 800x600, which truncated the client's
      // auth screen (the wp-login logo header + grant card + access-key
      // panel + footer all live on one page). Cap at 1024x820 so the
      // post-grant "Site Access Key" view fits without internal
      // scrolling on typical laptop screens; fall back to the available
      // screen rectangle minus a small margin when the display itself
      // is smaller than the cap. `availWidth/availHeight` excludes the
      // OS dock + menu bar, so the centering math doesn't position the
      // popup behind the dock on macOS.
      const screenW = window.screen.availWidth  || window.screen.width;
      const screenH = window.screen.availHeight || window.screen.height;
      const width   = Math.min( 1024, screenW - 40 );
      const height  = Math.min( 820, screenH - 80 );
      const left    = Math.max( 0, ( screenW - width ) / 2 );
      const top     = Math.max( 0, ( screenH - height ) / 2 );
      const params  = `width=${ width },height=${ height },scrollbars=yes,resizable=yes,left=${ left },top=${ top }`;
      const url     = this.getTrustedLoginUrl( type );

      this.popup = window.open( url, 'PopupWindow', params );

      // A: Popup blocked — show a friendly fallback with a manual link.
      if ( ! this.popup ) {
        this.showError( this.i18n.popupBlocked || 'Your browser blocked the popup. Click the link below to grant access in a new tab.' );

        const $link = $( '<a>', {
          href:   url,
          target: '_blank',
          rel:    'opener',
          class:  'tl-fallback-link-anchor',
          text:   this.i18n.openManually || 'Open grant-access page in a new tab',
        } );

        // When the fallback link is activated it opens as a new tab in which
        // window.opener is set — so postMessage from the client still reaches
        // this opener. When focus returns here, clear the pending error.
        $link.on( 'click', () => {
          setTimeout( () => this.clearError(), 500 );
        } );

        this.$fallbackSlot.empty().append( $link ).show();

        // Offer the manual-key path too: a target="_blank" tab that loses
        // its opener relationship (or finishes after the parent reloads)
        // can't postMessage back. The user can still copy the key off the
        // client's "Access granted" screen and paste it here.
        if ( 'grant' === type ) {
          this.showManualKeyEntry();
        }

        // Restore UI state so the user can retry after unblocking popups.
        this.resetJobs();
        return;
      }

      // Watchdog: if popup closes before any message arrives, tell the user
      // to try again (fix G).
      this.popupCloseInterval = setInterval( () => {
        if ( this.popup && this.popup.closed ) {
          clearInterval( this.popupCloseInterval );
          if ( ! this.popupFlowReceivedProgress ) {
            this.showError( this.i18n.popupClosedEarly || 'The grant window was closed before completing. Please try again.' );
            // The grant may actually have succeeded on the client side
            // before the postMessage made it back. Surface manual entry
            // so the user can paste the key off the granted-access screen
            // instead of running the whole flow again.
            if ( 'grant' === type ) {
              this.showManualKeyEntry();
            }
          }
          this.resetJobs();
        }
      }, 500 );
    }

    removeTrailingSlash( url ) {
      return url.replace( /\/$/, '' );
    }

    revokeAccess() {
      this.animateLogo();
      this.inputState( 'disabled' );
      this.changeButtonText( this.i18n.revoking );
      this.openAccessPopup( 'revoke' );
    }

    async copyToClipboard( text ) {
      try {
        await navigator.clipboard.writeText( text );
        return true;
      } catch ( err ) {
        console.error( 'Failed to copy text to clipboard:', err );
        return false;
      }
    }

    showCopiedMessage() {
      const $copied = this.$grantAccess.find( '.tl-key-copied-message' );
      $copied.addClass( 'tl-key-copied' );
      setTimeout( function () {
        $copied.removeClass( 'tl-key-copied' );
      }, 2000 );
    }

    initCopyKey() {
      const $copyButton = this.$grantAccess.find( '#tl-copy-key' );
      const $key        = this.$grantAccess.find( '.tl-site-key' );

      $copyButton.on( 'click', () => {
        this.copyToClipboard( $key.text() ).then( ( success ) => {
          if ( success ) {
            this.showCopiedMessage();
            // wp-a11y is not always enqueued on the page rendering this
            // form (e.g. WPForms / Elementor frontends). Guard before
            // calling so the copy still succeeds even when speak() is
            // unavailable.
            if ( typeof wp !== 'undefined' && wp.a11y && typeof wp.a11y.speak === 'function' ) {
              wp.a11y.speak( this.i18n.textCopied, 'assertive' );
            }
          }
        } );
      } );
    }

    /**
     * Listen for postMessage events from the client popup.
     *
     * Origin check: the message's full ORIGIN (scheme + host + port) must
     * match the login site URL's origin exactly. Pinned by tl-field.test.js.
     */
    listenPopupEvents() {
      window.addEventListener( 'message', ( event ) => {
        if ( ! this.loginSite ) {
          return;
        }

        let messageOrigin;
        try {
          messageOrigin = new URL( event.origin ).origin;
        } catch ( e ) {
          return;
        }

        let expectedOrigin;
        try {
          expectedOrigin = new URL( this.loginSite ).origin;
        } catch ( e ) {
          return;
        }

        if ( messageOrigin !== expectedOrigin ) {
          if ( window.tl_field_debug ) {
            console.info( '[tl-field] Dropped message from', event.origin, 'expected origin', expectedOrigin );
          }
          return;
        }

        if ( ! event.data || ! event.data.type ) {
          return;
        }

        // A real message means the popup is responsive — clear pending error.
        this.clearError();
        this.popupFlowReceivedProgress = true;

        switch ( event.data.type ) {
          case 'granting':
            this.showProgress();
            break;

          case 'granted':
            if ( event.data.key ) {
              this.hideManualKeyEntry();
              this.updateKey( event.data.key );
              this.showKey();
            } else {
              // C: visible error when key is missing (was console-only).
              this.showError( this.i18n.keyMissing || 'The grant response did not include an access key. Please try again.' );
              // The client confirmed the grant, but didn't ship the key
              // back. The user can copy it from the popup before closing
              // it and paste it here.
              this.showManualKeyEntry();
              this.resetJobs();
            }
            if ( this.popup ) {
              try { this.popup.close(); } catch ( e ) {}
            }
            break;

          case 'grant_error':
            this.showError( event.data.message || this.i18n.grantFailed || 'Grant failed. Please try again.' );
            this.resetProgress();
            // If the user has already copied a key from the client and
            // the failure is a non-fatal one (e.g. user-account collision
            // surfaced after the key was generated), the manual path is
            // still useful as a fallback.
            this.showManualKeyEntry();
            this.resetJobs();
            if ( this.popup ) {
              try { this.popup.close(); } catch ( e ) {}
            }
            break;

          case 'revoking':
            this.showProgress();
            // D: wait for the client's `revoked` message rather than a 2s
            // setTimeout. The fallback timer fires only if we never hear back.
            clearTimeout( this.revokeFallbackTimer );
            // Reset the completion flag at the start of each revoke cycle so
            // the fallback timer's `! this.revokeCompleted` check works on
            // subsequent grant→revoke rounds.
            this.revokeCompleted = false;
            this.revokeFallbackTimer = setTimeout( () => {
              if ( ! this.revokeCompleted ) {
                this.showError( this.i18n.revokeTimeout || 'The revoke request is taking longer than expected. If access is still active, please try again.' );
                this.resetProgress();
                this.resetJobs();
                if ( this.popup ) {
                  try { this.popup.close(); } catch ( e ) {}
                }
              }
            }, 15000 );
            break;

          case 'revoked':
            // D: definitive confirmation from the client.
            clearTimeout( this.revokeFallbackTimer );
            this.revokeCompleted = true;
            this.resetProgress();
            this.hideKey();
            this.$logoWrapper.removeClass( 'tl-loading' );
            if ( this.popup ) {
              try { this.popup.close(); } catch ( e ) {}
            }
            break;

          case 'revoke_error':
            clearTimeout( this.revokeFallbackTimer );
            this.showError( event.data.message || this.i18n.revokeFailed || 'Revoke failed. Please try again.' );
            this.resetProgress();
            this.resetJobs();
            if ( this.popup ) {
              try { this.popup.close(); } catch ( e ) {}
            }
            break;
        }
      } );
    }

    initForm() {
      this.$urlField.on( 'keypress', ( e ) => {
        if ( e.which === 13 ) {
          this.grantOrRevoke();
        }
      } ).on( 'input blur', () => {
        // Enable the submit button as soon as the URL "looks plausible"
        // (bare hostnames accepted). The stricter checkUrl() runs again on
        // actual click — if the user typed something that only looked
        // valid, they'll get the red error border at submit time.
        const plausible = this.isUrlPlausible();
        if ( plausible ) {
          this.$submitBtn.removeAttr( 'disabled' );
          this.$urlField.removeClass( 'tl-error' );
        } else {
          this.$submitBtn.attr( 'disabled', 'disabled' );
          // Only flag the red error border if the user typed SOMETHING —
          // don't yell at an empty field the moment it loses focus.
          if ( ( this.$urlField.val() || '' ).trim() ) {
            this.$urlField.addClass( 'tl-error' );
          } else {
            this.$urlField.removeClass( 'tl-error' );
          }
        }
      } );

      this.$submitBtn.click( ( e ) => {
        e.preventDefault();
        this.grantOrRevoke();
      } );

      this.listenPopupEvents();
      this.initCopyKey();
    }
  }

  new TLAccess();
} );

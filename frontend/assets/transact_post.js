var TransactWP = { hasTransact: false };

(function() {
    // Check if cookies are enabled
    document.cookie = `xsact_test_cookie=true; expires=${new Date(Date.now() + 100000000).toUTCString()}; SameSite=Lax;`;
    const cookieValue = document.cookie
        .split('; ')
        .find(row => row.startsWith('xsact_test_cookie='));
    if(cookieValue) {
        document.cookie = "xsact_test_cookie=true; expires=Fri, 31 Dec 2010 23:59:59 GMT; SameSite=Lax;";
    } else {
        console.error('Cannot set cookies');
        jQuery('#no_cookies_transact').css('display', 'block');
        jQuery('.transact_button_container').css('display', 'none');
    }

    if(!transact_params.transact_path) {
        throw Error('transact path not set');
    }
    var transactJs = document.createElement('script');
    var maxTries = 10;

    transactJs.onload = function() {
        var tries = 0;
        const transactInitCheck = setInterval(function() {
            if(typeof window.transactApi !== 'undefined') {
                init();
                clearInterval(transactInitCheck);
            } else {
                tries++;
                if(tries > maxTries) {
                    jQuery('#no_transact').css('display', 'block');
                    jQuery('.transact_button_container').css('display', 'none');
                    clearInterval(transactInitCheck);
                }
            }
        }, 250);
    };
    transactJs.onerror = function() {
        jQuery('#no_transact').css('display', 'block');
    }

    transactJs.setAttribute('src', transact_params.transact_path);
    document.head.appendChild(transactJs);

    function init() {
        transactApi.setFrontendUrl(transact_params.purchase_url);

        if(transact_params.theme) {
            transactApi.setThemeParams({
                bgcolor: transact_params.theme.background_color,
                tcolor: transact_params.theme.text_color
            });
        }

        transactApi.getCurrentPromo('en', function(arg, result) {
            let promoBlock = jQuery('#transact_promo');

            if (promoBlock && promoBlock.length) {
                if (result) {
                    promoBlock.css('color', transact_params.theme.background_color);
                    // this text comes from transact.io who we trust
                    // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
                    promoBlock.html(result.text);
                } else {
                    promoBlock.css('display', 'none');
                }
            }
        });

        TransactWP.hasTransact = true;
    }

    var purchase_token = {}; // token used for buying single item
    var subscribe_token = {}; // subscription token
    var donate_token = {}; // donate token
    var custom_redirect = ''; // Custom redirect (after donation for instance)

    var enabledPurchase = false;
    var enabledSubscribe = false;
    var loadingPurchase = false;
    var loadingSubscribe = false;
    var purchasedInSession = false;

    var transact_modal;
    var transact_backdrop;

    TransactWP = {
        setDonateAmount: setDonateAmount,
        doPurchase: doPurchase,
        doDonate: doDonate,
        doOauth: doOauth,
        doSubscription: doSubscription
    };


    if (transact_params.redirect_after_donation) {
        custom_redirect = transact_params.redirect_after_donation;
    }

    if (transact_params.donation == 1) {
        getDonationTokenAjaxCall(transact_params.price || 1);
    } else {
        if(document.getElementById('button_purchase_single')) {
            enabledPurchase = true;
            loadingPurchase = true;

            jQuery('#button_purchase_single_loading').css('display', 'block');
            jQuery('#button_purchase_single').css('display', 'none');

            var tokenUrl = transact_params.ajaxurl + 'token/' + transact_params.post_id;
            if(transact_params.affiliate_id) {
                tokenUrl += '/' + transact_params.affiliate_id;
            }
            ajaxGet(tokenUrl)
                .done(function(data) {
                    loadingPurchase = false;
                    jQuery('#button_purchase_single_loading').css('display', 'none');
                    jQuery('#button_purchase_single').css('display', 'block');

                    // If purchase token loads and subscribe token is not loaded yet, display the subscribe loading spinner
                    if (enabledSubscribe && loadingSubscribe) {
                        jQuery('#button_purchase_subscription_loading').css('display', 'block');
                    }

                    purchase_token = data.token;
                    //transactApi.setToken(data.token);
                })
                .fail(function(data) {
                    jQuery('#button_purchase_single_loading').css('display', 'none');
                    console.warn('Failed to get Transact token');
                });

        }

        // If subscription button is on the site, get subscription token too
        if(document.getElementById('button_purchase_subscription')) {
            enabledSubscribe = true;
            loadingSubscribe = true;

            // Only show the subscribe token spinner initially if purchase is disabled, so we don't show two spinners.
            if (!enabledPurchase) {
                jQuery('#button_purchase_subscription_loading').css('display', 'block');
            }
            jQuery('#button_purchase_subscription').css('display', 'none');

            var subscribeTokenUrl = transact_params.ajaxurl + 'subscription/' + transact_params.post_id;
            if(transact_params.affiliate_id) {
                subscribeTokenUrl += '/' + transact_params.affiliate_id;
            }
            ajaxGet(subscribeTokenUrl)
                .done(function(data) {
                    loadingSubscribe = false;
                    jQuery('#button_purchase_subscription_loading').css('display', 'none');
                    jQuery('#button_purchase_subscription').css('display', 'block');

                    subscribe_token = data.token;
                })
                .fail(function(data) {
                    jQuery('#button_purchase_subscription_loading').css('display', 'none');
                    console.warn('Failed to get Transact token');
                });

        }
    }
/*
    function getPremiumContent(synchronous) {
        let comment_form_wrapper = jQuery('#transact-comments');

        var premiumUrl = transact_params.ajaxurl + 'premium/' + transact_params.post_id;
        let result = ajaxGet(premiumUrl, synchronous);

        if(synchronous) {
            appendPremiumContent(result);
        } else {
            result
                .fail(function(data) {
                    console.error('Could not load premium state', data);
                })
                .done(data => {
                    appendPremiumContent(data);
                });
        }

        function appendPremiumContent(data) {
            if(data.is_premium) {
                gtmEvent('view-premium-content', { 'viewingPastPurchase': !purchasedInSession });

                // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
                jQuery('#transact_content').html(jQuery.parseHTML(data.content));

                // Get comments
                var commentsUrl = transact_params.ajaxurl + 'comments_template/' + transact_params.post_id;
                ajaxGet(commentsUrl)
                    .done(function(data) {
                        // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
                        comment_form_wrapper.append(jQuery.parseHTML(data.content));
                    })
                    .fail(function(data) {
                        console.warn('Could not fetch comments', data);
                    });

                reloadKnownScripts();
            } else {
                if(data.has_premium) {
                    jQuery('.transact-purchase_button').addClass('active');
                }

                gtmEvent('view-preview-content');
            }
        }
    }
*/

    /**
     * Gets a new donate token every time the user changes donation value
     */
    function setDonateAmount() {
        var donate = Math.round(jQuery('#donate_val').val() * 100);
        if (!donate || donate < 1) {
            alert('Invalid Donation Amount');
            return;
        }

        getDonationTokenAjaxCall(donate, window.WP_REST_NONCE);
    }

    /**
     * AJAX call responsible of donation token
     */
    function getDonationTokenAjaxCall(donate) {
        jQuery('#button_purchase_donation_loading').css('display', 'block');
        jQuery('#button_purchase_donation').css('display', 'none');

        var donateUrl = transact_params.ajaxurl + 'donation/' + transact_params.post_id +
            '/' + donate;
        if(transact_params.affiliate_id) {
            donateUrl += '/' + transact_params.affiliate_id;
        }
        ajaxGet(donateUrl)
            .done(function(data) {
                jQuery('#button_purchase_donation_loading').css('display', 'none');
                jQuery('#button_purchase_donation').css('display', 'block');

                donate_token = data.token;
                jQuery('#donation').removeAttr("disabled");
            })
            .fail(function(data) {
                jQuery('#button_purchase_donation_loading').css('display', 'none');
                jQuery('#donation').attr("disabled");
                console.warn('Failed to get Transact donate token');
            });
    }

    /**
     * onclick for purchase button
     * Will set up purchase token on transact
     * and get the callback
     */
    function doPurchase() {
        if (!TransactWP.hasTransact) {
            console.warn('Transact API not available');
            return;
        }
        transactApi.setToken(purchase_token);
        gtmEvent('start-purchase', { 'purchaseType': 'purchase' });
        transactApi.authorizeRedirect();
    }

    function doDonate() {
        if (!TransactWP.hasTransact) {
            console.warn('Transact API not available');
            return;
        }
        transactApi.setToken(donate_token);
        gtmEvent('start-purchase', { 'purchaseType': 'donation' });
        transactApi.authorizeRedirect();
    }

    /**
     * onclick for purchase button
     * Will set up subscription token on transact
     * and get the callback
     */
    function doSubscription() {
        if (!TransactWP.hasTransact) {
            console.warn('Transact API not available');
            return;
        }
        transactApi.setToken(subscribe_token);
        gtmEvent('start-purchase', { 'purchaseType': 'subscription' });
        transactApi.authorizeRedirect();
    }

    function doOauth(post_id) {
        console.log('doOauth_postd_id', post_id);
        const url='/wp-json/transact/v1/oauth/state?post_id=' + post_id + '&_=' + Date.now();
        document.getElementById('button_purchase_oauth').style.display= 'none';
        document.getElementById('button_purchase_oauth_loading').style.display= 'block';
        ajaxGet(url)
            .done(function(data) {
                console.log('response', data);
                const transact_url = data.url + '?redirect_uri=' + encodeURI(data.redirect)
                + '&response_type=code'
                + '&client_id=' + data.client_id
                + '&state=' + data.state
                + '&scope=email,name';
                console.log('transact_url', transact_url);
                location.assign(transact_url);
            })
            .fail(function(data) {
                document.getElementById('button_purchase_oauth_loading').style.display= 'none';
                document.getElementById('button_purchase_oauth').style.display= 'block';
                console.warn('Failed to get oauth', data);
            });

    }

    function parseUserInfo(data) {
        if (!data.userInfo) {
            return;
        }

        var validation_data = {};
        validation_data.post_id = transact_params.post_id;
        validation_data.token = data.t;
        var userInfo = transactApi.decodeUserInfo(data.userInfo);
        validation_data.user_email = userInfo.email;
        validation_data.user_display_name =
            userInfo.firstName && userInfo.lastName ? userInfo.firstName + ' ' + userInfo.lastName
            : userInfo.firstName ? userInfo.firstName
            : userInfo.lastName ? userInfo.lastName
            : userInfo.email;
        validation_data.user_xsact_id = userInfo.xsactId;

        return validation_data;
    }

    // TODO Does this need to be called?
    function loginSuccess(event) {
        if (event && event.data) {
            var validation_data = parseUserInfo(event.data);
            validation_data.check_premium = 1;

            ajaxPost(transact_params.ajaxurl + 'verify', validation_data)
                .done(function(resp_data) {
                    if (resp_data.is_premium) {
                        location.reload();
                    }
                })
                .fail(function(resp_data) {
                    console.warn('Error Response data:', resp_data);
                });
        }
    }

    // function purchaseFrameClosed(popup, event) {
    //     if(transact_modal && transact_modal.parentNode) {
    //         transact_modal.parentNode.removeChild(transact_modal);
    //         transact_modal = null;
    //     }
    //     if(transact_backdrop && transact_backdrop.parentNode) {
    //         transact_backdrop.parentNode.removeChild(transact_backdrop);
    //         transact_backdrop = null;
    //     }

    //     if (event && event.data && event.data.status !== 'cancelPurchase') {
    //         var validation_data = parseUserInfo(event.data);
    //         if(validation_data) {
    //             ajaxPost(transact_params.ajaxurl + 'verify', validation_data)
    //                 .done(function(resp_data) {
    //                     gtmEvent('complete-purchase');

    //                     purchasedInSession = true;
    //                     // Handles cookie
    //                     handleCookies(validation_data, resp_data);
    //                     // if custom redirect, send user to it, otherwise reload
    //                     if (custom_redirect.length > 0) {
    //                         // we set redirect here
    //                         // phpcs:ignore WordPressVIPMinimum.JS.Window.location
    //                         window.location = custom_redirect;
    //                     } else {
    //                         // getPremiumContent(false);
    //                         location.reload();
    //                     }
    //                 })
    //                 .fail(function(resp_data) {
    //                     console.warn('Error Response data:', resp_data);
    //                     jQuery('#button_purchase').html('purchase failed');
    //                 });
    //         }
    //     } else {
    //         gtmEvent('cancel-purchase');
    //     }
    // }

    function handleCookies(validation_data, resp_data)
    {
        // Set or Update Cookie
        if (resp_data.subscription == '1' || resp_data.subscription == 1) {
            handleSubscriptionCookies(resp_data);
        } else {
            handlePurchaseCookies(validation_data, resp_data);
        }
    }

    function handleSubscriptionCookies(resp_data)
    {
        var cookie = getCookie('wp_subscription_transact_');
        if (cookie != '') {
            cookie_array = JSON.parse(cookie);
            var new_cookie = {};
            new_cookie['expiration']  = resp_data.decoded.sub_expires;
            new_cookie['uid'] = resp_data.decoded.uid;
            new_cookie['bid'] = resp_data.decoded.bid;
            cookie_array.push(new_cookie);
            setCookie('wp_subscription_transact_', JSON.stringify(cookie_array), 365);
        } else {
            var new_cookie = {};
            new_cookie['expiration']  = resp_data.decoded.sub_expires;
            new_cookie['uid'] = resp_data.decoded.uid;
            new_cookie['bid'] = resp_data.decoded.bid;
            var cookies = [];
            cookies.push(new_cookie);
            setCookie('wp_subscription_transact_', JSON.stringify(cookies), 365);
        }
    }

    function handlePurchaseCookies(validation_data, resp_data)
    {
        if(!validation_data || !resp_data || !resp_data.decoded) {
            return;
        }
        var cookie = getCookie('wp_transact_');
        if (cookie != '') {
            cookie_array = JSON.parse(cookie);
            var new_cookie = {};
            new_cookie['id']  = validation_data.post_id;
            new_cookie['uid'] = resp_data.decoded.uid;
            cookie_array.push(new_cookie);
            setCookie('wp_transact_', JSON.stringify(cookie_array), 365);

        } else {
            var new_cookie = {};
            new_cookie['id']  = validation_data.post_id;
            new_cookie['uid'] = resp_data.decoded.uid;
            var cookies = [];
            cookies.push(new_cookie);

            setCookie('wp_transact_', JSON.stringify(cookies), 365);
        }
    }

    function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        var expires = "expires="+d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }

    function reloadKnownScripts() {
        document.body.querySelectorAll('script').forEach(script => {
            if(
                script.src.match(/tiled-gallery/) || script.src.match(/slideshow/)
            ) {
                var newScript = document.createElement('script');
                newScript.src = script.src;
                script.parentNode.removeChild(script);
                // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
                document.body.append(newScript);
            }
        });
    }

    function gtmEvent(name, addtlParams) {
        if(window.dataLayer !== undefined) {
            window.dataLayer.push(
                Object.assign(
                    {
                        'event': name,
                        'location': document.URL,
                        'postId': transact_params.post_id,
                        'postPrice': transact_params.price,
                    },
                    addtlParams || {}
                )
            );
        }
    }

    function ajaxGet(url, synchronous) {
        // Set the nonce token so we can get the user object in wordpress

        if(synchronous) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, false); // sync request
            if(window.WP_REST_NONCE) {
                xhr.setRequestHeader('X-WP-Nonce', window.WP_REST_NONCE);
            }
            xhr.send();
            xhr.responseJson = JSON.parse(xhr.response);
            return JSON.parse(xhr.response);
        } else {
            return jQuery.ajax({
                url: url,
                type: "GET",
                beforeSend: function(xhr){
                    if(window.WP_REST_NONCE) {
                        xhr.setRequestHeader('X-WP-Nonce', window.WP_REST_NONCE);
                    }
                },
                complete: function(result) {
                    console.log(result);
                    var newNonce = result.getResponseHeader('X-WP-Nonce');
                    if(newNonce) {
                        window.WP_REST_NONCE = newNonce;
                    }
                }
            });
        }
    }

    function ajaxPost(url, data) {
        return jQuery.ajax({
            url: url,
            type: "POST",
            data: data,
            beforeSend: function(xhr) {
                if(window.WP_REST_NONCE) {
                    xhr.setRequestHeader('X-WP-Nonce', window.WP_REST_NONCE);
                }
            },
            complete: function(result) {
                console.log(result);
                var newNonce = result.getResponseHeader('X-WP-Nonce');
                if(newNonce) {
                    window.WP_REST_NONCE = newNonce;
                }
            }
        });
    }
})();

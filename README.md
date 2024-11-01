## Transact Wordpress Plugin

This plugin integrates transact.io easily in your Wordpress installation.

## Installing this plugin from source code
```
1. npm is required to build the javascript install https://nodejs.org/en/download/
2. git clone https://gitlab.com/transact/transact-wordpress.git
3. cd transact-wordpress
4. npm install
5. npm run build
6. Copy the contents of dist/ to your wordpress  /plugins/transact folder
7. Log in into Wordpress Dashboard
8. Go to plugins
9. Click on activate
10. Go to the transact plugin settings, and input your Account ID and secret key
```

## Shortcode manual
1. You can set up a shortcode directly on your post content, this shortcode will override the default button.
2. Is possible to set up new texts for purchase and subscribe buttons, also choose between the 3 model buttons (as in the post settings)
3. You can choose to show "Only Purchase Button", "Only Subscribe button", "Purchase and Subscribe button".
4. Shortcode is `[transact_button]`
5. If you do not set up any option, it will use default transact buttons.
6. Options are:
   -  `button_text` : Text to display on purchase button
   -  `subscribe_text` : Text to display on subscribe button
   -  `button_type`
      * `1` = "Purchase and Subscribe button" = 1 (default)
      * `2` "Only Purchase Button" = 2
      * `3` "Only Subscribe button" = 3
      * `4` "Not used"
      * `5` "Sign in only required"
   -  `call_to_action`
      * `0` = don't display call to action text
      * `1` = Display call to action text (default)
   -  `display_promo`
      * `0` = don't display call to transact promotion text
      * `1` = Display call to promotion text (free credit text) (default)
7. Example:
   - `[transact_button button_text="purchase me" subscribe_text="subscribe to the site" button_type="1" call_to_action="1" display_promo="1"]`

## Google Tag Manager integration

If a google tag manager account ID is registered to the Transact plugin settings, the plugin will automatically connect to Google
Tag manager and emit events related to the user's purchasing and viewing activities.

### Events Emitted

- `start-purchase`
	- Emitted when purchase button is clicked
	- Also exposes a `purchaseType` flag, set to either `purchase`, `donation`, or `subscription`.
- `complete-purchase`
	- Emitted when a user purchases an article or subscription and the purchase window closes
- `cancel-purchase`
	- Emitted when a user cancels a purchase, closing the purchase window
- `view-premium-content`
	- Emitted when a user successfully views premium content an an article.
	- Also exposes a `viewingPastPurchase` flag, to show whether the user has purchased the post in the past and is just revisiting the premium content
- `view-preview-content`
	- Emitted when a user views preview content on a premium article, and does not have access to premium content.

All events return these parameters:
- `postId` The ID of the post
- `postPrice` The price of the post

### Datalayer Variables
`postPremiumOptions`
   - Array with possible values of `purchase`, `subscribe`, `donate`, or `free`.
   - Denotes what purchase buttons are available, or if the post is free.

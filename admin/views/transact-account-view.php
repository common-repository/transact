<div class="wrap">
    <h1>transact.io Account</h1>
</div>
<div class="clear"></div>

<?php if (get_transient(SETTING_VALIDATION_TRANSIENT)): ?>
    <div class="notice notice-success">
        <p>Your credentials are good</p>
    </div>
<?php else : ?>
    <div class="error">
        <p>Your credentials are wrong, please check them on transact.io</p>
    </div>
<?php endif; ?>

<?php if (!get_transient(SETTING_VALIDATION_SUBSCRIPTION_TRANSIENT)): ?>
    <div class="error">
        <p>You need to activate your subscription on transact.io</p>
    </div>
<?php endif; ?>

<form method="post" action="options.php">
    <?php
        settings_fields( 'transact-settings' );
        do_settings_sections( 'transact-settings' );
        submit_button();
    ?>
</form>
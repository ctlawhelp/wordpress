<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** SETTINGS REGISTRATION */
add_action( 'admin_init', function() {
    register_setting(
        'lgpm_settings_group',
        LGPM_OPTION_KEY,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'lgpm_sanitize_settings',
            'default'           => lgpm_default_settings(),
        )
    );

    add_settings_section(
        'lgpm_main_section',
        'PII Masker',
        function() {
            echo '<p>Mask selected fields in Gravity Forms entries after a set delay. Configure multiple forms below.</p>';
            echo '<p>Logs (if enabled) are written to <code>wp-content/uploads/' . esc_html( LGPM_LOG_BASENAME ) . '</code>.</p>';
            echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=lgpm-inspector' ) ) . '">Open Gravity Form Field Inspector</a></p>';
            echo '<p><em>Browser autocomplete/back‑button PII protection is automatically applied to configured forms (adds <code>autocomplete="off</code> to the form and fields).</em></p>';
        },
        'lgpm_settings_page'
    );

    // Global delay
    add_settings_field( 'delay_minutes', 'Global Delay (minutes)', function() {
        $s = lgpm_get_settings();
        printf(
            '<input type="number" name="%1$s[delay_minutes]" value="%2$d" min="1" class="small-text" /> ' .
            '<span class="description">Wait this many minutes after someone submits before masking their PII. (Example: <code>60</code> = 1 hour)</span>',
            esc_attr( LGPM_OPTION_KEY ),
            (int) $s['delay_minutes']
        );
    }, 'lgpm_settings_page', 'lgpm_main_section' );

    // Enable logging
    add_settings_field( 'enable_log', 'Enable logging', function() {
        $s = lgpm_get_settings();
        printf(
            '<label><input type="checkbox" name="%1$s[enable_log]" value="1" %2$s /> Write events to <code>%3$s</code></label>',
            esc_attr( LGPM_OPTION_KEY ),
            checked( ! empty( $s['enable_log'] ), true, false ),
            esc_html( 'wp-content/uploads/' . LGPM_LOG_BASENAME )
        );
        echo '<p class="description">Helpful while testing or troubleshooting. The log contains IDs/timestamps only (no raw PII).</p>';
    }, 'lgpm_settings_page', 'lgpm_main_section' );

    // Idle notice — friendlier wording
    add_settings_field( 'idle_notice_minutes', 'Idle notice after (minutes)', function() {
        $s = lgpm_get_settings();
        printf(
            '<input type="number" name="%1$s[idle_notice_minutes]" value="%2$d" min="1" class="small-text" /> ' .
            '<span class="description">Warn me if the masker hasn’t run in this many minutes. <em>Example:</em> <code>180</code> = 3 hours.</span>',
            esc_attr( LGPM_OPTION_KEY ),
            (int) $s['idle_notice_minutes']
        );
    }, 'lgpm_settings_page', 'lgpm_main_section' );

    // Lookback window — friendlier wording
    add_settings_field( 'lookback_minutes', 'Lookback window (minutes)', function() {
        $s = lgpm_get_settings();
        printf(
            '<input type="number" name="%1$s[lookback_minutes]" value="%2$d" min="1" class="small-text" /> ' .
            '<span class="description">When checking, only look at entries made in the past this many minutes. <em>Example:</em> <code>1440</code> = 24 hours.</span>',
            esc_attr( LGPM_OPTION_KEY ),
            (int) $s['lookback_minutes']
        );
    }, 'lgpm_settings_page', 'lgpm_main_section' );

    // Forms table
    add_settings_field( 'forms', 'Forms', 'lgpm_forms_table_field', 'lgpm_settings_page', 'lgpm_main_section' );
});

/** TOP‑LEVEL ADMIN MENU (no duplicate settings submenu) */
add_action( 'admin_menu', function() {
    add_menu_page(
        'PII Masker',                 // Page title
        'PII Masker',                 // Menu title
        'manage_options',             // Capability
        'lgpm-settings',              // Slug (top-level goes straight to settings)
        'lgpm_render_settings_page',  // Callback
        'dashicons-shield',           // Icon
        58
    );
});

/** SETTINGS PAGE RENDER */
function lgpm_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    echo '<div class="wrap"><h1>PII Masker – Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'lgpm_settings_group' );
    do_settings_sections( 'lgpm_settings_page' );
    submit_button();
    echo '</form></div>';
}

/** Forms repeater: choose form by name + input IDs CSV */
function lgpm_forms_table_field() {
    $s = lgpm_get_settings();
    $rows = ! empty( $s['forms'] ) ? $s['forms'] : array();

    // Build a map of active forms for the dropdown (by name)
    $forms = class_exists('GFAPI') ? GFAPI::get_forms( true ) : array();
    $form_opts = array();
    foreach ( $forms as $f ) {
        $form_opts[ (int) $f['id'] ] = sprintf( '%s (ID %d)', isset($f['title']) ? $f['title'] : 'Untitled', (int)$f['id'] );
    }
    ?>
    <table class="widefat striped" id="lgpm-forms-table" style="max-width:900px;">
        <thead>
            <tr>
                <th style="width:220px;">Form (ID)</th>
                <th>Input IDs to mask (comma-separated)</th>
                <th style="width:80px;">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $rows ) ) : ?>
            <tr>
                <td>
                    <select name="<?php echo esc_attr( LGPM_OPTION_KEY ); ?>[forms][0][form_id]">
                        <option value="">— choose form —</option>
                        <?php foreach ( $form_opts as $fid => $label ) : ?>
                            <option value="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text"
                        name="<?php echo esc_attr( LGPM_OPTION_KEY ); ?>[forms][0][input_ids]"
                        value="1.3,1.6,5.1,5.2,5.3,5.4,5.5,4"
                        class="regular-text" />
                    <p class="description" style="margin:.3em 0 0;">
                        Tip: Use the <em>Gravity Form Field Inspector</em> (button above) to copy the exact input IDs for your form.
                    </p>
                </td>
                <td><button type="button" class="button lgpm-remove-row" disabled>Remove</button></td>
            </tr>
        <?php else :
            foreach ( $rows as $i => $row ) :
                $current_form_id = isset($row['form_id']) ? (int)$row['form_id'] : 0; ?>
            <tr>
                <td>
                    <select name="<?php echo esc_attr( LGPM_OPTION_KEY ); ?>[forms][<?php echo esc_attr($i); ?>][form_id]">
                        <option value="">— choose form —</option>
                        <?php foreach ( $form_opts as $fid => $label ) : ?>
                            <option value="<?php echo esc_attr( $fid ); ?>" <?php selected( $current_form_id, $fid ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text"
                        name="<?php echo esc_attr( LGPM_OPTION_KEY ); ?>[forms][<?php echo esc_attr($i); ?>][input_ids]"
                        value="<?php echo esc_attr( $row['input_ids'] ); ?>"
                        class="regular-text" />
                </td>
                <td><button type="button" class="button lgpm-remove-row">Remove</button></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <p><button type="button" class="button button-secondary" id="lgpm-add-row">Add Form</button></p>

    <script>
    (function(){
        const table = document.getElementById('lgpm-forms-table').querySelector('tbody');
        const addBtn = document.getElementById('lgpm-add-row');
        addBtn.addEventListener('click', () => {
            const idx = table.querySelectorAll('tr').length;
            const tpl = `
                <tr>
                    <td>
                        <select name="<?php echo esc_js( LGPM_OPTION_KEY ); ?>[forms][${idx}][form_id]">
                            <option value="">— choose form —</option>
                            <?php foreach ( $form_opts as $fid => $label ) : ?>
                                <option value="<?php echo esc_js( $fid ); ?>"><?php echo esc_js( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="<?php echo esc_js( LGPM_OPTION_KEY ); ?>[forms][${idx}][input_ids]" value="" class="regular-text" placeholder="e.g. 1.3,1.6,5.1" /></td>
                    <td><button type="button" class="button lgpm-remove-row">Remove</button></td>
                </tr>`;
            table.insertAdjacentHTML('beforeend', tpl);
        });
        table.addEventListener('click', (e) => {
            if ( e.target && e.target.classList.contains('lgpm-remove-row') ) {
                const rows = table.querySelectorAll('tr');
                if ( rows.length > 1 ) {
                    e.target.closest('tr').remove();
                }
            }
        });
    })();
    </script>
    <?php
}

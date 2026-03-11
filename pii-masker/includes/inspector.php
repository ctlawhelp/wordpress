<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gravity Form Field Inspector
 * - Lists forms/fields/inputs with checkboxes and copy buttons
 * - Skips HTML fields and unused (hidden) sub-inputs
 * - Links back to PII Masker Settings
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'lgpm-settings',                    // parent: top-level PII Masker
        'Gravity Form Field Inspector',     // page title
        'Gravity Form Field Inspector',     // menu title
        'manage_options',                   // capability
        'lgpm-inspector',                   // slug
        'lgpm_render_inspector_page'        // callback
    );
}, 20);

/**
 * Whether to skip an entire field in the inspector.
 * - Skip HTML fields
 * - Optionally skip pure display-only fields
 */
function lgpm_inspector_skip_field( $field ) {
    if ( ! is_object( $field ) ) return true;
    $type = rgar( $field, 'type' );
    if ( $type === 'html' ) return true;              // don't show Gravity Forms HTML blocks
    if ( rgar( $field, 'displayOnly' ) ) return true; // extra safety: display-only fields
    return false;
}

/**
 * Whether to show a specific sub-input.
 * - Skip if 'isHidden' is true (GF marks unused sub-inputs this way)
 */
function lgpm_inspector_show_input( $input ) {
    if ( ! is_array( $input ) ) return false;
    if ( isset( $input['isHidden'] ) && $input['isHidden'] ) return false; // unused / disabled sub-input
    return true;
}

function lgpm_render_inspector_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( ! class_exists( 'GFAPI' ) ) {
        echo '<div class="wrap"><h1>Gravity Form Field Inspector</h1><p class="notice notice-error"><strong>Gravity Forms is not active.</strong></p></div>';
        return;
    }

    $selected_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
    $forms = GFAPI::get_forms( true ); // active forms only
    ?>
    <div class="wrap">
        <h1 style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <span>Gravity Form Field Inspector</span>
            <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=lgpm-settings' ) ); ?>">
                ← Return to PII Masker Settings
            </a>
        </h1>

        <form method="get" style="margin:1em 0;">
            <input type="hidden" name="page" value="lgpm-inspector" />
            <label for="lgpm-form-id"><strong>Select a form:</strong></label>
            <select id="lgpm-form-id" name="form_id">
                <option value="">— choose —</option>
                <?php foreach ( $forms as $f ) :
                    $fid   = (int) $f['id'];
                    $title = isset( $f['title'] ) ? $f['title'] : ('Form ' . $fid);
                ?>
                    <option value="<?php echo esc_attr( $fid ); ?>" <?php selected( $selected_id, $fid ); ?>>
                        <?php echo esc_html( $title . ' (ID ' . $fid . ')' ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="button button-primary">Load</button>
        </form>

        <?php
        if ( $selected_id ) :
            $form = GFAPI::get_form( $selected_id );
            if ( ! is_array( $form ) ) {
                echo '<p class="notice notice-error"><strong>Could not load form.</strong></p>';
            } else { ?>
                <p>
                    Check the inputs you want to mask. The “Copy selected IDs” box will auto‑populate so you can paste them into the
                    PII Masker settings for this form.
                </p>

                <div style="margin:.5em 0 1em; display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" class="button" id="lgpm-select-all">Select All</button>
                    <button type="button" class="button" id="lgpm-clear-all">Clear All</button>
                </div>

                <table class="widefat striped" style="max-width:1100px;">
                    <thead>
                        <tr>
                            <th style="width:40px;">Pick</th>
                            <th style="width:80px;">Field ID</th>
                            <th>Field Label</th>
                            <th style="width:480px;">Inputs (ID → Label)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $any_rows = false;

                    if ( ! empty( $form['fields'] ) ) {
                        foreach ( $form['fields'] as $field ) {

                            if ( lgpm_inspector_skip_field( $field ) ) {
                                continue; // skip HTML/display-only
                            }

                            $fid   = (string) rgar( $field, 'id' );
                            $label = trim( (string) rgar( $field, 'adminLabel' ) );
                            if ( $label === '' ) $label = (string) rgar( $field, 'label' );
                            if ( $label === '' ) $label = '(no label)';

                            // Build list of visible sub-inputs (or treat as single-input if none)
                            $inputs = rgar( $field, 'inputs' );
                            $visible_inputs = array();
                            if ( is_array( $inputs ) && ! empty( $inputs ) ) {
                                foreach ( $inputs as $inp ) {
                                    if ( lgpm_inspector_show_input( $inp ) ) {
                                        $visible_inputs[] = $inp;
                                    }
                                }
                            }

                            // If field has sub-inputs and all are hidden, skip entire field row
                            if ( is_array( $inputs ) && ! empty( $inputs ) && empty( $visible_inputs ) ) {
                                continue;
                            }

                            $any_rows = true;

                            echo '<tr>';
                            echo '<td style="vertical-align:top;"><input type="checkbox" disabled title="Use the checkboxes in the Inputs column."/></td>';
                            echo '<td style="vertical-align:top;"><code>' . esc_html( $fid ) . '</code></td>';
                            echo '<td style="vertical-align:top;">' . esc_html( $label ) . '</td>';
                            echo '<td>';

                            if ( ! empty( $visible_inputs ) ) {
                                foreach ( $visible_inputs as $inp ) {
                                    $iid  = (string) rgar( $inp, 'id' );     // e.g., "1.3"
                                    $ilbl = (string) rgar( $inp, 'label' );  // e.g., "First"
                                    if ( $ilbl === '' ) $ilbl = '(no input label)';
                                    echo '<div style="display:flex;gap:10px;align-items:center;margin:.2em 0;flex-wrap:wrap;">';
                                    echo '<label style="display:flex;gap:6px;align-items:center;cursor:pointer;">';
                                    echo '<input type="checkbox" class="lgpm-pick" data-id="' . esc_attr( $iid ) . '" />';
                                    echo '<code style="min-width:60px;display:inline-block;">' . esc_html( $iid ) . '</code>';
                                    echo '<span>' . esc_html( $ilbl ) . '</span>';
                                    echo '</label>';
                                    echo '<button type="button" class="button button-small lgpm-copy" data-copy="' . esc_attr( $iid ) . '">Copy</button>';
                                    echo '</div>';
                                }
                            } else {
                                // Single-input field (no sub-inputs array) — show the field ID itself
                                echo '<div style="display:flex;gap:10px;align-items:center;margin:.2em 0;flex-wrap:wrap;">';
                                echo '<label style="display:flex;gap:6px;align-items:center;cursor:pointer;">';
                                echo '<input type="checkbox" class="lgpm-pick" data-id="' . esc_attr( $fid ) . '" />';
                                echo '<code style="min-width:60px;display:inline-block;">' . esc_html( $fid ) . '</code>';
                                echo '<span>Single input</span>';
                                echo '</label>';
                                echo '<button type="button" class="button button-small lgpm-copy" data-copy="' . esc_attr( $fid ) . '">Copy</button>';
                                echo '</div>';
                            }

                            echo '</td>';
                            echo '</tr>';
                        }
                    }

                    if ( ! $any_rows ) {
                        echo '<tr><td colspan="4">No eligible fields found for this form (HTML/display-only fields and hidden sub-inputs are excluded).</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>

                <p style="margin-top:1em;">
                    <button type="button" class="button" id="lgpm-copy-checked">Copy selected IDs</button>
                    <input type="text" id="lgpm-selected-ids" class="regular-text" readonly style="width:70%;margin-left:8px;" value="" placeholder="Checked input IDs will appear here, comma‑separated" />
                </p>

                <script>
                (function(){
                    function copy(text){ navigator.clipboard.writeText(text).catch(()=>{}); }
                    function setSelectedText(ids){ document.getElementById('lgpm-selected-ids').value = ids.join(','); }
                    function collectChecked(){
                        const ids = [];
                        document.querySelectorAll('.lgpm-pick:checked').forEach(cb=>{
                            const v = cb.getAttribute('data-id');
                            if(v) ids.push(v);
                        });
                        return ids;
                    }
                    document.querySelectorAll('.lgpm-copy').forEach(btn=>{
                        btn.addEventListener('click', ()=>{
                            copy(btn.dataset.copy || '');
                            btn.textContent = 'Copied';
                            setTimeout(()=>{ btn.textContent = 'Copy'; }, 900);
                        });
                    });
                    document.addEventListener('change', (e)=>{
                        if(e.target && e.target.classList.contains('lgpm-pick')){
                            setSelectedText(collectChecked());
                        }
                    });
                    const selectAll = document.getElementById('lgpm-select-all');
                    const clearAll  = document.getElementById('lgpm-clear-all');
                    if(selectAll){
                        selectAll.addEventListener('click', ()=>{
                            document.querySelectorAll('.lgpm-pick').forEach(cb=>{
                                // only toggle visible/eligible picks
                                cb.checked = true;
                            });
                            setSelectedText(collectChecked());
                        });
                    }
                    if(clearAll){
                        clearAll.addEventListener('click', ()=>{
                            document.querySelectorAll('.lgpm-pick').forEach(cb=>cb.checked = false);
                            setSelectedText([]);
                        });
                    }
                    const copyChecked = document.getElementById('lgpm-copy-checked');
                    if(copyChecked){
                        copyChecked.addEventListener('click', ()=>{
                            const ids = collectChecked();
                            if(ids.length){
                                copy(ids.join(','));
                                copyChecked.textContent = 'Copied';
                                setTimeout(()=>{ copyChecked.textContent = 'Copy selected IDs'; }, 900);
                            }
                        });
                    }
                })();
                </script>
            <?php }
        endif; ?>
    </div>
    <?php
}

/**
 * Legal Aid Conditional Fields - Frontend JavaScript
 * Lightweight conditional fields for Elementor forms
 */
(function($) {
    'use strict';

    class LegalAidConditionalFields {
        constructor() {
            this.initializationAttempts = 0;
            this.maxAttempts = 10;
            this.init();
        }

        init() {
            // Multiple initialization strategies to handle different loading scenarios
            $(document).ready(() => {
                this.tryInitialization();
            });
            
            // Also try after a delay to ensure Elementor and PHP have loaded
            setTimeout(() => {
                this.tryInitialization();
            }, 500);
            
            // Listen for Elementor-specific events
            $(document).on('elementor/frontend/init', () => {
                this.tryInitialization();
            });
        }

        tryInitialization() {
            this.initializationAttempts++;
            console.log('LACF Debug: Initialization attempt', this.initializationAttempts);
            
            const formsFound = this.setupConditionalForms();
            
            // If no forms found and we haven't exceeded max attempts, try again
            if (!formsFound && this.initializationAttempts < this.maxAttempts) {
                setTimeout(() => {
                    this.tryInitialization();
                }, 200);
            }
        }

        setupConditionalForms() {
            let formsProcessed = 0;
            
            // Find all Elementor forms with conditional fields
            $('.elementor-form').each((index, form) => {
                const $form = $(form);
                
                console.log('LACF Debug: Checking form', index);
                
                const formSettings = this.getFormSettings($form);
                console.log('LACF Debug: Form settings:', formSettings);
                
                if (formSettings && formSettings.lacf_enable_conditional === 'yes' && formSettings.lacf_conditions) {
                    console.log('LACF Debug: Initializing conditional fields with conditions:', formSettings.lacf_conditions);
                    this.initializeForm($form, formSettings);
                    formsProcessed++;
                } else {
                    console.log('LACF Debug: No conditional fields enabled or no conditions found');
                }
            });
            
            console.log('LACF Debug: Processed', formsProcessed, 'forms with conditional fields');
            return formsProcessed > 0;
        }

        getFormSettings($form) {
            // Check if form has conditional fields enabled (set by PHP)
            if ($form.attr('data-lacf-enabled') === 'true') {
                // Get conditions from data attribute (single-encoded by PHP)
                const conditionsData = $form.attr('data-lacf-conditions');
                console.log('LACF Debug: Raw conditions data:', conditionsData);
                const fieldMapData = $form.attr('data-lacf-field-map');
                let fieldMap = [];
                
                if (fieldMapData) {
                    try {
                        fieldMap = JSON.parse(fieldMapData);
                    } catch (e) {
                        console.error('LACF Debug: Error parsing field map:', e, 'Raw data:', fieldMapData);
                    }
                }
                console.log('LACF Debug: Field map:', fieldMap);
                
                if (conditionsData) {
                    try {
                        // Parse the JSON once (no double encoding anymore)
                        const conditions = JSON.parse(conditionsData);
                        console.log('LACF Debug: Parsed conditions:', conditions);
                        return {
                            lacf_enable_conditional: 'yes',
                            lacf_conditions: conditions,
                            fieldMap
                        };
                    } catch (e) {
                        console.error('LACF Debug: Error parsing conditions:', e, 'Raw data:', conditionsData);
                    }
                }
            }
            
            return null;
        }

        initializeForm($form, formSettings) {
            const conditions = formSettings.lacf_conditions || [];
            const fieldMap = formSettings.fieldMap || [];
            
            if (!conditions.length) {
                return;
            }
            // Mark form as having conditional fields
            $form.attr('data-lacf-enabled', 'true');
            this.applyFieldMap($form, fieldMap);
            
            // Mark all target fields and hide them initially
            conditions.forEach(condition => {
                condition.__resolved_target = this.resolveTargetFieldId(condition.lacf_target_field, fieldMap);
                // Try multiple selectors for target field
                const $targetField = this.findTargetField($form, condition.__resolved_target, condition.lacf_target_field);
                
                console.log('LACF Debug: Found target field for', condition.lacf_target_field, '->', condition.__resolved_target, ':', $targetField.length > 0);
                
                if ($targetField.length) {
                    // Mark as conditional and hide immediately using multiple methods
                    $targetField.attr('data-lacf-target', 'true');
                    $targetField.addClass('lacf-hidden');
                    $targetField.css('display', 'none'); // Force immediate hide
                    $targetField.hide(); // jQuery hide
                    
                    // Disable form inputs to prevent validation
                    $targetField.find('input, select, textarea').prop('disabled', true);
                    
                    console.log('LACF Debug: Initially hiding field', condition.lacf_target_field);
                }
            });
            
            // Set up event listeners for condition fields
            conditions.forEach(condition => {
                // For checkbox groups, use the array notation
                let conditionFieldSelector = `[name="form_fields[${condition.lacf_condition_field}][]"]`;
                let $conditionField = $form.find(conditionFieldSelector);
                
                // If not found, try without array notation
                if (!$conditionField.length) {
                    conditionFieldSelector = `[name="form_fields[${condition.lacf_condition_field}]"]`;
                    $conditionField = $form.find(conditionFieldSelector);
                }
                
                if ($conditionField.length) {
                    console.log('LACF Debug: Setting up events for condition field', condition.lacf_condition_field);
                    
                    // Set up change event
                    $conditionField.on('change keyup', () => {
                        console.log('LACF Debug: Condition field changed, evaluating...');
                        this.evaluateCondition($form, condition);
                    });
                    
                    // Initial evaluation - do this immediately, not in timeout
                    this.evaluateCondition($form, condition);
                } else {
                    console.log('LACF Debug: Could not find condition field', condition.lacf_condition_field);
                }
            });
        }

        evaluateCondition($form, condition) {
            // Find condition field (use the same selector logic as initialization)
            let conditionFieldSelector = `[name="form_fields[${condition.lacf_condition_field}][]"]`;
            let $conditionField = $form.find(conditionFieldSelector);
            
            if (!$conditionField.length) {
                conditionFieldSelector = `[name="form_fields[${condition.lacf_condition_field}]"]`;
                $conditionField = $form.find(conditionFieldSelector);
            }
            
            // Find target field
            const resolvedTarget = condition.__resolved_target || condition.lacf_target_field;
            let $targetField = this.findTargetField($form, resolvedTarget, condition.lacf_target_field);
            
            if (!$conditionField.length || !$targetField.length) {
                return;
            }

            const actualValue = this.getFieldValue($conditionField);
            const conditionMet = this.checkCondition(actualValue, condition.lacf_operator, condition.lacf_value);
            
            // Determine if field should be visible
            const shouldShow = (condition.lacf_action === 'show') ? conditionMet : !conditionMet;
            
            this.toggleField($targetField, shouldShow);
        }

        getFieldValue($field) {
            const fieldType = $field.attr('type');
            
            if (fieldType === 'checkbox') {
                // For checkbox groups, get all checked values
                const checkedValues = [];
                $field.filter(':checked').each(function() {
                    checkedValues.push($(this).val());
                });
                return checkedValues.join(',');
            } else if (fieldType === 'radio') {
                return $field.filter(':checked').val() || '';
            } else if ($field.is('select')) {
                return $field.val() || '';
            } else {
                return $field.val() || '';
            }
        }

        checkCondition(actualValue, operator, expectedValue) {
            switch (operator) {
                case 'equals':
                    // Support comma-separated values in expectedValue
                    if (expectedValue.indexOf(',') !== -1) {
                        const expectedValues = expectedValue.split(',').map(v => v.trim());
                        
                        // If actualValue is also comma-separated (multiple checkboxes), check for any overlap
                        if (actualValue.indexOf(',') !== -1) {
                            const actualValues = actualValue.split(',').map(v => v.trim());
                            const hasMatch = actualValues.some(val => expectedValues.includes(val));
                            return hasMatch;
                        } else {
                            // Single actual value, check if it's in expected array
                            const match = expectedValues.includes(actualValue);
                            return match;
                        }
                    }
                    return actualValue === expectedValue;
                case 'not_equals':
                    // Support comma-separated values  
                    if (expectedValue.indexOf(',') !== -1) {
                        const values = expectedValue.split(',').map(v => v.trim());
                        return !values.includes(actualValue);
                    }
                    return actualValue !== expectedValue;
                case 'contains':
                    return actualValue.indexOf(expectedValue) !== -1;
                case 'not_empty':
                    return actualValue.trim() !== '';
                case 'empty':
                    return actualValue.trim() === '';
                default:
                    return false;
            }
        }

        toggleField($field, show) {
            if (show) {
                $field.show().removeClass('lacf-hidden');
                // Re-enable any form validation for input fields
                $field.find('input, select, textarea').prop('disabled', false);
            } else {
                $field.hide().addClass('lacf-hidden');
                // Disable validation for hidden input fields (don't clear HTML content)
                $field.find('input, select, textarea').prop('disabled', true);
                // Only clear values for actual form inputs, not HTML content
                $field.find('input[type="text"], input[type="email"], input[type="tel"], select, textarea').val('');
            }
        }

        resolveTargetFieldId(identifier, fieldMap) {
            if (!identifier) {
                return '';
            }

            const normalized = identifier.toLowerCase();
            const mapMatch = (fieldMap || []).find(field => {
                const elementId = (field.element_id || '').toLowerCase();
                const fieldId = (field.field_id || '').toLowerCase();
                const label = (field.label || '').toLowerCase();
                return elementId === normalized || fieldId === normalized || (label && label === normalized);
            });

            if (mapMatch && mapMatch.element_id) {
                return this.normalizeElementId(mapMatch.element_id);
            }

            if (identifier.startsWith('field_')) {
                return identifier;
            }

            // If it's a hex-like Elementor ID, prefix it automatically
            if (/^[a-z0-9]+$/i.test(identifier)) {
                return this.normalizeElementId(identifier);
            }

            return identifier;
        }

        findTargetField($form, resolvedId, originalIdentifier) {
            const trySelectors = (id) => {
                if (!id) {
                    return $();
                }
                const normalizedId = this.normalizeElementId(id);
                const rawId = id.toString().trim().toLowerCase();
                let $field = $();

                if (normalizedId) {
                    $field = $form.find(`.elementor-field-group-${normalizedId}`);
                    if ($field.length) {
                        return $field;
                    }
                    $field = $form.find(`[data-field-id="${normalizedId}"]`);
                    if ($field.length) {
                        return $field;
                    }
                }

                if (rawId) {
                    $field = $form.find(`.elementor-field-group-${rawId}`);
                    if ($field.length) {
                        return $field;
                    }
                    $field = $form.find(`[data-field-id="${rawId}"]`);
                }

                return $field;
            };

            let $targetField = trySelectors(resolvedId);

            if (!$targetField.length) {
                $targetField = trySelectors(originalIdentifier);
            }

            if (!$targetField.length && originalIdentifier) {
                const lowered = originalIdentifier.toLowerCase();
                $targetField = $form.find(`.elementor-field-group[data-lacf-map-id="${lowered}"]`);
                
                if (!$targetField.length) {
                    $targetField = $form.find(`.elementor-field-group[data-lacf-map-label*="${lowered}"]`);
                }
            }

            if (!$targetField.length && originalIdentifier) {
                const cssIdMatch = $form.find(`#${originalIdentifier}`);
                if (cssIdMatch.length) {
                    $targetField = cssIdMatch.closest('.elementor-field-group');
                }
            }

            if (!$targetField.length && originalIdentifier) {
                const cssClassMatch = $form.find(`.${originalIdentifier}`);
                if (cssClassMatch.length) {
                    $targetField = cssClassMatch.closest('.elementor-field-group');
                }
            }

            if (!$targetField.length && originalIdentifier) {
                const lowered = originalIdentifier.toLowerCase();
                $targetField = $form.find('.elementor-field-group').filter(function() {
                    const labelText = $(this).find('label').text().trim().toLowerCase();
                    return labelText && labelText.indexOf(lowered) !== -1;
                });
            }

            return $targetField;
        }

        applyFieldMap($form, fieldMap) {
            if (!fieldMap || !fieldMap.length) {
                return;
            }

            fieldMap.forEach(field => {
                if (!field || !field.element_id) {
                    return;
                }

                const normalizedElementId = this.normalizeElementId(field.element_id);
                const $fieldGroup = $form.find(`.elementor-field-group-${normalizedElementId}`);
                if (!$fieldGroup.length) {
                    return;
                }

                if (normalizedElementId) {
                    $fieldGroup.attr('data-lacf-map-element', normalizedElementId);
                }

                if (field.field_id) {
                    $fieldGroup.attr('data-lacf-map-id', field.field_id.toLowerCase());
                } else if (normalizedElementId) {
                    $fieldGroup.attr('data-lacf-map-id', normalizedElementId);
                }

                if (field.label) {
                    $fieldGroup.attr('data-lacf-map-label', field.label.toLowerCase());
                }

                if (field.type) {
                    $fieldGroup.attr('data-lacf-map-type', field.type);
                }
            });
        }

        normalizeElementId(id) {
            if (!id) {
                return '';
            }
            const trimmed = id.toString().trim();
            if (!trimmed) {
                return '';
            }
            const value = trimmed.startsWith('field_') ? trimmed : `field_${trimmed}`;
            return value.toLowerCase();
        }

    }

    // Initialize when page loads
    new LegalAidConditionalFields();

    // Re-initialize on Elementor AJAX forms
    $(document).on('elementor/popup/show', () => {
        setTimeout(() => {
            new LegalAidConditionalFields();
        }, 100);
    });

})(jQuery);
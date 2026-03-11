jQuery(document).ready(function ($) {
    const container = $('#ig-step-builder');
    const input = $('#ig-steps-json');

    let steps = [];
    try {
        steps = JSON.parse(input.val() || '[]');
    } catch (e) {
        steps = [];
    }

    function renderSteps() {
        container.empty();
        steps.forEach((step, index) => {
            const card = $(`
                <div class="ig-step-card" data-index="${index}">
                    <h3>Step ${index + 1}: <input class="ig-step-title" value="${step.title || ''}" placeholder="Enter step title (e.g., 'Do you have children under 18?')"/></h3>
                    <label>Type:
                        <select class="ig-step-type">
                            <option value="info"${step.type === 'info' ? ' selected' : ''}>Info</option>
                            <option value="question"${step.type === 'question' ? ' selected' : ''}>Question</option>
                            <option value="form"${step.type === 'form' ? ' selected' : ''}>Form</option>
                        </select>
                    </label>
                    <textarea class="ig-step-content" placeholder="Content or Question text">${step.content || ''}</textarea>
                    
                    <div class="ig-step-type-options">
                        <div class="ig-question-options ${step.type === 'question' ? 'visible' : ''}">
                            <h4>Question Options</h4>
                            <label>
                                <input type="radio" name="question_type_${index}" value="yes_no" ${(step.question_type === 'yes_no' || !step.question_type) ? 'checked' : ''}> 
                                Yes/No Question
                            </label><br>
                            <label>
                                <input type="radio" name="question_type_${index}" value="multiple_choice" ${step.question_type === 'multiple_choice' ? 'checked' : ''}> 
                                Multiple Choice
                            </label><br>
                            <label>
                                <input type="radio" name="question_type_${index}" value="text_input" ${step.question_type === 'text_input' ? 'checked' : ''}> 
                                Text Input
                            </label>
                            
                            <div class="ig-choices" style="margin-top: 10px; ${step.question_type === 'multiple_choice' ? '' : 'display: none;'}">
                                <label>Answer Choices (one per line):</label>
                                <textarea placeholder="Option 1\nOption 2\nOption 3" style="width: 100%; height: 60px;">${step.choices || ''}</textarea>
                            </div>
                            
                            <div class="ig-actions-section" style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                                <h4>Actions (What happens after answering?)</h4>
                                <div class="ig-actions-content"></div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="button ig-remove-step">Remove</button>
                </div>
            `);
            container.append(card);
        });
        
        // Show/hide question options based on type
        updateQuestionOptionsVisibility();
        
        // Build action interfaces
        buildActionInterfaces();
    }

    function updateJSON() {
        const data = [];
        container.find('.ig-step-card').each(function () {
            const card = $(this);
            const index = card.data('index');
            const stepData = {
                title: card.find('.ig-step-title').val(),
                type: card.find('.ig-step-type').val(),
                content: card.find('.ig-step-content').val()
            };
            
            // Add question-specific data if it's a question type
            if (stepData.type === 'question') {
                stepData.question_type = card.find(`input[name="question_type_${index}"]:checked`).val();
                
                if (stepData.question_type === 'multiple_choice') {
                    stepData.choices = card.find('.ig-choices textarea').val();
                    stepData.choice_actions = card.find('.ig-choice-actions-config').val();
                } else if (stepData.question_type === 'yes_no') {
                    // Yes action data
                    stepData.yes_action = card.find('.ig-yes-action').val();
                    stepData.yes_jump_step = card.find('.ig-yes-jump-step').val();
                    stepData.yes_modal_message = card.find('.ig-yes-modal-message').val();
                    
                    // No action data
                    stepData.no_action = card.find('.ig-no-action').val();
                    stepData.no_jump_step = card.find('.ig-no-jump-step').val();
                    stepData.no_modal_message = card.find('.ig-no-modal-message').val();
                }
            }
            
            data.push(stepData);
        });
        input.val(JSON.stringify(data, null, 2));
    }
    
    function updateQuestionOptionsVisibility() {
        container.find('.ig-step-card').each(function() {
            const card = $(this);
            const type = card.find('.ig-step-type').val();
            const questionOptions = card.find('.ig-question-options');
            
            if (type === 'question') {
                questionOptions.addClass('visible');
            } else {
                questionOptions.removeClass('visible');
            }
        });
    }
    
    function buildActionInterfaces() {
        container.find('.ig-step-card').each(function() {
            const card = $(this);
            const index = card.data('index');
            const step = steps[index];
            const actionsContent = card.find('.ig-actions-content');
            
            // Only show actions for question steps
            if (step.type !== 'question') {
                actionsContent.empty();
                return;
            }
            
            const questionType = card.find('input[name="question_type_' + index + '"]:checked').val() || 'yes_no';
            
            if (questionType === 'yes_no') {
                const yesNoHTML = `
                    <div class="ig-yes-no-actions">
                        <div style="margin-bottom: 10px;">
                            <strong>If YES:</strong>
                            <select class="ig-yes-action" style="margin-left: 10px;">
                                <option value="next" ${(step.yes_action === 'next' || !step.yes_action) ? 'selected' : ''}>Continue to next step</option>
                                <option value="jump" ${step.yes_action === 'jump' ? 'selected' : ''}>Jump to specific step</option>
                                <option value="modal" ${step.yes_action === 'modal' ? 'selected' : ''}>Show modal message</option>
                                <option value="end" ${step.yes_action === 'end' ? 'selected' : ''}>End guide</option>
                            </select>
                            <div class="ig-yes-action-details" style="margin-top: 5px;">
                                <input type="number" class="ig-yes-jump-step" placeholder="Step #" min="1" value="${step.yes_jump_step || ''}" style="width: 80px; ${step.yes_action === 'jump' ? '' : 'display: none;'}">
                                <input type="text" class="ig-yes-modal-message" placeholder="Modal message" value="${step.yes_modal_message || ''}" style="width: 250px; ${step.yes_action === 'modal' ? '' : 'display: none;'}">
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <strong>If NO:</strong>
                            <select class="ig-no-action" style="margin-left: 10px;">
                                <option value="next" ${(step.no_action === 'next' || !step.no_action) ? 'selected' : ''}>Continue to next step</option>
                                <option value="jump" ${step.no_action === 'jump' ? 'selected' : ''}>Jump to specific step</option>
                                <option value="modal" ${step.no_action === 'modal' ? 'selected' : ''}>Show modal message</option>
                                <option value="end" ${step.no_action === 'end' ? 'selected' : ''}>End guide</option>
                            </select>
                            <div class="ig-no-action-details" style="margin-top: 5px;">
                                <input type="number" class="ig-no-jump-step" placeholder="Step #" min="1" value="${step.no_jump_step || ''}" style="width: 80px; ${step.no_action === 'jump' ? '' : 'display: none;'}">
                                <input type="text" class="ig-no-modal-message" placeholder="Modal message" value="${step.no_modal_message || ''}" style="width: 250px; ${step.no_action === 'modal' ? '' : 'display: none;'}">
                            </div>
                        </div>
                    </div>
                `;
                actionsContent.html(yesNoHTML);
                
            } else if (questionType === 'multiple_choice') {
                const multipleChoiceHTML = `
                    <div class="ig-choice-actions">
                        <p><em>For multiple choice, actions are set per choice below:</em></p>
                        <textarea class="ig-choice-actions-config" placeholder="Choice 1: next\nChoice 2: jump:5\nChoice 3: modal:You selected option 3" style="width: 100%; height: 80px;">${step.choice_actions || ''}</textarea>
                        <p class="description">Format: "Choice Text: action" or "Choice Text: action:parameter"<br>
                        Actions: next, jump:X, modal:message, end</p>
                    </div>
                `;
                actionsContent.html(multipleChoiceHTML);
                
            } else {
                actionsContent.html('<p><em>No additional actions needed for text input questions.</em></p>');
            }
        });
    }

    // Add Step
    const addButton = $('<button class="button button-primary ig-add-step">+ Add Step</button>');
    container.after(addButton);

    addButton.on('click', function (e) {
        e.preventDefault();
        steps.push({ title: '', type: 'info', content: '' });
        renderSteps();
        updateJSON();
    });

    // Handle step type changes
    container.on('change', '.ig-step-type', function() {
        updateQuestionOptionsVisibility();
        updateJSON();
    });
    
    // Handle question type changes
    container.on('change', 'input[type="radio"]', function() {
        const card = $(this).closest('.ig-step-card');
        const questionType = $(this).val();
        const choicesDiv = card.find('.ig-choices');
        
        if (questionType === 'multiple_choice') {
            choicesDiv.show();
        } else {
            choicesDiv.hide();
        }
        
        // Rebuild action interface for this step
        buildActionInterfaces();
        updateJSON();
    });
    
    // Handle action dropdown changes
    container.on('change', '.ig-yes-action, .ig-no-action', function() {
        const actionType = $(this).val();
        const detailsDiv = $(this).siblings('.ig-yes-action-details, .ig-no-action-details');
        
        detailsDiv.find('input').hide();
        
        if (actionType === 'jump') {
            detailsDiv.find('input[class*="jump-step"]').show();
        } else if (actionType === 'modal') {
            detailsDiv.find('input[class*="modal-message"]').show();
        }
        
        updateJSON();
    });
    
    container.on('change keyup', 'input, textarea, select', updateJSON);
    container.on('click', '.ig-remove-step', function (e) {
        e.preventDefault();
        const i = $(this).closest('.ig-step-card').data('index');
        steps.splice(i, 1);
        renderSteps();
        updateJSON();
    });

    renderSteps();
});

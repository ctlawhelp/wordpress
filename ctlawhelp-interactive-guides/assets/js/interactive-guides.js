/**
 * Interactive Legal Guides JavaScript
 */

jQuery(document).ready(function($) {
    
    // Initialize Interactive Guides
    $('.interactive-guide').each(function() {
        var $guide = $(this);
        var currentStep = 1;
        var totalSteps = $guide.find('.ig-step').length;
        var answers = {};
        
        // Store step data for action handling
        $guide.find('.ig-step').each(function() {
            var $step = $(this);
            var stepNum = $step.data('step');
            var stepData = $step.data('step-data');
            
            // If stepData is a string, try to parse it as JSON
            if (typeof stepData === 'string') {
                try {
                    stepData = JSON.parse(stepData);
                } catch (e) {
                    console.error('Failed to parse step data for step', stepNum, ':', e);
                }
            }
            
            if (stepData) {
                $guide.data('step-' + stepNum, stepData);
            }
        });
        
        // Initialize progress
        updateProgress();
        
        // Handle answer button clicks
        $guide.on('click', '.ig-answer-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $step = $btn.closest('.ig-step');
            var stepNumber = $step.data('step');
            var answer = $btn.data('answer');
            
            // Handle text input special case
            if ($btn.hasClass('ig-submit-text')) {
                var textValue = $step.find('.ig-text-answer').val().trim();
                if (!textValue) {
                    alert('Please enter an answer before continuing.');
                    return;
                }
                answer = textValue;
                $btn.data('answer', answer);
            }
            
            // Store the answer
            var questionTitle = $step.find('h3').text();
            answers[stepNumber] = {
                question: questionTitle,
                answer: answer
            };
            
            // Update answers sidebar
            updateAnswersSidebar();
            
            // Handle step actions based on answer
            setTimeout(function() {
                handleStepAction(stepNumber, answer);
            }, 300);
        });
        
        // Handle navigation buttons
        $guide.on('click', '.ig-prev-btn', function(e) {
            e.preventDefault();
            prevStep();
        });
        
        $guide.on('click', '.ig-next-btn', function(e) {
            e.preventDefault();
            nextStep();
        });
        
        $guide.on('click', '.ig-finish-btn', function(e) {
            e.preventDefault();
            finishGuide();
        });
        
        // Handle text input enter key
        $guide.on('keypress', '.ig-text-answer', function(e) {
            if (e.which === 13) { // Enter key
                $(this).siblings('.ig-submit-text').click();
            }
        });
        
        function nextStep() {
            if (currentStep < totalSteps) {
                $guide.find('.ig-step[data-step="' + currentStep + '"]').hide();
                currentStep++;
                $guide.find('.ig-step[data-step="' + currentStep + '"]').show();
                updateProgress();
                scrollToTop();
            }
        }
        
        function prevStep() {
            if (currentStep > 1) {
                $guide.find('.ig-step[data-step="' + currentStep + '"]').hide();
                currentStep--;
                $guide.find('.ig-step[data-step="' + currentStep + '"]').show();
                updateProgress();
                scrollToTop();
            }
        }
        
        function updateProgress() {
            var percentage = (currentStep / totalSteps) * 100;
            $guide.find('.ig-progress').css('width', percentage + '%');
            $guide.find('.ig-progress-text').text('Step ' + currentStep + ' of ' + totalSteps);
        }
        
        function updateAnswersSidebar() {
            var $answersList = $guide.find('.ig-answers-list');
            $answersList.empty();
            
            if (Object.keys(answers).length === 0) {
                $answersList.html('<p><em>Answer questions to see your personalized results here.</em></p>');
                return;
            }
            
            // Sort answers by step number
            var sortedAnswers = Object.keys(answers).sort(function(a, b) {
                return parseInt(a) - parseInt(b);
            });
            
            sortedAnswers.forEach(function(stepNum) {
                var answer = answers[stepNum];
                var $answerItem = $('<div class="ig-answer-item">' +
                    '<div class="ig-answer-question">' + answer.question + '</div>' +
                    '<div class="ig-answer-value">' + answer.answer + '</div>' +
                    '</div>');
                $answersList.append($answerItem);
            });
        }
        
        function finishGuide() {
            var $summary = $guide.find('.ig-right');
            $summary.html('<div class="ig-completion">' +
                '<h4>✅ Guide Complete!</h4>' +
                '<p>Based on your answers, here are your next steps:</p>' +
                '<div class="ig-final-recommendations">' +
                '<p><em>Personalized recommendations will be added here based on your specific answers.</em></p>' +
                '</div>' +
                '<button class="ig-answer-btn" onclick="window.print()">📄 Print Results</button>' +
                '</div>');
        }
        
        function handleStepAction(stepNumber, answer) {
            // Get step data (we need to store this when rendering)
            var stepData = $guide.data('step-' + stepNumber);
            
            if (!stepData) {
                // Default behavior - just go to next step
                nextStep();
                return;
            }
            
            var action = null;
            var actionParam = null;
            
            // Determine action based on question type and answer
            if (stepData.question_type === 'yes_no') {
                if (answer.toLowerCase() === 'yes') {
                    action = stepData.yes_action || 'next';
                    actionParam = {
                        jump_step: stepData.yes_jump_step,
                        modal_message: stepData.yes_modal_message
                    };
                } else if (answer.toLowerCase() === 'no') {
                    action = stepData.no_action || 'next';
                    actionParam = {
                        jump_step: stepData.no_jump_step,
                        modal_message: stepData.no_modal_message
                    };
                }
            } else if (stepData.question_type === 'multiple_choice' && stepData.choice_actions) {
                // Parse choice actions format: "Choice Text: action" or "Choice Text: action:parameter"
                var choiceActions = stepData.choice_actions.split('\n');
                for (var i = 0; i < choiceActions.length; i++) {
                    var line = choiceActions[i].trim();
                    if (line.startsWith(answer + ':')) {
                        var actionPart = line.substring((answer + ':').length).trim();
                        if (actionPart.includes(':')) {
                            var parts = actionPart.split(':');
                            action = parts[0];
                            actionParam = { value: parts.slice(1).join(':') };
                        } else {
                            action = actionPart;
                            actionParam = {};
                        }
                        break;
                    }
                }
            }
            
            // Execute the action
            executeAction(action || 'next', actionParam || {});
        }
        
        function executeAction(action, params) {
            switch (action) {
                case 'next':
                    nextStep();
                    break;
                    
                case 'jump':
                    var targetStep = parseInt(params.jump_step || params.value);
                    if (targetStep && targetStep > 0 && targetStep <= totalSteps) {
                        jumpToStep(targetStep);
                    } else {
                        nextStep(); // Fallback
                    }
                    break;
                    
                case 'modal':
                    var message = params.modal_message || params.value || 'Thank you for your response.';
                    showModal(message, function() {
                        nextStep();
                    });
                    break;
                    
                case 'end':
                    finishGuide();
                    break;
                    
                default:
                    nextStep();
            }
        }
        
        function jumpToStep(targetStep) {
            // Hide current step
            var $currentStep = $guide.find('.ig-step[data-step="' + currentStep + '"]');
            $currentStep.hide();
            
            // Show target step
            var $targetStep = $guide.find('.ig-step[data-step="' + targetStep + '"]');
            
            if ($targetStep.length > 0) {
                currentStep = targetStep;
                $targetStep.show();
                updateProgress();
                scrollToTop();
            } else {
                console.error('Target step', targetStep, 'not found! Available steps:', $guide.find('.ig-step').map(function() { return $(this).data('step'); }).get());
                // Fallback to next step
                nextStep();
            }
        }
        
        function showModal(message, callback) {
            var $modal = $('<div class="ig-modal-overlay">' +
                '<div class="ig-modal">' +
                '<h3>Information</h3>' +
                '<p>' + message + '</p>' +
                '<div class="ig-modal-actions">' +
                '<button class="ig-modal-btn ig-modal-continue">Continue</button>' +
                '</div>' +
                '</div>' +
                '</div>');
            
            $('body').append($modal);
            
            $modal.find('.ig-modal-continue').on('click', function() {
                $modal.remove();
                if (callback) callback();
            });
            
            // Close on overlay click
            $modal.on('click', function(e) {
                if (e.target === $modal[0]) {
                    $modal.remove();
                    if (callback) callback();
                }
            });
        }
        
        function scrollToTop() {
            $guide[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
    
    // Legacy compatibility
    $('.interactive-guide-category').on('click', function(e) {
        var category = $(this).text();
    });
});
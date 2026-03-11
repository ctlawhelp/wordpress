<?php
/**
 * Front-End Shortcode Renderer for Interactive Guides
 */
if (!defined('ABSPATH')) exit;

add_shortcode('interactive_guide', function($atts) {
    $atts = shortcode_atts(['id' => get_the_ID()], $atts);
    $json = get_post_meta($atts['id'], '_clh_guide_steps', true);
    $steps = json_decode($json, true);

    if (empty($steps)) return '<p>No steps available for this guide.</p>';

    ob_start(); ?>
    <div class="interactive-guide parallax-guide" data-guide-id="<?php echo esc_attr($atts['id']); ?>">
        <div class="ig-progress-bar">
            <div class="ig-progress" style="width: 0%"></div>
            <span class="ig-progress-text">Question 1 of <?php echo count($steps); ?></span>
        </div>
        
        <div class="ig-questions-container">
            <?php foreach ($steps as $i => $step): ?>
                <div class="ig-question-block" id="ig-question-<?php echo $i + 1; ?>" data-step="<?php echo $i + 1; ?>"
                     data-step-data="<?php echo esc_attr(json_encode($step)); ?>">
                    
                    <div class="ig-question-content">
                        <div class="ig-step-number"><?php echo $i + 1; ?></div>
                        <h2 class="ig-question-title"><?php echo esc_html($step['title']); ?></h2>
                        
                        <?php if (!empty($step['content'])): ?>
                            <div class="ig-question-description">
                                <?php echo wp_kses_post(wpautop($step['content'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($step['type']) && $step['type'] === 'question'): ?>
                            <div class="ig-question-interface">
                                <?php 
                                $question_type = isset($step['question_type']) ? $step['question_type'] : 'yes_no';
                                
                                if ($question_type === 'yes_no'): ?>
                                    <div class="ig-yes-no-buttons">
                                        <button class="ig-answer-btn ig-yes-btn" data-answer="yes">
                                            <span class="btn-icon">✓</span> Yes
                                        </button>
                                        <button class="ig-answer-btn ig-no-btn" data-answer="no">
                                            <span class="btn-icon">✗</span> No
                                        </button>
                                    </div>
                                
                                <?php elseif ($question_type === 'multiple_choice' && !empty($step['choices'])): ?>
                                    <div class="ig-multiple-choice">
                                        <?php 
                                        $choices = explode("\n", trim($step['choices']));
                                        foreach ($choices as $choice_index => $choice): 
                                            $choice = trim($choice);
                                            if (!empty($choice)): ?>
                                                <button class="ig-answer-btn ig-choice-btn" data-answer="<?php echo esc_attr($choice); ?>">
                                                    <span class="btn-icon">→</span> <?php echo esc_html($choice); ?>
                                                </button>
                                            <?php endif;
                                        endforeach; ?>
                                    </div>
                                
                                <?php elseif ($question_type === 'text_input'): ?>
                                    <div class="ig-text-input">
                                        <input type="text" class="ig-text-answer" placeholder="Enter your answer...">
                                        <button class="ig-answer-btn ig-submit-text" data-answer="">
                                            <span class="btn-icon">→</span> Submit
                                        </button>
                                    </div>
                                
                                <?php endif; ?>
                            </div>
                        
                        <?php elseif (isset($step['type']) && $step['type'] === 'info'): ?>
                            <div class="ig-info-navigation">
                                <?php if ($i < count($steps) - 1): ?>
                                    <button class="ig-answer-btn ig-continue-btn" data-answer="continue">
                                        <span class="btn-icon">→</span> Continue
                                    </button>
                                <?php else: ?>
                                    <button class="ig-answer-btn ig-finish-btn" data-answer="finish">
                                        <span class="btn-icon">✓</span> Finish Guide
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Completion message (initially hidden) -->
        <div class="ig-completion-block" id="ig-completion" style="display: none;">
            <div class="ig-question-content">
                <div class="ig-completion-icon">🎉</div>
                <h2>Guide Complete!</h2>
                <p>Thank you for completing the guide. Based on your answers, here are your next steps:</p>
                <div class="ig-recommendations">
                    <p><em>Personalized recommendations will appear here based on your responses.</em></p>
                </div>
                <button class="ig-answer-btn" onclick="window.print()">
                    <span class="btn-icon">📄</span> Print Results
                </button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

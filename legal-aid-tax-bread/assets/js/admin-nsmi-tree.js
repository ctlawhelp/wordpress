jQuery(document).ready(function($){
    alert("NSMI tree JS loaded!");
    // Collapse all subcategories initially
    $('.editor-post-taxonomies__hierarchical-terms-subchoices').hide();

    // Add toggle buttons to parent categories
    $('.editor-post-taxonomies__hierarchical-terms-choice').each(function(){
        var $choice = $(this);
        var $sub = $choice.children('.editor-post-taxonomies__hierarchical-terms-subchoices');
        if ($sub.length) {
            if ($choice.find('.nsmi-toggle').length === 0) {
                $choice.prepend('<span class="nsmi-toggle" style="cursor:pointer; margin-right:4px;">▸</span>');
            }
        }
    });

    // Toggle expand/collapse on click
    $(document).on('click', '.nsmi-toggle', function(){
        var $toggle = $(this);
        var $choice = $toggle.closest('.editor-post-taxonomies__hierarchical-terms-choice');
        var $sub = $choice.children('.editor-post-taxonomies__hierarchical-terms-subchoices');
        $sub.toggle();
        $toggle.text($toggle.text() === '▸' ? '▾' : '▸');
    });
});
.nsmi-toggle {
  font-weight: bold;
  user-select: none;
}

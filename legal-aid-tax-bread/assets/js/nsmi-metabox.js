// Improved parent checkbox detection for NSMI tree
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    var nsmiInside = document.querySelector('.postbox#nsmi_category_custom > .inside');
    if (nsmiInside) {
      nsmiInside.style.padding = '0';
    }

    // Helper to find parent checkbox (works for label nesting)
    function findParentCheckbox(childCheckbox) {
      var parentUl = childCheckbox.closest('.nsmi-tree-children');
      if (parentUl) {
        var parentLi = parentUl.parentElement.closest('li');
        if (parentLi) {
          var parentCheckbox = parentLi.querySelector('input[type="checkbox"]');
          if (parentCheckbox && parentCheckbox !== childCheckbox) {
            return parentCheckbox;
          }
        }
      }
      return null;
    }

    // Helper to find all child checkboxes
    function findChildCheckboxes(parentLi) {
      var childUls = parentLi.querySelectorAll('.nsmi-tree-children');
      var checkboxes = [];
      childUls.forEach(function(ul) {
        checkboxes = checkboxes.concat(Array.from(ul.querySelectorAll('input[type="checkbox"]')));
      });
      return checkboxes;
    }

    // Listen for changes on all checkboxes
    document.querySelectorAll('#nsmi-category-tree input[type="checkbox"]').forEach(function(checkbox) {
      checkbox.addEventListener('change', function(e) {
        // If checked, check all parents
        if (this.checked) {
          var parent = findParentCheckbox(this);
          while (parent) {
            parent.checked = true;
            parent = findParentCheckbox(parent);
          }
        } else {
          // If unchecked, uncheck all children
          var li = this.closest('li');
          var children = findChildCheckboxes(li);
          children.forEach(function(child) {
            child.checked = false;
          });
        }
      });
    });
  });
})();

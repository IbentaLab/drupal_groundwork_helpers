((Drupal, once) => {
  Drupal.behaviors.groundworkStylesFilter = {
    attach(context) {
      const wrapper = once('groundwork-styles-filter', '#groundwork-styles-wrapper', context);

      if (!wrapper.length) {
        return;
      }

      const filterInput = wrapper[0].querySelector('.groundwork-style-filter');
      const allOptions = wrapper[0].querySelectorAll('.groundwork-style-options .form-item');
      const allDetails = wrapper[0].querySelectorAll('details');

      if (!filterInput || !allOptions.length) {
        return;
      }

      filterInput.addEventListener('keyup', () => {
        const searchTerm = filterInput.value.toLowerCase();

        // First, hide everything and close all details sections.
        allOptions.forEach(option => {
          option.style.display = 'none';
        });
        allDetails.forEach(detail => {
          // Don't close the main wrapper.
          if (detail.id !== 'groundwork-styles-wrapper') {
            detail.open = false;
          }
        });

        // If the search is cleared, just show all options and exit.
        if (searchTerm === '') {
          allOptions.forEach(option => {
            option.style.display = '';
          });
          return;
        }

        // Find matches and make them and their parents visible.
        allOptions.forEach(option => {
          const label = option.querySelector('label');
          if (label) {
            const labelText = label.textContent.toLowerCase();
            if (labelText.includes(searchTerm)) {
              // Show the matching option.
              option.style.display = '';
              // Find the closest parent <details> and open it.
              let parentDetails = option.closest('details');
              while (parentDetails) {
                // Ensure we don't try to open the main wrapper.
                if (parentDetails.id !== 'groundwork-styles-wrapper') {
                  parentDetails.open = true;
                }
                // Move up to the next parent <details>.
                parentDetails = parentDetails.parentElement.closest('details');
              }
            }
          }
        });
      });
    },
  };
})(Drupal, once);

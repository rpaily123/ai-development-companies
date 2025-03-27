jQuery(document).ready(function($) {
    const $form = $('#disk-prices-filter');
    const $results = $('#disk-prices-results');
    let filterTimeout;

    // Handle form submission
    $form.on('submit', function(e) {
        e.preventDefault();
        updateResults();
    });

    // Handle input changes with debounce
    $form.find('select, input').on('change', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(updateResults, 500);
    });

    // Function to update results via AJAX
    function updateResults() {
        // Show loading state
        $results.addClass('disk-prices-loading');

        // Get form data
        const formData = new FormData($form[0]);
        formData.append('action', 'disk_prices_filter');
        formData.append('nonce', diskPricesAjax.nonce);

        // Send AJAX request
        $.ajax({
            url: diskPricesAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $results.html(response.data.html);
                    
                    // Update URL with filter parameters
                    updateURL(formData);
                } else {
                    $results.html('<p class="no-results">Error loading results. Please try again.</p>');
                }
            },
            error: function() {
                $results.html('<p class="no-results">Error loading results. Please try again.</p>');
            },
            complete: function() {
                $results.removeClass('disk-prices-loading');
            }
        });
    }

    // Function to update URL with filter parameters
    function updateURL(formData) {
        const params = new URLSearchParams();
        
        // Add non-empty form values to URL
        for (const [key, value] of formData.entries()) {
            if (value && key !== 'action' && key !== 'nonce') {
                params.append(key, value);
            }
        }

        // Update URL without reloading the page
        const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.pushState({}, '', newURL);
    }

    // Handle browser back/forward buttons
    window.onpopstate = function() {
        const params = new URLSearchParams(window.location.search);
        
        // Update form values from URL
        for (const [key, value] of params.entries()) {
            const $field = $form.find(`[name="${key}"]`);
            if ($field.length) {
                $field.val(value);
            }
        }
        
        // Update results
        updateResults();
    };

    // Initialize price range inputs
    const $priceMin = $('#price_min');
    const $priceMax = $('#price_max');

    // Validate price range
    function validatePriceRange() {
        const min = parseFloat($priceMin.val());
        const max = parseFloat($priceMax.val());
        
        if (!isNaN(min) && !isNaN(max) && min > max) {
            $priceMin.val(max);
        }
    }

    $priceMin.on('change', validatePriceRange);
    $priceMax.on('change', validatePriceRange);

    // Add number input validation
    $priceMin.add($priceMax).on('input', function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
    });
}); 
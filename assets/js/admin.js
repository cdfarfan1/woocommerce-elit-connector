jQuery(document).ready(function($) {
    // Test Connection Button
    $('#test-elit-connection').on('click', function() {
        const resultDiv = $('#elit-connection-result');
        resultDiv.html('<span class="spinner is-active"></span> Probando...');

        $.post(elit_ajax_object.ajax_url, {
            action: 'test_elit_connection',
            _ajax_nonce: elit_ajax_object.nonce
        }, function(response) {
            if (response.success) {
                resultDiv.html('<div style="color: green;">' + response.data.message + '</div>');
            } else {
                resultDiv.html('<div style="color: red;">' + response.data.message + '</div>');
            }
        });
    });

    // Product Preview Button
    $('#generate_preview').on('click', function() {
        const sku = $('#preview_sku').val();
        const resultDiv = $('#preview_result');
        
        if (!sku) {
            resultDiv.html('<div style="color: red;">Por favor, introduce un SKU.</div>');
            return;
        }

        resultDiv.html('<span class="spinner is-active"></span> Generando vista previa...');

        $.post(elit_ajax_object.ajax_url, {
            action: 'get_elit_product_preview',
            _ajax_nonce: elit_ajax_object.nonce,
            sku: sku
        }, function(response) {
            if (response.success) {
                 let html = '<h4>' + response.data.name + '</h4>';
                 html += '<p><strong>SKU:</strong> ' + response.data.sku + '</p>';
                 html += '<p><strong>Precio:</strong> $' + response.data.price + '</p>';
                 html += '<p><strong>Stock:</strong> ' + response.data.stock + '</p>';
                 html += '<img src="' + response.data.image_url + '" style="max-width: 150px; height: auto; margin-top: 10px;">';
                 resultDiv.html(html);
            } else {
                resultDiv.html('<div style="color: red;">' + response.data.message + '</div>');
            }
        });
    });

    // Manual Sync Buttons
    $('#elit-full-sync').on('click', function() {
        sync(false);
    });

    $('#elit-desc-sync').on('click', function() {
        sync(true);
    });

    function sync(onlyDescriptions) {
        const resultDiv = $('#elit-sync-results');
        resultDiv.html('<span class="spinner is-active"></span> Sincronizando...');

        const action = onlyDescriptions ? 'elit_desc_sync' : 'elit_full_sync';

        $.post(elit_ajax_object.ajax_url, {
            action: action,
            _ajax_nonce: elit_ajax_object.nonce
        }, function(response) {
            if (response.success) {
                resultDiv.html('<div style="color: green;">' + response.data.message + '</div>');
            } else {
                resultDiv.html('<div style="color: red;">' + response.data.message + '</div>');
            }
        });
    }
});

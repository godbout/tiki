(function($){
    var $doc = $(document);
    var $container = $('#tiki-admin_oauthserver');
    var $client_form_tpl = $('form.js-oauth-client:last').clone();

    $client_form_tpl.find(':input').each(function(){
        var $input = $(this);
        if (!($input.is('button') && $input.prop('type') === 'submit')) {
            $input.val('');
        }
    });

    $doc.on('click', 'form.js-oauth-client :input[type=submit]', function(evt){
        evt.preventDefault();
        var $form = $(this).parents('form.js-oauth-client:first');
        var data = $form.serializeArray();

        if ($(this).attr('name') === 'delete') {
            data.push({'name': 'delete', 'value': '1'});
        }

        data = data.reduce(function(obj, el) {
            obj[ el.name ] = el.value;
            return obj;
        }, {});

        $form.trigger('submit', [ data ]);
    });

    $doc.on('submit', 'form.js-oauth-client', function(evt, data) {
        evt.preventDefault();
        var $form = $(this);
        data = data || $form.serializeArray();
        var endpoint = $form.prop('action');

        $.ajax({
            data: data,
            dataType: 'json',
            type: 'POST',
            url: endpoint
        })
        .always(function(){
            $form.find('.validation-error').remove()
        })
        .done(function(content, status, resp){
            if(data.delete) {
                return $form.trigger('client-deleted', [content, resp]);
            } else if(!data.id || data.id == '0') {
                return $form.trigger('client-created', [content, resp]);
            }
            return $form.trigger('client-updated', [content, resp]);
        })
        .fail(function(resp, status){
            var content = resp.responseJSON || resp.responseText;

            if(data.delete) {
                return $form.trigger('client-delete-failed', [content, resp]);
            } else if(!data.id || data.id == '0') {
                return $form.trigger('client-create-failed', [content, resp]);
            }
            return $form.trigger('client-update-failed', [content, resp]);
        });
    });

    $doc.on('client-deleted', 'form.js-oauth-client', function(evt, content) {
        var $form = $(this);
        $form.hide('fast', function(){
            $form.remove(); 
        });
    })

    $doc.on('client-created', 'form.js-oauth-client', function(evt, content) {
        var $form = $(this);

        for (var name in content) {
            var value = content[name];
            $form.find(':input[name=' + name + ']').val(value);
        }

        $container.append($client_form_tpl.clone());
        feedback('Record created successfully');
    })

    $doc.on('client-updated', 'form.js-oauth-client', function(evt, content) {
        feedback('Record updated successfully');
    })

    $doc.on(
        'client-update-failed client-create-failed',
        'form.js-oauth-client',
        function(evt, content) {
            var $form = $(this);
            var $input;

            for(var att in content) {
                $input = $form.find(':input[name='+att+']');
                $('<p class="text-danger validation-error">')
                    .text(content[att])
                    .insertAfter($input);
            }
        }
    );
})(jQuery)
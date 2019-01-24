(function($){
    var $doc = $(document);
    var $client_form_tpl = $('form.js-client:last');

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
        .done(function(content, status, resp){
            if(data.delete) {
                return $form.trigger('client-deleted', [content, resp]);
            } else if(!data.identifier || data.identifier == '0') {
                return $form.trigger('client-created', [content, resp]);
            }
            return $form.trigger('client-updated', [content, resp]);
        })
        .fail(function(content, status, resp){
            if(data.delete) {
                return $form.trigger('client-delete-failed', [content, resp]);
            } else if(!data.identifier) {
                return $form.trigger('client-create-failed', [content, resp]);
            }
            return $form.trigger('client-update-failed', [content, resp]);
        })
    });

    $doc.on('client-deleted', 'form.js-oauth-client', function(evt, content) {
        var $form = $(this);
        $form.hide('fast', function(){
            $form.remove(); 
            feedback('Record deleted successfully');
        });
    })

    $doc.on('client-created', 'form.js-oauth-client', function(evt, content) {
        var $form = $(this);

        for (var name in content) {
            var value = content[name];
            $form.find(':input[name=' + name + ']').val(value);
        }

        feedback('Record created successfully');
    })

    $doc.on('client-updated', 'form.js-oauth-client', function(evt, content) {
        feedback('Record updated successfully');
    })


})(jQuery)
(function($){
    var $modals = null;
    var $modal = null;
    var $html = $(document.documentElement);
    var $body = $(document.body);
    var $converse = $('#conversejs');

    var Template = function(tpl) {
        this.content = {};

        this.setContent = function(content) {
            this.content = content;
            return this;
        }

        this.render = function() {
            var self = this;
            return tpl.replace(/\{\{ *(\w+) *\}\}/g, function(g0, g1){
                var value = self.content[g1];
                if(typeof value === 'string' || value instanceof String) {
                    return value;
                }
                return '';
            });
        }
    };

    var tplform = $('#wikiplugin_xmpp_tplform').html();
    var tplitem = $('#wikiplugin_xmpp_tplitem').html();

    function show_modal(data) {
        $modals = $modals || $('.modal.fade');
        $modal = $modals.filter(':not(.show)').first();
        var items = data['items'] || [];
        var form = new Template(tplform);

        form.setContent({
            'title': data['title'],
            'action': data['action'],
            'room': data['room'],
            'next': data['next'],
            'items': items
                .map(function(item){
                    if(!item.fullname) {
                        item.fullname = item.jid;
                    }
                    return item;
                })
                .reduce(function(html, item){
                    return html + (new Template(tplitem))
                        .setContent(item)
                        .render();
            }, '')
        });

        $modal.find('.modal-dialog').html(form.render());
        $modal.modal('show');
    }

    $(document).on('submit', 'form.wikiplugin-xmpp-form', function(evt){
        evt.preventDefault();
        var $form = $(this);
        var room = $form.find(':input[name=room]').val();

        var cursor = document.body.style.cursor;
        document.body.style.cursor = 'wait';

        $.ajax({
            data: $form.serialize(),
            dataType: 'json',
            type: 'POST',
            url: $form.prop('action')
        })
        .done(function(response){
            $modal && $modal.modal('hide');
            var msg = response.length + ' users added to ' + room;
            feedback(msg, 'success');
        })
        .fail(function(){
            var msg = 'Failed to add users to ' + room;
            feedback(msg, 'error');
        })
        .always(function() {
            document.body.style.cursor = cursor;
        });

        return false;
    });

    $(document).on('click', 'a[data-xmpp]', function(evt) {
        evt.preventDefault();
        var $this = $(this);

        var data = {
            'title': $this.text(),
            'room': $this.data('xmpp'),
            'action': $this.data('xmpp-action'),
            'items': [
            ]
        };

        var cursor = document.body.style.cursor;
        document.body.style.cursor = 'wait';

        $.ajax({
            url: data['action'],
            dataType: 'json'
        })
        .done(function(items){
            data.items = items;
            show_modal(data);
        })
        .always(function() {
            document.body.style.cursor = cursor;
        });

        return false;
    });

    if ($converse.data('view-mode') === 'fullscreen') {
        $body
            .css('border-width', '0')
            .css('height', '100vh')
            .css('margin', '0')
            .css('overflow', 'hidden')
            .css('padding', '0');

        $converse.remove()
            .css('width', '100%')
            .css('height', '100%')
            .css('position', 'fixed')
            .css('left', 0)
            .css('top', 0)
            .appendTo($body);
    }

})(jQuery);
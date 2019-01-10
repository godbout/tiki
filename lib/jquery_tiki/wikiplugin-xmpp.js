(function($){
    var $modals = null;
    var $modal = null;

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

    var tplform = ''
        + '<div class="modal-content">'
        + '    <form id="wikiplugin-xmpp-form" action="{{ action }}" method="POST">'
        + '        <input type="hidden" name="room" value="{{ room }}">'
        + '        <div class="modal-header">'
        + '            <h4 class="modal-title" id="myModalLabel">{{ title }}</h4>'
        + '        </div>'
        + '        <div class="modal-body">'
        + '            <div class="row">{{ items }}</div>'
        + '        </div>'
        + '        <div class="modal-footer">'
        + '            <button type="button" class="btn btn-secondary btn-dismiss" data-dismiss="modal">Close</button>'
        + '            <input type="submit" class="btn btn-primary" value="Add"/>'
        + '        </div>'
        + '    </form>'
        + '</div>';

    var tplitem = ''
        +'<div class="col-md-6">'
        +'    <label for="input_{{ name }}">'
        +'        <input type="checkbox" name="item[][jid]" id="input_{{ name }}" value="{{ jid }}"/>'
        +'        <span>{{ fullname }}</span>'
        +'    </label>'
        +'</div>';

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
            'items': items.reduce(function(html, item){
                return html + (new Template(tplitem))
                    .setContent(item)
                    .render();
            }, '')
        });

        $modal.find('.modal-dialog').html(form.render());
        $modal.modal('show');
    }

    $(document).on('submit', '#wikiplugin-xmpp-form', function(evt){
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

})(jQuery);
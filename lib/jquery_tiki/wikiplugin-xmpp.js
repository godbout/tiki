(function($){
    var $modals = null;
    var $form = null;

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
        + '    <form action="{{ action }}" method="POST">'
        + '        <input type="hidden" name="room" value="{{ room }}">'
        + '        <input type="hidden" name="next" value="{{ next }}">'
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
        +'    <label for="input_{{ id }}">'
        +'        <input type="checkbox" name="item[]" id="input_{{ id }}" value="{{ name }}"/>'
        +'        <span>{{ name }}</span>'
        +'    </label>'
        +'</div>';

    function show_modal(data) {
        $modals = $modals || $('.modal.fade');
        var $modal = $modals.filter(':not(.show)').first();
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

    $(document).on('click', 'a[data-xmpp]', function(evt) {
        evt.preventDefault();
        var $this = $(this);

        var data = {
            'title': $this.text(),
            'room': $this.data('xmpp'),
            'next': escape(document.location.href),
            'action': $this.data('xmpp-action'),
            'items': [
            ]
        };

        $.ajax({
            url: data['action'],
            dataType: 'json'
        })
        .done(function(items){
            data.items = items;
            show_modal(data);
        });

        return false;
    });

})(jQuery);
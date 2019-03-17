
<script type="text/template" id="wikiplugin_xmpp_tplform">
    <!-- the modal form to select users -->
    <div class="modal-content">
        <form class="wikiplugin-xmpp-form" action="{{ action }}" method="POST">
            <input type="hidden" name="room" value="{{ room }}" />
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">{{ title }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">{{ items }}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-dismiss" data-dismiss="modal">{tr}Close{/tr}</button>
                <input type="submit" class="btn btn-primary" value="{tr}Add{/tr}"/>
            </div>
        </form>
    </div></script>
<script type="text/template" id="wikiplugin_xmpp_tplitem">
    <!-- the form items -->
    <div class="col-md-6">
        <label for="input_{{ name }}">
            <input type="checkbox" name="item[][jid]" id="input_{{ name }}" value="{{ jid }}"/>
            <span>{{ fullname }}</span>
        </label>
    </div></script>

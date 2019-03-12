converse.plugins.add("tiki", {
    "dependencies": [
        'converse-muc-views',
        'converse-controlbox'
    ],

    "initialize": function () {
        var _converse = window._converse = this._converse;
        var error = console && console.error
            ? console.error.bind(console)
            : function (msg) { return feedback(msg, "error", false); };
        var tr = window.tr 
            ? tr
            : function(str) { return str; };

        _converse.api.listen.on("noResumeableSession", function (xhr) {
            error(tr("XMPP Module error") + ": " + xhr.statusText);
            $("#conversejs").fadeOut("fast");
        });
    },

    "overrides":{
        "ChatRoomView": {
            "createOccupantsView": function() {
                var _converse = this.__super__._converse;
                
                this.model.occupants.chatroomview = this;
                this.occupantsview = new _converse.ChatRoomOccupantsView({
                    'model': this.model.occupants
                });

                this.model.save({
                    'hidden_occupants': window.innerWidth < 576
                });

                return this;
            },
        },
        "ChatBoxes": {
            "chatBoxMayBeShown": function(chatbox) {
                var _converse = this.__super__._converse;
                if (chatbox.get('id') === 'controlbox') {
                    return _converse.show_controlbox_by_default === true
                        && window.innerWidth >= 1024
                        && window.innerHeight >= 768;
                }
                return this.__super__.chatBoxMayBeShown(chatbox);
            }
        }
    }

});
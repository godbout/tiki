converse.plugins.add("tiki", {
    "dependencies": [
        'converse-muc-views',
        'converse-controlbox',
        'converse-chatview',
        'converse-bookmarks',
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
        "Bookmarks": {
            "openBookmarkedRoom": function (bookmark) {
                if (bookmark.get('autojoin')) {
                    const groupchat = _converse.api.rooms.create(bookmark.get('jid'), bookmark.get('nick'));
                    if (!(groupchat.get('hidden') || groupchat.get('minimized'))) {
                        groupchat.trigger('show');
                    }
                }
                return bookmark;
            },
        },
        "ChatRoomView": {
            "initialize": function () {
                this.model.set('minimized', this.is_chatroom === true);
                this.__super__.initialize.apply(this, arguments);
            },
            "createOccupantsView": function() {
                var _converse = this.__super__._converse;

                this.model.occupants.chatroomview = this;
                this.occupantsview = new _converse.ChatRoomOccupantsView({
                    'model': this.model.occupants
                });

                this.model.save({
                    'hidden_occupants': _converse.authentication === "anonymous"
                        || window.innerWidth < 576
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
        },
        "ChatBoxView":{
            "renderMessageForm": function() {
                var _converse = this.__super__._converse;
                var form_container = this.el.querySelector('.message-form-container');
                var object = window.sessionStorage.getItem(_converse.jid);

                if (object) {
                    object = JSON.parse(object);
                }
                var nickSet = object ? object.nickSet : null;

                if (_converse.authentication === "anonymous" && !nickSet) {
                    form_container.classList.add('hidden');
                    this.__super__.renderMessageForm();

                    var message_box = form_container.querySelector('.message-form-container');
                    message_box.classList.add('hidden');
                    form_container.classList.remove('hidden');

                    this.renderNickResetBox(form_container);
                    return this;
                }

                this.__super__.renderMessageForm();
                return this;
            },

            "onNickReset": function(evt, nick) {
                var _converse = this.__super__._converse;

                if(!(typeof nick === "string" || (nick instanceof String))) {
                    return;
                }

                nick = nick.trim().replace(/\s+/g, ' ');
                if (!nick.length) {
                    return;
                }

                var object = window.sessionStorage.getItem(_converse.jid);
                object = object || {};
                object.nickSet = nick;

                this.parseMessageForCommands('/nick ' + nick);
                var message_box = this.el.querySelector('.message-form-container.hidden')
                var nick_reset_box = this.el.querySelector('.tiki-reset-box');
                window.sessionStorage.setItem(_converse.jid, JSON.stringify(object));

                nick_reset_box.classList.add('hidden');
                message_box.classList.remove('hidden');
            },

            "renderNickResetBox": function (container) {
                var resetBox = document.createElement('div');
                resetBox.classList.add('tiki-reset-box', 'form-row', 'px-1', 'py-1', 'bg-secondary');

                var col = document.createElement('div');
                col.classList.add('col');
                resetBox.appendChild(col);

                var input = document.createElement('input');
                input.type="text";
                input.placeholder="Type a nick";
                input.classList.add('form-control');
                col.appendChild(input);

                col = document.createElement('div');
                col.classList.add('col', 'btn-group');
                resetBox.appendChild(col);

                var button = document.createElement('button');
                button.innerText = 'Join';
                button.classList.add('btn', 'btn-info');
                col.appendChild(button);

                var onNickReset = this.onNickReset.bind(this);
                button.addEventListener("click", function (evt) {
                    return onNickReset(evt, input.value);
                });
                input.addEventListener("keydown", function(evt){
                    return evt.keyCode === 13 && onNickReset(evt, input.value);
                });

                button = document.createElement('a');
                button.href = 'tiki-register.php';
                button.innerText = 'Register';
                button.classList.add('btn', 'btn-success', 'text-white');
                col.appendChild(button);

                container.appendChild(resetBox);
                return this;
            },
        }
    }

});
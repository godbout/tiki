var tiki_groupmail_content = function(id, folder) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_groupmail'},
        {'name': 'folder', 'value': folder},
        {'name': 'imap_server_ids', 'value': id}],
        function(res) {
            var ids = res.imap_server_ids.split(',');
            if (folder) {
                var i;
                for (i=0;i<ids.length;i++) {
                    ids[i] = ids[i]+'_'+Hm_Utils.clean_selector(folder);
                }
            }
            if (res.auto_sent_folder) {
                add_auto_folder(res.auto_sent_folder);
            }
            Hm_Message_List.update(ids, res.formatted_message_list, 'imap');
        },
        [],
        false,
        function() { Hm_Message_List.set_message_list_state('formatted_tiki_groupmail'); }
    );
    return false;
};

var tiki_groupmail_take = function(btn, id) {
    var detail = Hm_Utils.parse_folder_path(id);
    $(btn).text(tr('Taking')+'...');
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_take_groupmail'},
        {'name': 'msgid', 'value': id},
        {'name': 'imap_msg_uid', 'value': detail.uid},
        {'name': 'imap_server_id', 'value': detail.server_id},
        {'name': 'folder', 'value': detail.folder}],
        function(res) {
            if (res.operator) {
                $(btn).text(res.operator);
            } else {
                $(btn).text(tr('TAKE'));
            }
            tiki_groupmail_content(detail.server_id, detail.folder);
        },
        [],
        false
    );
}

var tiki_groupmail_put_back = function(btn, id) {
    var detail = Hm_Utils.parse_folder_path(id);
    $(btn).text(tr('Putting back')+'...');
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_put_back_groupmail'},
        {'name': 'msgid', 'value': id},
        {'name': 'imap_msg_uid', 'value': detail.uid},
        {'name': 'imap_server_id', 'value': detail.server_id},
        {'name': 'folder', 'value': detail.folder}],
        function(res) {
            if (res.item_removed) {
                $(btn).text(tr('TAKE'));
            }
            tiki_groupmail_content(detail.server_id, detail.folder);
        },
        [],
        false
    );
}

var tiki_event_rsvp_actions = function() {
    $(document).on("click", '.event_rsvp_link', function(e) {
        var uid = hm_msg_uid();
        var detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
        var $btn = $(this);
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_rsvp_action'},
            {'name': 'rsvp_action', 'value': $btn.data('action')},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            function(res) {
                $.each($('span.event_rsvp_link'), function(i,el) {
                    tiki_event_rsvp_button(el);
                });
                tiki_event_rsvp_button($btn[0]);
            },
            [],
            false
        );
    });
    $(document).on("change", 'select.event_calendar_select', function(e) {
        var uid = hm_msg_uid();
        var detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
        var $btn = $(this);
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_add_to_calendar'},
            {'name': 'calendar_id', 'value': $(this).val()},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            function(res) {
                // noop
            },
            [],
            false
        );
    });
    $(document).on("click", '.event_update_participant_status', function(e) {
        e.preventDefault();
        var uid = hm_msg_uid();
        var detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
        var $btn = $(this);
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_update_participant_status'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            function(res) {
                // noop
            },
            [],
            false
        );
    });
    $(document).on("click", '.event_remove_from_calendar', function(e) {
        e.preventDefault();
        var uid = hm_msg_uid();
        var detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
        var $btn = $(this);
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_remove_from_calendar'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            function(res) {
                // noop
            },
            [],
            false
        );
    });
}

var tiki_event_rsvp_button = function(el) {
    var attrs = { };
    $.each(el.attributes, function(idx, attr) {
        attrs[attr.nodeName] = attr.nodeValue;
    });
    $(el).replaceWith(function () {
        var type = $(this).is('a') ? 'span' : 'a';
        return $("<"+type+">", attrs).append($(this).html());
    });
}

var tiki_mobilecheck = function () {
    (function (a) {
        (jQuery.browser = jQuery.browser || {}).mobile = /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))
    })(navigator.userAgent || navigator.vendor || window.opera);
    return jQuery.browser.mobile;
};

var tiki_Hm_Ajax_Request = function() {
    var new_request = new Hm_Ajax_Request();
    new_request.fail = function(xhr, not_callable) {
        if (xhr.status && xhr.status == 500) {
            Hm_Notices.show(['ERRInternal Server Error - check server log file for details.']);
        } else if (not_callable === true) {
            Hm_Notices.show(['ERRCould not perform action - your session probably expired. Please reload page.']);
        } else {
            $('.offline').show();
        }
        Hm_Ajax.err_condition = true;
        this.run_on_failure();
    };
    new_request.format_xhr_data = function(data) {
        var res = []
        for (var i in data) {
            res.push(encodeURIComponent(data[i]['name']) + '=' + encodeURIComponent(data[i]['value']));
        }
        if ($('#hm_session_prefix').length > 0) {
            res.push(encodeURIComponent('hm_session_prefix') + '=' + encodeURIComponent($('#hm_session_prefix').val()));
        }
        return res.join('&');
    };
    return new_request;
}

/* executes on onload, has access to other module code */
$(function() {
    if (hm_page_name() == 'groupmail') {
        Hm_Message_List.select_combined_view();
        $('.content_cell').swipeDown(function(e) { e.preventDefault(); Hm_Message_List.load_sources(); });
        $('.source_link').click(function() { $('.list_sources').toggle(); return false; });
    }

    if (hm_page_name() == 'message') {
        tiki_event_rsvp_actions();
    }

    if (tiki_mobilecheck()) {
        if (! $('body').hasClass('mobile')) $('body').addClass('mobile');
    }

    if (! $('body').hasClass('tiki-cypth')) $('body').addClass('tiki-cypht');
    $('.mobile .folder_cell').detach().appendTo('body');

    $('.mobile .folder_toggle').click(function(){
        $('.mobile .folder_cell').toggleClass('slide-in');
        if ($(this).attr('style') == '') $('.mobile .folder_list').hide();
    });

    $('.inline-cypht .chosen-container').each(function () {
        $(this).prev().addClass('unchosen');
    });
});

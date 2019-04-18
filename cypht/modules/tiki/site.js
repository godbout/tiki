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
        {'name': 'uid', 'value': detail.uid},
        {'name': 'server_id', 'value': detail.server_id},
        {'name': 'folder', 'value': detail.folder}],
        function(res) {
            if (res.error) {
                feedback(res.error, 'error');
                $(btn).text(tr('TAKE'));
            } else {
                $(btn).text(res.operator);
            }
        },
        [],
        false
    );
}

/* executes on onload, has access to other module code */
$(function() {
    if (hm_page_name() == 'groupmail') {
        Hm_Message_List.select_combined_view();
        $('.content_cell').swipeDown(function(e) { e.preventDefault(); Hm_Message_List.load_sources(); });
        $('.source_link').click(function() { $('.list_sources').toggle(); return false; });
    }
});

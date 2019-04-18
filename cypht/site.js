
var swipe_event = function(el, callback, direction) {
    var start_x, start_y, dist_x, dist_y, threshold = 150, restraint = 100,
        allowed_time = 500, start_time;

    el.addEventListener('touchstart', function(e) {
        var touchobj = e.changedTouches[0];
        start_x = touchobj.pageX;
        start_y = touchobj.pageY;
        start_time = new Date().getTime();
    }, false);

    el.addEventListener('touchend', function(e) {
        var touchobj = e.changedTouches[0];
        dist_x = touchobj.pageX - start_x;
        dist_y = touchobj.pageY - start_y;
        if ((new Date().getTime() - start_time) <= allowed_time) {
            if (Math.abs(dist_x) >= threshold && Math.abs(dist_y) <= restraint) {
                var dir = (dist_x < 0) ? 'left' : 'right';
                if (dir == direction) {
                    callback();
                }
            }
        }
    }, false);
};

/* ajax multiplexer */
var Hm_Ajax = {
    batch_callbacks: {},
    callback_hooks: [],
    p_callbacks: [],
    aborted: false,
    err_condition: false,
    batch_callback: false,
    active_reqs: 0,
    icon_loading_id: false,

    get_ajax_hook_name: function(args) {
        var index;
        for (index in args) {
            if (args[index]['name'] == 'hm_ajax_hook') {
                return args[index]['value'];
            }
        }
        return;
    },

    request: function(args, callback, extra, no_icon, batch_callback, on_failure) {
        var bcb = false;
        if (typeof batch_callback != 'undefined' && $.inArray(batch_callback, this.batch_callbacks) === -1) {
            bcb = batch_callback.toString();
            var detail = Hm_Ajax.batch_callbacks[bcb];
            if (typeof detail !== 'undefined') {
                Hm_Ajax.batch_callbacks[bcb] += 1;
            }
            else {
                Hm_Ajax.batch_callbacks[bcb] = 1;
            }
        }
        var name = Hm_Ajax.get_ajax_hook_name(args);
        var ajax = new Hm_Ajax_Request();
        if (!no_icon) {
            Hm_Ajax.show_loading_icon();
            $('body').addClass('wait');
        }
        Hm_Ajax.active_reqs++;
        return ajax.make_request(args, callback, extra, name, on_failure, batch_callback);
    },

    show_loading_icon: function() {
        if (Hm_Ajax.icon_loading_id !== false) {
            return;
        }
        var hm_loading_pos = $('.loading_icon').width()/40;
        $('.loading_icon').show();
        function move_background_image() {
            hm_loading_pos = hm_loading_pos + 50;
            $('.loading_icon').css('background-position', hm_loading_pos+'px 0');
            Hm_Ajax.icon_loading_id = setTimeout(move_background_image, 100);
        }
        move_background_image();
    },

    stop_loading_icon : function(loading_id) {
        clearTimeout(loading_id);
        $('.loading_icon').hide();
        Hm_Ajax.icon_loading_id = false;
    },

    process_callback_hooks: function(name, res) {
        var hook;
        var func;
        for (var i in Hm_Ajax.callback_hooks) {
            hook = Hm_Ajax.callback_hooks[i];
            if (hook[0] == name || hook[0] == '*') {
                func = hook[1];
                func(res);
                if (hook[0] == '*') {
                    if ($.inArray(hook, Hm_Ajax.p_callbacks) === -1) {
                        Hm_Ajax.p_callbacks.push(hook);
                    }
                }
            }
        }
    },

    add_callback_hook: function(request_name, hook_function) {
        Hm_Ajax.callback_hooks.push([request_name, hook_function]);
    }
};

/* ajax request wrapper */
var Hm_Ajax_Request = function() { return { 
    callback: false,
    name: false,
    batch_callback: false,
    index: 0,
    on_failure: false,
    start_time: 0,

    xhr_fetch: function(config) {
        var xhr = new XMLHttpRequest();
        var data = '';
        if (config.data) {
            data = this.format_xhr_data(config.data);
        }
        xhr.open('POST', 'cypht/ajax.php')
        xhr.addEventListener('load', function() {
            config.callback.done(Hm_Utils.json_decode(xhr.response, true), xhr);
            config.callback.always(Hm_Utils.json_decode(xhr.response, true));
        });
        xhr.addEventListener('error', function() {
            Hm_Ajax.stop_loading_icon(Hm_Ajax.icon_loading_id);
            config.callback.fail(xhr);
            config.callback.always(Hm_Utils.json_decode(xhr.response, true));
        });
        xhr.addEventListener('abort', function() {
            Hm_Ajax.stop_loading_icon(Hm_Ajax.icon_loading_id);
            config.callback.fail(xhr);
            config.callback.always(Hm_Utils.json_decode(xhr.response, true));

        });
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-with', 'xmlhttprequest');
        xhr.send(data);
    },

    format_xhr_data: function(data) {
        var res = []
        for (var i in data) {
            res.push(encodeURIComponent(data[i]['name']) + '=' + encodeURIComponent(data[i]['value']));
        }
        return res.join('&');
    },

    make_request: function(args, callback, extra, request_name, on_failure, batch_callback) {
        var name;
        var arg;
        this.batch_callback = batch_callback;
        this.name = request_name;
        this.callback = callback;
        if (on_failure) {
            this.on_failure = true;
        }
        if (extra) {
            for (name in extra) {
                args.push({'name': name, 'value': extra[name]});
            }
        }
        var key_found = false;
        for (arg in args) {
            if (args[arg].name == 'hm_page_key') {
                key_found = true;
                break;
            }
        }
        if (!key_found) {
            args.push({'name': 'hm_page_key', 'value': $('#hm_page_key').val()});
        }
        var dt = new Date();
        this.start_time = dt.getTime();
        this.xhr_fetch({url: 'cypht/ajax.php', data: args, callback: this});
        return false;
    },

    done: function(res, xhr) {
        if (Hm_Ajax.aborted) {
            return;
        }
        else if (!res || typeof res == 'string' && (res == 'null' || res.indexOf('<') === 0 || res == '{}')) {
            this.fail(xhr);
            return;
        }
        else {
            $('.offline').hide();
            if (hm_encrypt_ajax_requests()) {
                res = Hm_Utils.json_decode(Hm_Crypt.decrypt(res.payload));
            }
            if ((res.state && res.state == 'not callable') || !res.router_login_state) {
                this.fail(xhr, true);
                return;
            }
            if (Hm_Ajax.err_condition) {
                Hm_Ajax.err_condition = false;
                Hm_Notices.hide(true);
            }
            if (res.router_user_msgs && !$.isEmptyObject(res.router_user_msgs)) {
                Hm_Notices.show(res.router_user_msgs);
            }
            if (res.folder_status) {
                for (var name in res.folder_status) {
                    Hm_Folders.unread_counts[name] = res.folder_status[name]['unseen'];
                    Hm_Folders.update_unread_counts();
                }
            }
            if (this.callback) {
                this.callback(res);
            }
            Hm_Ajax.process_callback_hooks(this.name, res);
        }
    },

    run_on_failure: function() {
        if (this.on_failure && this.callback) {
            this.callback(false);
        }
        return false;
    },

    fail: function(xhr, not_callable) {
        if (not_callable === true || (xhr.status && xhr.status == 500)) {
            Hm_Notices.show(['ERRServer Error']);
        }
        else {
            $('.offline').show();
        }
        Hm_Ajax.err_condition = true;
        this.run_on_failure();
    },

    always: function(res) {
        Hm_Ajax.active_reqs--;
        var batch_count = 1;
        if (this.batch_callback) {
            if (typeof Hm_Ajax.batch_callbacks[this.batch_callback.toString()] != 'undefined') {
                batch_count = --Hm_Ajax.batch_callbacks[this.batch_callback.toString()];
            }
        }
        Hm_Message_List.set_checkbox_callback();
        if (batch_count === 0) {
            Hm_Ajax.batch_callbacks[this.batch_callback.toString()] = 0;
            Hm_Ajax.aborted = false;
            Hm_Ajax.p_callbacks = [];
            this.batch_callback(res);
            this.batch_callback = false;
            Hm_Ajax.stop_loading_icon(Hm_Ajax.icon_loading_id);
            $('body').removeClass('wait');
        }
        if (Hm_Ajax.active_reqs == 0) {
            Hm_Ajax.stop_loading_icon(Hm_Ajax.icon_loading_id);
            $('body').removeClass('wait');
        }
        res = null;
    }
}};

/* user notification manager */
var Hm_Notices = {
    hide_id: false,

    show: function(msgs) {
        var msg_list = [];
        for (var i in msgs) {
            if (msgs[i].match(/^ERR/)) {
                msg_list.push('<span class="err">'+msgs[i].substring(3)+'</span>');
            }
            else {
                msg_list.push(msgs[i]);
            }
        }
        $('.sys_messages').html(msg_list.join(', '));
        $('.sys_messages').show();
        $('.sys_messages').on('click', function() {
            $('.sys_messages').hide();
            $('.sys_messages').html('');
        });
    },

    hide: function(now) {
        if (Hm_Notices.hide_id) {
            clearTimeout(Hm_Notices.hide_id);
        }
        if (now) {
            $('.sys_messages').hide();
            $('.sys_messages').html('');
        }
        else {
            Hm_Notices.hide_id = setTimeout(function() {
                $('.sys_messages').hide();
                $('.sys_messages').html('');
            }, 5000);
        }
    }
};

/* job scheduler */
var Hm_Timer = {
    jobs: [],
    interval: 1000,

    add_job: function(job, interval, defer, custom_defer) {
        if (custom_defer) {
            Hm_Timer.jobs.push([job, interval, custom_defer]);
        }
        else if (interval) {
            Hm_Timer.jobs.push([job, interval, interval]);
        }
        if (!defer) {
            try { job(); } catch(e) { console.log(e); }
        }
    },

    cancel: function(job) {
        for (var index in Hm_Timer.jobs) {
            if (Hm_Timer.jobs[index][0] == job) {
                Hm_Timer.jobs.splice(index, 1);
                return true;
            }
        }
        return false;
    },

    fire: function() {
        var job;
        var index;
        for (index in Hm_Timer.jobs) {
            job = Hm_Timer.jobs[index];
            job[2]--;
            if (job[2] === 0) {
                job[2] = job[1];
                Hm_Timer.jobs[index] = job;
                try { job[0](); } catch(e) { console.log(e); }
            }
        }
        setTimeout(Hm_Timer.fire, Hm_Timer.interval);
    }
};

/* message list */
function Message_List() {
    var self = this;
    this.sources = [];
    this.deleted = [];
    this.background = false;
    this.completed_count = 0;
    this.callbacks = [];
    this.sort_fld = 4;
    this.past_total = 0;
    this.just_inserted = [];

    this.page_caches = {
        'feeds': 'formatted_feed_data',
        'combined_inbox': 'formatted_combined_inbox',
        'email': 'formatted_all_mail',
        'unread': 'formatted_unread_data',
        'flagged': 'formatted_flagged_data'
    };

    this.run_callbacks = function (completed) {
        var func;
        var index;
        if (completed) {
            for (index in this.callbacks) {
                func = this.callbacks[index];
                try { func(); } catch(e) { console.log(e); }
            }
        }
    };

    this.update = function(ids, msgs, type, cache) {
        var completed = false;
        this.completed_count++;
        if (this.completed_count == this.sources.length) {
            this.completed_count = 0;
            completed = true;
        }
        if ($('input[type=checkbox]', $('.message_table')).filter(function() {return this.checked; }).length > 0) {
            this.run_callbacks(completed);
            Hm_Ajax.aborted = true;
            return 0;
        }
        if (msgs[0] === "") {
            this.run_callbacks(completed);
            return 0;
        }
        var msg_rows;
        if (!cache) {
            msg_rows = Hm_Utils.tbody();
        }
        else {
            msg_rows = cache;
        }
        if (!this.background && !$.isEmptyObject(msgs)) {
            $('.empty_list').remove();
        }
        var msg_ids = this.add_rows(msgs, msg_rows);
        var count = this.remove_rows(ids, msg_ids, type, msg_rows);
        this.run_callbacks(completed);
        if (!cache) {
            this.set_tab_index();
        }
        return count;
    };

    this.set_tab_index = function() {
        var msg_rows = Hm_Utils.rows();
        var count = 1;
        msg_rows.each(function() {
            $(this).attr('tabindex', count);
            count++;
        });
    };

    this.remove_rows = function(ids, msg_ids, type, msg_rows) {
        var count = $('tr', msg_rows).length;
        var parts;
        var re;
        var i;
        var id;
        for (i=0;i<ids.length;i++) {
            id = ids[i];
            if ((id+'').search('_') != -1) {
                parts = id.split('_', 2);
                parts[0] -= 0;
                re = new RegExp(parts[1]+'$');
                parts[1] = re;
            }
            else {
                parts = [id, false];
            }
            $('tr[class^='+type+'_'+parts[0]+'_]', msg_rows).filter(function() {
                var id = this.className;
                if (id.indexOf(' ') != -1) {
                    id = id.split(' ')[0];
                }
                if (!parts[1] || parts[1].exec(id)) {
                    if ($.inArray(id, msg_ids) == -1) {
                        count--;
                        $(this).remove();
                    }
                }
            });
        }
        return count;
    };

    this.sort = function(fld) {
        var listitems = Hm_Utils.rows();
        var aval;
        var bval;
        var sort_result = listitems.sort(function(a, b) {
            switch (Math.abs(fld)) {
                case 1:
                case 2:
                case 3:
                    aval = $($('td', a)[Math.abs(fld)]).text().replace(/^\s+/g, '');
                    bval = $($('td', b)[Math.abs(fld)]).text().replace(/^\s+/g, '');
                    break;
                case 4:
                default:
                    aval = $('input', $($('td', a)[Math.abs(fld)])).val();
                    bval = $('input', $($('td', b)[Math.abs(fld)])).val();
                    break;
            }
            if (fld == 4 || fld == -4 || !fld) {
                if (fld == -4) {
                    return aval - bval;
                }
                return bval - aval;
            }
            else {
                if (fld && fld < 0) {
                    return bval.toUpperCase().localeCompare(aval.toUpperCase());
                }
                return aval.toUpperCase().localeCompare(bval.toUpperCase());
            }
        });
        this.sort_fld = fld;
        Hm_Utils.tbody().html('');
        for (var i = 0, len=sort_result.length; i < len; i++) {
            Hm_Utils.tbody().append(sort_result[i]);
        }
        this.save_updated_list();
    };

    this.add_rows = function(msgs, msg_rows) {
        var msg_ids = [];
        var row;
        var id;
        var index;
        for (index in msgs) {
            row = msgs[index][0];
            id = msgs[index][1];
            if (this.deleted.indexOf(Hm_Utils.clean_selector(id)) != -1) {
                continue;
            }
            id = id.replace(/ /, '-');
            if (!$('.'+Hm_Utils.clean_selector(id), msg_rows).length) { 
                this.insert_into_message_list(row, msg_rows);
                $('.'+Hm_Utils.clean_selector(id), msg_rows).show();
            }
            else {
                $('.'+Hm_Utils.clean_selector(id), msg_rows).replaceWith(row)
            }
            msg_ids.push(id);
        }
        return msg_ids;
    };

    this.insert_into_message_list = function(row, msg_rows) {
        var sort_fld = this.sort_fld;
        if (typeof sort_fld == 'undefined' || sort_fld == null) {
            sort_fld = 4;
        }
        var element = false;
        if (sort_fld == 4 || sort_fld == -4) {
            var timestr2;
            var timestr = $('.msg_timestamp', $(row)).val();
            $('tr', msg_rows).each(function() {
                timestr2 = $('.msg_timestamp', $(this)).val();
                if ((sort_fld == -4 && (timestr2*1) >= (timestr*1)) ||
                    (sort_fld == 4 && (timestr*1) >= (timestr2*1))) {
                    element = $(this);
                    return false;
                }
            });
        }
        else {
            var bval;
            var aval = $($('td', $(row))[Math.abs(sort_fld)]).text().replace(/^\s+/g, '');
            $('tr', msg_rows).each(function() {
                bval = $($('td', $(this))[Math.abs(sort_fld)]).text().replace(/^\s+/g, '');
                if ((sort_fld < 0 && aval.toUpperCase().localeCompare(bval.toUpperCase()) > 0) ||
                   (sort_fld > 0 && bval.toUpperCase().localeCompare(aval.toUpperCase()) > 0)) {
                    element = $(this);
                    return false;
                }
            });
        }
        if (element) {
            $(row, msg_rows).insertBefore(element);
        }
        else {
            msg_rows.append(row);
        }
        self.just_inserted.push($('.from', $(row)).text()+' - '+$('.subject', $(row)).text());
    };

    this.reset_checkboxes = function() {
        this.toggle_msg_controls();
        this.set_checkbox_callback();
    };

    this.toggle_msg_controls = function() {
        if ($('input[type=checkbox]', $('.message_table')).filter(function() {return this.checked; }).length > 0) {
            $('.msg_controls').addClass('msg_controls_visible');
        }
        else {
            $('.msg_controls').removeClass('msg_controls_visible');
        }
    };

    this.update_after_action = function(action_type, selected) {
        var remove = false;
        if (action_type == 'read' && hm_list_path() == 'unread') {
            remove = true;
        }
        if (action_type == 'unflag' && hm_list_path() == 'flagged') {
            remove = true;
        }
        else if (action_type == 'delete') {
            remove = true;
        }
        if (remove) {
            this.remove_after_action(action_type, selected);
        }
        else {
            if (action_type == 'read' || action_type == 'unread') {
                this.read_after_action(action_type, selected);
            }
            else if (action_type == 'flag' || action_type == 'unflag') {
                this.flag_after_action(action_type, selected);
            }
        }
        this.save_updated_list();
        this.reset_checkboxes();
    };

    this.save_updated_list = function() {
        if (this.page_caches.hasOwnProperty(hm_list_path())) {
            this.set_message_list_state(this.page_caches[hm_list_path()]);
            Hm_Utils.save_to_local_storage('sort_'+hm_list_path(), this.sort_fld);
        }
    };

    this.remove_after_action = function(action_type, selected) {
        var removed = 0;
        var class_name = false;
        var index;
        for (index in selected) {
            class_name = selected[index];
            $('.'+Hm_Utils.clean_selector(class_name)).remove();
            if (action_type == 'delete') {
                this.deleted.push(class_name);
            }
            removed++;
        }
        return removed;
    };

    this.read_after_action = function(action_type, selected) {
        var read = 0;
        var row;
        var index;
        var class_name = false;
        for (index in selected) {
            class_name = selected[index];
            row = $('.'+Hm_Utils.clean_selector(class_name));
            if (action_type == 'read') {
                $('.subject > div', row).removeClass('unseen');
                row.removeClass('unseen');
            }
            else {
                $('.subject > div', row).addClass('unseen');
                row.addClass('unseen');
            }
            read++;
        }
        return read;
    };

    this.flag_after_action = function(action_type, selected) {
        var flagged = 0;
        var class_name;
        var row;
        var index;
        for (index in selected) {
            class_name = selected[index];
            row = $('.'+Hm_Utils.clean_selector(class_name));
            if (action_type == 'flag') {
                $('.icon', row).html('<img width="16" height="16" src="'+hm_flag_image_src()+'" />');
            }
            else {
                $('.icon', row).empty();
            }
            flagged++;
        }
        return flagged;
    };

    this.load_sources = function() {
        var index;
        var source;
        if (!self.background) {
            $('.src_count').text(self.sources.length);
            $('.total').text(Hm_Utils.rows().length);
        }
        for (index in self.sources) {
            source = self.sources[index];
            source.callback(source.id, source.folder);
        }
        return false;
    };

    this.select_combined_view = function() {
        if (self.page_caches.hasOwnProperty(hm_list_path())) {
            self.setup_combined_view(self.page_caches[hm_list_path()]);
        }
        else {
            if (hm_page_name() == 'search') {
                self.setup_combined_view('formatted_search_data');
            }
            else {
                self.setup_combined_view(false);
            }
        }
        var sort_type = Hm_Utils.get_from_local_storage('sort_'+hm_list_path());
        if (sort_type != null) {
            this.sort_fld = sort_type;
            $('.combined_sort').val(sort_type);
        }
        $('.core_msg_control').on("click", function() { return self.message_action($(this).data('action')); });
        $('.toggle_link').on("click", function() { return self.toggle_rows(); });
        $('.refresh_link').on("click", function() { return self.load_sources(); });
    };

    this.add_sources = function(sources) {
        self.sources = sources;
    };

    this.setup_combined_view = function(cache_name) {
        self.add_sources(hm_data_sources());
        var data = Hm_Utils.get_from_local_storage(cache_name);
        var interval = Hm_Utils.get_from_global('combined_view_refresh_interval', 60);
        if (data && data.length) {
            Hm_Utils.tbody().html(data);
            if (cache_name == 'formatted_unread_data') {
                self.clear_read_messages();
            }
            self.set_checkbox_callback();
            $('.combined_sort').show();
        }
        if (hm_page_name() == 'search' && hm_run_search() == "0") {
            Hm_Timer.add_job(self.load_sources, interval, true);
        }
        else {
            Hm_Timer.add_job(this.load_sources, interval);
        }
    };

    this.clear_read_messages = function() {
        var class_name;
        var list = Hm_Utils.get_from_local_storage('read_message_list');
        if (list && list.length) {
            list = Hm_Utils.json_decode(list);
            for (class_name in list) {
                $('.'+Hm_Utils.clean_selector(class_name)).remove();
            }
            Hm_Utils.save_to_local_storage('read_message_list', '');
        }
    };

    /* TODO: remove module specific refs */
    this.update_title = function() {
        var count = 0;
        var rows = Hm_Utils.rows();
        var tbody = Hm_Utils.tbody();
        if (hm_list_path() == 'unread') {
            count = rows.length;
            document.title = count+' Unread';
        }
        else if (hm_list_path() == 'flagged') {
            count = rows.length;
            document.title = count+' Flagged';
        }
        else if (hm_list_path() == 'combined_inbox') {
            count = $('tr .unseen', tbody).length;
            document.title = count+' Unread in Everything';
        }
        else if (hm_list_path() == 'email') {
            count = $('tr .unseen', tbody).length;
            document.title = count+' Unread in Email';
        }
        else if (hm_list_path() == 'feeds') {
            count = $('tr .unseen', tbody).length;
            document.title = count+' Unread in Feeds';
        }
    };

    this.message_action = function(action_type) {
        if (action_type == 'delete' && !hm_delete_prompt()) {
            return false;
        }
        var msg_list = $('.message_table');
        var selected = [];
        var current_list = self.filter_list();
        $('input[type=checkbox]', msg_list).each(function() {
            if (this.checked) {
                selected.push($(this).val());
            }
        });
        if (selected.length > 0) {
            Hm_Ajax.request(
                [{'name': 'hm_ajax_hook', 'value': 'ajax_message_action'},
                {'name': 'action_type', 'value': action_type},
                {'name': 'message_ids', 'value': selected}],
                function(res) {
                    if (!res) {
                        $('.message_table_body').replaceWith(current_list);
                        self.save_updated_list();
                        self.toggle_msg_controls();
                    }
                },
                [],
                false,
                false,
                true
            );
            self.update_after_action(action_type, selected);
        }
        return false;
    };

    this.prev_next_links = function(cache, class_name) {
        var href;
        var target;
        var subject;
        var plink = false;
        var nlink = false;
        var list = Hm_Utils.get_from_local_storage(cache);
        var current = $('<div></div>').append(list).find('.'+Hm_Utils.clean_selector(class_name));
        var prev = current.prev();
        var next = current.next();
        target = $('.msg_headers tr').last();
        if (prev.length) {
            href = prev.find('.subject').find('a').prop('href');
            subject = new Option(prev.find('.subject').text()).innerHTML;
            plink = '<a class="plink" href="'+href+'"><div class="prevnext prev_img"></div> '+subject+'</a>';
            $('<tr class="prev"><th colspan="2">'+plink+'</th></tr>').insertBefore(target);
        }
        if (next.length) {
            href = next.find('.subject').find('a').prop('href');
            subject = new Option(next.find('.subject').text()).innerHTML;
            nlink = '<a class="nlink" href="'+href+'"><div class="prevnext next_img"></div> '+subject+'</a>';
            $('<tr class="next"><th colspan="2">'+nlink+'</th></tr>').insertBefore(target);
        }
    };

    this.check_empty_list = function() {
        var count = Hm_Utils.rows().length;
        if (!count) {
            if (!$('.empty_list').length) {
                if (hm_page_name() == 'search') {
                    $('.search_content').append('<div class="empty_list">'+hm_empty_folder()+'</div>');
                }
                else {
                    $('.message_list').append('<div class="empty_list">'+hm_empty_folder()+'</div>');
                }
            }
        }
        else {
            $('.empty_list').remove();
            $('.combined_sort').show();
        }
        return count === 0;
    };

    this.track_read_messages = function(class_name) {
        var read_messages = Hm_Utils.get_from_local_storage('read_message_list');
        if (read_messages && read_messages.length) {
            read_messages = Hm_Utils.json_decode(read_messages);
        }
        else {
            read_messages = {};
        }
        var added = false;
        if (!(class_name in read_messages)) {
            added = true;
        }
        read_messages[class_name] = 1;
        Hm_Utils.save_to_local_storage('read_message_list', Hm_Utils.json_encode(read_messages));
        return added;
    };

    this.adjust_unread_total = function(amount, replace) {
        var missing = $('.total_unread_count').text() === '' ? true : false;
        var current = $('.total_unread_count').text()*1;
        var new_total;
        if (replace && amount == current && amount != 0) {
            return;
        }
        if (!replace && amount == 0) {
            return;
        }
        if (replace) {
            new_total = amount;
        }
        else {
            new_total = current + amount;
        }
        if (new_total < 0) {
            new_total = 0;
        }
        if (new_total != current || missing) {
            $('.total_unread_count').html('&#160;'+new_total+'&#160;');
        }
        if (new_total > current && hm_page_name() != 'message_list' && hm_list_path() != 'unread') {
            $('.menu_unread > a').css('font-weight', 'bold');
        }
        if (amount == -1 || new_total < current) {
            $('.menu_unread > a').css('font-weight', 'normal');
        }
        Hm_Folders.save_folder_list();
        self.past_total = current;
    };

    this.toggle_rows = function() {
        $('input[type=checkbox]', $('.message_table')).each(function () { this.checked = !this.checked; });
        self.toggle_msg_controls();
        return false;
    };

    this.filter_list = function() {
        var data = Hm_Utils.rows().clone().filter(function() {
            if (this.className == 'inline_msg') {
                return false;
            }
            return true;
        });
        var res = $('<tbody class="message_table_body"></tbody>');
        data.appendTo(res);
        return res;
    };

    this.set_message_list_state = function(list_type) {
        var data = this.filter_list();
        data.find('*[style]').attr('style', '');
        Hm_Utils.save_to_local_storage(list_type, data.html());
        var empty = self.check_empty_list();
        if (!empty) {
            self.set_checkbox_callback();
        }
        $('.total').text(Hm_Utils.rows().length);
        self.update_title();
        if (list_type == 'formatted_unread_data') {
            self.adjust_unread_total(Hm_Utils.rows().length, true);
        }
    };

    this.set_checkbox_callback = function() {
        $('input[type=checkbox]', $('.message_table')).off('click');
        $('input[type=checkbox]', $('.message_table')).on("click", function(e) {
            self.toggle_msg_controls();
        });
    };

    this.set_all_mail_state = function() { self.set_message_list_state('formatted_all_mail'); };
    this.set_combined_inbox_state = function() { self.set_message_list_state('formatted_combined_inbox'); };
    this.set_flagged_state = function() { self.set_message_list_state('formatted_flagged_data'); };
    this.set_unread_state = function() { self.set_message_list_state('formatted_unread_data'); };
    this.set_search_state = function() { self.set_message_list_state('formatted_search_data'); };
};

/* folder list */
var Hm_Folders = {
    expand_after_update: false,
    unread_counts: {},
    observer : false,

    save_folder_list: function() {
        Hm_Utils.save_to_local_storage('formatted_folder_list', $('.folder_list').html());
    },

    load_unread_counts: function() {
        var res = Hm_Utils.json_decode(Hm_Utils.get_from_local_storage('unread_counts'));
        if (!res) {
            Hm_Folders.unread_counts = {};
        }
        else {
            Hm_Folders.unread_counts = res;
        }
    },

    update_unread_counts: function(folder) {
        if (folder) {
            $('.unread_'+folder).html('&#160;'+Hm_Folders.unread_counts[folder]+'&#160;');
        }
        else {
            var name;
            for (name in Hm_Folders.unread_counts) {
                if (!Hm_Folders.unread_counts[name]) {
                    Hm_Folders.unread_counts[name] = 0;
                }
                if (hm_list_path() == name && hm_page_name() == 'message_list') {
                    var title = document.title.replace(/^\[\d+\]/, '');
                    document.title = '['+Hm_Folders.unread_counts[name]+'] '+title;
                    /* HERE */
                }
                $('.unread_'+name).html('&#160;'+Hm_Folders.unread_counts[name]+'&#160;');
            }
        }
        Hm_Utils.save_to_local_storage('unread_counts', Hm_Utils.json_encode(Hm_Folders.unread_counts));
    },

    open_folder_list: function() {
        $('.folder_list').show();
        $('.folder_toggle').toggle();
        if (hm_mobile()) {
            $('main').hide();
        }
        else {
            $('main').css('display', 'table-cell');
        }
        Hm_Utils.save_to_local_storage('hide_folder_list', '');
        return false;
    },

    toggle_folder_list: function() {
        if ($('.folder_list').css('display') == 'none') {
            Hm_Folders.open_folder_list();
        }
        else {
            Hm_Folders.hide_folder_list();
        }
    },

    hide_folder_list: function(forget) {
        $('.folder_list').hide();
        $('.folder_toggle').show();
        if (!forget) {
            Hm_Utils.save_to_local_storage('formatted_folder_list', $('.folder_list').html());
            Hm_Utils.save_to_local_storage('hide_folder_list', '1');
            $('main').css('display', 'block');
        }
        return false;
    },

    reload_folders: function(force, expand_after_update) {
        if (document.cookie.indexOf('hm_reload_folders=1') > -1 || force) {
            Hm_Folders.expand_after_update = expand_after_update;
            var ui_state = Hm_Utils.preserve_local_settings();
            Hm_Folders.update_folder_list();
            sessionStorage.clear();
            Hm_Utils.restore_local_settings(ui_state);
            document.cookie = 'hm_reload_folders=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            Hm_Utils.expand_core_settings();
            return true;
        }
        return false;
    },

    sort_list: function(class_name, exclude_name, last_name) {
        var folder = $('.'+class_name+' ul');
        var listitems;
        if (exclude_name) {
            listitems = $('li:not(.'+exclude_name+')', folder);
        }
        else {
            listitems = $('li', folder);
        }
        listitems = listitems.sort(function(a, b) {
            if (last_name && ($(a).attr('class') == last_name || $(b).attr('class') == last_name)) {
                return false;
            }
            if ($(b).text().toUpperCase() == 'ALL') {
                return true;
            }
           return $(a).text().toUpperCase().localeCompare($(b).text().toUpperCase());
        });
        $.each(listitems, function(_, itm) { folder.append(itm); });
    },

    update_folder_list_display: function(res) {
        $('.folder_list').html(res.formatted_folder_list);
        Hm_Folders.sort_list('email_folders', 'menu_email');
        Hm_Folders.sort_list('feeds_folders', 'menu_feeds', 'feeds_add_new');
        Hm_Folders.sort_list('main', 'menu_search', 'menu_logout');
        Hm_Utils.save_to_local_storage('formatted_folder_list', $('.folder_list').html());
        Hm_Folders.hl_selected_menu();
        Hm_Folders.folder_list_events();
        if (Hm_Folders.expand_after_update) {
            Hm_Utils.toggle_section(Hm_Folders.expand_after_update);
        }
        Hm_Folders.expand_after_update = false;
        Hm_Folders.listen_for_new_messages();
        hl_save_link();
    },

    update_folder_list: function() {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_hm_folders'}],
            Hm_Folders.update_folder_list_display,
            [],
            true
        );
        return false;
    },

    folder_list_events: function() {
        $('.imap_folder_link').on("click", function() { return expand_imap_folders($(this).data('target')); });
        $('.src_name').on("click", function() { return Hm_Utils.toggle_section($(this).data('source')); });
        $('.update_message_list').on("click", function() { return Hm_Folders.update_folder_list(); });
        $('.hide_folders').on("click", function() { return Hm_Folders.hide_folder_list(); });
        $('.logout_link').on("click", function() { return Hm_Utils.confirm_logout(); });
        if (hm_search_terms()) {
            $('.search_terms').val(hm_search_terms());
        }
        $('.search_terms').on('search', function() {
            Hm_Ajax.request([{'name': 'hm_ajax_hook', 'value': 'ajax_reset_search'}]);
        });
    },

    hl_selected_menu: function() {
        var page = hm_page_name();
        var path = hm_list_path();
        $('.folder_list').find('*').removeClass('selected_menu');
        if (path.length) {
            if (page == 'message_list' || page == 'message') {
                $("[data-id='"+Hm_Utils.clean_selector(path)+"']").addClass('selected_menu');
                $('.menu_'+Hm_Utils.clean_selector(path)).addClass('selected_menu');
            }
            else {
                $('.menu_'+path).addClass('selected_menu');
            }
        }
        else {
            $('.menu_'+page).addClass('selected_menu');
        }
    },

    listen_for_new_messages: function() {
        var target = $('.total_unread_count').get(0);
        if (!Hm_Folders.observer) {
            Hm_Folders.observer = new MutationObserver(function(mutations) {
                $('body').trigger('new_message');
            });
        }
        else {
            Hm_Folders.observer.disconnect();
        }
        Hm_Folders.observer.observe(target, {attributes: true, childList: true, characterData: true});
    },

    load_from_local_storage: function() {
        var folder_list = Hm_Utils.get_from_local_storage('formatted_folder_list');
        if (folder_list) {
            $('.folder_list').html(folder_list);
            if (Hm_Utils.get_from_local_storage('hide_folder_list') == '1') {
                $('.folder_list').hide();
                $('.folder_toggle').show();
                $('main').css('display', 'block');
            }
            Hm_Folders.hl_selected_menu();
            Hm_Folders.folder_list_events();
            Hm_Folders.load_unread_counts();
            Hm_Folders.update_unread_counts();
            Hm_Folders.listen_for_new_messages();
            return true;
        }
        return false;
    },

    toggle_folders_event: function() {
        $('.folder_toggle').on("click", function() { return Hm_Folders.open_folder_list(); });
    }
};

/* misc */
var Hm_Utils = {
    get_url_page_number: function() {
        var index;
        var match_result;
        var page_number = 1;
        var params = location.search.substr(1).split('&');
        var param_len = params.length;

        for (index=0; index < param_len; index++) {
            match_result = params[index].match(/list_page=(\d+)/);
            if (match_result) {
                page_number = match_result[1];
                break;
            }
        }
        return page_number;
    },

    get_from_global: function(name, def) {
        if (globals[name]) {
            return globals[name];
        }
        return def;
    },

    preserve_local_settings: function() {
        var i;
        var result = {};
        var prefix = window.location.pathname.length;
        for (i in sessionStorage) {
            i = i.substr(prefix);
            if (i.match(/\..+(_setting|_section)/)) {
                result[i] = Hm_Utils.get_from_local_storage(i);
            }
        }
        return result;
    },

    restore_local_settings: function(settings) {
        var i;
        for (i in settings) {
            Hm_Utils.save_to_local_storage(i, settings[i]);
        }
    },

    reset_search_form: function() {
        Hm_Utils.save_to_local_storage('formatted_search_data', '');
        Hm_Ajax.request([{'name': 'hm_ajax_hook', 'value': 'ajax_reset_search'}],
            function(res) { window.location = '?page=search'; }, false, true);
        return false;
    },

    confirm_logout: function() {
        if ($('#unsaved_changes').val() == 0) {
            document.getElementById('logout_without_saving').click();
        }
        else {
            $('.confirm_logout').show();
        }
        return false;
    },

    get_path_type: function(path) {
        if (path.indexOf('_') != -1) {
            var path_parts = path.split('_');
            return path_parts[0];
        }
        return false;
    },

    parse_folder_path: function(path, path_type) {
        if (!path_type) {
            path_type = Hm_Utils.get_path_type(path);
        }
        if (path && path.indexOf(' ') != -1) {
            path = path.split(' ')[0];
        }
        var type = false;
        var server_id = false;
        var uid = false;
        var folder = '';
        var parts;

        if (path_type == 'imap') {
            parts = path.split('_', 4);
            if (parts.length == 2) {
                type = parts[0];
                server_id = parts[1];
            }
            else if (parts.length == 3) {
                type = parts[0];
                server_id = parts[1];
                folder = parts[2];
            }
            else if (parts.length == 4) {
                type = parts[0];
                server_id = parts[1];
                uid = parts[2];
                folder = parts[3];
            }
            if (type && server_id) {
                return {'type': type, 'server_id' : server_id, 'folder' : folder, 'uid': uid};
            }
        }
        else if (path_type == 'pop3' || path_type == 'feeds') {
            parts = path.split('_', 3);
            if (parts.length > 1) {
                type = parts[0];
                server_id = parts[1];
            }
            if (parts.length == 3) {
                uid = parts[2];
            }
            if (type && server_id) {
                return {'type': type, 'server_id' : server_id, 'uid': uid};
            }
        }
        return false;
    },

    toggle_section: function(class_name, force_on, force_off) {
        if ($(class_name).length) {
            if (force_off) {
                $(class_name).css('display', 'block');
            }
            if (force_on) {
                $(class_name).css('display', 'none');
            }
            $(class_name).toggle();
            Hm_Utils.save_to_local_storage('formatted_folder_list', $('.folder_list').html());
        }
        return false;
    },

    toggle_page_section: function(class_name) {
        if ($(class_name).length) {
            $(class_name).toggle();
            Hm_Utils.save_to_local_storage(class_name, $(class_name).css('display'));
        }
        return false;
    },

    expand_core_settings: function() {
        var sections = Hm_Utils.get_core_settings();
        var key;
        var dsp;
        for (key in sections) {
            dsp = sections[key];
            if (!dsp) {
                dsp = 'none';
            }
            $(key).css('display', dsp);
            Hm_Utils.save_to_local_storage(key, dsp);
        }
    },

    get_core_settings: function() {
        var dsp;
        var results = {}
        var i;
        var hash = window.location.hash;
        var sections = ['.wp_notifications_setting', '.github_all_setting', '.tfa_setting', '.sent_setting', '.general_setting', '.unread_setting', '.flagged_setting', '.all_setting', '.email_setting'];
        for (i=0;i<sections.length;i++) {
            dsp = Hm_Utils.get_from_local_storage(sections[i]);
            if (hash) {
                if (hash.replace('#', '.') != sections[i]) {
                    dsp = 'none';
                }
                else {
                    dsp = 'table-row';
                }
            }
            results[sections[i]] = dsp;
        }
        return results;
    },

    get_from_local_storage: function(key) {
        var prefix = window.location.pathname;
        key = prefix+key;
        var res = false;
        if (hm_encrypt_local_storage()) {
             res = Hm_Crypt.decrypt(sessionStorage.getItem(key));
        }
        else {
            res = sessionStorage.getItem(key);
        }
        return res;
    },

    save_to_local_storage: function(key, val) {
        var prefix = window.location.pathname;
        key = prefix+key;
        if (hm_encrypt_local_storage()) {
            val = Hm_Crypt.encrypt(val);
        }
        if (Storage !== void(0)) {
            try { sessionStorage.setItem(key, val); } catch(e) {
                sessionStorage.clear();
                sessionStorage.setItem(key, val);
            }
            if (sessionStorage.getItem(key) === null) {
                sessionStorage.clear();
                sessionStorage.setItem(key, val);
            }
        }
        return false;
    },

    clean_selector: function(str) {
        return str.replace(/(:|\.|\[|\]|\/)/g, "\\$1");
    },

    toggle_long_headers: function() {
        $('.long_header').toggle();
        $('.all_headers').toggle();
        $('.small_headers').toggle();
        return false;
    },

    set_unsaved_changes: function(state) {
        $('#unsaved_changes').val(state);
    },

    show_sys_messages: function() {
        if ($('.sys_messages').text().length) {
            $('.sys_messages').show();
            $('.sys_messages').on('click', function() {
                $('.sys_messages').hide();
                $('.sys_messages').html('');
            });
        }
    },

    cancel_logout_event: function() {
        $('.cancel_logout').on("click", function() { $('.confirm_logout').hide(); return false; });
    },

    json_encode: function(val) {
        try {
            return JSON.stringify(val);
        }
        catch (e) {
            return false;
        }
    },

    json_decode: function(val, original) {
        try {
            return JSON.parse(val);
        }
        catch (e) {
            if (original === true) {
                return val;
            }
            return false;
        }
    },

    rows: function() {
        return $('.message_table_body > tr').not('.inline_msg');
    },

    tbody: function() {
        return $('.message_table_body');
    },

    html_entities: function(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },

    test_connection: function() {
        $('.offline').hide();
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_test'}],
            false, [], false, false, false);
    }
};

var Hm_Crypt = {
    decrypt: function(ciphertext) {
        try {
            ciphertext = atob(ciphertext);
            if (!ciphertext || ciphertext.length < 200) {
                return false;
            }
            var secret = $('#hm_page_key').val();
            var payload = ciphertext.substr(192);
            var hmac_sig = ciphertext.substr(128, 64);
            var salt = ciphertext.substr(0, 128);
            var digest = forge.md.sha512.create();
            var hmac = forge.hmac.create();
            var key = forge.pkcs5.pbkdf2(secret, salt, 100, 32, digest);
            var hmac_key = forge.pkcs5.pbkdf2(secret, salt, 101, 32, digest);

            hmac.start(digest, hmac_key);
            hmac.update(payload);
            if (hmac.digest().data != hmac_sig) {
                return false;
            }
            var iv = forge.pkcs5.pbkdf2(secret, salt, 100, 16, digest);
            var decipher = forge.cipher.createDecipher('AES-CBC', key);
            decipher.start({iv: iv});
            decipher.update(forge.util.createBuffer(payload, 'raw'));
            decipher.finish();
            return forge.util.decodeUtf8(decipher.output.data);
        } catch(e) {
            return false;
        }
    },

    encrypt: function(plaintext) {
        try {
            var secret = $('#hm_page_key').val();
            var salt = forge.random.getBytesSync(128);
            var digest = forge.md.sha512.create();
            var key = forge.pkcs5.pbkdf2(secret, salt, 100, 32, digest);
            var hmac_key = forge.pkcs5.pbkdf2(secret, salt, 101, 32, digest);
            var iv = forge.pkcs5.pbkdf2(secret, salt, 100, 16, digest);
            var hmac = forge.hmac.create();
            var cipher = forge.cipher.createCipher('AES-CBC', key);
            cipher.start({iv: iv});
            cipher.update(forge.util.createBuffer(plaintext, 'utf8'));
            cipher.finish();
            hmac.start(digest, hmac_key);
            hmac.update(cipher.output.data);
            return btoa(salt+hmac.digest().data+cipher.output.data);
        } catch(e) {
            return false;
        }
    },
}

var update_password = function(id) {
    var pass = $('#update_pw_'+id).val();
    if (pass && pass.length) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_update_server_pw'},
            {'name': 'password', 'value': pass},
            {'name': 'server_pw_id', 'value': id}],
            function(res) {
                if (res.connect_status) {
                    $('.div_'+id).remove();
                    if ($('.home_password_dialogs div').length == 1) {
                        $('.home_password_dialogs').remove();
                    }
                }
            }
        );
    }
}

var elog = function(val) {
    if (hm_debug()) {
        console.log(val);
    }
};

var hl_save_link = function() {
    if ($('.save_reminder').length) {
        $('.menu_save a').css('font-weight', 'bold');
    }
    else {
        $('.menu_save a').css('font-weight', 'normal');
    }
};

/* create a default message list object */
var Hm_Message_List = new Message_List();

/* executes on onload, has access to other module code */
$(function() {

    /* setup settings and server pages */
    if (hm_page_name() == 'settings') {
        Hm_Utils.expand_core_settings();
        $('.settings_subtitle').on("click", function() { return Hm_Utils.toggle_page_section($(this).data('target')); });
    }
    else if (hm_page_name() == 'servers') {
        $('.server_section').on("click", function() { return Hm_Utils.toggle_page_section($(this).data('target')); });
    }

    /* check for folder reload */
    var reloaded = Hm_Folders.reload_folders();

    /* show any pending notices */
    Hm_Utils.show_sys_messages();

    /* setup a few page wide event handlers */
    Hm_Utils.cancel_logout_event();
    Hm_Folders.toggle_folders_event();

    /* fire up the job scheduler */
    Hm_Timer.fire();

    /* load folder list */
    if (!reloaded && !Hm_Folders.load_from_local_storage()) {
        Hm_Folders.update_folder_list();
    }
    if (hm_page_name() == 'message_list' || hm_page_name() == 'search') {
        Hm_Message_List.select_combined_view();
        $('.combined_sort').on("change", function() { Hm_Message_List.sort($(this).val()); });
        $('.source_link').on("click", function() { $('.list_sources').toggle(); return false; });
        if (hm_list_path() == 'unread' && $('.menu_unread > a').css('font-weight') == 'bold') {
            $('.menu_unread > a').css('font-weight', 'normal');
            Hm_Folders.save_folder_list();
        }
    }
    hl_save_link();
    if (hm_page_name() == 'search') {
        $('.search_reset').on("click", Hm_Utils.reset_search_form);
    }
    if (hm_mailto()) {
        try { navigator.registerProtocolHandler("mailto", "?page=compose&compose_to=%s", "Cypht"); } catch(e) {}
    }

    if (hm_page_name() == 'home') {
        $('.pw_update').on("click", function() { update_password($(this).data('id')); });
    }
    if (hm_mobile()) {
        swipe_event(document.body, function() { Hm_Folders.open_folder_list(); }, 'right');
        swipe_event(document.body, function() { Hm_Folders.hide_folder_list(); }, 'left');
    }
    $('.offline').on("click", function() { Hm_Utils.test_connection(); });

});


var delete_contact = function(id, source, type) {
    if (!hm_delete_prompt()) {
        return false;
    }
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_delete_contact'},
        {'name': 'contact_id', 'value': id},
        {'name': 'contact_type', 'value': type},
        {'name': 'contact_source', 'value': source}],
        function(res) {
            if (res.contact_deleted && res.contact_deleted === 1) {
                $('.contact_row_'+id).remove();
            }
        }
    );
};

var add_contact_from_message_view = function() {
    var contact = $('#add_contact').val();
    var source = $('#contact_source').val();
    if (contact) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_add_contact'},
            {'name': 'contact_value', 'value': contact},
            {'name': 'contact_source', 'value': source}],
            function(res) { $('.add_contact_controls').toggle(); }
        );
    }
};

var get_search_term = function(class_name) {
    var fld_val = $(class_name).val();
    var addresses = fld_val.split(' ');
    if (addresses.length > 1) {
        fld_val = addresses.pop();
    }
    return fld_val;
};

var autocomplete_contact = function(e, class_name, list_div) {
    var key_code = e.keyCode;
    if (key_code >= 37 && key_code <= 40) {
        return;
    }
    var first;
    var div = $('<div></div>');
    var fld_val = get_search_term(class_name);
    if (fld_val.length > 0) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_autocomplete_contact'},
            {'name': 'contact_value', 'value': fld_val}],
            function(res) {
                var active = $(document.activeElement).attr('class');
                if (active == 'compose_to' || active == 'compose_bcc' || active == 'compose_cc') {
                    if (res.contact_suggestions) {
                        var i;
                        var count = 0;
                        $(list_div).html('');
                        for (i in res.contact_suggestions) {
                            div.html(res.contact_suggestions[i]);
                            if ($(class_name).val().match(div.text())) {
                                continue;
                            }
                            if (count == 0) {
                                first = 'first ';
                            }
                            else {
                                first = '';
                            }
                            count++;
                            $(list_div).append('<a tabindex="1" href="#" class="'+first+'contact_suggestion unread_link">'+res.contact_suggestions[i]+'</a>');
                        }
                        if (count > 0) {
                            $(list_div).show();
                            setup_autocomplete_events(class_name, list_div, fld_val);
                        }
                        else {
                            $(list_div).hide();
                        }
                    }
                }
            }, [], true
        );
    }
};

var autocomplete_keyboard_nav = function(event, list_div, class_name, fld_val) {
    var in_list = false;
    if (event.keyCode == 40) {
        if ($(event.target).prop('nodeName') == 'INPUT') {
            $('.first').addClass('selected_menu');
            $('.first').focus();
            in_list = true;
        }
        else {
            $(event.target).removeClass('selected_menu');
            $(event.target).next().addClass('selected_menu');
            $(event.target).next().focus();
            in_list = true;
        }
        return false;
    }
    else if (event.keyCode == 38) {
        if ($(event.target).prev().length) {
            $(event.target).removeClass('selected_menu');
            $(event.target).prev().addClass('selected_menu');
            $(event.target).prev().focus();
            in_list = true;
        }
        else {
            $(class_name).focus();
            $(event.target).removeClass('selected_menu');
        }
        return false;
    }
    else if (event.keyCode == 13) {
        $(class_name).focus();
        $(list_div).hide();
        add_autocomplete(event, class_name, list_div);
        return false;
    }
    else if (event.keyCode == 27) {
        $(list_div).html('');
        $(list_div).hide();
        $(class_name).focus();
        return false;
    }
    else if (event.keyCode == 9) {
        $(list_div).html('');
        $(list_div).hide();
        $(class_name).trigger('focusout');
        return true;
    }
    if (in_list) {
        return false;
    }
    return true;
};

var setup_autocomplete_events = function(class_name, list_div, fld_val) {
    $('.contact_suggestion').on("click", function(event) { return add_autocomplete(event, class_name, list_div); });
    $(class_name).on('keydown', function(event) { return autocomplete_keyboard_nav(event, list_div, class_name, fld_val); });
    $('.contact_suggestion').on('keydown', function(event) { return autocomplete_keyboard_nav(event, list_div, class_name, fld_val); });
    $(document).on("click", function() { $(list_div).hide(); });
};

var add_autocomplete = function(event, class_name, list_div, fld_val) {
    if (!fld_val) {
        fld_val = get_search_term(class_name);
    }
    var new_address = $(event.target).text()
    var existing = $(class_name).val();
    var re = new RegExp(fld_val+'$');
    existing = existing.replace(re, '');
    if (existing.length) {
        existing = existing.replace(/[\s,]+$/, '')+', ';
    }
    $(list_div).html('');
    $(list_div).hide();
    $(class_name).val(existing+new_address);
    $(class_name).focus();
    return false;
};

if (hm_page_name() == 'contacts') {
    $('.delete_contact').on("click", function() {
        delete_contact($(this).data('id'), $(this).data('source'), $(this).data('type'));
        return false;
    });
    $('.show_contact').on("click", function() {
        $('#'+$(this).data('id')).toggle();
        return false;
    });
    $('.reset_contact').on("click", function() {
        window.location.href = '?page=contacts';
    });
    $('.server_title').on("click", function() {
        $(this).next().toggle();
    });
}
else if (hm_page_name() == 'compose') {
    $('.compose_to').on('keyup', function(e) { autocomplete_contact(e, '.compose_to', '#to_contacts'); });
    $('.compose_cc').on('keyup', function(e) { autocomplete_contact(e, '.compose_cc', '#cc_contacts'); });
    $('.compose_bcc').on('keyup', function(e) { autocomplete_contact(e, '.compose_bcc', '#bcc_contacts'); });
    $('.compose_to').focus();
}


var feed_test_action = function(event) {
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function() {},
        {'feed_connect': 1}
    );
};

var feed_delete_action = function(event) {
    if (!hm_delete_prompt()) {
        return false;
    }
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            if (res.deleted_server_id > -1 ) {
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
                form.parent().remove();
            }
        },
        {'delete_feed': 1}
    );
};

var feeds_search_page_content = function(id) {
    if (hm_search_terms) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_feed_combined'},
            {'name': 'feed_search', 'value': 1},
            {'name': 'feed_server_ids', 'value': id}],
            display_feeds_search_result,
            [],
            false,
            Hm_Message_List.set_search_state
        );
    }
    return false;
};

var display_feeds_search_result = function(res) {
    var ids = [res.feed_server_ids];
    Hm_Message_List.update(ids, res.formatted_message_list, 'feeds');
};

var feeds_combined_content_unread = function(id) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_feed_combined'},
        {'name': 'feed_unread_only', 'value': 1},
        {'name': 'feed_server_ids', 'value': id}],
        display_feeds_combined_unread,
        [],
        false,
        Hm_Message_List.set_unread_state
    );
    return false;
};

var display_feeds_combined_unread = function(res) {
    var ids = [res.feed_server_ids];
    Hm_Message_List.update(ids, res.formatted_message_list, 'feeds');
};

var feeds_combined_content = function(id) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_feed_combined'},
        {'name': 'feed_server_ids', 'value': id}],
        display_feeds_combined,
        [],
        false,
        set_combined_feeds_state
    );
    return false;
};

var set_combined_feeds_state = function() {
    var data = Hm_Message_List.filter_list();
    data.find('*[style]').attr('style', '');
    Hm_Utils.save_to_local_storage('formatted_feed_data', data.html());
    $('input[type=checkbox]').on("click", function() {
        Hm_Message_List.toggle_msg_controls();
    });
    Hm_Message_List.update_title();
};

var display_feeds_combined = function(res) {
    var ids = res.feed_server_ids.split(',');
    Hm_Message_List.update(ids, res.formatted_message_list, 'feeds');
    $('.total').text($('.message_table tbody tr').length);
};

var feeds_combined_inbox_content= function(id) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_feed_combined'},
        {'name': 'feed_server_ids', 'value': id}],
        display_feeds_combined_inbox,
        [],
        false,
        Hm_Message_List.set_combined_inbox_state
    );
    return false;
};

var display_feeds_combined_inbox = function(res) {
    var ids = res.feed_server_ids.split(',');
    Hm_Message_List.update(ids, res.formatted_message_list, 'feeds');
};

var feed_item_view = function(uid, list_path, callback) {
    if (!uid) {
        uid = hm_msg_uid();
    }
    if (!list_path) {
        list_path = hm_list_path();
    }
    $('.msg_text_inner').html('');
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_feed_item_content'},
        {'name': 'feed_list_path', 'value': list_path},
        {'name': 'feed_uid', 'value': uid}],
        display_feed_item_content,
        [],
        false,
        callback
    );
    return false;
};

var display_feed_item_content = function(res) {
    if (!res.feed_msg_headers) {
        return;
    }
    var msg_uid = hm_msg_uid();
    $('.msg_text').html('');
    $('.msg_text').append(res.feed_msg_headers);
    $('.msg_text').append(res.feed_msg_text);
    set_message_content();
    document.title = $('.header_subject th').text();
    var path = hm_list_path();
    if (hm_list_parent() == 'feeds') {
        Hm_Message_List.prev_next_links('formatted_feed_data', path+'_'+msg_uid);
    }
    else if (hm_list_parent() == 'combined_inbox') {
        Hm_Message_List.prev_next_links('formatted_combined_inbox', path+'_'+msg_uid);
    }
    else if (hm_list_parent() == 'unread') {
        Hm_Message_List.prev_next_links('formatted_unread_data', path+'_'+msg_uid);
    }
    else if (hm_list_parent() === 'search') {
        Hm_Message_List.prev_next_links('formatted_search_data', path+'_'+msg_uid);
    }
    else {
        Hm_Message_List.prev_next_links(path, path+'_'+msg_uid);
    }
    if (Hm_Message_List.track_read_messages(path+'_'+msg_uid)) {
        if (hm_list_parent() == 'unread') {
            Hm_Message_List.adjust_unread_total(-1);
        }
    }
};

var load_feed_list = function(id) {
    var cached = Hm_Utils.get_from_local_storage(hm_list_path());
    if (cached) {
        $('.message_table tbody').html(cached);
    }
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_feed_combined'},
        {'name': 'feed_server_ids', 'value': id}],
        display_feed_list
    );
    return false;
};

var display_feed_list = function(res) {
    var ids = [res.feed_server_ids];
    Hm_Message_List.update(ids, res.formatted_message_list, 'feeds');
    var key = 'feeds_'+res.feed_server_ids;
    var data = Hm_Message_List.filter_list();
    data.find('*[style]').attr('style', '');
    $('.total').text($('.message_table tbody tr').length);
    Hm_Utils.save_to_local_storage(key, data.html());
};

var feed_status_update = function() {
    var id;
    var i;
    if ($('.feed_server_ids').length) {
        var ids = $('.feed_server_ids').val().split(',');
        if ( ids && ids !== '') {
            for (i=0;i<ids.length;i++) {
                id=ids[i];
                Hm_Ajax.request(
                    [{'name': 'hm_ajax_hook', 'value': 'ajax_feed_status'},
                    {'name': 'feed_server_ids', 'value': id}],
                    update_feed_status_display
                );
            }
        }
    }
    return false;
};

var update_feed_status_display = function(res) {
    var id = res.feed_status_server_id;
    $('.feeds_status_'+id).html(res.feed_status_display);
};

var expand_feed_settings = function() {
    var hash = window.location.hash;
    if (hash) {
        if (hash.replace('#', '.') == '.feeds_setting') {
            $('.feeds_setting').css('display', 'table-row');
        }
    }
    else {
        var dsp = Hm_Utils.get_from_local_storage('.feeds_setting');
        if (dsp == 'table-row' || dsp == 'none') {
            $('.feeds_setting').css('display', dsp);
        }
    }
};

if (hm_page_name() == 'message' && hm_list_path().substr(0, 4) == 'feed') {
    feed_item_view();
}
else if (hm_page_name() == 'servers') {
    $('.feed_delete').on('click', feed_delete_action);
    $('.test_feed_connect').on('click', feed_test_action);
    var dsp = Hm_Utils.get_from_local_storage('.feed_section');
    if (dsp == 'block' || dsp == 'none') {
        $('.feed_section').css('display', dsp);
    }
}
else if (hm_page_name() == 'info') {
    setTimeout(feed_status_update, 100);
}
else if (hm_page_name() == 'settings') {
    expand_feed_settings();
}



var pop3_test_action = function(event) {
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function() { },
        {'pop3_connect': 1}
    );
};

var pop3_save_action = function(event) {
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            if (res.just_saved_credentials) {
                form.find('.credentials').attr('disabled', true);
                form.find('.save_pop3_connection').hide();
                form.find('.pop3_password').val('');
                form.find('.pop3_password').attr('placeholder', '[saved]');
                form.append('<input type="submit" value="Forget" class="forget_pop3_connection" />');
                $('.forget_pop3_connection').on('click', pop3_forget_action);
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
            }
        },
        {'pop3_save': 1}
    );
};

var pop3_forget_action = function(event) {
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            if (res.just_forgot_credentials) {
                form.find('.credentials').prop('disabled', false);
                form.find('.credentials').val('');
                form.find('.credentials').attr('placeholder', '');
                form.append('<input type="submit" value="Save" class="save_pop3_connection" />');
                $('.save_pop3_connection').on('click', pop3_save_action);
                $('.forget_pop3_connection', form).remove();
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
            }
        },
        {'pop3_forget': 1}
    );
};

var pop3_delete_action = function(event) {
    if (!hm_delete_prompt()) {
        return false;
    }
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            if (res.deleted_server_id > -1 ) {
                form.parent().remove();
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
                var label = $('.server_count', $('.pop3_server_setup')).text();
                if (label) {
                    var parts = label.split(' ');
                    var count = parts[0]*1;
                    if (count > 0) {
                        count--;
                    }
                    else {
                        count = 0;
                    }
                    $('.server_count', $('.pop3_server_setup')).text(count+' '+parts[1]);

                }
            }
        },
        {'pop3_delete': 1}
    );
};

var display_pop3_mailbox = function(res) {
    var ids = [res.pop3_server_id];
    Hm_Message_List.update(ids, res.formatted_message_list, 'pop3');
    if (res.page_links) {
        $('.page_links').html(res.page_links);
    }
    var key = 'pop3_'+Hm_Utils.get_url_page_number()+'_'+hm_list_path();
    var data = Hm_Message_List.filter_list();
    data.find('*[style]').attr('style', '');
    Hm_Utils.save_to_local_storage(key, data.html());
};

var load_pop3_list = function(id) {
    var key = 'pop3_'+Hm_Utils.get_url_page_number()+'_'+hm_list_path();
    var cached = Hm_Utils.get_from_local_storage(key);
    if (cached) {
        $('.message_table tbody').html(cached);
    }
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_pop3_folder_display'},
        {'name': 'pop3_server_id', 'value': id}],
        display_pop3_mailbox
    );
    return false;
};

var pop3_message_view = function(uid, list_path, callback) {
    if (!uid) {
        uid = hm_msg_uid();
    }
    if (!list_path) {
        list_path = hm_list_path();
    }
    $('.msg_text_inner').html('');
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_pop3_message_display'},
        {'name': 'pop3_list_path', 'value': list_path},
        {'name': 'pop3_uid', 'value': uid}],
        display_pop3_message,
        [],
        false,
        callback
    );
    return false;
};

var display_pop3_message = function(res) {
    $('.msg_text').html('');
    $('.msg_text').append(res.msg_headers);
    $('.msg_text').append(res.msg_text);
    set_message_content();
    document.title = $('.header_subject th').text();
    pop3_message_view_finished();
};

var pop3_message_view_finished = function() {
    var detail = Hm_Utils.parse_folder_path(hm_list_path(), 'pop3');
    if (detail) {
        var class_name = 'pop3_'+detail.server_id+'_'+hm_msg_uid();
        if (hm_list_parent() == 'combined_inbox') {
            Hm_Message_List.prev_next_links('formatted_combined_inbox', class_name);
        }
        else if (hm_list_parent() == 'unread') {
            Hm_Message_List.prev_next_links('formatted_unread_data', class_name);
        }
        else if (hm_list_parent() === 'search') {
            Hm_Message_List.prev_next_links('formatted_search_data', class_name);
        }
        else {
            var key = 'pop3_'+Hm_Utils.get_url_page_number()+'_'+hm_list_path();
            Hm_Message_List.prev_next_links(key, class_name);
        }
    }
    if (Hm_Message_List.track_read_messages(class_name)) {
        if (hm_list_parent() == 'unread') {
            Hm_Message_List.adjust_unread_total(-1);
        }
    }
    $('.header_toggle').on("click", function() { return Hm_Utils.toggle_long_headers(); });
    $('.msg_part_link').on("click", function() { return get_message_content($(this).data('messagePart')); });
};

var pop3_all_mail_content = function(id) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_pop3_folder_display'},
        {'name': 'pop3_server_id', 'value': id}],
        display_pop3_list,
        [],
        false,
        Hm_Message_List.set_all_mail_state
    );
    return false;
};

var pop3_combined_inbox_content = function(id) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_pop3_folder_display'},
        {'name': 'pop3_server_id', 'value': id}],
        display_pop3_list,
        [],
        false,
        Hm_Message_List.set_combined_inbox_state
    );
    return false;
};

var display_pop3_list = function(res) {
    var ids = [res.pop3_server_id];
    Hm_Message_List.update(ids, res.formatted_message_list, 'pop3');
};

var pop3_status_update = function() {
    var i;
    var id;
    if ($('.pop3_server_ids').length) {
        var ids = $('.pop3_server_ids').val().split(',');
        if ( ids && ids !== '') {
            for (i=0;i<ids.length;i++) {
                id=ids[i];
                Hm_Ajax.request(
                    [{'name': 'hm_ajax_hook', 'value': 'ajax_pop3_status'},
                    {'name': 'pop3_server_ids', 'value': id}],
                    update_pop3_status_display
                );
            }
        }
    }
    return false;
};

var update_pop3_status_display = function(res) {
    var id = res.pop3_status_server_id;
    $('.pop3_status_'+id).html(res.pop3_status_display);
};

var pop3_search_page_content = function(id) {
    if (hm_search_terms) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_pop3_folder_display'},
            {'name': 'pop3_search', 'value': 1},
            {'name': 'pop3_server_id', 'value': id}],
            update_pop3_search_result,
            [],
            false,
            Hm_Message_List.set_search_state
        );
    }
    return false;
};

var update_pop3_search_result = function(res) {
    var ids = [res.pop3_server_id];
    Hm_Message_List.update(ids, res.formatted_message_list, 'pop3');
};

var pop3_unread_background = function(id) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_pop3_folder_display'},
        {'name': 'pop3_unread_only', 'value': 1},
        {'name': 'pop3_server_id', 'value': id}],
        update_pop3_unread_display_background
    );
    return false;
};

var update_pop3_unread_display_background = function(res) {
    var ids = [res.pop3_server_id];
    var cache = $('<tbody></tbody>').append($(Hm_Utils.get_from_local_storage('formatted_unread_data')));
    globals.Hm_Background_Unread.update(ids, res.formatted_message_list, 'pop3', cache);
    Hm_Utils.save_to_local_storage('formatted_unread_data', cache.html());
};

var pop3_combined_unread_content = function(id) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_pop3_folder_display'},
        {'name': 'pop3_unread_only', 'value': 1},
        {'name': 'pop3_server_id', 'value': id}],
        update_pop3_unread_display,
        [],
        false,
        Hm_Message_List.set_unread_state
    );
    return false;
};

var update_pop3_unread_display = function(res) {
    var ids = [res.pop3_server_id];
    Hm_Message_List.update(ids, res.formatted_message_list, 'pop3');
};

var expand_pop3_settings = function() {
    var hash = window.location.hash;
    if (hash) {
        if (hash.replace('#', '.') == '.pop3_setting') {
            $('.pop3_setting').css('display', 'table-row');
        }
    }
    else {
        var dsp = Hm_Utils.get_from_local_storage('.pop3_setting');
        if (dsp == 'table-row' || dsp == 'none') {
            $('.pop3_setting').css('display', dsp);
        }
    }
};

if (hm_page_name() == 'servers') {
    $('.test_pop3_connect').on('click', pop3_test_action);
    $('.save_pop3_connection').on('click', pop3_save_action);
    $('.forget_pop3_connection').on('click', pop3_forget_action);
    $('.delete_pop3_connection').on('click', pop3_delete_action);
    var dsp = Hm_Utils.get_from_local_storage('.pop3_section');
    if (dsp == 'block' || dsp == 'none') {
        $('.pop3_section').css('display', dsp);
    }
}
else if (hm_page_name() == 'message' && hm_list_path().substr(0, 4) == 'pop3') {
    pop3_message_view();
}
else if (hm_page_name() == 'info') {
    setTimeout(pop3_status_update, 100);
}
else if (hm_page_name() == 'settings') {
    expand_pop3_settings();
}


var imap_delete_action = function(event) {
    if (!hm_delete_prompt()) {
        return false;
    }
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            if (res.deleted_server_id > -1 ) {
                form.parent().remove();
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
            }
        },
        {'imap_delete': 1}
    );
};

var imap_hide_action = function(form, server_id, hide) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_debug'},
        {'name': 'imap_server_id', 'value': server_id},
        {'name': 'hide_imap_server', 'value': hide}],
        function() {
            if (hide) {
                $('.unhide_imap_connection', form).show();
                $('.hide_imap_connection', form).hide();
            }
            else {
                $('.unhide_imap_connection', form).hide();
                $('.hide_imap_connection', form).show();
            }
            Hm_Folders.reload_folders(true);
        }
    );
};

var imap_hide = function(event) {
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    var server_id = $('.imap_server_id', form).val();
    imap_hide_action(form, server_id, 1);
};

var imap_unhide = function(event) {
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    var server_id = $('.imap_server_id', form).val();
    imap_hide_action(form, server_id, 0);
};

var imap_forget_action = function(event) {
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            if (res.just_forgot_credentials) {
                form.find('.credentials').prop('disabled', false);
                form.find('.credentials').val('');
                form.find('.credentials').attr('placeholder', '');
                form.append('<input type="submit" value="Save" class="save_imap_connection" />');
                $('.save_imap_connection').on('click', imap_save_action);
                $('.forget_imap_connection', form).hide();
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
            }
        },
        {'imap_forget': 1}
    );
};

var imap_save_action = function(event) {
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            if (res.just_saved_credentials) {
                form.find('.credentials').attr('disabled', true);
                form.find('.save_imap_connection').hide();
                form.find('.imap_password').val('');
                form.find('.imap_password').attr('placeholder', '[saved]');
                form.append('<input type="submit" value="Forget" class="forget_imap_connection" />');
                $('.forget_imap_connection').on('click', imap_forget_action);
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
            }
        },
        {'imap_save': 1}
    );
};

var imap_test_action = function(event) {
    $('.imap_folder_data').empty();
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        false,
        {'imap_connect': 1}
    );
}

var imap_setup_server_page = function() {
    $('.imap_delete').on('click', imap_delete_action);
    $('.save_imap_connection').on('click', imap_save_action);
    $('.hide_imap_connection').on('click', imap_hide);
    $('.unhide_imap_connection').on('click', imap_unhide);
    $('.forget_imap_connection').on('click', imap_forget_action);
    $('.test_imap_connect').on('click', imap_test_action);

    var dsp = Hm_Utils.get_from_local_storage('.imap_section');
    if (dsp === 'block' || dsp === 'none') {
        $('.imap_section').css('display', dsp);
    }
    var jdsp = Hm_Utils.get_from_local_storage('.jmap_section');
    if (jdsp === 'block' || jdsp === 'none') {
        $('.jmap_section').css('display', jdsp);
    }
};

var set_message_content = function(path, msg_uid) {
    if (!path) {
        path = hm_list_path();
    }
    if (!msg_uid) {
        msg_uid = hm_msg_uid();
    }
    var key = msg_uid+'_'+path;
    Hm_Utils.save_to_local_storage(key, $('.msg_text').html());
};

var imap_delete_message = function(state, supplied_uid, supplied_detail) {
    if (!hm_delete_prompt()) {
        return false;
    }
    var uid = hm_msg_uid();
    var detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
    if (supplied_uid) {
        uid = supplied_uid;
    }
    if (supplied_detail) {
        detail = supplied_detail;
    }
    if (detail && uid) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_delete_message'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            function(res) {
                if (!res.imap_delete_error) {
                    if (Hm_Utils.get_from_global('msg_uid', false)) {
                        return;
                    }
                    var nlink = $('.nlink');
                    if (nlink.length) {
                        window.location.href = nlink.attr('href');
                    }
                    else {
                        if (!hm_list_parent()) {
                            window.location.href = "?page=message_list&list_path="+hm_list_path();
                        }
                        else {
                            window.location.href = "?page=message_list&list_path="+hm_list_parent();
                        }
                    }
                }
            }
        );
    }
    return false;
};

var imap_flag_message = function(state, supplied_uid, supplied_detail) {
    var uid = hm_msg_uid();
    var detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
    if (supplied_uid) {
        uid = supplied_uid;
    }
    if (supplied_detail) {
        detail = supplied_detail;
    }
    if (detail && uid) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_flag_message'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_flag_state', 'value': state},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            function() {
                if (state === 'flagged') {
                    $('#flag_msg').show();
                    $('#unflag_msg').hide();
                }
                else {
                    $('#flag_msg').hide();
                    $('#unflag_msg').show();
                }
                set_message_content();
                imap_message_view_finished(false, false, true);
            }
        );
    }
    return false;
};

var imap_status_update = function() {
    var id;
    var i;
    if ($('.imap_server_ids').length) {
        var ids = $('.imap_server_ids').val().split(',');
        if ( ids && ids !== '') {
            var process_result = function(res) {
                var id = res.imap_status_server_id;
                $('.imap_status_'+id).html(res.imap_status_display);
            };
            for (i=0;i<ids.length;i++) {
                id=ids[i];
                Hm_Ajax.request(
                    [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_status'},
                    {'name': 'imap_server_ids', 'value': id}],
                    process_result
                );
            }
        }
    }
    return false;
};

var imap_message_list_content = function(id, folder, hook, batch_callback) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': hook},
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
        batch_callback
    );
    return false;
};

var add_auto_folder = function(folder) {
    $('.list_sources').append('<div class="list_src">imap '+folder+'</div>');
    var count = $('.src_count').text()*1;
    count++;
    $('.src_count').html(count);
};

var imap_sent_content = function(id, folder) {
    return imap_message_list_content(id, folder, 'ajax_imap_sent', cache_sent_data);
};

var cache_sent_data = function() {
    if (hm_list_path() == 'sent') {
        Hm_Message_List.set_message_list_state('formatted_sent_data');
    }
};

var imap_all_mail_content = function(id, folder) {
    return imap_message_list_content(id, folder, 'ajax_imap_combined_inbox', Hm_Message_List.set_all_mail_state);
};

var imap_search_page_content = function(id, folder) {
    if (hm_search_terms()) {
        return imap_message_list_content(id, folder, 'ajax_imap_search', Hm_Message_List.set_search_state);
    }
    return false;
};

var update_imap_combined_source = function(path, state, event) {
    event.preventDefault();
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_update_combined_source'},
        {'name': 'list_path', 'value': path},
        {'name': 'combined_source_state', 'value': state}],
        function() {
            if (state === 1) {
                $('.add_source').hide();
                $('.remove_source').show();
            }
            else {
                $('.add_source').show();
                $('.remove_source').hide();
            }
        },
        [],
        true
    );
    return false;
};

var remove_imap_combined_source = function(event) {
    return update_imap_combined_source(hm_list_path(), 0, event);
};

var add_imap_combined_source = function(event) {
    return update_imap_combined_source(hm_list_path(), 1, event);
};

var imap_combined_unread_content = function(id, folder) {
    return imap_message_list_content(id, folder, 'ajax_imap_unread', Hm_Message_List.set_unread_state);
};

var imap_combined_flagged_content = function(id, folder) {
    return imap_message_list_content(id, folder, 'ajax_imap_flagged', Hm_Message_List.set_flagged_state);
};

var imap_combined_inbox_content = function(id, folder) {
    return imap_message_list_content(id, folder, 'ajax_imap_combined_inbox', Hm_Message_List.set_combined_inbox_state);
};

var cache_imap_page = function() {
    var key = 'imap_'+Hm_Utils.get_url_page_number()+'_'+hm_list_path();
    var data = Hm_Message_List.filter_list();
    Hm_Utils.save_to_local_storage(key, data.html());
    Hm_Utils.save_to_local_storage(key+'_page_links', $('.page_links').html());
}

var fetch_cached_imap_page = function() {
    var key = 'imap_'+Hm_Utils.get_url_page_number()+'_'+hm_list_path();
    var page = Hm_Utils.get_from_local_storage(key);
    var links = Hm_Utils.get_from_local_storage(key+'_page_links');
    return [ page, links ];

}

var select_imap_folder = function(path, callback) {
    var detail = Hm_Utils.parse_folder_path(path, 'imap');
    if (detail) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folder_display'},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            display_imap_mailbox,
            [],
            false,
            callback
        );
    }
    return false;
};

var setup_imap_folder_page = function() {
    var cache_details = fetch_cached_imap_page();
    if (cache_details[0]) {
        $('.message_table tbody').html(cache_details[0]);
    }
    if (cache_details[1]) {
        $('.page_links').html(cache_details[1]);
    }
    Hm_Timer.add_job(function() { select_imap_folder(hm_list_path()); }, 60);
    $('.remove_source').on("click", remove_imap_combined_source);
    $('.add_source').on("click", add_imap_combined_source);
    $('.refresh_link').on("click", function() {
        if ($('.imap_keyword').val()) {
            $('#imap_filter_form').submit();
        }
        else {
            select_imap_folder(hm_list_path());
        }
    });
    $('.imap_filter').on("change", function() { $('#imap_filter_form').submit(); });
    $('.imap_sort').on("change", function() { $('#imap_filter_form').submit(); });
    $('.imap_keyword').on('search', function() {
        $('#imap_filter_form').submit();
    });
    Hm_Ajax.add_callback_hook('ajax_message_action', function() { select_imap_folder(hm_list_path()); });
};

var display_imap_mailbox = function(res) {
    var ids = [res.imap_server_id];
    Hm_Message_List.update(ids, res.formatted_message_list, 'imap');
    Hm_Message_List.check_empty_list();
    $('.page_links').html(res.page_links);
    $('input[type=checkbox]').on("click", function(e) {
        Hm_Message_List.toggle_msg_controls();
        Hm_Message_List.check_select_range(e);
    });
    cache_imap_page();
};

var expand_imap_mailbox = function(res) {
    if (res.imap_expanded_folder_path) {
        $('.'+Hm_Utils.clean_selector(res.imap_expanded_folder_path), $('.email_folders')).append(res.imap_expanded_folder_formatted);
        $('.imap_folder_link', $('.email_folders')).off('click');
        $('.imap_folder_link', $('.email_folders')).on("click", function() { return expand_imap_folders($(this).data('target')); });
        Hm_Folders.update_unread_counts();
    }
};

var prefetch_imap_folders = function() {
    var id_el = $('#imap_prefetch_ids');
    if (!id_el.length) {
        return;
    }
    var ids = id_el.val().split(',');
    if (ids.length == 0 ) {
        return;
    }
    var id = ids.shift();
    if (id === '') {
        return;
    }

    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folder_expand'},
        {'name': 'imap_server_id', 'value': id},
        {'name': 'imap_prefetch', 'value': true},
        {'name': 'folder', 'value': ''}],
        function(res) { $('#imap_prefetch_ids').val(ids.join(',')); prefetch_imap_folders(); },
        [],
        true
    );

};

var expand_imap_folders = function(path) {
    var detail = Hm_Utils.parse_folder_path(path, 'imap');
    var list = $('.imap_'+detail.server_id+'_'+Hm_Utils.clean_selector(detail.folder), $('.email_folders'));
    if ($('li', list).length === 0) {
        $('.expand_link', list).html('-');
        if (detail) {
            Hm_Ajax.request(
                [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folder_expand'},
                {'name': 'imap_server_id', 'value': detail.server_id},
                {'name': 'folder', 'value': detail.folder}],
                expand_imap_mailbox,
                [],
                false,
                Hm_Folders.save_folder_list
            );
        }
    }
    else {
        $('.expand_link', list).html('+');
        $('ul', list).remove();
        Hm_Folders.save_folder_list();
    }
    return false;
};

var get_message_content = function(msg_part, uid, list_path, detail, callback, noupdate, images) {
    if (!images) {
        images = 0;
    }
    if (!uid) {
        uid = $('.msg_uid').val();
    }
    if (!detail) {
        detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
    }
    if (detail && uid) {
        if (hm_page_name() == 'message') {
            window.scrollTo(0,0);
        }
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_message_content'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_msg_part', 'value': msg_part},
            {'name': 'imap_allow_images', 'value': images},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            function(res) {
                if (!noupdate) {
                    $('.msg_text').html('');
                    $('.msg_text').append(res.msg_headers);
                    $('.msg_text').append(res.msg_text);
                    $('.msg_text').append(res.msg_parts);
                    set_message_content(list_path, uid);
                    document.title = $('.header_subject th').text();
                    imap_message_view_finished();
                }
                else {
                    $('.reply_link, .reply_all_link, .forward_link').each(function() {
                        $(this).attr("href", $(this).data("href"));
                        $(this).removeClass('disabled_link');
                    });
                }
            },
            [],
            false,
            callback
        );
    }
    return false;
};

var imap_mark_as_read = function(uid, detail) {
    if (!uid) {
        uid = $('.msg_uid').val();
    }
    if (!detail) {
        detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
    }
    if (detail && uid) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_mark_as_read'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder}],
            function() {},
            false,
            true
        );
    }
    return false;
};

var imap_message_view_finished = function(msg_uid, detail, skip_links) {
    var class_name = false;
    if (!detail) {
        detail = Hm_Utils.parse_folder_path(hm_list_path(), 'imap');
    }
    if (!msg_uid) {
        msg_uid = hm_msg_uid();
    }
    if (detail && !skip_links) {
        class_name = 'imap_'+detail.server_id+'_'+msg_uid+'_'+detail.folder;
        if (hm_list_parent() === 'combined_inbox') {
            Hm_Message_List.prev_next_links('formatted_combined_inbox', class_name);
        }
        else if (hm_list_parent() === 'unread') {
            Hm_Message_List.prev_next_links('formatted_unread_data', class_name);
        }
        else if (hm_list_parent() === 'flagged') {
            Hm_Message_List.prev_next_links('formatted_flagged_data', class_name);
        }
        else if (hm_list_parent() === 'advanced_search') {
            Hm_Message_List.prev_next_links('formatted_advanced_search_data', class_name);
        }
        else if (hm_list_parent() === 'search') {
            Hm_Message_List.prev_next_links('formatted_search_data', class_name);
        }
        else if (hm_list_parent() === 'sent') {
            Hm_Message_List.prev_next_links('formatted_sent_data', class_name);
        }
        else {
            var key = 'imap_'+Hm_Utils.get_url_page_number()+'_'+hm_list_path();
            Hm_Message_List.prev_next_links(key, class_name);
        }
    }
    if (Hm_Message_List.track_read_messages(class_name)) {
        if (hm_list_parent() == 'unread') {
            Hm_Message_List.adjust_unread_total(-1);
        }
    }
    $('.all_headers').on("click", function() { return Hm_Utils.toggle_long_headers(); });
    $('.small_headers').on("click", function() { return Hm_Utils.toggle_long_headers(); });
    $('.msg_part_link').on("click", function() {
        $('.header_subject')[0].scrollIntoView();
        $('.msg_text_inner').css('visibility', 'hidden');
        return get_message_content($(this).data('messagePart'), false, false, false, false, false, $(this).data('allowImages'));
    });
    $('#flag_msg').on("click", function() { return imap_flag_message($(this).data('state')); });
    $('#unflag_msg').on("click", function() { return imap_flag_message($(this).data('state')); });
    $('#delete_message').on("click", function() { return imap_delete_message(); });
    $('#move_message').on("click", function(e) { return imap_move_copy(e, 'move', 'message');});
    $('#copy_message').on("click", function(e) { return imap_move_copy(e, 'copy', 'message');});
};

var get_local_message_content = function(msg_uid, path) {
    if (!path) {
        path = hm_list_path();
    }
    if (!msg_uid) {
        msg_uid = hm_msg_uid();
    }
    var key = msg_uid+'_'+path;
    return Hm_Utils.get_from_local_storage(key);
};

var imap_prefetch_message_content = function(uid, server_id, folder) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_message_content'},
        {'name': 'imap_msg_uid', 'value': uid},
        {'name': 'imap_msg_part', 'value': ''},
        {'name': 'imap_server_id', 'value': server_id},
        {'name': 'imap_prefetch', 'value': true},
        {'name': 'folder', 'value': folder}],
        function(res) {
            var key = uid+'_imap_'+server_id+'_'+folder;
            if (!Hm_Utils.get_from_local_storage(key)) {
                var div;
                div = $('<div></div>');
                div.append(res.msg_headers);
                div.append(res.msg_text);
                div.append(res.msg_parts);
                Hm_Utils.save_to_local_storage(key, div.html());
            }
        },
        [],
        true
    );
    return false;
};

var imap_prefetch_msgs = function() {
    var detail;
    var key;
    $(Hm_Utils.get_from_local_storage('formatted_unread_data')).each(function() {
        if ($(this).attr('class').match(/^imap/)) {
            detail = Hm_Utils.parse_folder_path($(this).attr('class'), 'imap');
            key = detail.uid+'_'+detail.type+'_'+detail.server_id+'_'+detail.folder;
            if (!Hm_Utils.get_from_local_storage(key)) {
                imap_prefetch_message_content(detail.uid, detail.server_id, detail.folder);
                return false;
            }
        }
    });
};

var imap_setup_message_view_page = function(uid, details, list_path, callback) {
    var msg_content = get_local_message_content(uid, list_path);
    if (!msg_content || !msg_content.length || msg_content.indexOf('<div class="msg_text_inner"></div>') > -1) {
        get_message_content(false, uid, list_path, details, callback);
    }
    else {
        $('.msg_text').html(msg_content);
        document.title = $('.header_subject th').text();
        $('.reply_link, .reply_all_link, .forward_link').each(function() {
            $(this).data("href", $(this).attr("href")).removeAttr("href");
            $(this).addClass('disabled_link');
        });
        imap_message_view_finished();
        get_message_content(false, uid, list_path, details, callback, true);
    }
};

var display_reply_content = function(res) {
    $('.compose_to').prop('disabled', false);
    $('.smtp_send').prop('disabled', false);
    $('.compose_subject').prop('disabled', false);
    $('.compose_body').prop('disabled', false);
    $('.smtp_server_id').prop('disabled', false);
    $('.compose_body').text(res.reply_body);
    $('.compose_subject').val(res.reply_subject);
    $('.compose_to').val(res.reply_to);
    document.title = res.reply_subject;
};

var imap_background_unread_content_result = function(res) {
    if (!$.isEmptyObject(res.folder_status)) {
        var detail = Hm_Utils.parse_folder_path(Object.keys(res.folder_status)[0], 'imap');
        var ids = [detail.server_id+'_'+detail.folder];
        var cache = $('<tbody></tbody>').append($(Hm_Utils.get_from_local_storage('formatted_unread_data')));
        globals.Hm_Background_Unread.update(ids, res.formatted_message_list, 'imap', cache);
        Hm_Utils.save_to_local_storage('formatted_unread_data', cache.html());
    }
};

var check_select_for_imap = function() {
    $('input[type=checkbox]').off('change'); 
    $('input[type=checkbox]').on("change", function(e) { search_selected_for_imap(); });
};

var search_selected_for_imap = function() {
    var imap_selected = false;
    $('input[type=checkbox]').each(function() {
        if (this.checked && this.id.search('imap') != -1) {
            imap_selected = true;
            return false;
        }
    });
    if (imap_selected) {
        $('.imap_move').removeClass('disabled_input');
        $('.imap_move').off('click');
        $('.imap_move').on("click", function(e) {return imap_move_copy(e, $(this).data('action'), 'list');});
    }
    else {
        $('.imap_move').addClass('disabled_input');
        $('.imap_move').off('click');
        $('.imap_move').on("click", function() { return false; });
        $('.move_to_location').html('');
        $('.move_to_location').hide();
    }
};

var unselect_non_imap_messages = function() {
    var unselected = 0;
    $('input[type=checkbox]').each(function() {
        if (this.checked && this.id.search('imap') == -1) {
            this.checked = false;
            unselected++;
        }
    });
    if (unselected > 0) {
        Hm_Notices.show({0: 'ERR'+$('.move_to_string3').val()});
    }
};

var imap_move_copy = function(e, action, context) {
    var move_to;
    if (!e.target || e.target.className == 'imap_move') {
        move_to = $('.msg_controls .move_to_location');
    }
    else {
        move_to = $('.msg_text .move_to_location');
    }
    unselect_non_imap_messages();
    var label;
    var folders = $('.email_folders').clone(false);
    folders.find('.manage_folders_li').remove();
    $('.menu_email', folders).remove();
    folders.removeClass('email_folders');
    folders.show();
    $('.imap_folder_link', folders).addClass('imap_move_folder_link').removeClass('imap_folder_link');
    if (action == 'move') {
        label = $('.move_to_string1').val(); 
    }
    else {
        label = $('.move_to_string2').val();
    }
    folders.prepend('<div class="move_to_title">'+label+'<span><a class="close_move_to" href="#">X</a></span></div>');
    move_to.html(folders.html());
    $('.imap_move_folder_link', move_to).on("click", function() { return expand_imap_move_to_folders($(this).data('target'), context); });
    $('a', move_to).not('.imap_move_folder_link').not('.close_move_to').off('click');
    $('a', move_to).not('.imap_move_folder_link').not('.close_move_to').on("click", function() { imap_perform_move_copy($(this).data('id'), context); return false; });
    $('.move_to_type').val(action);
    $('.close_move_to').on("click", function() {
        $('.move_to_location').html('');
        $('.move_to_location').hide();
        return false;
    });
    move_to.show();
    return false;
};

var imap_perform_move_copy = function(dest_id, context) {
    var action = $('.move_to_type').val();
    var ids = [];
    var page = hm_page_name();
    $('.move_to_location').html('');
    $('.move_to_location').hide();

    if (context == 'message') {
        var inline_uuid = Hm_Utils.get_from_global('inline_move_uuid', false);
        if (inline_uuid) {
            ids.push(inline_uuid);
            globals['inline_move_uuid'] = false;
        }
        else if (page == 'message') {
            var uid = hm_msg_uid();
            var path = Hm_Utils.parse_folder_path(hm_list_path());
            ids.push('imap_'+path['server_id']+'_'+uid+'_'+path['folder']);
        }
    }
    else if (context == 'list') {
        $('input[type=checkbox]').each(function() {
            if (this.checked && this.id.search('imap') != -1) {
                ids.push(this.id);
            }
        });
    }
    if (ids.length > 0 && dest_id) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_move_copy_action'},
            {'name': 'imap_move_ids', 'value': ids.join(',')},
            {'name': 'imap_move_to', 'value': dest_id},
            {'name': 'imap_move_page', 'value': page},
            {'name': 'imap_move_action', 'value': action}],
            function(res) {
                var index;
                if (hm_page_name() == 'message_list') {
                    Hm_Message_List.reset_checkboxes();
                    if (action == 'move') {
                        for (index in res.move_count) {
                            $('.'+Hm_Utils.clean_selector(res.move_count[index])).remove();
                        }
                    }
                    if (hm_list_path().substr(0, 4) === 'imap') {
                        select_imap_folder(hm_list_path());
                    }
                    else {
                        Hm_Message_List.load_sources();
                    }
                }
                else {
                    if (action == 'move') {
                        var nlink = $('.nlink');
                        if (nlink.length) {
                            window.location.href = nlink.attr('href');
                        }
                        else {
                            window.location.href = "?page=message_list&list_path="+hm_list_parent();
                        }
                    }
                }
            }
        );
    }
};

var expand_imap_move_to_mailbox = function(res, context) {
    if (res.imap_expanded_folder_path) {
        var move_to = $('.move_to_location');
        var folders = $(res.imap_expanded_folder_formatted);
        folders.find('.manage_folders_li').remove();
        $('.'+Hm_Utils.clean_selector(res.imap_expanded_folder_path), $('.move_to_location')).append(folders);
        $('.imap_folder_link', move_to).addClass('imap_move_folder_link').removeClass('imap_folder_link');
        $('.imap_move_folder_link', move_to).off('click');
        $('.imap_move_folder_link', move_to).on("click", function() { return expand_imap_move_to_folders($(this).data('target'), context); });
        $('a', move_to).not('.imap_move_folder_link').off('click');
        $('a', move_to).not('.imap_move_folder_link').on("click", function() { imap_perform_move_copy($(this).data('id'), context); return false; });
    }
};

var expand_imap_move_to_folders = function(path, context) {
    var detail = Hm_Utils.parse_folder_path(path, 'imap');
    var list = $('.imap_'+detail.server_id+'_'+Hm_Utils.clean_selector(detail.folder), $('.move_to_location'));
    if ($('li', list).length === 0) {
        $('.expand_link', list).html('-');
        if (detail) {
            Hm_Ajax.request(
                [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folder_expand'},
                {'name': 'imap_server_id', 'value': detail.server_id},
                {'name': 'folder', 'value': detail.folder}],
                function (res) { expand_imap_move_to_mailbox(res, context); }
            );
        }
    }
    else {
        $('.expand_link', list).html('+');
        $('ul', list).remove();
    }
    return false;
};

var imap_background_unread_content = function(id, folder) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_unread'},
        {'name': 'folder', 'value': folder},
        {'name': 'imap_server_ids', 'value': id}],
        imap_background_unread_content_result,
        [],
        false,
        function() {
            var cache = $('<tbody></tbody>').append($(Hm_Utils.get_from_local_storage('formatted_unread_data')));
            Hm_Message_List.adjust_unread_total($('tr', cache).length, true);
        }
    );
    return false;
};

var get_imap_folder_status = function(id, folder) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folder_status'},
        {'name': 'imap_server_id', 'value': id},
        {'name': 'folder', 'value': folder}],
        false,
        [],
        true,
        Hm_Folders.update_unread_counts
    );
}

var imap_folder_status = function() {
    var source;
    var sources = hm_data_sources();
    if (!sources || !sources.length) {
        sources = hm_data_sources_background();
    }
    for (var index in sources) {
        source = sources[index];
        if (source.type == 'imap') {
            get_imap_folder_status(source.id, source.folder);
        }
    }
};

if (hm_list_path() == 'sent') {
    Hm_Message_List.page_caches.sent = 'formatted_sent_data';
}

$(function() {
    if (hm_page_name() === 'message_list' && hm_list_path().substr(0, 4) === 'imap') {
        setup_imap_folder_page();
    }
    else if (hm_page_name() === 'message' && hm_list_path().substr(0, 4) === 'imap') {
        imap_setup_message_view_page();
    }
    else if (hm_page_name() === 'servers') {
        imap_setup_server_page();
    }
    else if (hm_page_name() === 'info') {
        setTimeout(imap_status_update, 100);
    }

    if ($('.imap_move').length > 0) {
        check_select_for_imap();
        $('.toggle_link').on("click", function() { setTimeout(search_selected_for_imap, 100); });
        Hm_Ajax.add_callback_hook('ajax_imap_folder_display', check_select_for_imap);
        Hm_Message_List.callbacks.push(check_select_for_imap);
        $('.imap_move').on("click", function() { return false; });
    }

    if (hm_list_path() !== 'unread') {
        if (typeof hm_data_sources_background === 'function') {
            globals.Hm_Background_Unread = new Message_List();
            globals.Hm_Background_Unread.background = true;
            globals.Hm_Background_Unread.add_sources(hm_data_sources_background());
            var interval = Hm_Utils.get_from_global('imap_background_update_interval', 33);
            Hm_Timer.add_job(globals.Hm_Background_Unread.load_sources, interval, true);
        }
    }
    var prefetch_interval = Hm_Utils.get_from_global('imap_prefetch_msg_interval', 43);
    Hm_Timer.add_job(imap_prefetch_msgs, prefetch_interval, true);
    setTimeout(prefetch_imap_folders, 2);
});


var smtp_test_action = function(event) {
    event.preventDefault();
    var form = $(this).parent();
    Hm_Notices.hide(true);
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            Hm_Notices.show(res.router_user_msgs);
        },
        {'smtp_connect': 1}
    );
};

var smtp_save_action = function(event) {
    event.preventDefault();
    var form = $(this).parent();
    Hm_Notices.hide(true);
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            Hm_Notices.show(res.router_user_msgs);
            if (res.just_saved_credentials) {
                form.find('.credentials').attr('disabled', true);
                form.find('.save_smtp_connection').hide();
                form.find('.smtp_password').val('');
                form.find('.smtp_password').attr('placeholder', '[saved]');
                form.append('<input type="submit" value="Forget" class="forget_smtp_connection" />');
                $('.forget_smtp_connection').on('click', smtp_forget_action);
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
            }
        },
        {'smtp_save': 1}
    );
};

var smtp_forget_action = function(event) {
    event.preventDefault();
    var form = $(this).parent();
    Hm_Notices.hide(true);
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            Hm_Notices.show(res.router_user_msgs);
            if (res.just_forgot_credentials) {
                form.find('.credentials').prop('disabled', false);
                form.find('.credentials').val('');
                form.append('<input type="submit" value="Save" class="save_smtp_connection" />');
                $('.save_smtp_connection').on('click', smtp_save_action);
                $('.forget_smtp_connection', form).remove();
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
            }
        },
        {'smtp_forget': 1}
    );
};

var smtp_delete_action = function(event) {
    if (!hm_delete_prompt()) {
        return false;
    }
    event.preventDefault();
    Hm_Notices.hide(true);
    var form = $(this).parent();
    Hm_Ajax.request(
        form.serializeArray(),
        function(res) {
            Hm_Notices.show(res.router_user_msgs);
            if (res.deleted_server_id > -1 ) {
                form.parent().remove();
                Hm_Utils.set_unsaved_changes(1);
                Hm_Folders.reload_folders(true);
            }
        },
        {'smtp_delete': 1}
    );
};

var smtp_delete_draft = function(id) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_smtp_delete_draft'},
        {'name': 'draft_id', 'value': id}],
        function(res) {
            if (res.draft_id != -1) {
                $('.draft_'+id).remove();
                $('.draft_list').toggle();
            }
        }
    );
};

var save_compose_state = function(no_files, notice) {
    var no_icon = true;
    if (notice) {
        no_icon = false;
    }
    var body = $('.compose_body').val();
    var subject = $('.compose_subject').val();
    var to = $('.compose_to').val();
    var smtp = $('.compose_server').val();
    var cc = $('.compose_cc').val();
    var bcc = $('.compose_bcc').val();
    var inreplyto = $('.compose_in_reply_to').val();
    var draft_id = $('.compose_draft_id').val();
    if (globals.draft_state == body+subject+to+smtp+cc+bcc) {
        return;
    }
    globals.draft_state = body+subject+to+smtp+cc+bcc;

    $('.smtp_send').prop('disabled', true);
    $('.smtp_send').addClass('disabled_input');
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_smtp_save_draft'},
        {'name': 'draft_body', 'value': body},
        {'name': 'draft_id', 'value': draft_id},
        {'name': 'draft_smtp', 'value': smtp},
        {'name': 'draft_subject', 'value': subject},
        {'name': 'draft_cc', 'value': cc},
        {'name': 'draft_bcc', 'value': bcc},
        {'name': 'draft_notice', 'value': notice},
        {'name': 'draft_in_reply_to', 'value': inreplyto},
        {'name': 'delete_uploaded_files', 'value': no_files},
        {'name': 'draft_to', 'value': to}],
        function(res) {
            $('.smtp_send').prop('disabled', false);
            $('.smtp_send').removeClass('disabled_input');
            if (res.draft_subject) {
                $('.draft_list .draft_'+draft_id+' a').text(res.draft_subject);
            }
        },
        [],
        no_icon
    );
};

var toggle_recip_flds = function() {
    var symbol = '+';
    if ($('.toggle_recipients').text() == '+') {
        symbol = '-';
    }
    $('.toggle_recipients').text(symbol);
    $('.recipient_fields').toggle();
    return false;
}

if (hm_page_name() === 'servers') {
    $('.test_smtp_connect').on('click', smtp_test_action);
    $('.save_smtp_connection').on('click', smtp_save_action);
    $('.forget_smtp_connection').on('click', smtp_forget_action);
    $('.delete_smtp_connection').on('click', smtp_delete_action);
    var dsp = Hm_Utils.get_from_local_storage('.smtp_section');
    if (dsp === 'block' || dsp === 'none') {
        $('.smtp_section').css('display', dsp);
    }
}

var reset_smtp_form = function() {
    $('.compose_body').val('');
    $('.compose_subject').val('');
    $('.compose_to').val('');
    $('.compose_cc').val('');
    $('.compose_bcc').val('');
    $('.ke-content', $('iframe').contents()).html('');
    $('.uploaded_files').html('');
    save_compose_state(true);
};

var upload_file = function(file) {
    var res = '';
    var form = new FormData();
    var xhr = new XMLHttpRequest;
    Hm_Ajax.show_loading_icon();
    form.append('upload_file', file);
    form.append('hm_ajax_hook', 'ajax_smtp_attach_file');
    form.append('hm_page_key', $('#hm_page_key').val());
    form.append('draft_id', $('.compose_draft_id').val());
    xhr.open('POST', '', true);
    xhr.setRequestHeader('X-Requested-With', 'xmlhttprequest');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4){ 
            if (hm_encrypt_ajax_requests()) {
                res = Hm_Utils.json_decode(xhr.responseText);
                res = Hm_Utils.json_decode(Hm_Crypt.decrypt(res.payload));
            }
            else {
                res = Hm_Utils.json_decode(xhr.responseText);
            }
            if (res.file_details) {
                $('.uploaded_files').append(res.file_details);
                $('.delete_attachment').on("click", function() { return delete_attachment($(this).data('id'), this); });
            }
            Hm_Ajax.stop_loading_icon();
            if (res.router_user_msgs && !$.isEmptyObject(res.router_user_msgs)) {
                Hm_Notices.show(res.router_user_msgs);
            }
        }
    }
    xhr.send(form);
};

var delete_attachment = function(file, link) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_smtp_delete_attachment'},
        {'name': 'attachment_id', 'value': file}],
        function(res) { $(link).parent().parent().remove(); }
    );
    return false;
};

$(function() {
    if (hm_page_name() === 'compose') {
        var interval = Hm_Utils.get_from_global('compose_save_interval', 30);
        Hm_Timer.add_job(function() { save_compose_state(); }, interval, true);
        $('.draft_title').on("click", function() { $('.draft_list').toggle(); });
        $('.toggle_recipients').on("click", function() { return toggle_recip_flds(); });
        $('.smtp_reset').on("click", reset_smtp_form);
        $('.delete_draft').on("click", function() { smtp_delete_draft($(this).data('id')); });
        $('.smtp_save').on("click", function() { save_compose_state(false, true); });
        $('.compose_attach_button').on("click", function() { $('.compose_attach_file').trigger('click'); });
        $('.compose_attach_file').on("change", function() { upload_file(this.files[0]); $('.compose_attach_file').val(''); });
        $('.compose_form').on('submit', function() { Hm_Ajax.show_loading_icon(); $('.smtp_send').addClass('disabled_input'); $('.smtp_send').on("click", function() { return false; }); });
        if ($('.compose_cc').val() || $('.compose_bcc').val()) {
            toggle_recip_flds();
        }
        $('.delete_attachment').on("click", function() { return delete_attachment($(this).data('id'), this); });
    }
});
$(function() {
    $('.delete_user_form').on('submit', function() {
        return hm_delete_prompt();
    });
});


$(function() {
    if (hm_page_name() == 'calendar') {
        $('.event_delete').on("click", function() {
            if (hm_delete_prompt()) {
                $(this).parent().submit();
            }
        });
        $('.cal_title').on("click", function() {
            $('.event_details').hide();
            $('.event_details', $(this).parent()).show();
            $('.event_details').on("click", function() {
                $(this).hide();
            });
        });
    }
});


var display_next_nux_step = function(res) {
    $('.nux_step_two').html(res.nux_service_step_two);
    $('.nux_step_one').hide();
    $('.nux_submit').on("click", nux_add_account);
    $('.reset_nux_form').on("click", function() {
        $('.nux_step_one').show();
        $('.nux_step_two').html('');
        document.getElementById('service_select').getElementsByTagName('option')[0].selected = 'selected';
        $('.nux_username').val('');
        return false;
    });
};

var nux_add_account = function() {
    var nux_border = $('.nux_username').css('border');
    $('.nux_password').css('border', nux_border);
    var service = $('#nux_service').val();
    var name = $('.nux_name').val();
    var email = $('#nux_email').val();
    var pass = $('.nux_password').val();
    if (name.length && service.length && email.length && pass.length) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_nux_add_service'},
            {'name': 'nux_service', 'value': service},
            {'name': 'nux_email', 'value': email},
            {'name': 'nux_name', 'value': name},
            {'name': 'nux_pass', 'value': pass}],
            display_final_nux_step,
            [],
            false
        );
    }
    else {
        if (!pass.length) {
            $('.nux_password').css('border', 'solid red 1px');
        }
    }
    return false;
};

var display_final_nux_step = function(res) {
    if (res.nux_account_added) {
        window.location.href = "?page=servers";
    }
};

var nux_service_select = function() {
    var nux_border = $('.nux_username').css('border');
    var el = document.getElementById('service_select');
    var service = el.options[el.selectedIndex].value;
    var email = $('.nux_username').val();
    var account = $('.nux_account_name').val();
    if (email.length && service.length) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_nux_service_select'},
            {'name': 'nux_service', 'value': service},
            {'name': 'nux_account_name', 'value': account},
            {'name': 'nux_email', 'value': email}],
            display_next_nux_step,
            [],
            false
        );
    }
    else {
        if (!email.length) {
            $('.nux_username').css('border', 'solid 1px red');
        }
        else {
            $('.nux_username').css('border', nux_border);
        }
        if (!service.length) {
            $('#service_select').css('border', 'solid 1px red');
        }
        else {
            $('#service_select').css('border', nux_border);
        }
    }
};

var expand_server_settings = function() {
    var dsp;
    var i;
    var hash = window.location.hash;
    var sections = ['.feeds_section', '.quick_add_section', '.smtp_section', '.imap_section', '.pop3_section'];
    for (i=0;i<sections.length;i++) {
        dsp = Hm_Utils.get_from_local_storage(sections[i]);
        if (hash) {
            if (hash.replace('#', '.') != sections[i]) {
                dsp = 'none';
            }
            else {
                dsp = 'block';
            }
        }
        if (dsp === 'block' || dsp === 'none') {
            $(sections[i]).css('display', dsp);
            Hm_Utils.save_to_local_storage(sections[i], dsp);
        }
    }
};

$(function() {
    if (hm_page_name() === 'servers') {
        expand_server_settings();
        $('.nux_next_button').on("click", nux_service_select);
    }
    else if (hm_page_name() === 'message_list') {
        var list_path = hm_list_path();
        if (list_path === 'unread' || list_path === 'combined_inbox' || list_path === 'flagged') {
            var data_sources = hm_data_sources();
            if (data_sources.length === 0) {
                $('.nux_empty_combined_view').show();
            }
        }
    }
});


$('.config_map_page').on("click", function() {
    var target = $(this).data('target');
    $('.'+target).toggle();
});


var update_search = function(event) {
    event.preventDefault();
    if ($('.search_terms').val().length && $('.search_name').val().length) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_update_search'},
            {'name': 'search_name', 'value': $('.search_name').val()},
            {'name': 'search_terms', 'value': $('.search_terms').val()},
            {'name': 'search_fld', 'value': $('#search_fld').val()},
            {'name': 'search_since', 'value': $('#search_since').val()}],
            search_update_results
        );
    }
    return false;
};

var delete_search = function(event) {
    if (!hm_delete_prompt()) {
        return false;
    }
    var name = $('.search_name').val();
    event.preventDefault();
    if (name.length) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_delete_search'},
            {'name': 'search_name', 'value': name}],
            search_delete_results
        );
    }
    return false;
};

var save_search = function(event) {
    event.preventDefault();
    if ($('.search_terms').val().length) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_save_search'},
            {'name': 'search_name', 'value': $('.search_terms').val()},
            {'name': 'search_terms', 'value': $('.search_terms').val()},
            {'name': 'search_fld', 'value': $('#search_fld').val()},
            {'name': 'search_since', 'value': $('#search_since').val()}],
            search_save_results
        );
    }
    return false;
};

var search_delete_results = function(res) {
    if (res.saved_search_result) {
        Hm_Folders.reload_folders(true, '.search_folders');
        Hm_Utils.reset_search_form();
    }
};

var search_update_results = function(res) {
    if (res.saved_search_result) {
        $('.update_search').remove();
        Hm_Folders.reload_folders(true, '.search_folders');
    }
};

var search_save_results = function(res) {
    if (res.saved_search_result) {
        $('.search_name').val($('.new_search_name').val());
        $('.delete_search').show();
        $('.save_search').hide();
        Hm_Folders.reload_folders(true, '.search_folders');
    }
};

if (hm_page_name() == 'search') {
    $('.save_search').on("click", save_search);
    $('.update_search').on("click", update_search);
    $('.delete_search').on("click", delete_search);
    if ($('.search_name').val().length) {
        Hm_Utils.save_to_local_storage('formatted_search_data', '');
    }
    else if ($('.search_terms').val().length) {
        $('.save_search').show();
    }
}
"use strict"

var add_remove_terms = function(el) {
    var close = $(globals.close_html);
    var count = $('.adv_terms').length;
    var term = $('#adv_term').clone(false);
    var not_chk = $('<span id="adv_term_not" class="adv_term_nots"><input type="checkbox" value="not" id="adv_term_not" /> !</span>');
    var and_or_html = '<div class="andor"><input checked="checked" type="radio" name="term_and'
    and_or_html += '_or'+count+'" value="and">and <input type="radio" name="term_and_or'+count;
    and_or_html += '" value="or">or</div>';
    var and_or = $(and_or_html);
    term.attr('id', 'adv_term'+count);
    close.attr('id', 'term_adv_remove'+count);
    and_or.attr('id', 'term_and_or'+count);
    not_chk.attr('id', 'adv_term_not'+count);
    $(el).prev().after(and_or.prop('outerHTML')+not_chk.prop('outerHTML')+term.prop('outerHTML')+close.prop('outerHTML'));
    $(el).hide();
    $('#term_adv_remove'+count).on("click", function() {
        $('#adv_term'+count).remove();
        $('#adv_term_not'+count).remove();
        $('#term_and_or'+count).remove();
        $(this).remove();
        $(el).show();
    });
};

var add_remove_times = function(el) {
    var close = $(globals.close_html);
    var count = $('.adv_times').length;
    var time_html = '<span id="adv_time" class="adv_times">From <input class="adv_time_fld_from" ';
    time_html += 'type="date" value=""> To <input class="adv_time_fld_to" type="date" value=""></span>';
    var timeset = $(time_html);
    var and_or_html = '<div class="timeandor"><input type="radio" name="time_and_or'+count;
    and_or_html += '" checked="checked" value="or">or</div>';
    var and_or = $(and_or_html);
    timeset.attr('id', 'adv_time'+count);
    close.attr('id', 'time_adv_remove'+count);
    and_or.attr('id', 'time_and_or'+count);
    $(el).prev().after(and_or.prop('outerHTML')+timeset.prop('outerHTML')+close.prop('outerHTML'));
    $('#time_adv_remove'+count).on("click", function() {
        $('#adv_time'+count).remove();
        $('#time_and_or'+count).remove();
        $(this).remove();
    });
};

var add_remove_targets = function(el) {
    var close = $(globals.close_html);
    var count = $('.adv_targets').length;
    var target = $('#adv_target').clone(false);
    var and_or_html = '<div class="andor"><input type="radio" name="target_and_or'+count;
    and_or_html += '" value="and">and <input type="radio" name="target_and_or'+count;
    and_or_html += '" checked="checked" value="or">or</div>';
    var and_or = $(and_or_html);

    target.attr('id', 'adv_target'+count);
    $('.target_radio', target).attr('name', 'target_type'+count);
    $('.target_radio', target).removeAttr('checked');
    close.attr('id', 'target_adv_remove'+count);
    and_or.attr('id', 'target_and_or'+count);
    $(el).prev().after(and_or.prop('outerHTML')+target.prop('outerHTML')+close.prop('outerHTML'));
    $(el).hide();
    $('#target_adv_remove'+count).on("click", function() {
        $('#adv_target'+count).remove();
        $('#target_and_or'+count).remove();
        $(this).remove();
        $(el).show();
    });
};

var expand_adv_folder = function(res) {
    if (res.imap_expanded_folder_path) {
        var list_container = $('.adv_folder_list');
        var folders = $(res.imap_expanded_folder_formatted);
        folders.find('.manage_folders_li').remove();
        $('.'+Hm_Utils.clean_selector(res.imap_expanded_folder_path), $('.adv_folder_list')).append(folders);
        $('.imap_folder_link', list_container).addClass('adv_folder_link').removeClass('imap_folder_link');
        $('.adv_folder_link', list_container).off('click');
        $('.adv_folder_link', list_container).on("click", function() { return expand_adv_folder_list($(this).data('target')); });
        $('a', list_container).not('.adv_folder_link').off('click');
        $('a', list_container).not('.adv_folder_link').on("click", function() { adv_folder_select($(this).data('id')); return false; });
    }
};

var adv_select_imap_folder = function(el) {
    var close = $(globals.close_html);
    close.addClass('close_adv_folders');
    var list_container = $('.adv_folder_list');
    var folders = $('.email_folders').clone(false);
    folders.find('.manage_folders_li').remove();
    $('.menu_email', folders).remove();
    folders.removeClass('email_folders');
    $(el).after(close);
    list_container.show();
    folders.show();
    $('.imap_folder_link', folders).addClass('adv_folder_link').removeClass('imap_folder_link');
    $('.adv_folder_list').html(folders.html());

    $('.adv_folder_link', list_container).on("click", function() { return expand_adv_folder_list($(this).data('target')); });
    $('a', list_container).not('.adv_folder_link').not('.close_adv_folders').off('click');
    $('a', list_container).not('.adv_folder_link').not('.close_adv_folders').on("click", function() { adv_folder_select($(this).data('id')); return false; });
    $('.close_adv_folders').on("click", function() {
        $('.adv_folder_list').html('');
        $('.adv_folder_list').hide();
        $(this).remove();
        return false;
    });
};

var adv_folder_select = function(id) {
    if ($('.'+id, $('.adv_source_list')).length > 0) {
        $('.adv_folder_list').html('');
        $('.close_adv_folders').remove();
        $('.adv_folder_list').hide();
        return;
    }
    var container = $('.adv_folder_list');
    var list_item = $('.'+Hm_Utils.clean_selector(id));
    var folder = $('a', list_item).first().text();
    if (folder == '+' || folder == '-') {
        folder = $('a', list_item).eq(1).text();
    }
    var parts = id.split('_', 3);
    var parent_class = '.'+parts[0]+'_'+parts[1]+'_';
    var account = $('a', $(parent_class, container)).first().text();
    var label = account+' &gt; '+folder;
    add_source_to_list(id, label);
    $('.adv_folder_list').html('');
    $('.close_adv_folders').remove();
    $('.adv_folder_list').hide();
};

var add_source_to_list = function(id, label) {
    var close = $(globals.close_html);
    close.addClass('adv_remove_source');
    close.attr('data-target', id);
    var row = '<div class="'+id+'">'+close.prop('outerHTML')+label;
    row += '<input type="hidden" value="'+id+'" /></div>';
    $('.adv_source_list').append(row);
    $('.adv_remove_source').off('click');
    $('.adv_remove_source').on("click", function() {
        $('.'+$(this).data('target'), $('.adv_source_list')).remove();
    });
};

var expand_adv_folder_list = function(path) {
    var detail = Hm_Utils.parse_folder_path(path, 'imap');
    var list = $('.imap_'+detail.server_id+'_'+Hm_Utils.clean_selector(detail.folder), $('.adv_folder_list'));
    if ($('li', list).length === 0) {
        $('.expand_link', list).html('-');
        if (detail) {
            Hm_Ajax.request(
                [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folder_expand'},
                {'name': 'imap_server_id', 'value': detail.server_id},
                {'name': 'folder', 'value': detail.folder}],
                function (res) { expand_adv_folder(res); }
            );
        }
    }
    else {
        $('.expand_link', list).html('+');
        $('ul', list).remove();
    }
    return false;
};

var adv_collapse = function() {
    $('.terms_section').hide();
    $('.source_section').hide();
    $('.targets_section').hide();
    $('.time_section').hide();
    $('.other_section').hide();
    $('.adv_expand_all').show();
    $('.adv_collapse_all').hide();
}

var adv_expand_sections = function() {
    $('.terms_section').show();
    $('.source_section').show();
    $('.targets_section').show();
    $('.time_section').show();
    $('.other_section').show();
    $('.adv_expand_all').hide();
    $('.adv_collapse_all').show();
}

var get_adv_sources = function() {
    var sources = [];
    var selected_sources = $('div', $('.adv_source_list'));
    if (!selected_sources) {
        return sources;
    }
    selected_sources.each(function() {
        sources.push({'source': this.className, 'label': $(this).text()});
    });
    return sources;
};

var get_adv_terms = function() {
    var term;
    var term_id;
    var condition;
    var not;
    var terms = [];
    var term_flds = $('.adv_terms');
    term_flds.each(function() {
        term = $(this).val();
        if (term && term.trim()) {
            term_id = this.id.substr(8);
            if (term_id) {
                condition = $('input:checked', $('#term_and_or'+term_id)).val();
            }
            else {
                condition = false;
            }
            if ($('input:checked', $('#adv_term_not'+term_id)).val() == 'not') {
                term = 'NOT '+term;
            }
            terms.push({'term': term, 'condition': condition});
        }
    });
    return terms;
};

var get_adv_times = function() {
    var time;
    var from;
    var to;
    var times = [];
    var time_flds = $('.adv_times');
    time_flds.each(function() {
        from = $('.adv_time_fld_from', $(this)).val();
        to = $('.adv_time_fld_to', $(this)).val();
        if (to && from && to.trim() && from.trim()) {
            times.push({'from': from, 'to': to});
        }
    });
    return times;

};

var get_adv_targets = function() {
    var target;
    var value;
    var target_id;
    var condition;
    var targets = [];
    var target_flds = $('.adv_targets');
    target_flds.each(function() {
        target = $('.target_radio:checked', $(this)).val();
        if (target == 'header') {
            value = $('.adv_header_select', $(this)).val();
        }
        else if (target == 'custom') {
            value = 'HEADER '+$('.adv_custom_header', $(this)).val();
        }
        else {
            value = target;
        }
        if (target) {
            target_id = this.id.substr(10);
            if (target_id) {
                condition = $('input:checked', $('#target_and_or'+target_id)).val();
            }
            else {
                condition = false;
            }
            targets.push({'target': value, 'orig': target, 'condition': condition});
        }
    });
    return targets;
};

var get_adv_other = function() {
    var charset = $('.charset').val();
    var flags = [];
    var flag_flds = $('.adv_flag:checked');
    if (flag_flds) {
        flag_flds.each(function() {
            flags.push($(this).val());
        });
    }
    var limit = $('.adv_source_limit').val();
    return {'limit': limit, 'flags': flags, 'charset': charset};
};

var process_advanced_search = function() {
    Hm_Notices.hide(true);
    var terms = get_adv_terms();
    if (terms.length == 0) {
        Hm_Notices.show(['ERRYou must enter at least one search term']);
        return;
    }
    var sources = get_adv_sources();
    if (sources.length == 0) {
        Hm_Notices.show(['ERRYou must select at least one source']);
        return;
    }
    var targets = get_adv_targets();
    if (targets.length == 0) {
        Hm_Notices.show(['ERRYou must have at least one target']);
        return;
    }
    var times = get_adv_times();
    if (times.length == 0) {
        Hm_Notices.show(['ERRYou must enter at least one time range']);
        return;
    }
    var other = get_adv_other();

    save_search_details(terms, sources, targets, times, other);
    search_summary({ 'terms': terms, 'targets': targets, 'sources': sources,
            'times': times, 'other': other });

    send_requests(build_adv_search_requests(terms, sources, targets, times, other));
};

var save_search_details = function(terms, sources, targets, times, other) {
    Hm_Utils.save_to_local_storage('adv_search_params',
        Hm_Utils.json_encode({
            'terms': terms,
            'targets': targets,
            'sources': sources,
            'times': times,
            'other': other
        })
    );
};

var load_search_details = function() {
    return Hm_Utils.json_decode(Hm_Utils.get_from_local_storage('adv_search_params'));
};


var adv_group_vals = function(data, type) {
    var groups = [];
    if (data.length == 2 && data[1]['condition'] == 'or') {
        groups.push([data[0][type]]);
        groups.push([data[1][type]]);
    }
    else if (data.length == 2) {
        groups.push([data[0][type], data[1][type]]);
    }
    else {
        groups.push([data[0][type]]);
    }
    return groups;
};

var send_requests = function(requests) {
    var request;
    $('tr', Hm_Utils.tbody()).remove();
    Hm_Utils.save_to_local_storage('formatted_advanced_search_data', '');
    adv_collapse();
    $('.adv_controls').hide();
    $('.empty_list').remove();
    for (var n=0, rlen=requests.length; n < rlen; n++) {
        request = requests[n];
        var params = [
            {'name': 'hm_ajax_hook', 'value': 'ajax_adv_search'},
            {'name': 'adv_source', 'value': request['source']},
            {'name': 'adv_start', 'value': request['time']['from']},
            {'name': 'adv_end', 'value': request['time']['to']},
            {'name': 'adv_source_limit', 'value': request['other']['limit']},
            {'name': 'adv_charset', 'value': request['other']['charset']},
        ];

        for (var i=0, len=request['terms'].length; i < len; i++) {
            params.push({'name': 'adv_terms[]', 'value': request['terms'][i]});
        }
        for (var i=0, len=request['targets'].length; i < len; i++) {
            params.push({'name': 'adv_targets[]', 'value': request['targets'][i]});
        }
        for (var i=0, len=request['other']['flags'].length; i < len; i++) {
            params.push({'name': 'adv_flags[]', 'value': request['other']['flags'][i]});
        }
        Hm_Ajax.request(
            params,
            function(res) {
                var detail = Hm_Utils.parse_folder_path(request['source'], 'imap');
                Hm_Message_List.update([detail.server_id+n], res.formatted_message_list, 'imap');
                if (Hm_Utils.rows().length > 0) {
                    $('.adv_controls').show();
                    $('.core_msg_control').off('click');
                    $('.core_msg_control').on("click", function() { return Hm_Message_List.message_action($(this).data('action')); });
                    Hm_Message_List.set_checkbox_callback();
                }
                Hm_Message_List.check_empty_list();
            },
            [],
            false,
            function() {
                Hm_Message_List.set_message_list_state('formatted_advanced_search_data');
            }
        );
    }
};

var build_adv_search_requests = function(terms, sources, targets, times, other) {
    var source;
    var time;
    var term_vals;
    var target_vals;
    var requests = []
    var term_groups = adv_group_vals(terms, 'term');
    var target_groups = adv_group_vals(targets, 'target');

    for (var tv=0, tvlen=term_groups.length; tv < tvlen; tv++) {
        term_vals = term_groups[tv];
        for (var tag=0, taglen=target_groups.length; tag < taglen; tag++) {
            target_vals = target_groups[tag];
            for (var s=0, slen=sources.length; s < slen; s++) {
                source = sources[s]['source'];
                for (var ti=0, tilen=times.length; ti < tilen; ti++) {
                    time = times[ti];
                    requests.push({'source': source, 'time': time, 'other': other,
                        'targets': target_vals, 'terms': term_vals});
                }
            }
        }
    }
    return requests;
};

var search_summary = function(details) {
    if (!details) {
        return;
    }
    var charset = 0;
    if (details['other']['charset']) { charset = 1; }
    $('.term_count').text($('.term_count').text().replace(/\d+/, details['terms'].length)).show();
    $('.target_count').text($('.target_count').text().replace(/\d+/, details['targets'].length)).show();
    $('.source_count').text($('.source_count').text().replace(/\d+/, details['sources'].length)).show();
    $('.time_count').text($('.time_count').text().replace(/\d+/, details['times'].length)).show();
    $('.other_count').text($('.other_count').text().replace(/\d+/, (charset + details['other']['flags'].length))).show();
};

var apply_saved_search = function() {
    var details = load_search_details();
    if (!details) {
        return;
    }
    search_summary(details);
    var target_id;
    var time_id;
    var not;
    for (var i=0, len=details['terms'].length; i < len; i++) {
        not = false;
        if (details['terms'][i]['term'].substring(0, 4) == 'NOT ') {
            details['terms'][i]['term'] = details['terms'][i]['term'].substring(4);
            not = true;
        }
        if (i == 0) {
            $('#adv_term').val(details['terms'][i]['term']);
            if (not) {
                $('input', $('#adv_term_not')).attr('checked', true);
            }
        }
        else {
            $('.new_term').trigger('click');
            $('#adv_term'+i).val(details['terms'][i]['term']);
            $('input[type=radio][value='+details['terms'][i]['condition']+']', $('#term_and_or'+i)).attr('checked', true);
            if (not) {
                $('input', $('#adv_term_not'+i)).attr('checked', true);
            }
        }
    }
    for (var i=0, len=details['sources'].length; i < len; i++) {
        add_source_to_list(details['sources'][i]['source'], details['sources'][i]['label']);
    }
    for (var i=0, len=details['targets'].length; i < len; i++) {
        if (i == 0) {
            target_id = '#adv_target';
        }
        else {
            target_id = '#adv_target'+i;
            $('.new_target').trigger('click');
            $('input[type=radio][value='+details['targets'][i]['condition']+']', $('#target_and_or'+i)).attr('checked', true);
        }
        $('input[type=radio][value='+details['targets'][i]['orig']+']', $(target_id)).attr('checked', true);
        if (details['targets'][i]['orig'] == 'custom') {
            $('.adv_custom_header', $(target_id)).val(details['targets'][i]['target'].substring(7));
        }
        else if (details['targets'][i]['orig'] == 'header') {
            $('.adv_header_select', $(target_id)).val(details['targets'][i]['target']);
        }
    }
    for (var i=0, len=details['times'].length; i < len; i++) {
        if (i == 0) {
            time_id = '#adv_time';
        }
        else {
            time_id = '#adv_time'+i;
            $('.new_time').trigger('click');
        }
        $('.adv_time_fld_from', $(time_id)).val(details['times'][i]['from']);
        $('.adv_time_fld_to', $(time_id)).val(details['times'][i]['to']);
    }
    $('.charset').val(details['other']['charset']);
    for (var i=0, len=details['other']['flags'].length; i < len; i++) {
        $('input[type=checkbox][value='+details['other']['flags'][i]+']', $('.flags')).attr('checked', true);
    }
    $('.adv_source_limit').val(details['other']['limit']);
};

var adv_reset_page = function() {
    Hm_Utils.save_to_local_storage('formatted_advanced_search_data', '');
    Hm_Utils.save_to_local_storage('adv_search_params', '');
    document.location.href = '?page=advanced_search';
};

$(function() {
    if (hm_page_name() == 'advanced_search') {

        globals.close_html = '<img width="16" height="16" src="data:image/svg+xml,%3Csvg%20xmlns%3D%22';
        globals.close_html += 'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%228%22%20height%3D%228%2';
        globals.close_html += '2%20viewBox%3D%220%200%208%208%22%3E%0A%20%20%3Cpath%20d%3D%22M4%200c-2.21%';
        globals.close_html += '200-4%201.79-4%204s1.79%204%204%204%204-1.79%204-4-1.79-4-4-4zm-1.5%201.781';
        globals.close_html += 'l1.5%201.5%201.5-1.5.719.719-1.5%201.5%201.5%201.5-.719.719-1.5-1.5-1.5%201';
        globals.close_html += '.5-.719-.719%201.5-1.5-1.5-1.5.719-.719z%22%20%2F%3E%0A%3C%2Fsvg%3E" alt="R';
        globals.close_html += 'emove">';

        $('.settings_subtitle').on("click", function() { return Hm_Utils.toggle_page_section($(this).data('target')); });
        $('.adv_folder_select').on("click", function() { adv_select_imap_folder(this); });
        $('.new_time').on("click", function() { add_remove_times(this); });
        $('.new_target').on("click", function() { add_remove_targets(this); });
        $('.new_term').on("click", function() { add_remove_terms(this); });
        $('.adv_expand_all').on("click", function() { adv_expand_sections(); });
        $('.adv_collapse_all').on("click", function() { adv_collapse(); });
        $('#adv_search').on("click", function() { process_advanced_search(); });
        $('.toggle_link').on("click", function() { return Hm_Message_List.toggle_rows(); });
        $('.adv_reset').on("click", function() { adv_reset_page(); });
        $('.combined_sort').on("change", function() { Hm_Message_List.sort($(this).val()); });

        apply_saved_search();
        var data = Hm_Utils.get_from_local_storage('formatted_advanced_search_data');
        if (data && data.length) {
            adv_collapse(); 
            Hm_Utils.tbody().html(data);
            $('.adv_controls').show();
            $('.core_msg_control').off('click');
            $('.core_msg_control').on("click", function() { return Hm_Message_List.message_action($(this).data('action')); });
            Hm_Message_List.set_checkbox_callback();
        }
        Hm_Message_List.check_empty_list();
    }
});


if (hm_page_name() == 'compose') {
    $('.compose_sign').on("click", function() {
        var server_id = $('.compose_server').val();
        if (profile_signatures[server_id]) {
            var ta = $('.ke-content', $('iframe').contents());
            if (ta.length) {
                ta.html(ta.html() + profile_signatures[server_id].replace(/\n/g, '<br />'));
            }
            else {
                ta = $('#compose_body');
                insert_sig(ta[0], profile_signatures[server_id]);
            }
        }
    });
}

var insert_sig = function(textarea, sig) {
    var tmpta = document.createElement('textarea');
    tmpta.innerHTML = sig;
    sig = tmpta.value;
    if (document.selection) {
        textarea.focus();
        var sel = document.selection.createRange();
        sel.text = sig;
    }
    else if (textarea.selectionStart || textarea.selectionStart == '0') {
        var startPos = textarea.selectionStart;
        var endPos = textarea.selectionEnd;
        textarea.value = textarea.value.substring(0, startPos) + sig + textarea.value.substring(endPos, textarea.value.length);
    }
    else {
        textarea.value += textarea;
    }
};

$(function() {
    if (hm_page_name() === 'profiles') {
        $('.add_profile').on("click", function() { $('.edit_profile').show(); });
    }
});


var inline_pop3_msg = function(details, uid, list_path, inline_msg_loaded_callback) {
    details['uid'] = uid;
    var path = '.'+details['type']+'_'+details['server_id']+'_'+uid;
    clear_open_msg(inline_msg_style());
    msg_container(inline_msg_style(), path);
    pop3_message_view(uid, list_path, inline_msg_loaded_callback);
    $('div', $(path)).removeClass('unseen');
    return false;
};

var inline_wp_msg = function(uid, list_path, inline_msg_loaded_callback) {
    clear_open_msg(inline_msg_style());
    msg_container(inline_msg_style(), '.'+uid);
    wp_notice_view(uid, inline_msg_loaded_callback);
    $('div', $('.'+uid)).removeClass('unseen');
    return false;
};

var inline_github_msg = function(uid, list_path, inline_msg_loaded_callback) {
    clear_open_msg(inline_msg_style());
    msg_container(inline_msg_style(), '.'+uid);
    github_item_view(list_path, uid, inline_msg_loaded_callback);
    $('div', $('.'+uid)).removeClass('unseen');
    return false;
};

var inline_feed_msg = function(uid, list_path, inline_msg_loaded_callback) {
    clear_open_msg(inline_msg_style());
    msg_container(inline_msg_style(), '.'+list_path+'_'+uid);
    feed_item_view(uid, list_path, inline_msg_loaded_callback);
    $('div', $('.'+list_path+'_'+uid)).removeClass('unseen');
    return false;
};


var inline_msg_prep_imap_delete = function(path, uid, details) {
    $('#'+path).prop('checked', false);
    Hm_Message_List.remove_after_action('delete', [path]);
    return imap_delete_message(false, uid, details);
};

var inline_imap_msg = function(details, uid, list_path, inline_msg_loaded_callback) {
    details['uid'] = uid;
    var path = '.'+details['type']+'_'+details['server_id']+'_'+uid+'_'+details['folder'];
    globals['inline_move_uuid'] = path.substr(1);
    clear_open_msg(inline_msg_style());
    msg_container(inline_msg_style(), path);

    imap_setup_message_view_page(uid, details, list_path, inline_msg_loaded_callback);
    $('.part_encoding').hide();
    $('.part_charset').hide();
    $('div', $(path)).removeClass('unseen');
    $(path).removeClass('unseen');
    update_imap_links(uid, details);
};

var msg_container = function(type, path) {
    if (type == 'right') {
        $('.content_title').after('<div class="inline_right msg_text"></div>');
        $('.message_table').css('width', '50%');
    }
    else {
        $(path).after('<tr class="inline_msg"><td colspan="6"><div class="msg_text"></div></td></tr>');
    }
    $(path).addClass('hl');
    $(path).removeClass('unseen');
};

var clear_open_msg = function(type) {
    if (type == 'right') {
        $('.msg_text').html('');
        $('.msg_text').remove();
        $('tr').removeClass('hl');
    }
    else {
        $('.inline_msg').html('');
        $('.inline_msg').remove();
        $('tr').removeClass('hl');
    }
};

var get_inline_msg_details = function(link) {
    var index;
    var pair;
    var uid = false;
    var list_path = false;
    var pairs = $(link).attr('href').split('&');
    for (index in pairs) {
        pair = pairs[index].split('=');
        if (pair[0] == 'uid') {
            uid = pair[1];
        }
        if (pair[0] == 'list_path') {
            list_path = pair[1];
        }
    }
    return [uid, list_path];
};

var msg_inline_close = function() {
    $('.refresh_link').trigger('click');
    if (inline_msg_style() == 'right') {
        $('.msg_text').remove();
        $('.message_table').css('width', '100%');
    }
    else {
        $('.inline_msg').remove();
    }
    $('tr').removeClass('hl');
};

var update_imap_links = function(uid, details) {
    var path = details['type']+'_'+details['server_id']+'_'+uid+'_'+details['folder'];
    $('#unflag_msg').off('click');
    $('#flag_msg').off('click');
    $('#delete_message').off('click');
    $('#delete_message').on("click", function() { return inline_msg_prep_imap_delete(path, uid, details); });
    $('#flag_msg').on("click", function() { return imap_flag_message($(this).data('state'), uid, details); });
    $('#unflag_msg').on("click", function() { return imap_flag_message($(this).data('state', uid, details)); });
};

var capture_subject_click = function() {
    $('.subject a').off('click');
    $('.subject a').on("click", function(e) {
        var msg_details = get_inline_msg_details(this); 
        var uid = msg_details[0];
        var list_path = msg_details[1];
        var inline_msg_loaded_callback = function() {
            $('.header_subject th').append('<span class="close_inline_msg">X</span>');
            $('.close_inline_msg').on("click", function() { msg_inline_close(); });
            $('.msg_part_link').on("click", function() { return get_message_content($(this).data('messagePart'), uid, list_path, details, inline_msg_loaded_callback); });
            update_imap_links(uid, details);
        };

        if (list_path && uid) {
            var details = Hm_Utils.parse_folder_path(list_path);
            globals.msg_uid = uid;
            if (details['type'] == 'feeds') {
                inline_feed_msg(uid, list_path, inline_msg_loaded_callback);
                return false;
            }
            else if (details['type'] == 'imap') {
                inline_imap_msg(details, uid, list_path, inline_msg_loaded_callback);
                return false;
            }
            else if (details['type'] == 'pop3') {
                inline_pop3_msg(details, uid, list_path, inline_msg_loaded_callback);
                return false;
            }
            else if (list_path.substr(0, 6) == 'github') {
                inline_github_msg(uid, list_path, inline_msg_loaded_callback);
                return false;
            }
            else if (list_path.substr(0, 3) == 'wp_') {
                inline_wp_msg(uid, list_path, inline_msg_loaded_callback);
                return false;
            }
            return false;
        }
        return true;
    });
};

$(function() {
    if (hm_page_name() == 'message_list' || hm_page_name() == 'search') {
        if (inline_msg()) {
            setTimeout(capture_subject_click, 100);
            $('tr').removeClass('hl');
            Hm_Ajax.add_callback_hook('*', capture_subject_click);
            Hm_Ajax.add_callback_hook('ajax_imap_delete_message', msg_inline_close);
            Hm_Ajax.add_callback_hook('ajax_imap_move_copy_action', msg_inline_close);
            if (hm_list_path().substr(0, 4) === 'imap') {
                Hm_Ajax.add_callback_hook('ajax_imap_folder_display', capture_subject_click);
            }
        }
    }
});


var folder_page_folder_list = function(container, title, link_class, target, id_dest) {
    var id = $('#imap_server_folder').val();
    var folder_location = $('.'+container);
    $('li', folder_location).not('.'+title).remove();
    var folders = $('.folder_list .imap_'+id+'_').clone(false);
    folders.find('.manage_folders_li').remove();
    $('.imap_folder_link', folders).addClass(link_class).removeClass('imap_folder_link');
    folder_location.prepend(folders);
    folder_location.show();
    $('.'+link_class, folder_location).on("click", function() { return expand_folders_page_list($(this).data('target'), container, link_class, target, id_dest); });
    $('a', folder_location).not('.'+link_class).not('.close').off('click');
    $('a', folder_location).not('.'+link_class).not('.close').on("click", function() { set_folders_page_value($(this).data('id'), container, target, id_dest); return false; });
    $('.close', folder_location).on("click", function() {
        folders.remove();
        folder_location.hide();
        $('.'+target).html('');
        $('#'+id_dest).val('');
        return false;
    });
    return false;
};


var expand_folders_page_list = function(path, container, link_class, target, id_dest) {
    var detail = Hm_Utils.parse_folder_path(path, 'imap');
    var list = $('.imap_'+detail.server_id+'_'+Hm_Utils.clean_selector(detail.folder), $('.'+container));
    if ($('li', list).length === 0) {
        $('.expand_link', list).html('-');
        if (detail) {
            Hm_Ajax.request(
                [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folder_expand'},
                {'name': 'imap_server_id', 'value': detail.server_id},
                {'name': 'folder', 'value': detail.folder}],
                function(res) {
                    if (res.imap_expanded_folder_path) {
                        var folder_location = $('.'+container);
                        var folders = $(res.imap_expanded_folder_formatted);
                        folders.find('.manage_folders_li').remove();
                        $('.'+Hm_Utils.clean_selector(res.imap_expanded_folder_path), folder_location).append(folders);
                        $('.imap_folder_link', folder_location).addClass(link_class).removeClass('imap_folder_link');
                        $('.'+link_class, folder_location).off('click');
                        $('.'+link_class, folder_location).on("click", function() { return expand_folders_page_list($(this).data('target'), container, link_class, target, id_dest); });
                        $('a', folder_location).not('.'+link_class).not('.close').off('click');
                        $('a', folder_location).not('.'+link_class).not('.close').on("click", function() { set_folders_page_value($(this).data('id'), container, target, id_dest); return false; });
                    }
                }
            );
        }
    }
    else {
        $('.expand_link', list).html('+');
        $('ul', list).remove();
    }
    return false;
};

var set_folders_page_value = function(id, container, target, id_dest) {
    var list = $('.'+container);
    var list_item = $('.'+Hm_Utils.clean_selector(id), list);
    var link = $('a', list_item).first().text();
    if (link == '+' || link == '-') {
        link = $('a', list_item).eq(1).text();
    }
    $('.'+target).html(link);
    $('#'+id_dest).val(id);
    list.hide();

};

var folder_page_delete = function() {
    var val = $('#delete_source').val();
    var id = $('#imap_server_folder').val();
    if (!id.length) {
        Hm_Notices.show({0: 'ERR'+$('#server_error').val()});
        return;
    }
    if (!val.length) {
        Hm_Notices.show({0: 'ERR'+$('#delete_folder_error').val()});
        return;
    }
    if (!confirm($('#delete_folder_confirm').val())) {
        return;
    }
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folders_delete'},
        {'name': 'imap_server_id', value: id},
        {'name': 'folder', 'value': val}],
        function(res) {
            if (res.imap_folders_success) {
                $('#delete_source').val('');
                $('.selected_delete').html('');
                Hm_Folders.reload_folders(true);
            }
        }
    );
};

var folder_page_rename = function() {
    var val = $('#rename_value').val();
    var par = $('#rename_parent_source').val().trim();
    var folder = $('#rename_source').val().trim();
    var notices = {};
    var id = $('#imap_server_folder').val();
    if (!id.length) {
        Hm_Notices.show({0: 'ERR'+$('#server_error').val()});
        return;
    }
    if (!val.length) {
        notices[0] = 'ERR'+$('#rename_folder_error').val(); 
    }
    if (!folder.length) {
        notices[1] = 'ERR'+$('#folder_name_error').val();
    }
    if (!$.isEmptyObject(notices)) {
        Hm_Notices.show(notices);
        return;
    }
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folders_rename'},
        {'name': 'imap_server_id', value: id},
        {'name': 'folder', 'value': folder},
        {'name': 'parent', 'value': par},
        {'name': 'new_folder', 'value': val}],
        function(res) {
            if (res.imap_folders_success) {
                $('#rename_value').val('');
                $('#rename_source').val('');
                $('#rename_parent_source').val('');
                $('.selected_rename').html('');
                $('.selected_rename_parent').html('');
                Hm_Folders.reload_folders(true);
            }
        }
    );
};


var folder_page_assign_trash = function() {
    var id = $('#imap_server_folder').val();
    var folder = $('#trash_source').val();
    if (id && folder) {
        assign_special_folder(id, folder, 'trash', function(res) {
            $('#trash_val').text(res.imap_special_name);
            $('.selected_trash').text('');
        });
    }
};

var folder_page_assign_sent = function() {
    var id = $('#imap_server_folder').val();
    var folder = $('#sent_source').val();
    if (id && folder) {
        assign_special_folder(id, folder, 'sent', function(res) {
            $('#sent_val').text(res.imap_special_name);
            $('.selected_sent').text('');
        });
    }
};

var folder_page_assign_draft = function() {
    var id = $('#imap_server_folder').val();
    var folder = $('#draft_source').val();
    if (id && folder) {
        assign_special_folder(id, folder, 'draft', function(res) {
            $('#draft_val').text(res.imap_special_name);
            $('.selected_draft').text('');
        });
    }
};

var clear_special_folder = function(type) {
    var id = $('#imap_server_folder').val();
    if (id) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_clear_special_folder'},
            {'name': 'imap_server_id', 'value': id},
            {'name': 'special_folder_type', 'value': type}],
            function(res) { $('#'+type+'_val').text($('#not_set_string').val()); }
        );
    }
};

var assign_special_folder = function(id, folder, type, callback) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_special_folder'},
        {'name': 'imap_server_id', 'value': id},
        {'name': 'special_folder_type', 'value': type},
        {'name': 'folder', 'value': folder}],
        callback
    );
};

var folder_page_create = function() {
    var par = $('#create_parent').val();
    var folder = $('#create_value').val().trim();
    var id = $('#imap_server_folder').val();
    if (!id.length) {
        Hm_Notices.show({0: 'ERR'+$('#server_error').val()});
        return;
    }
    if (!folder.length) {
        Hm_Notices.show({0: 'ERR'+$('#folder_name_error').val()});
        return;
    }
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_imap_folders_create'},
        {'name': 'imap_server_id', value: id},
        {'name': 'folder', 'value': folder},
        {'name': 'parent', 'value': par}],
        function(res) {
            if (res.imap_folders_success) {
                $('#create_value').val('');
                $('#create_parent').val('');
                $('.selected_parent').html('');
                Hm_Folders.reload_folders(true);
            }
        }
    );

};

$(function() {
    if (hm_page_name() == 'folders') {
        $('#imap_server_folder').on("change", function() {
            $(this).parent().submit();
        });
        $('.settings_subtitle').on("click", function() { return Hm_Utils.toggle_page_section($(this).data('target')); });
    }
    $('.select_parent_folder').on("click", function() { return folder_page_folder_list('parent_folder_select', 'parent_title', 'imap_parent_folder_link', 'selected_parent', 'create_parent'); });
    $('.select_rename_folder').on("click", function() { return folder_page_folder_list('rename_folder_select', 'rename_title', 'imap_rename_folder_link', 'selected_rename', 'rename_source'); });
    $('.select_delete_folder').on("click", function() { return folder_page_folder_list('delete_folder_select', 'delete_title', 'imap_delete_folder_link', 'selected_delete', 'delete_source'); });
    $('.select_trash_folder').on("click", function() { return folder_page_folder_list('trash_folder_select', 'trash_title', 'imap_trash_folder_link', 'selected_trash', 'trash_source'); });
    $('.select_sent_folder').on("click", function() { return folder_page_folder_list('sent_folder_select', 'sent_title', 'imap_sent_folder_link', 'selected_sent', 'sent_source'); });
    $('.select_draft_folder').on("click", function() { return folder_page_folder_list('draft_folder_select', 'draft_title', 'imap_draft_folder_link', 'selected_draft', 'draft_source'); });
    $('.select_rename_parent_folder').on("click", function() { return folder_page_folder_list('rename_parent_folder_select', 'rename_parent_title', 'imap_rename_parent_folder_link', 'selected_rename_parent', 'rename_parent_source'); });
    $('#create_folder').on("click", function() { folder_page_create(); return false; });
    $('#delete_folder').on("click", function() { folder_page_delete(); return false; });
    $('#rename_folder').on("click", function() { folder_page_rename(); return false; });

    $('#set_trash_folder').on("click", function() { folder_page_assign_trash(); return false; });
    $('#set_sent_folder').on("click", function() { folder_page_assign_sent(); return false; });
    $('#set_draft_folder').on("click", function() { folder_page_assign_draft(); return false; });

    $('#clear_trash_folder').on("click", function() { clear_special_folder('trash'); return false; });
    $('#clear_sent_folder').on("click", function() { clear_special_folder('sent'); return false; });
    $('#clear_draft_folder').on("click", function() { clear_special_folder("draft"); return false; });
});


var ks_follow_link = function(target) {
    var link = $(target);
    if (link.length > 0) {
        document.location.href = link.attr('href');
    }
};

var ks_redirect = function(target) {
    document.location.href = target;
};

var ks_select_all = function() {
    Hm_Message_List.toggle_rows();
};

var ks_select_msg = function() {
    var focused = $(document.activeElement);
    $('input', focused).each(function() {
        if ($(this).prop('checked')) {
            $(this).prop('checked', false);
        }
        else {
            $(this).prop('checked', true);
        }
    });
    Hm_Message_List.toggle_msg_controls();
};

var ks_prev_msg_list = function() {
    var focused = $(document.activeElement);
    if (focused.prop('tagName').toLowerCase() != 'tr') {
        var row = $('.message_table tbody tr').last();
        row.focus();
    }
    else {
        focused.prev().focus();
    }
};

var ks_load_msg = function() {
    var focused = $(document.activeElement);
    var inline;
    if (focused.prop('tagName').toLowerCase() == 'tr') {
        try {
            inline = inline_msg();
        }
        catch (e) {
            inline = false;
        }
        if (inline) {
            $('a', focused).trigger('click');
        }
        else {
            document.location.href = $('a', focused).attr('href');
        }
    }
};

var ks_next_msg_list = function() {
    var focused = $(document.activeElement);
    if (focused.prop('tagName').toLowerCase() != 'tr') {
        var row = $('.message_table tbody tr').first();
        row.focus();
    }
    else {
        focused.next().focus();
    }
};

var ks_click_button = function(target) {
    $(target).trigger('click');
};

var Keyboard_Shortcuts = {

    unfocus: function() {
        $('input').blur();
        $('textarea').blur();
    },

    check: function(e, shortcuts) {
        var combo;
        var index;
        var matched;
        var control_keys = {'alt': e.altKey, 'shift': e.shiftKey, 'meta': e.metaKey, 'control': e.ctrlKey};
        for (index in shortcuts) {
            combo = shortcuts[index];
            if (combo['page'] != '*' && combo['page'] != hm_page_name()) {
                continue;
            }
            if (e.keyCode != combo['char']) {
                continue;
            }
            matched = Keyboard_Shortcuts.check_control_chars(combo['control_chars'], control_keys);
            if (matched) {
                if (combo['action'] == 'unfocus') {
                    Keyboard_Shortcuts.unfocus();
                    return false;
                }
                if (Keyboard_Shortcuts.in_input_tag(e)) {
                    return true;
                }
                Keyboard_Actions[combo['action']](combo['target']);
                return false;
            }
        }
        return true;
    },

    check_control_char: function(key_type, control_chars, matched, key_status) {
        if (matched && $.inArray(key_type, control_chars) !== -1 && !key_status) {
            matched = false;
        }
        else if ($.inArray(key_type, control_chars) === -1  && key_status) {
            matched = false;
        }
        return matched
    },

    in_input_tag: function(e) {
        var tag = e.target.tagName.toLowerCase();
        if (tag == 'input' || tag == 'textarea') {
            return true;
        }
        return false;
    },

    check_control_chars: function(control_chars, control_keys) {
        var key_type;
        var key_status;
        var matched = true;
        for (key_type in control_keys) {
            key_status = control_keys[key_type];
            matched = Keyboard_Shortcuts.check_control_char(key_type, control_chars, matched, key_status);
        }
        return matched;
    }
};

var Keyboard_Actions = {
    'unfocus': false,
    'redirect': ks_redirect,
    'toggle': Hm_Folders.toggle_folder_list,
    'next': ks_next_msg_list,
    'prev': ks_prev_msg_list,
    'load': ks_load_msg,
    'select': ks_select_msg,
    'select_all': ks_select_all,
    'click': ks_click_button,
    'link': ks_follow_link
};

$(function() {

    if (typeof shortcuts != 'undefined') {
        $(document).not('input').on('keydown', function(e) { return Keyboard_Shortcuts.check(e, shortcuts); });
    }
    if (hm_page_name() == 'shortcuts') {
        $('.reset_shortcut').on("click", function() {
            window.location.href = '?page=shortcuts';
        });
    }
});


/*
 * Update intervals. Uncomment and change the values to override
 * the defaults. Current values are set to the defaults
 */

// Delay between saving drafts on the compose page
// globals.compose_save_interval = 30;

// Delay between IMAP background checks for unread messages
// globals.imap_background_update_interval = 33;

// Delay between background message content prefetching for
// globals.imap_prefetch_msg_interval = 43;

// Delay between updates to compbined message views
// globals.combined_view_refresh_interval = 60;
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

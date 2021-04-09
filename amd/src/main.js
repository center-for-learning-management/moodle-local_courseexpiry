define(
    ['jquery', 'core/ajax'],
    function($, AJAX) {
    return {
        toggle: function(uniqid, courseid) {
            var a = $('#local_courseexpiry_' + uniqid + ' tr-' + uniqid + '-' + courseid + ' a');
            $(a).find('i').css('color', 'lightgray');

            var setto = a.hasClass('status-0') ? 1 : 0;
            AJAX.call([{
                methodname: 'local_courseexpiry_toggle',
                args: { 'courseid': courseid, 'setto': setto },
                done: function(result) {
                    if (typeof result.courseid !== 'undefined' && result.courseid == courseid) {
                        if (result.status == 1) {
                            $(a).empty().append($('<i class="fa fa-check" style="color: darkgreen;"></i>'));
                        } else {
                            $(a).empty().append($('<i class="fa fa-exclamation" style="color: red;"></i>'));
                        }
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },


        requestId: 0,
        debug: 3,
        /**
        * Sets and removes the confirmed state for html elements
        **/
        confirmed: function(selector, success, timeout) {
            var className = 'local_eduvidual_' + ((success)?'stored':'failed');
            if (typeof timeout === 'undefined' || timeout == 0) timeout = 1000;
            console.log('MAIN.confirmed(selector, success, timeout)', selector, success, timeout);
            $(selector).addClass(className);
            setTimeout(function(){
                $(selector).removeClass(className);
            }, timeout);
        },
        connect: function(data, payload) {
            if (this.debug > 0) console.log('MAIN.connect(data, payload)', data, payload);
            var o = { 'data': data, 'payload': payload, requestId: this.requestId++ };
            var MAIN =  this;
            MAIN.signal(o.payload, true);
            MAIN.spinnerGrid(true);
            $.ajax({
                url: URL.fileUrl("/local/eduvidual/ajax/ajax.php", ""),
                method: 'POST',
                data: data,
            }).done(function(res){
                try { res = JSON.parse(res); } catch(e){}
                o.result = res;
                if (typeof o.result !== 'undefined' && typeof o.result.status !== 'undefined') {
                    MAIN.signal(o.payload, false, (o.result.status == 'ok'));
                }
                if(MAIN.debug>2) console.log('< RequestId #' + o.requestId, o);
                MAIN.result(o);
            }).fail(function(jqXHR, textStatus){
                MAIN.signal(o.payload, false, false);
                o.textStatus = textStatus;
                if(MAIN.debug>2) console.error('* RequestId #' + o.requestId, o);
            }).always(function(){
                MAIN.spinnerGrid(false);
            });
        },
        /**
         * Commands a logout
        **/
        doLogout: function(){
            var originallocation = localStorage.getItem('local_eduvidual_originallocation');
            if (originallocation == null) originallocation = '';
            top.location.href = URL.fileUrl('/local/eduvidual/pages/login_app.php', '') + '?dologout=1&originallocation=' + encodeURI(originallocation);
        },
        navigate: function(urltogo) {
            if (urltogo.indexOf('#') == 0) return;
            var MAIN =  this;
            require(['local_eduvidual/user'], function(USER) { USER.toggleSubmenu(false); });
            MAIN.spinnerGrid(true);
            console.log('Normal navigate to ', urltogo);
            location.href = urltogo;
            return false;
        },
        result: function(o) {
            if (typeof o.result.error !== 'undefined' && o.result.error != '') {
                console.log(o.result.error);

                STR.get_strings([
                    {'key' : 'confirm', component: 'core' },
                    {'key' : o.result.error, component: 'local_eduvidual' },
                ]).done(function(s) {
                        NOTIFICATION.alert(s[1], s[0]);
                    }
                ).fail(NOTIFICATION.exception);
            }
            var module = o.data.module;
            // @todo maybe change everything from "manage" to "manager"
            if (module == 'manage') { module = 'manager'; }
            require(['local_eduvidual/' + module], function(MOD) { MOD.result(o); });
        },
        /**
         * Calls a page in embedded layout and displays it as modal.
         */
        popPage: function(page, params) {
            if (typeof params === 'undefined') params = '?';
            //params += '&embed=1';
            var url = URL.fileUrl("/local/eduvidual/pages/" + page + ".php", params);
            console.log('popPage ', url);
            $.get(url)
                .done(function(body) {
                    console.log('Got body ', body);
                    ModalFactory.create({
                        title: '',
                        //type: ModalFactory.types.OK,
                        body: body,
                        //footer: 'footer',
                    }).done(function(modal) {
                        console.log('Created modal');
                        modal.show();
                    });
                })
                .fail(function(err) { console.err('Error', err); });
        },
        signal: function(payload, to, success) {
            console.log('MAIN.signal(payload, to, success)', payload, to, success);
            if (typeof payload !== 'undefined' && typeof payload.signalItem !== 'undefined') {
                if (typeof to !== 'undefined' && to) {
                    $(payload.signalItem).addClass('local_eduvidual_signal');
                } else {
                    $(payload.signalItem).removeClass('local_eduvidual_signal');
                }
                if (typeof success !== 'undefined') {
                    $(payload.signalItem).addClass('local_eduvidual_signal_' + ((success)?'success':'error'));
                    setTimeout(function(){
                        $(payload.signalItem).removeClass('local_eduvidual_signal_' + ((success)?'success':'error'));
                    },1000);
                }
            }
        },
        spinnerGrid: function(state) {
            if (typeof $('.spinner-grid') === 'undefined' || $('.spinner-grid') == null || $('.spinner-grid').length == 0) {
                $('body').prepend($('<div class="spinner-grid"><div /><div /><div /><div /></div>'));
            }
            if (typeof state !== 'undefined' && (state == 'show' || state == true)) {
                $('.spinner-grid').addClass('show');
            } else {
                $('.spinner-grid').removeClass('show');
            }
        },
        /**
         * Used to toggle between buttons and toggle visibility of a linked element.
         * @param uniqid of the mustache
         * @param a the button that was pressed.
         * @param target String id of target (without uniqid)
         */
        toggle: function(uniqid, a, target) {
            if (this.debug > 5) console.log('local_eduvidual/main:toggle(uniqid, a, target)', uniqid, a, target);
            // Hide all cards of this uniqid.
            $('.' + uniqid + '-card').addClass('hidden');
            // Set all buttons to "non-pressed" state.
            $(a).closest('.toggle-controller-' + uniqid).find('a').removeClass('active');
            // Show the linked card, that was identified by target.
            $('#' + uniqid + '-' + target).removeClass('hidden');
            // Set the pressed button active.
            $(a).addClass('active');
        },
        watchValue: function(o) {
            if (this.debug > 5) console.log('MAIN.watchValue(o)', o);
            var self = this;

            if ($(o.target).attr('data-iswatched') != '1') {
                $(o.target).attr('data-iswatched', 1);

                o.interval = setInterval(
                    function() {
                         if ($(o.target).val() == o.compareto) {
                            o.run();
                            clearInterval(o.interval);
                            $(o.target).attr('data-iswatched', 0);
                         } else {
                            o.compareto = $(o.target).val();
                         }
                    },
                    1000
                );
            }
        },
    };
});

define(
    ['jquery', 'core/ajax', 'core/notification'],
    function($, AJAX, NOTIFICATION) {
    return {
        debug: false,
        toggle: function(uniqid, courseid) {
            var MAIN = this;
            if (MAIN.debug) console.log('local_courseexpiry/main:toggle(uniqid, courseid)', uniqid, courseid);
            var a = $('#local_courseexpiry_' + uniqid + ' #tr-' + uniqid + '-' + courseid + ' td.status a');
            $(a).find('i').css('color', 'lightgray');

            var setto = a.hasClass('status-1') ? 0 : 1;
            if (MAIN.debug) console.log('setto', setto);
            AJAX.call([{
                methodname: 'local_courseexpiry_toggle',
                args: { 'courseid': courseid, 'status': setto },
                done: function(result) {
                    if (MAIN.debug) console.log('=> Result for ' + uniqid + '-' + courseid, result);
                    if (typeof result.courseid !== 'undefined' && result.courseid == courseid) {
                        if (result.status != 1) result.status = 0;
                        $(a).removeClass('status-0').removeClass('status-1').addClass('status-' + result.status);
                        if (result.status == 1) {
                            $(a).empty().append($('<i class="fa fa-exclamation" style="color: red;"></i>'));
                        } else {
                            $(a).empty().append($('<i class="fa fa-check" style="color: darkgreen;"></i>'));
                        }
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
    };
});

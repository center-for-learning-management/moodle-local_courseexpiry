define(
    ['jquery', 'core/ajax', 'core/notification'],
    function($, AJAX, NOTIFICATION) {
    return {
        debug: false,
        toggle: function(uniqid, courseid, action) {
            var MAIN = this;
            if (MAIN.debug) console.log('local_courseexpiry/main:toggle(uniqid, courseid, action)', uniqid, courseid, action);


            var a = $('#local_courseexpiry_' + uniqid + ' #tr-' + uniqid + '-' + courseid + ' a i').css('color', 'lightgray');

            var setto = (action == 'keep') ? 0 : 1;
            if (MAIN.debug) console.log('setto', setto);
            AJAX.call([{
                methodname: 'local_courseexpiry_toggle',
                args: { 'courseid': courseid, 'status': setto },
                done: function(result) {
                    if (MAIN.debug) console.log('=> Result for ' + uniqid + '-' + courseid, result);
                    if (typeof result.courseid !== 'undefined' && result.courseid == courseid) {
                        var check = $('<i class="fa fa-square" style="color: darkgray;"></i>');
                        var checksquare = $('<i class="fa fa-check-square" style="color: black;"></i>');
                        setTimeout(function() {
                            if (result.status == 1) {
                                $('#status-' + uniqid + '-delete').empty().append(checksquare);
                                $('#status-' + uniqid + '-keep').empty().append(check);
                            } else {
                                $('#status-' + uniqid + '-delete').empty().append(check);
                                $('#status-' + uniqid + '-keep').empty().append(checksquare);
                            }
                        }, 500);
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
    };
});

define(
  ['jquery', 'core/ajax', 'core/notification'],
  function ($, AJAX, NOTIFICATION) {
    return {
      debug: false,
      toggle: function (el, courseid, action) {
        var MAIN = this;
        if (MAIN.debug) console.log('local_courseexpiry/main:toggle(el, courseid, action)', el, courseid, action);

        $(el).css('color', 'lightgray');

        var check = $('<i class="fa fa-square" style="color: darkgray;"></i>');
        var checksquare = $('<i class="fa fa-check-square" style="color: black;"></i>');
        $(el).empty().append(checksquare);

        // uncheck the other one
        $(el).closest('tr').find('.expiredcourse-toggler').not(el).empty().append(check);

        AJAX.call([{
          methodname: 'local_courseexpiry_toggle',
          args: {'courseid': courseid, 'keep': (action == 'keep' ? 1 : 0 /* convert to int, because moodle doesn't allow boolean parameters in call */)},
          done: function (result) {
            table_sql_reload();
          },
          fail: NOTIFICATION.exception
        }]);
      },
    };
  });

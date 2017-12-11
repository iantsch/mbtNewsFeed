jQuery(document).ready(function($) {
    $('body').on('click', '#mbt-news-feed .follow, #mbt-news-feed .unfollow', function(e) {
        e.preventDefault();
        var $me = $(this),
            action = 'mbt_follow';
        if ($me.hasClass('unfollow')) action = 'mbt_unfollow';
        var data = $.extend(true, $me.data(), {
            action: action,
            _ajax_nonce: admin_ajax.nonce
        });
        $.post(admin_ajax.url, data, function(response) {
            if(response == '0' || response == '-1'){
            } else {
                $($me).parent().empty().html(response);
            }
        });
    });
});
